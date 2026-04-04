<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

try {
    $currentUser = get_current_user_info();
    $scannedBy   = $currentUser['username'] ?? ($_SESSION['username'] ?? 'system');

    $runsheetId = (int)($_POST['runsheet_id'] ?? 0);
    $scanValue  = trim($_POST['scan_value'] ?? '');

    if ($runsheetId <= 0 || $scanValue === '') {
        throw new Exception('runsheet_id and scan_value required');
    }

    // Check runsheet exists
    $rStmt = $pdo->prepare("SELECT * FROM tbl_runsheet WHERE id = :id LIMIT 1");
    $rStmt->execute([':id' => $runsheetId]);
    $runsheet = $rStmt->fetch(PDO::FETCH_ASSOC);
    if (!$runsheet) throw new Exception('Run Sheet not found');

    // Check duplicate in details table
    $dupStmt = $pdo->prepare(
        "SELECT id FROM tbl_runsheet_details WHERE runsheet_id = :rid AND awb_no = :awb LIMIT 1"
    );
    $dupStmt->execute([':rid' => $runsheetId, ':awb' => $scanValue]);
    if ($dupStmt->fetch()) {
        throw new Exception('AWB ' . $scanValue . ' already in this run sheet');
    }

    // Lookup shipment in packages first
    $pkgStmt = $pdo->prepare(
        "SELECT bp.awb_no, b.id AS booking_id,
                b.consignee_name, b.consignee_city,
                b.consignee_address, b.consignee_phone, b.courier_id
         FROM tbl_booking_packages bp
         JOIN tbl_bookings b ON b.id = bp.booking_id
         WHERE bp.awb_no = :awb LIMIT 1"
    );
    $pkgStmt->execute([':awb' => $scanValue]);
    $pkgRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $booking = null;
    if (!$pkgRow) {
        $bkStmt = $pdo->prepare(
            "SELECT id AS booking_id, waybill_no AS awb_no,
                    consignee_name, consignee_city,
                    consignee_address, consignee_phone, courier_id
             FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1"
        );
        $bkStmt->execute([':awb' => $scanValue]);
        $booking = $bkStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$pkgRow && !$booking) throw new Exception('Shipment not found: ' . $scanValue);

    $row = $pkgRow ?: $booking;

    if ($row['courier_id'] != 2) {
        throw new Exception('Only Own Courier (ID 2) shipments can be added to Run Sheet');
    }

    // Insert into tbl_runsheet_details
    $now = date('Y-m-d H:i:s');
    $insStmt = $pdo->prepare(
        "INSERT INTO tbl_runsheet_details
            (runsheet_id, awb_no, booking_id, consignee_name, consignee_city, consignee_phone, address, status, scanned_at, scanned_by)
         VALUES
            (:runsheet_id, :awb_no, :booking_id, :consignee_name, :consignee_city, :consignee_phone, :address, 'Pending', :scanned_at, :scanned_by)"
    );
    $insStmt->execute([
        ':runsheet_id'     => $runsheetId,
        ':awb_no'          => $scanValue,
        ':booking_id'      => $row['booking_id'],
        ':consignee_name'  => $row['consignee_name']  ?? '',
        ':consignee_city'  => $row['consignee_city']  ?? '',
        ':consignee_phone' => $row['consignee_phone'] ?? '',
        ':address'         => $row['consignee_address'] ?? '',
        ':scanned_at'      => $now,
        ':scanned_by'      => $scannedBy,
    ]);
    $detailId = $pdo->lastInsertId();

    // Update shipment_count in header
    $pdo->prepare("UPDATE tbl_runsheet SET shipment_count = shipment_count + 1 WHERE id = :id")
        ->execute([':id' => $runsheetId]);

    $total = $runsheet['shipment_count'] + 1;

    echo json_encode([
        'status'      => 'success',
        'entry'       => [
            'id'             => $detailId,
            'runsheet_id'    => $runsheetId,
            'awb_no'         => $scanValue,
            'booking_id'     => $row['booking_id'],
            'consignee_name' => $row['consignee_name']  ?? '',
            'consignee_city' => $row['consignee_city']  ?? '',
            'consignee_phone'=> $row['consignee_phone'] ?? '',
            'address'        => $row['consignee_address'] ?? '',
            'status'         => 'Pending',
            'scanned_at'     => $now,
            'scanned_by'     => $scannedBy,
        ],
        'total_count' => $total,
        'message'     => 'AWB added to run sheet',
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
