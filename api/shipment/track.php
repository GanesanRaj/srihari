<?php
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';

// require_permission('shipment', 'is_view'); 

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $bookingId = $_GET['id'] ?? null;
    $waybillParam = $_GET['waybill'] ?? null;

    if (!$bookingId && !$waybillParam) {
        throw new Exception("Missing Booking ID or Waybill Number");
    }

    // 1. Fetch Booking and Courier Details
    $sql = "SELECT b.id, b.waybill_no, b.courier_id, b.last_status, c.api_key, c.api_url, c.partner_name 
            FROM tbl_bookings b
            JOIN tbl_courier_partner c ON b.courier_id = c.id
            WHERE " . ($bookingId ? "b.id = :id" : "b.waybill_no = :waybill");

    $stmt = $pdo->prepare($sql);
    if ($bookingId) {
        $stmt->execute([':id' => $bookingId]);
    } else {
        $stmt->execute([':waybill' => $waybillParam]);
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found");
    }

    if (!$booking['waybill_no']) {
        throw new Exception("No Waybill Number associated with this booking");
    }

    // 2. Call Courier Service
    $courierNameLower = strtolower($booking['partner_name']);
    $trackResult = ['success' => false, 'message' => 'Service not implemented'];

    if (strpos($courierNameLower, 'delhivery') !== false) {
        require_once __DIR__ . '/services/delhivery.php';
        // Pass dummy PDO if needed or null, my function doesn't use it
        $trackResult = trackShipmentDelhivery($pdo, $booking, $booking['waybill_no']);
    } else {
        throw new Exception("Only Delhivery tracking supported");
    }

    if (!$trackResult['success']) {
        throw new Exception("Tracking API Failed: " . ($trackResult['message'] ?? 'Unknown error'));
    }

    $shipmentData = $trackResult['data']; // ShipmentData[0]
    $scans = $shipmentData['Scans'] ?? [];
    $currentStatus = $shipmentData['Shipment']['Status']['Status'] ?? $booking['last_status'];

    // 3. Update last_status in tbl_bookings
    if ($currentStatus !== $booking['last_status']) {
        $updSql = "UPDATE tbl_bookings SET last_status = :status, updated_at = NOW() WHERE id = :id";
        $pdo->prepare($updSql)->execute([':status' => $currentStatus, ':id' => $booking['id']]);
    }

    // 4. Update tbl_tracking (Insert new events)
    // To avoid duplicates, we can check latest scan time or try to insert and ignore duplicates if we had a unique constraint (we don't on scans)
    // Simple approach: Check if scan already exists for this booking by timestamp + location + type

    $checkScanStmt = $pdo->prepare("SELECT id FROM tbl_tracking WHERE booking_id = :bid AND scan_datetime = :dt AND scan_type = :st");
    $insertScanStmt = $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)");

    foreach ($scans as $scan) {
        $scanTime = $scan['ScanDetail']['ScanDateTime'] ?? null;
        $scanType = $scan['ScanDetail']['ScanType'] ?? 'Unknown';
        $location = $scan['ScanDetail']['ScannedLocation'] ?? '';
        $statusCode = $scan['ScanDetail']['Status'] ?? '';
        $instructions = $scan['ScanDetail']['Instructions'] ?? '';

        // Convert ISO 8601 to MySQL datetime if needed, usually passed as "2023-01-01T12:00:00"
        // PHP DateTime handles it well
        $dtObj = new DateTime($scanTime);
        $mysqlTime = $dtObj->format('Y-m-d H:i:s');

        // Check duplicate
        $checkScanStmt->execute([':bid' => $booking['id'], ':dt' => $mysqlTime, ':st' => $scanType]);
        if (!$checkScanStmt->fetch()) {
            // New scan, insert
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

    // Return formatted history
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