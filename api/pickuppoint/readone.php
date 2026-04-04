<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('pickuppoint', 'is_view');

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }

    $sql = "SELECT p.*, c.company_name, br.branch_name, cp.partner_name as courier_name, cp.api_key as courier_token, cp.api_url as courier_api_url
            FROM tbl_pickup_points p
            LEFT JOIN tbl_company c ON p.company_id = c.id
            LEFT JOIN tbl_branch br ON p.branch_id = br.id
            LEFT JOIN tbl_courier_partner cp ON p.courier_id = cp.id
            WHERE p.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Pickup point not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
