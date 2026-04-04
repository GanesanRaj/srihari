<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Delete Permission
require_api_permission('consignor', 'is_delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }

    try {
        // Prepare delete statement
        $sql = "DELETE FROM tbl_consignor WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Consignor deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete consignor']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>