<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Optional: Check if any employee is using this designation before deleting
    $checkSql = "SELECT COUNT(*) FROM tbl_employees WHERE designation_id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete designation as it is assigned to employees']);
        exit;
    }

    $sql = "DELETE FROM tbl_designations WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Designation deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete designation']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
