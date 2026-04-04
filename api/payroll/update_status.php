<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';

    if (empty($id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? 1;

    if ($status === 'approved') {
        $stmt = $pdo->prepare("
            UPDATE tbl_payroll
            SET status = 'approved', approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$user_id, $id]);
    } elseif ($status === 'paid') {
        $stmt = $pdo->prepare("
            UPDATE tbl_payroll
            SET status = 'paid', paid_date = CURDATE(), updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
