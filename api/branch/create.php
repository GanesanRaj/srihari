<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('branch', 'is_add');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

// Declare variables
$company_id = $branch_name = $branch_code = $contact_no = $address = $state = $email = $status = $remarks = '';
$errors = [];



// Validate POST data
$requiredFields = ['company_id', 'branch_name', 'branch_code', 'contact_no', 'address', 'state'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$email = isset($_POST['email']) ? sanitizeText($_POST['email']) : '';
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if branch_code already exists
    $checkSql = "SELECT id FROM tbl_branch WHERE branch_code = :branch_code";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':branch_code', $branch_code);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Branch code already exists']);
        exit;
    }

    $sql = "INSERT INTO tbl_branch (company_id, branch_name, branch_code, contact_no, address, state, email, status, remarks, created_by, created_at) 
            VALUES (:company_id, :branch_name, :branch_code, :contact_no, :address, :state, :email, :status, :remarks, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->bindParam(':branch_name', $branch_name);
    $stmt->bindParam(':branch_code', $branch_code);
    $stmt->bindParam(':contact_no', $contact_no);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':created_by', $current_user['id']);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Branch created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create branch']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>