<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('status', 'is_add');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

$name   = $code = $status = $remarks = '';
$errors = [];

// Required fields
$requiredFields = ['name', 'code'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && $_POST[$field] !== '') {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional
$status  = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Unique code check
    $checkSql  = "SELECT id FROM tbl_master_status WHERE code = :code";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':code', $code);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Status code already exists']);
        exit;
    }

    $sql = "INSERT INTO tbl_master_status (name, code, status, remarks, created_by, created_at)
            VALUES (:name, :code, :status, :remarks, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':created_by', $current_user['id']);

    if ($stmt->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Status created successfully',
            'id'      => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create status']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

