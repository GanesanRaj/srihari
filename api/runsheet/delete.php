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
    if ($runsheetId <= 0) throw new Exception('runsheet_id required');

    // Delete details first (in case no FK cascade)
    $pdo->prepare("DELETE FROM tbl_runsheet_details WHERE runsheet_id = :id")
        ->execute([':id' => $runsheetId]);

    $stmt = $pdo->prepare("DELETE FROM tbl_runsheet WHERE id = :id");
    $stmt->execute([':id' => $runsheetId]);

    if ($stmt->rowCount() === 0) throw new Exception('Run Sheet not found');

    echo json_encode(['status' => 'success', 'message' => 'Run Sheet deleted']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
