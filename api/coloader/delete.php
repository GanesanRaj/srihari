<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

require_api_permission('coloader', 'is_delete');

$id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
}

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM tbl_coloader WHERE id = :id");
    $stmt->bindValue(':id', $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Coloader deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete coloader']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
