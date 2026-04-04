<?php
/**
 * Tag API – Scan Shipment
 * Location: /apps-api/tag/scan.php
 * Params (GET): tag_id, awb_no, eway_bill_no (opt), user_id (opt), remarks (opt), clear (opt, "1" = clear json before insert)
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

$tag_id       = (int)($req['tag_id']      ?? 0);
$awb_no       = trim($req['awb_no']       ?? '');
$eway_bill_no = trim($req['eway_bill_no'] ?? '');
$user_id      = (int)($req['user_id']     ?? 1);
$remarks      = trim($req['remarks']      ?? '');
$clear        = ($req['clear']            ?? '0') === '1';

if ($tag_id <= 0 || $awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'tag_id and awb_no are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch tag
    $tagStmt = $pdo->prepare("SELECT * FROM tbl_tags WHERE id = :id FOR UPDATE");
    $tagStmt->execute([':id' => $tag_id]);
    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);
    if (!$tag) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    // Lookup AWB (logic from api/tag/scan.php)
    $pkgStmt = $pdo->prepare("SELECT bp.*, b.consignee_name, b.consignee_city, b.consignee_phone, b.courier_id, b.invoice_value, b.ewaybill_no
                               FROM tbl_booking_packages bp
                               JOIN tbl_bookings b ON b.id = bp.booking_id
                               WHERE bp.awb_no = :awb LIMIT 1");
    $pkgStmt->execute([':awb' => $awb_no]);
    $pkgRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $booking = null;
    if (!$pkgRow) {
        $bkStmt = $pdo->prepare("SELECT id, consignee_name, consignee_city, consignee_phone, courier_id, invoice_value, ewaybill_no FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1");
        $bkStmt->execute([':awb' => $awb_no]);
        $booking = $bkStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$pkgRow && !$booking) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Shipment not found']);
        exit;
    }

    $bookingId = $pkgRow['booking_id'] ?? ($booking['id'] ?? null);
    $courierId = $pkgRow['courier_id'] ?? ($booking['courier_id'] ?? null);
    $invoiceValue = $pkgRow['invoice_value'] ?? ($booking['invoice_value'] ?? 0);
    $existingEwayBillNo = $pkgRow['ewaybill_no'] ?? ($booking['ewaybill_no'] ?? '');

    // Own Courier restriction (Courier ID 2)
    if ($courierId != 2) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Only Own Courier shipments can be scanned into Tags']);
        exit;
    }

    // E-Waybill check
    if (floatval($invoiceValue) > 50000) {
        if (empty($existingEwayBillNo) && empty($eway_bill_no)) {
            $pdo->rollBack();
            echo json_encode([
                'status'      => 'success',
                // 'status' => 'require_ewaybill',
                'message' => 'Invoice value over ₹50,000. E-waybill required.',
                'invoice_value' => $invoiceValue
            ]);
            exit;
        }

        if (!empty($eway_bill_no)) {
            $updEwayStmt = $pdo->prepare("UPDATE tbl_bookings SET ewaybill_no = :eway WHERE id = :id");
            $updEwayStmt->execute([':eway' => $eway_bill_no, ':id' => $bookingId]);
            $existingEwayBillNo = $eway_bill_no;
        }
    }

    // Current entries — clear all if clear=1
    $entries = $clear ? [] : json_decode($tag['json_data'] ?: '[]', true);
    foreach ($entries as $entry) {
        if ($entry['awb_no'] === $awb_no) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'AWB already scanned in this tag']);
            exit;
        }
    }

    $newEntry = [
        'awb_no' => $awb_no,
        'booking_id' => $bookingId,
        'consignee_name' => $pkgRow['consignee_name'] ?? ($booking['consignee_name'] ?? ''),
        'consignee_city' => $pkgRow['consignee_city'] ?? ($booking['consignee_city'] ?? ''),
        'status' => 'packed',
        'timestamp' => date('Y-m-d H:i:s'),
        'remarks' => $remarks,
        'ewaybill_no' => $existingEwayBillNo
    ];
    $entries[] = $newEntry;

    // Recalculate tag status
    $hasHold   = in_array('hold', array_column($entries, 'status'));
    $tagStatus = $hasHold ? 'hold' : 'packed';

    // Update tag
    $updStmt = $pdo->prepare("UPDATE tbl_tags SET json_data = :json, total_count = :cnt, status = :status WHERE id = :id");
    $updStmt->execute([
        ':json'   => json_encode($entries),
        ':cnt'    => count($entries),
        ':status' => $tagStatus,
        ':id'     => $tag_id
    ]);

    $pdo->commit();

    echo json_encode([
        'status'      => 'success',
        'message'     => 'AWB added to tag',
        'tag_status'  => $tagStatus,
        'total_count' => count($entries),
        'cleared'     => $clear,
        'entry'       => $newEntry
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
