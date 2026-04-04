<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Update Permission
require_api_permission('client', 'is_edit');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate ID
if (!isset($input['id']) || empty($input['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Client ID is required']);
    exit;
}

$id = intval($input['id']);

// Get courier assignments (optional)
$courier_assignments = isset($input['courier_assignments']) ? json_encode($input['courier_assignments']) : null;

// Check if client exists
try {
    $checkQuery = "SELECT id FROM tbl_client WHERE id = :id";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Client not found']);
        exit;
    }

    // Update courier assignments
    $updateQuery = "UPDATE tbl_client SET courier_assignments = :courier_assignments, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($updateQuery);

    $success = $stmt->execute([
        ':courier_assignments' => $courier_assignments,
        ':updated_by' => $current_user['id'],
        ':id' => $id
    ]);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Courier assignments updated successfully',
            'data' => [
                'id' => $id,
                'courier_assignments' => $input['courier_assignments'] ?? []
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update courier assignments']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>