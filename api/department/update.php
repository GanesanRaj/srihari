<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_POST['id']);
$department_name = $status = '';
$errors = [];

if (isset($_POST['department_name']) && !empty($_POST['department_name'])) {
    $department_name = sanitizeText($_POST['department_name']);
} else {
    $errors[] = "Field 'department_name' is required";
}

$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "UPDATE tbl_departments 
            SET department_name = :department_name, 
                status = :status, 
                updated_by = :updated_by, 
                updated_at = NOW() 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':department_name', $department_name);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':updated_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Department updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update department']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
