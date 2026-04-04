<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Edit Permission
require_api_permission('serial_allocation', 'is_edit');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Allocation ID is required']);
    exit;
}

$id = sanitizeText($_POST['id']);
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : '';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

if (empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required']);
    exit;
}

try {
    $sql = "UPDATE tbl_serial_allocation
            SET status = :status,
                remarks = :remarks,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':updated_by', $current_user['id']);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        // Update all serial numbers status if allocation is deactivated
        if ($status === 'inactive' || $status === 'expired') {
            $updateSerialsSql = "UPDATE tbl_serial_numbers
                                SET status = 'cancelled'
                                WHERE allocation_id = :allocation_id AND is_used = 0";
            $updateStmt = $pdo->prepare($updateSerialsSql);
            $updateStmt->bindParam(':allocation_id', $id);
            $updateStmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'Allocation updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update allocation']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
