<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Delete Permission
require_api_permission('company', 'is_delete');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Fetch logo path first for deletion
    $stmt = $pdo->prepare("SELECT company_logo FROM tbl_company WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['company_logo'])) {
        delete_image($row['company_logo']); // Helper handles physical file deletion
    }

    $sql = "DELETE FROM tbl_company WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Company deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete company']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>