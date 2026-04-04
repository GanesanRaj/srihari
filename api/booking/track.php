<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// require_api_permission('shipment', 'is_view');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $waybillParam = isset($_GET['waybill']) ? trim($_GET['waybill']) : '';

    if ($bookingId <= 0 && $waybillParam === '') {
        throw new Exception('Missing Booking ID or Waybill Number');
    }

    $sql = "SELECT b.id, b.waybill_no, b.courier_id, b.last_status, c.api_key, c.api_url, c.partner_name, c.partner_code
            FROM tbl_bookings b
            JOIN tbl_courier_partner c ON b.courier_id = c.id
            WHERE " . ($bookingId > 0 ? 'b.id = :id' : 'b.waybill_no = :waybill');

    $stmt = $pdo->prepare($sql);
    if ($bookingId > 0) {
        $stmt->execute([':id' => $bookingId]);
    } else {
        $stmt->execute([':waybill' => $waybillParam]);
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    if (empty($booking['waybill_no'])) {
        throw new Exception('No Waybill Number associated with this booking');
    }

    require_once __DIR__ . '/services/courier_service.php';
    $trackResult = trackBookingWithCourier($pdo, $booking, $booking['waybill_no']);

    if (empty($trackResult['success'])) {
        throw new Exception('Tracking API failed: ' . ($trackResult['message'] ?? 'Unknown error'));
    }

    $shipmentData = $trackResult['data'];
    $scans = $shipmentData['Scans'] ?? [];
    $currentStatus = $shipmentData['Shipment']['Status']['Status'] ?? $booking['last_status'];

    if ($currentStatus !== $booking['last_status']) {
        $updSql = "UPDATE tbl_bookings SET last_status = :status, updated_at = NOW() WHERE id = :id";
        $pdo->prepare($updSql)->execute([':status' => $currentStatus, ':id' => $booking['id']]);
    }

    $checkScanStmt = $pdo->prepare("SELECT id FROM tbl_tracking WHERE booking_id = :bid AND scan_datetime = :dt AND scan_type = :st");
    $insertScanStmt = $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)");

    foreach ($scans as $scan) {
        $scanTime = $scan['ScanDetail']['ScanDateTime'] ?? null;
        if (!$scanTime) {
            continue;
        }

        $scanType = $scan['ScanDetail']['ScanType'] ?? 'Unknown';
        $location = $scan['ScanDetail']['ScannedLocation'] ?? '';
        $statusCode = $scan['ScanDetail']['Status'] ?? '';
        $instructions = $scan['ScanDetail']['Instructions'] ?? '';

        $mysqlTime = (new DateTime($scanTime))->format('Y-m-d H:i:s');

        $checkScanStmt->execute([
            ':bid' => $booking['id'],
            ':dt' => $mysqlTime,
            ':st' => $scanType
        ]);

        if (!$checkScanStmt->fetch()) {
            $insertScanStmt->execute([
                ':bid' => $booking['id'],
                ':wn' => $booking['waybill_no'],
                ':st' => $scanType,
                ':sl' => $location,
                ':dt' => $mysqlTime,
                ':sc' => $statusCode,
                ':rem' => $instructions,
                ':raw' => json_encode($scan)
            ]);
        }
    }

    echo json_encode([
        'status' => 'success',
        'current_status' => $currentStatus,
        'scans_count' => count($scans),
        'data' => $shipmentData
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
