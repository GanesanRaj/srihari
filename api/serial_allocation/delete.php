<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Delete Permission
require_api_permission('serial_allocation', 'is_delete');

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Allocation ID is required']);
    exit;
}

$id = sanitizeText($_POST['id']);

try {
    // Check if any serial numbers are currently used
    $checkSql = "SELECT COUNT(*) as used_count FROM tbl_serial_numbers WHERE allocation_id = :allocation_id AND is_used = 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':allocation_id', $id);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['used_count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete allocation with used serial numbers. Please deactivate it instead.']);
        exit;
    }

    // Delete allocation (cascade will delete serial numbers and history)
    $sql = "DELETE FROM tbl_serial_allocation WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Allocation deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete allocation']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
