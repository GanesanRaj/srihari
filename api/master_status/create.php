<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
// require_api_permission('master_status', 'is_add');

// Get current user info
$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

// Declare variables
$name = $code = $status = $remarks = '';
$errors = [];

// Validate POST data
$requiredFields = ['name', 'code'];

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
    // Check if code already exists
    $checkSql = "SELECT id FROM tbl_master_status WHERE code = :code";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':code', $code);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Status code already exists']);
        exit;
    }

    $sql = "INSERT INTO tbl_master_status (name, code, status, status_query, remarks, created_by, created_at) 
            VALUES (:name, :code, :status, :status_query, :remarks, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':status_query', $status_query);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':created_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create status']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>