<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get current user
    $current_user = get_current_user_info();
    $userId = $current_user ? $current_user['id'] : 1;

    // Required fields
    $bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $statusDate = isset($_POST['status_date']) ? trim($_POST['status_date']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    // Validate required fields
    if ($bookingId <= 0) {
        throw new Exception('Booking ID is required');
    }
    if (empty($status)) {
        throw new Exception('Status is required');
    }
    if (empty($statusDate)) {
        throw new Exception('Status date is required');
    }

    // Check if booking exists
    $checkStmt = $pdo->prepare("SELECT id, waybill_no, last_status, courier_id FROM tbl_bookings WHERE id = :id");
    $checkStmt->execute([':id' => $bookingId]);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    // Convert datetime-local format to MySQL datetime
    if (strpos($statusDate, 'T') !== false) {
        $statusDateTime = str_replace('T', ' ', $statusDate);
        if (strlen($statusDateTime) === 16) {
            $statusDateTime .= ':00';
        }
    } else if (strpos($statusDate, ' ') !== false) {
        $statusDateTime = $statusDate;
        if (strlen($statusDateTime) === 16) {
            $statusDateTime .= ':00';
        }
    } else {
        $statusDateTime = $statusDate . ' 00:00:00';
    }

    // Validate datetime format
    $dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $statusDateTime);
    if (!$dateObj) {
        throw new Exception('Invalid date format. Received: ' . $statusDate);
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Fetch existing tracking record to check for raw_response
        $existingTrackStmt = $pdo->prepare("SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1");
        $existingTrackStmt->execute([':wn' => $booking['waybill_no']]);
        $existingTrack = $existingTrackStmt->fetch(PDO::FETCH_ASSOC);

        // Prepare JSON data based on courier type and existing data
        if ($booking['courier_id'] == 2) {
            // "Own Courier" specific structure with history appending
            $history = [];
            $decoded = null;

            if ($existingTrack && !empty($existingTrack['raw_response'])) {
                $decoded = json_decode($existingTrack['raw_response'], true);
                if (isset($decoded['scan_details_history'])) {
                    $history = $decoded['scan_details_history'];
                } else if (isset($decoded['scan_details'])) {
                    // Migrate old format
                    $history[] = $decoded['scan_details'];
                }
            }

            // New scan entry
            $newScan = [
                'status' => $status,
                'location' => $location,
                'datetime' => $statusDateTime,
                'remarks' => $remarks,
                'updated_by' => $userId
            ];

            // Append to history
            $history[] = $newScan;

            $rawData = json_encode([
                'awb_no' => $booking['waybill_no'],
                'shipment_details' => [
                    'id' => $bookingId,
                    'booking_ref_id' => $booking['booking_ref_id'] ?? ''
                ],
                'current_status' => $status,
                'scan_details' => $newScan, // Latest scan
                'scan_details_history' => $history // Full array of scans
            ]);
        } else {
            // Standard tracking structure for other carriers
            $rawData = json_encode([
                'status' => $status,
                'location' => $location,
                'remarks' => $remarks,
                'user_id' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        if ($existingTrack) {
            // UPDATE existing row to append history and keep unique waybill
            $sql = "UPDATE tbl_tracking SET 
                    scan_type = :st, 
                    scan_location = :sl, 
                    scan_datetime = :dt, 
                    status_code = :sc, 
                    remarks = :rem, 
                    raw_response = :raw 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $existingTrack['id'],
                ':st' => $status,
                ':sl' => $location,
                ':dt' => $statusDateTime,
                ':sc' => $status,
                ':rem' => $remarks,
                ':raw' => $rawData
            ]);
            $trackingId = $existingTrack['id'];
        } else {
            // INSERT if no record exists for this waybill
            $sql = "INSERT INTO tbl_tracking 
                    (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) 
                    VALUES 
                    (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':bid' => $bookingId,
                ':wn' => $booking['waybill_no'] ?: '',
                ':st' => $status,
                ':sl' => $location,
                ':dt' => $statusDateTime,
                ':sc' => $status,
                ':rem' => $remarks,
                ':raw' => $rawData
            ]);
            $trackingId = $pdo->lastInsertId();
        }

        // Update booking's last_status
        $updateBookingStmt = $pdo->prepare(
            "UPDATE tbl_bookings 
             SET last_status = :status, updated_by = :uid, updated_at = NOW() 
             WHERE id = :id"
        );
        $updateBookingStmt->execute([
            ':status' => $status,
            ':uid' => $userId,
            ':id' => $bookingId
        ]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Tracking updated successfully',
            'tracking_id' => $trackingId,
            'booking_id' => $bookingId,
            'new_status' => $status
        ]);

    } catch (Exception $transactionError) {
        $pdo->rollBack();
        throw $transactionError;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>