<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('branch', 'is_view');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid branch ID']);
    exit;
}

try {
    $sql = "SELECT b.*, c.company_name,
            u1.username as created_by_name,
            u2.username as updated_by_name
            FROM tbl_branch b
            LEFT JOIN tbl_company c ON b.company_id = c.id
            LEFT JOIN tbl_user u1 ON b.created_by = u1.id
            LEFT JOIN tbl_user u2 ON b.updated_by = u2.id
            WHERE b.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($branch) {
        echo json_encode(['status' => 'success', 'data' => $branch]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Branch not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>