<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('company', 'is_view');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_GET['id']);

try {
    $sql = "SELECT * FROM tbl_company WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo json_encode(['status' => 'success', 'data' => $company]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Company not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>