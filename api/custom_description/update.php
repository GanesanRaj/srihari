<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

$id = $description = $status = '';
$errors = [];

if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
} else {
    $errors[] = "Field 'id' is required";
}

if (isset($_POST['description']) && !empty($_POST['description'])) {
    $description = sanitizeText($_POST['description']);
} else {
    $errors[] = "Field 'description' is required";
}

$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "UPDATE tbl_custom_description 
            SET description = :description,
                status = :status,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':updated_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Description updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update description']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
