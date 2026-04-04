<?php
/**
 * Delivery API – Update Status with optional POD image upload
 * Location: /apps-api/delivery/update-status.php
 * Method: GET | POST | multipart/form-data (for image upload)
 * Params:
 *   awb_no         (required) – child or parent AWB
 *   status         (required) – Delivered | Delivery Attempt | Out for Delivery
 *   user_id        (opt)
 *   status_date    (opt) – datetime, defaults to now
 *   location       (opt)
 *   remarks        (opt)
 *   receiver_name  (opt) – for Delivered
 *   receiver_phone (opt) – for Delivered
 *   delivery_pod_images[]  (required for Delivered, optional for others) – image files (multipart)
 *
 * Rules:
 *   Delivered       → delivery_pod_images[] is REQUIRED (at least 1 image)
 *   Delivery Attempt→ no image required
 *   Out for Delivery→ no image required
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../config/config.php';

// Support JSON body, form-data, and GET
$req = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonBody = json_decode(file_get_contents('php://input'), true);
    $req = (!empty($jsonBody) && is_array($jsonBody)) ? $jsonBody : $_POST;
} else {
    $req = $_GET;
}

$awb_no        = trim($req['awb_no']        ?? '');
$status        = trim($req['status']         ?? '');
$user_id       = (int)($req['user_id']       ?? 0);
$status_date   = trim($req['status_date']    ?? '');
$location      = trim($req['location']       ?? '');
$remarks       = trim($req['remarks']        ?? '');
$receiver_name = trim($req['receiver_name']  ?? '');
$receiver_phone= trim($req['receiver_phone'] ?? '');

$allowed_statuses = ['Delivered', 'Delivery Attempt', 'Out for Delivery'];

if ($awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'awb_no is required']);
    exit;
}
if ($status === '') {
    echo json_encode(['status' => 'error', 'message' => 'status is required: ' . implode(', ', $allowed_statuses)]);
    exit;
}
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Allowed: ' . implode(', ', $allowed_statuses)]);
    exit;
}

// Default status_date to now
$now = date('Y-m-d H:i:s');
if ($status_date === '') {
    $statusDateTime = $now;
} elseif (strpos($status_date, 'T') !== false) {
    $statusDateTime = str_replace('T', ' ', $status_date);
    if (strlen($statusDateTime) === 16) $statusDateTime .= ':00';
} else {
    $statusDateTime = $status_date;
    if (strlen($statusDateTime) === 16) $statusDateTime .= ':00';
    if (strlen($statusDateTime) === 10) $statusDateTime .= ' 00:00:00';
}

if ($user_id < 1) $user_id = 1;

try {
    // Resolve AWB → booking (child package first, then parent)
    $pkgStmt = $pdo->prepare("SELECT bp.id as package_id, bp.booking_id
                               FROM tbl_booking_packages bp
                               WHERE LOWER(TRIM(bp.child_ewaybill_no)) = LOWER(TRIM(:awb))
                                  OR LOWER(TRIM(bp.awb_no)) = LOWER(TRIM(:awb))
                               LIMIT 1");
    $pkgStmt->execute([':awb' => $awb_no]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $bookingId = null;
    $packageId = 0;
    $isChild   = false;

    if ($package) {
        $bookingId = (int)$package['booking_id'];
        $packageId = (int)$package['package_id'];
        $isChild   = true;
    } else {
        $parentStmt = $pdo->prepare("SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1");
        $parentStmt->execute([':awb' => $awb_no]);
        $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if ($parent) $bookingId = (int)$parent['id'];
    }

    if (!$bookingId) {
        echo json_encode(['status' => 'error', 'message' => 'AWB not found: ' . $awb_no]);
        exit;
    }

    // Get booking info
    $bookStmt = $pdo->prepare("SELECT id, waybill_no, booking_ref_id, last_status FROM tbl_bookings WHERE id = :id LIMIT 1");
    $bookStmt->execute([':id' => $bookingId]);
    $booking = $bookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    // Handle POD image uploads
    // Accepts field name: pod_images[] OR delivery_pod_images[]
    $podImages   = [];
    $imageFolder = 'delivery/' . date('Y/m/d');
    $fileField   = isset($_FILES['delivery_pod_images']) ? 'delivery_pod_images'
                 : (isset($_FILES['pod_images'])         ? 'pod_images' : null);

    if ($fileField && is_array($_FILES[$fileField]['name'])) {
        $maxKb = 200;
        for ($i = 0, $n = count($_FILES[$fileField]['name']); $i < $n; $i++) {
            if ($_FILES[$fileField]['error'][$i] !== UPLOAD_ERR_OK) continue;
            $fileInput = [
                'name'     => $_FILES[$fileField]['name'][$i],
                'tmp_name' => $_FILES[$fileField]['tmp_name'][$i],
                'error'    => UPLOAD_ERR_OK,
                'size'     => $_FILES[$fileField]['size'][$i],
            ];
            $path = handle_image_upload($fileInput, $imageFolder, null, $maxKb);
            if ($path) $podImages[] = $path;
        }
    }

    // Delivered requires at least 1 POD image
    if ($status === 'Delivered' && empty($podImages)) {
        echo json_encode(['status' => 'error', 'message' => 'Proof of delivery image is required for Delivered status']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $finalStatus        = $status;
        $shouldWriteTracking = true;

        $hasImages   = !empty($podImages);
        $imgsJson    = $hasImages ? json_encode($podImages) : null;
        $isDelivered = ($status === 'Delivered');

        // Build SET clause dynamically:
        // - delivery_date  → only on Delivered
        // - pod_images     → only on Delivered (preserve pickup images otherwise)
        // - delivery_pod_images → only when images were actually uploaded
        $setClauses = [
            'status     = :status',
            'remarks    = :rem',
            'updated_by = :uid',
            'updated_at = NOW()',
        ];
        $params = [
            ':status' => $status,
            ':rem'    => $remarks,
            ':uid'    => $user_id,
        ];

        if ($isDelivered) {
            $setClauses[] = 'delivery_date = :dt';
            $params[':dt'] = $statusDateTime;
        }

        if ($hasImages) {
            $setClauses[] = 'delivery_pod_images = :dimgs';
            $params[':dimgs'] = $imgsJson;
            if ($isDelivered) {
                // Also update pod_images column on final delivery
                $setClauses[] = 'pod_images = :pimgs';
                $params[':pimgs'] = $imgsJson;
            }
        }

        $setSQL = implode(', ', $setClauses);

        if ($isChild && $packageId > 0) {
            $params[':id'] = $packageId;
            $pdo->prepare("UPDATE tbl_booking_packages SET $setSQL WHERE id = :id")
                ->execute($params);

            // Check all package statuses to decide booking-level status
            $allStmt = $pdo->prepare("SELECT status FROM tbl_booking_packages WHERE booking_id = :bid");
            $allStmt->execute([':bid' => $bookingId]);
            $allStatuses = $allStmt->fetchAll(PDO::FETCH_COLUMN);
            $unique = array_unique(array_filter($allStatuses));

            if (count($unique) === 1 && strtolower(reset($unique)) === 'delivered') {
                $finalStatus = 'Delivered';
            } elseif ($isDelivered) {
                $finalStatus = 'Partially Delivered';
            }
        } else {
            $params[':bid'] = $bookingId;
            $pdo->prepare("UPDATE tbl_booking_packages SET $setSQL WHERE booking_id = :bid")
                ->execute($params);
        }

        // Update booking last_status
        $pdo->prepare("UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id")
            ->execute([':status' => $finalStatus, ':uid' => $user_id, ':id' => $bookingId]);

        // Write tracking record
        $waybillNo = $booking['waybill_no'] ?? '';
        $exStmt = $pdo->prepare("SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1");
        $exStmt->execute([':wn' => $waybillNo]);
        $exTrack = $exStmt->fetch(PDO::FETCH_ASSOC);

        $history = [];
        if ($exTrack && !empty($exTrack['raw_response'])) {
            $decoded = json_decode($exTrack['raw_response'], true);
            if (!empty($decoded['scan_details_history'])) $history = $decoded['scan_details_history'];
            elseif (!empty($decoded['scan_details']))     $history = [$decoded['scan_details']];
        }

        $newScan = [
            'status'              => $finalStatus,
            'datetime'            => $statusDateTime,
            'location'            => $location,
            'remarks'             => $remarks,
            'receiver_name'       => $receiver_name,
            'receiver_phone'      => $receiver_phone,
            'delivery_pod_images' => $podImages,
            'image_count'         => count($podImages),
            'updated_by'          => $user_id,
            'type'                => 'DELIVERY',
        ];
        $history[] = $newScan;

        $rawData = json_encode([
            'awb_no'               => $waybillNo,
            'booking_id'           => $bookingId,
            'booking_ref_id'       => $booking['booking_ref_id'] ?? '',
            'current_status'       => $finalStatus,
            'scan_details'         => $newScan,
            'scan_details_history' => $history,
        ]);

        if ($exTrack) {
            $pdo->prepare("UPDATE tbl_tracking SET scan_type=:st, scan_location=:loc, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id")
                ->execute([':st'=>$finalStatus,':loc'=>$location,':dt'=>$statusDateTime,':sc'=>$finalStatus,':rem'=>$remarks,':raw'=>$rawData,':id'=>$exTrack['id']]);
            $trackingId = (int)$exTrack['id'];
        } else {
            $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid,:wn,:st,:loc,:dt,:sc,:rem,:raw)")
                ->execute([':bid'=>$bookingId,':wn'=>$waybillNo,':st'=>$finalStatus,':loc'=>$location,':dt'=>$statusDateTime,':sc'=>$finalStatus,':rem'=>$remarks,':raw'=>$rawData]);
            $trackingId = (int)$pdo->lastInsertId();
        }

        $pdo->commit();

        echo json_encode([
            'status'        => 'success',
            'message'       => $finalStatus . ' updated' . (count($podImages) > 0 ? ' with ' . count($podImages) . ' image(s)' : ''),
            'booking_id'    => $bookingId,
            'waybill_no'    => $waybillNo,
            'new_status'    => $finalStatus,
            'is_child'      => $isChild,
            'package_id'    => $packageId,
            'tracking_id'   => $trackingId,
            'pod_images'    => $podImages,
            'image_count'   => count($podImages),
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
