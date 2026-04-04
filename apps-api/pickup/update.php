<?php
/**
 * Pickup status update (apps-api)
 * Same conditions as get-booking-info: branch_id + awb_no (child or parent). POD images compressed (200KB).
 * Location: /apps-api/pickup/update.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

error_log("Ganesan...");
$branch_id   = trim($req['branch_id'] ?? '');
$awb_no      = trim($req['awb_no'] ?? '');
$status      = trim($req['status'] ?? '');
$status_date = trim($req['status_date'] ?? '');
$location    = trim($req['location'] ?? '');
$remarks     = trim($req['remarks'] ?? '');
$receiver_name  = trim($req['receiver_name'] ?? '');
$receiver_phone = trim($req['receiver_phone'] ?? '');
$user_id     = isset($req['user_id']) ? (int)$req['user_id'] : 1;
$image_folder = trim($req['images_folder'] ?? 'pickup/' . date('Y/m/d'));

if ($awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'awb_no is required']);
    exit;
}
if ($branch_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'branch_id is required']);
    exit;
}
if ($status === '') {
    echo json_encode(['status' => 'error', 'message' => 'status is required']);
    exit;
}
if ($status_date === '') {
    echo json_encode(['status' => 'error', 'message' => 'status_date is required']);
    exit;
}

$branch_id_int = (int) $branch_id;
if ($user_id < 1) $user_id = 1;
error_log("Ganesan...111");
try {
    // Resolve awb_no to booking (same as get-booking-info): child or parent
    $pkgStmt = $pdo->prepare("SELECT bp.id as package_id, bp.booking_id FROM tbl_booking_packages bp
                WHERE LOWER(TRIM(bp.child_ewaybill_no)) = LOWER(TRIM(:awb)) OR LOWER(TRIM(bp.awb_no)) = LOWER(TRIM(:awb)) LIMIT 1");
    $pkgStmt->execute([':awb' => $awb_no]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Ganesan...222");

    $bookingId = null;
    $packageId = 0;
    $isChild   = false;
    if ($package) {
        $bookingId = (int) $package['booking_id'];
        $packageId = (int) $package['package_id'];
        $isChild   = true;
    } else {
        $parentStmt = $pdo->prepare("SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1");
        $parentStmt->execute([':awb' => $awb_no]);
        $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if ($parent) {
            $bookingId = (int) $parent['id'];
        }
    }

    if (!$bookingId) {
        echo json_encode(['status' => 'error', 'message' => 'AWB number not found']);
        exit;
    }

    // Get booking + branch (same as get-booking-info)
    $bookStmt = $pdo->prepare("SELECT b.id, b.booking_ref_id, b.waybill_no, b.last_status,
                COALESCE(b.branch_id, p.branch_id) AS booking_branch_id
                FROM tbl_bookings b
                LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                WHERE b.id = :bid LIMIT 1");
    $bookStmt->execute([':bid' => $bookingId]);
    $booking = $bookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    $bookingBranchId = (int) ($booking['booking_branch_id'] ?? 0);
    if ($bookingBranchId > 0 && $bookingBranchId !== $branch_id_int) {
        echo json_encode(['status' => 'error', 'message' => 'This AWB belongs to another branch. Cannot update here.']);
        exit;
    }

    // Already picked up: do not update again
    $currentStatus = trim($booking['last_status'] ?? '');
    if ($isChild && $packageId > 0) {
        $pkgStatusStmt = $pdo->prepare("SELECT status FROM tbl_booking_packages WHERE id = :id LIMIT 1");
        $pkgStatusStmt->execute([':id' => $packageId]);
        $pkgRow = $pkgStatusStmt->fetch(PDO::FETCH_ASSOC);
        if ($pkgRow && isset($pkgRow['status']) && $pkgRow['status'] !== '' && $pkgRow['status'] !== null) {
            $currentStatus = trim($pkgRow['status']);
        }
    }
    if ($currentStatus !== '' && strtolower($currentStatus) !== 'pending') {
        echo json_encode(['status' => 'error', 'message' => 'Already picked up']);
        exit;
    }

    // Status datetime
    if (strpos($status_date, 'T') !== false) {
        $statusDateTime = str_replace('T', ' ', $status_date);
        if (strlen($statusDateTime) === 16) $statusDateTime .= ':00';
    } elseif (strpos($status_date, ' ') !== false) {
        $statusDateTime = $status_date;
        if (strlen($statusDateTime) === 16) $statusDateTime .= ':00';
    } else {
        $statusDateTime = $status_date . ' 00:00:00';
    }

    // POD images (compress 200KB like pickup-status-update / pod_upload)
    $podImages = [];
    if (isset($_FILES['pod_images']) && is_array($_FILES['pod_images']['name'])) {
        $maxKb = 200;
        for ($i = 0, $n = count($_FILES['pod_images']['name']); $i < $n; $i++) {
            if ($_FILES['pod_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $fileInput = [
                'name' => $_FILES['pod_images']['name'][$i],
                'tmp_name' => $_FILES['pod_images']['tmp_name'][$i],
                'error' => UPLOAD_ERR_OK,
                'size' => $_FILES['pod_images']['size'][$i],
            ];
            $path = handle_image_upload($fileInput, $image_folder, null, $maxKb);
            if ($path) $podImages[] = $path;
        }
    }

    $pdo->beginTransaction();
    try {
        $shouldWriteTracking = false;
        $finalStatus = $status;

        if ($isChild && $packageId > 0) {
            $updPkg = $pdo->prepare("UPDATE tbl_booking_packages SET status = :status, status_date = :dt, pod_images = :imgs, remarks = :rem, updated_by = :uid, updated_at = NOW() WHERE id = :id");
            $updPkg->execute([
                ':status' => $status, ':dt' => $statusDateTime, ':imgs' => json_encode($podImages),
                ':rem' => $remarks, ':uid' => $user_id, ':id' => $packageId
            ]);

            $pkgStmt = $pdo->prepare("SELECT status FROM tbl_booking_packages WHERE booking_id = :bid");
            $pkgStmt->execute([':bid' => $bookingId]);
            $allStatuses = $pkgStmt->fetchAll(PDO::FETCH_COLUMN);
            $unique = array_unique(array_filter($allStatuses));

            if (count($unique) === 1) {
                $finalStatus = reset($unique);
                $pdo->prepare("UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id")
                    ->execute([':status' => $finalStatus, ':uid' => $user_id, ':id' => $bookingId]);
                $shouldWriteTracking = true;
            } else {
                $pdo->prepare("UPDATE tbl_bookings SET last_status = 'Partially Picked Up', updated_by = :uid, updated_at = NOW() WHERE id = :id")
                    ->execute([':uid' => $user_id, ':id' => $bookingId]);
            }
        } else {
            $pdo->prepare("UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id")
                ->execute([':status' => $status, ':uid' => $user_id, ':id' => $bookingId]);
            try {
                $pdo->prepare("UPDATE tbl_booking_packages SET status = :status, status_date = :dt, pod_images = :imgs, remarks = :rem, updated_by = :uid, updated_at = NOW() WHERE booking_id = :bid")
                    ->execute([
                        ':status' => $status, ':dt' => $statusDateTime, ':imgs' => json_encode($podImages),
                        ':rem' => $remarks, ':uid' => $user_id, ':bid' => $bookingId
                    ]);
            } catch (Exception $e) {
                // table may not have status columns
            }
            $shouldWriteTracking = true;
        }

        $trackingId = null;
        if ($shouldWriteTracking) {
            $waybillNo = $booking['waybill_no'] ?? '';
            $existingTrackStmt = $pdo->prepare("SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1");
            $existingTrackStmt->execute([':wn' => $waybillNo]);
            $existingTrack = $existingTrackStmt->fetch(PDO::FETCH_ASSOC);

            $history = [];
            if ($existingTrack && !empty($existingTrack['raw_response'])) {
                $decoded = json_decode($existingTrack['raw_response'], true);
                if (!empty($decoded['scan_details_history'])) $history = $decoded['scan_details_history'];
                elseif (!empty($decoded['scan_details'])) $history = [$decoded['scan_details']];
            }

            $newScan = [
                'status' => $finalStatus,
                'receiver_name' => $receiver_name,
                'receiver_phone' => $receiver_phone,
                'location' => $location,
                'datetime' => $statusDateTime,
                'remarks' => $remarks,
                'pod_images' => $podImages,
                'pod_image_count' => count($podImages),
                'updated_by' => $user_id,
                'updated_at' => date('Y-m-d H:i:s'),
                'type' => 'POD',
            ];
            $history[] = $newScan;
            $rawData = json_encode([
                'awb_no' => $waybillNo,
                'shipment_details' => ['id' => $bookingId, 'booking_ref_id' => $booking['booking_ref_id'] ?? ''],
                'current_status' => $finalStatus,
                'scan_details' => $newScan,
                'scan_details_history' => $history,
            ]);

            if ($existingTrack) {
                $pdo->prepare("UPDATE tbl_tracking SET scan_type=:st, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id")
                    ->execute([
                        ':id' => $existingTrack['id'], ':st' => $finalStatus, ':dt' => $statusDateTime,
                        ':sc' => $finalStatus, ':rem' => $remarks, ':raw' => $rawData
                    ]);
                $trackingId = (int) $existingTrack['id'];
            } else {
                $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :dt, :sc, :rem, :raw)")
                    ->execute([
                        ':bid' => $bookingId, ':wn' => $waybillNo ?: '', ':st' => $finalStatus,
                        ':dt' => $statusDateTime, ':sc' => $finalStatus, ':rem' => $remarks, ':raw' => $rawData
                    ]);
                $trackingId = (int) $pdo->lastInsertId();
            }

            if (function_exists('syncShipmentStatusAcrossTables')) {
                syncShipmentStatusAcrossTables($pdo, $bookingId, $waybillNo, $finalStatus, $remarks, 'app-' . $user_id);
            }
        }

        $pdo->commit();

        $bookingStatus = $shouldWriteTracking ? $finalStatus : 'Partially Picked Up';
        error_log("Package updated. Booking is Partially Picked Up");
        echo json_encode([
            'status' => 'success',
            'message' => $shouldWriteTracking
                ? 'Status updated successfully' . (count($podImages) > 0 ? ' with ' . count($podImages) . ' image(s)' : '')
                : 'Package updated. Booking is Partially Picked Up.',
            'booking_id' => $bookingId,
            'master_awb' => $booking['waybill_no'] ?? '',
            'is_child' => $isChild,
            'package_id' => $packageId,
            'new_status' => $status,
            'booking_status' => $bookingStatus,
            'fully_updated' => $shouldWriteTracking,
            'tracking_id' => $trackingId,
            'pod_images' => $podImages,
            'image_count' => count($podImages),
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
