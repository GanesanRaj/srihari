<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

require_api_permission('coloader', 'is_view');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = (int) $_GET['id'];

try {
    $sql = "SELECT c.*, uc.username AS created_by_name, uu.username AS updated_by_name
            FROM tbl_coloader c
            LEFT JOIN tbl_user uc ON c.created_by = uc.user_id
            LEFT JOIN tbl_user uu ON c.updated_by = uu.user_id
            WHERE c.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Coloader not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
