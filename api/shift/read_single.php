<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

// require_api_permission('shift', 'is_view');

try {
    $id = $_GET['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Shift ID required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM tbl_shifts WHERE id = ?");
    $stmt->execute([$id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($shift) {
        echo json_encode(['success' => true, 'data' => $shift]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Shift not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
