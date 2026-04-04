<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

// require_api_permission('shift', 'is_delete');

try {
    $id = $_POST['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Shift ID required']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tbl_shifts WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Shift deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete shift']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
