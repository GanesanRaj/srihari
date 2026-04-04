<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Delete Permission
require_api_permission('pickuppoint', 'is_delete');

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }

    // Check if pickup point exists
    $checkSql = "SELECT id, courier_id FROM tbl_pickup_points WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    $pickupPoint = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pickupPoint) {
        echo json_encode(['status' => 'error', 'message' => 'Pickup point not found']);
        exit;
    }

    // Check if synced
    $synced = ($pickupPoint['delhivery_synced'] ?? 0) == 1;

    // Delete the pickup point
    $sql = "DELETE FROM tbl_pickup_points WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $message = 'Pickup point deleted successfully.';
    if ($synced) {
        $message .= ' Note: This was synced with Delhivery, please contact Delhivery support to deactivate the warehouse.';
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>