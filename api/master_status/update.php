<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Edit Permission
// require_api_permission('master_status', 'is_edit');

// Get current user info
$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

// Declare variables
$id = $name = $code = $status = $remarks = '';
$errors = [];

// Validate POST data
$requiredFields = ['id', 'name', 'code'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$status_query = isset($_POST['status_query']) ? $_POST['status_query'] : '';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if code already exists for other statuses
    $checkSql = "SELECT id FROM tbl_master_status WHERE code = :code AND id != :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':code', $code);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Status code already exists']);
        exit;
    }

    $sql = "UPDATE tbl_master_status 
            SET name = :name,
                code = :code,
                status = :status,
                status_query = :status_query,
                remarks = :remarks,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':status_query', $status_query);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':updated_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>