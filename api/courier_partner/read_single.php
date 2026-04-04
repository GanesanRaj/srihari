<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('courier_partner', 'is_view');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

try {
    $sql = "SELECT * FROM tbl_courier_partner WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($partner) {
        echo json_encode(['status' => 'success', 'data' => $partner]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Courier partner not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>