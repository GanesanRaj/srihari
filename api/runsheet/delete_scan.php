<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

try {
    $runsheetId = (int)($_POST['runsheet_id'] ?? 0);
    $awbNo      = trim($_POST['awb_no'] ?? '');

    if ($runsheetId <= 0 || $awbNo === '') throw new Exception('runsheet_id and awb_no required');

    // Delete the detail row
    $delStmt = $pdo->prepare(
        "DELETE FROM tbl_runsheet_details WHERE runsheet_id = :rid AND awb_no = :awb"
    );
    $delStmt->execute([':rid' => $runsheetId, ':awb' => $awbNo]);

    if ($delStmt->rowCount() === 0) throw new Exception('AWB not found in this run sheet');

    // Decrement shipment_count (floor at 0)
    $pdo->prepare(
        "UPDATE tbl_runsheet SET shipment_count = GREATEST(shipment_count - 1, 0) WHERE id = :id"
    )->execute([':id' => $runsheetId]);

    // Return new total
    $cntStmt = $pdo->prepare("SELECT shipment_count FROM tbl_runsheet WHERE id = :id");
    $cntStmt->execute([':id' => $runsheetId]);
    $total = (int)$cntStmt->fetchColumn();

    echo json_encode(['status' => 'success', 'total_count' => $total]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
