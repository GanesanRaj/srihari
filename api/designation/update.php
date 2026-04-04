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
$designation = $status = '';
$errors = [];

if (isset($_POST['designation']) && !empty($_POST['designation'])) {
    $designation = sanitizeText($_POST['designation']);
} else {
    $errors[] = "Field 'designation' is required";
}

$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "UPDATE tbl_designations 
            SET designation = :designation, 
                status = :status, 
                updated_by = :updated_by, 
                updated_at = NOW() 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':designation', $designation);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':updated_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Designation updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update designation']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
