<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

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
    $sql = "INSERT INTO tbl_departments (department_name, status, created_by, created_at) 
            VALUES (:department_name, :status, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':department_name', $department_name);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':created_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Department created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create department']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
