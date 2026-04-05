<?php
/**
 * booking_cancel.php
 *
 * Service-based booking cancellation
 * Handles different courier partners: Shiprocket, Delhivery, Own Courier, etc.
 *
 * Features:
 * - Detects courier service type from booking
 * - Calls appropriate external API cancel endpoint
 * - Updates local booking status to 'Cancelled' (soft cancel)
 * - Adds tracking record for the cancellation
 * - Restores serial allocations for Own Courier
 *
 * POST JSON:
 * {
 *   "ids": [123, 456]  // booking IDs
 * }
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Superadmin only (same pattern as shipment_delete)
$roleId = (int)($_SESSION['role_id'] ?? 0);
if ($roleId !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Superadmin only.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: $_POST;

$ids = $body['ids'] ?? [];

if (!is_array($ids)) {
    $ids = array_filter(array_map('intval', explode(',', (string)$ids)));
}
$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No booking ID(s) provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $cancelled = 0;
    $failed = [];
    $updatedBy = (int)($_SESSION['user_id'] ?? 1);

    foreach ($ids as $bookingId) {
        // Fetch booking with courier info
        $bkStmt = $pdo->prepare("
            SELECT b.id, b.courier_id, b.waybill_no,
                   c.partner_name, c.partner_code, c.token, c.api_url, c.api_key
            FROM tbl_bookings b
            JOIN tbl_courier_partner c ON b.courier_id = c.id
            WHERE b.id = :id LIMIT 1
        ");
        $bkStmt->execute([':id' => $bookingId]);
        $booking = $bkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $failed[] = ['id' => $bookingId, 'error' => 'Booking not found'];
            continue;
        }

        $waybillNo = $booking['waybill_no'] ?? '';

        // Call service-specific cancel function
        require_once __DIR__ . '/../cancel/courier_service.php';
        $apiResult = cancelBookingWithCourierService($pdo, $booking, $booking);

        if (!$apiResult['success']) {
            $failed[] = ['id' => $bookingId, 'error' => $apiResult['error'] ?? $apiResult['message'] ?? 'Cancel failed'];
            continue;
        }

        // Call tracking API to update status and JSON after cancellation
        try {
            require_once __DIR__ . '/../booking/services/courier_service.php';
            $trackResult = trackBookingWithCourier($pdo, $booking, $waybillNo);
            
            if (!empty($trackResult['success'])) {
                $shipmentData = $trackResult['data'] ?? [];
                $currentStatus = $shipmentData['Shipment']['Status']['Status'] ?? 'Cancelled';
                
                // Update booking status with latest from tracking API
                if ($currentStatus !== 'Cancelled') {
                    $pdo->prepare("
                        UPDATE tbl_bookings 
                        SET last_status = :status, updated_at = NOW() 
                        WHERE id = :id
                    ")->execute([':status' => $currentStatus, ':id' => $bookingId]);
                }
                
                // Update tracking with latest data
                if (!empty($shipmentData['Scans'])) {
                    $insertScanStmt = $pdo->prepare("
                        INSERT INTO tbl_tracking 
                        (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) 
                        VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)
                    ");
                    
                    foreach ($shipmentData['Scans'] as $scan) {
                        $scanTime = $scan['ScanDetail']['ScanDateTime'] ?? '';
                        if ($scanTime) {
                            $mysqlTime = (new DateTime($scanTime))->format('Y-m-d H:i:s');
                            $insertScanStmt->execute([
                                ':bid' => $bookingId,
                                ':wn' => $waybillNo,
                                ':st' => $scan['ScanDetail']['ScanType'] ?? 'Unknown',
                                ':sl' => $scan['ScanDetail']['ScannedLocation'] ?? '',
                                ':dt' => $mysqlTime,
                                ':sc' => $scan['ScanDetail']['Status'] ?? '',
                                ':rem' => $scan['ScanDetail']['Instructions'] ?? '',
                                ':raw' => json_encode($scan)
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Tracking API call failed, but cancellation was successful
            // Continue with the cancellation process
        }

        // Update local booking status (soft cancel)
        $updStmt = $pdo->prepare("
            UPDATE tbl_bookings 
            SET last_status = 'Cancelled', updated_by = :user_id, updated_at = NOW() 
            WHERE id = :id
        ");
        $updStmt->execute([':user_id' => $updatedBy, ':id' => $bookingId]);

        // Add tracking record (check for duplicate)
        if ($waybillNo) {
            // Check if tracking record already exists for this booking with Cancelled status
            $checkTrack = $pdo->prepare("SELECT id FROM tbl_tracking WHERE booking_id = :bid AND scan_type = 'Cancelled' LIMIT 1");
            $checkTrack->execute([':bid' => $bookingId]);
            
            if (!$checkTrack->fetch()) {
                // Use a unique identifier to avoid waybill_no constraint
                $trackSql = "INSERT INTO tbl_tracking 
                    (booking_id, waybill_no, scan_type, status_code, scan_datetime, remarks) 
                    VALUES (:bid, :wn, 'Cancelled', 'Cancelled', NOW(), :remark)";
                $remark = 'Shipment cancelled via API';
                
                try {
                    $pdo->prepare($trackSql)->execute([':bid' => $bookingId, ':wn' => $waybillNo . '_CANCELLED', ':remark' => $remark]);
                } catch (Exception $e) {
                    // If still fails due to waybill constraint, skip tracking insert
                    // The booking status update is more important
                }
            }
        }

        // Update manifest JSON if exists
        try {
            $mfStmt = $pdo->prepare("SELECT id, json_data FROM tbl_manifest WHERE json_data LIKE :wbn");
            $mfStmt->execute([':wbn' => '%' . $waybillNo . '%']);
            $manifests = $mfStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($manifests as $mf) {
                $mfData = json_decode($mf['json_data'], true);
                if (!is_array($mfData)) continue;
                $changed = false;
                foreach ($mfData as &$entry) {
                    if (($entry['awb_no'] ?? '') === $waybillNo) {
                        $entry['status'] = 'Cancelled';
                        $entry['remarks'] = 'Booking cancelled by superadmin';
                        $changed = true;
                    }
                }
                unset($entry);
                if ($changed) {
                    $pdo->prepare("UPDATE tbl_manifest SET json_data = :json, updated_at = NOW() WHERE id = :id")
                        ->execute([':json' => json_encode($mfData), ':id' => $mf['id']]);
                }
            }
        } catch (Exception $e) { /* non-fatal */ }

        $cancelled++;
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "Cancelled {$cancelled} booking(s).",
        'cancelled' => $cancelled,
        'failed' => $failed
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cancel failed: ' . $e->getMessage()]);
}
?>
