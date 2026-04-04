<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $id = $_POST['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Payroll ID required']);
        exit;
    }

    // Check if status is draft
    $checkStmt = $pdo->prepare("SELECT status FROM tbl_payroll WHERE id = ?");
    $checkStmt->execute([$id]);
    $payroll = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        echo json_encode(['success' => false, 'message' => 'Payroll not found']);
        exit;
    }

    if ($payroll['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Only draft payrolls can be deleted']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tbl_payroll WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payroll deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete payroll']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
