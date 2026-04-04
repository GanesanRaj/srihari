<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Edit Permission
require_api_permission('status', 'is_edit');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

$id = $name = $code = $status = $remarks = '';
$errors = [];

// Required fields
$requiredFields = ['id', 'name', 'code'];

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
    // Ensure record exists
    $checkSql  = "SELECT id FROM tbl_master_status WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Status record not found']);
        exit;
    }

    // Unique code check for others
    $codeSql  = "SELECT id FROM tbl_master_status WHERE code = :code AND id != :id";
    $codeStmt = $pdo->prepare($codeSql);
    $codeStmt->bindParam(':code', $code);
    $codeStmt->bindParam(':id', $id);
    $codeStmt->execute();

    if ($codeStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Status code already exists']);
        exit;
    }

    $sql = "UPDATE tbl_master_status
            SET name = :name,
                code = :code,
                status = :status,
                remarks = :remarks,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':updated_by', $current_user['id']);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

