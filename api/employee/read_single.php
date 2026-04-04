<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT e.*, b.branch_name, r.name as role_name, d.designation 
            FROM tbl_employees e
            LEFT JOIN tbl_branch b ON e.branch_id = b.id
            LEFT JOIN roles r ON e.role_id = r.id
            LEFT JOIN tbl_designations d ON e.designation_id = d.id
            WHERE e.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
