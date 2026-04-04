<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('client', 'is_view');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No ID specified']);
    exit;
}

try {
    $id = intval($_GET['id']);

    // Join with branch and creator/updater for details
    $sql = "SELECT c.*, b.branch_name, 
                   u1.username as created_by_name, 
                   u2.username as updated_by_name
            FROM tbl_client c
            LEFT JOIN tbl_branch b ON c.branch_id = b.id
            LEFT JOIN tbl_user u1 ON c.created_by = u1.id
            LEFT JOIN tbl_user u2 ON c.updated_by = u2.id
            WHERE c.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Client not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>