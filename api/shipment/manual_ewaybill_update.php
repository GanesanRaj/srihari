<?php
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';
require_once __DIR__ . '/../booking/services/delhivery.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) {
        $json = [];
    }

    $updates = [];
    if (!empty($json['updates']) && is_array($json['updates'])) {
        $updates = $json['updates'];
    } elseif (!empty($_POST['id'])) {
        $updates[] = [
            'id' => (int)$_POST['id'],
            'invoice_no' => trim((string)($_POST['invoice_no'] ?? '')),
            'ewaybill_no' => trim((string)($_POST['ewaybill_no'] ?? ''))
        ];
    } elseif (!empty($json['id'])) {
        $updates[] = [
            'id' => (int)$json['id'],
            'invoice_no' => trim((string)($json['invoice_no'] ?? '')),
            'ewaybill_no' => trim((string)($json['ewaybill_no'] ?? ''))
        ];
    }

    if (empty($updates)) {
        throw new Exception('No shipment update payload found');
    }

    $results = [];
    $successCount = 0;
    $failCount = 0;

    $bookingStmt = $pdo->prepare("SELECT b.id, b.waybill_no, b.invoice_no, b.ewaybill_no, b.courier_id,
                                         cp.partner_name, cp.partner_code, cp.api_key, cp.api_url
                                  FROM tbl_bookings b
                                  LEFT JOIN tbl_courier_partner cp ON cp.id = b.courier_id
                                  WHERE b.id = :id LIMIT 1");
    $updStmt = $pdo->prepare("UPDATE tbl_bookings
                              SET invoice_no = :inv,
                                  ewaybill_no = :ewb,
                                  ewb_update_status = :st,
                                  ewb_update_response = :resp,
                                  ewb_update_at = NOW(),
                                  updated_at = NOW()
                              WHERE id = :id");

    foreach ($updates as $item) {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) {
            $failCount++;
            $results[] = ['id' => $id, 'success' => false, 'message' => 'Invalid booking id'];
            continue;
        }

        $bookingStmt->execute([':id' => $id]);
        $bk = $bookingStmt->fetch(PDO::FETCH_ASSOC);
        if (!$bk) {
            $failCount++;
            $results[] = ['id' => $id, 'success' => false, 'message' => 'Shipment not found'];
            continue;
        }

        $partnerCode = strtolower(trim((string)($bk['partner_code'] ?? '')));
        $partnerName = strtolower(trim((string)($bk['partner_name'] ?? '')));
        $isDelhivery = (strpos($partnerCode, 'del') === 0 || strpos($partnerName, 'delhivery') !== false);
        if (!$isDelhivery) {
            $failCount++;
            $results[] = ['id' => $id, 'success' => false, 'message' => 'Manual EWB update is allowed only for Delhivery shipments'];
            continue;
        }

        $invoiceNo = trim((string)($item['invoice_no'] ?? ''));
        $ewaybillNo = trim((string)($item['ewaybill_no'] ?? ''));
        if ($invoiceNo === '') {
            $invoiceNo = trim((string)($bk['invoice_no'] ?? ''));
        }
        if ($ewaybillNo === '') {
            $ewaybillNo = trim((string)($bk['ewaybill_no'] ?? ''));
        }
        $waybillNo = trim((string)($bk['waybill_no'] ?? ''));

        $ewbResult = updateDelhiveryEwaybill($bk, $waybillNo, $invoiceNo, $ewaybillNo);
        $ok = !empty($ewbResult['success']);
        try {
            $updStmt->execute([
                ':inv' => $invoiceNo,
                ':ewb' => $ewaybillNo,
                ':st' => $ok ? 'success' : 'failed',
                ':resp' => json_encode($ewbResult),
                ':id' => $id
            ]);
        } catch ( Exception $e ) {
            $pdo->prepare("UPDATE tbl_bookings SET invoice_no = :inv, ewaybill_no = :ewb, updated_at = NOW() WHERE id = :id")
                ->execute([':inv' => $invoiceNo, ':ewb' => $ewaybillNo, ':id' => $id]);
        }

        if ($ok) {
            $successCount++;
        } else {
            $failCount++;
        }
        $results[] = [
            'id' => $id,
            'success' => $ok,
            'message' => $ewbResult['message'] ?? ($ok ? 'EWB updated' : 'EWB update failed')
        ];
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Processed {$successCount} success, {$failCount} failed",
        'success_count' => $successCount,
        'failure_count' => $failCount,
        'results' => $results
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
