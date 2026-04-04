<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('consignee', 'is_view');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit();
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT c.*, b.branch_name, cl.client_name, 
            uc.username as created_by_name, uu.username as updated_by_name
            FROM tbl_consignee c
            LEFT JOIN tbl_branch b ON c.branch_id = b.id
            LEFT JOIN tbl_client cl ON c.client_id = cl.id
            LEFT JOIN tbl_user uc ON c.created_by = uc.user_id
            LEFT JOIN tbl_user uu ON c.updated_by = uu.user_id
            WHERE c.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $consignee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($consignee) {
        echo json_encode(['status' => 'success', 'data' => $consignee]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Consignee not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>