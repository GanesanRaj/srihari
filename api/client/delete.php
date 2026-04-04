<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Delete Permission
require_api_permission('client', 'is_delete');

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Client ID is required']);
    exit;
}

try {
    $id = intval($_POST['id']);

    // First get the logo path to delete it
    $stmt = $pdo->prepare("SELECT client_logo FROM tbl_client WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['status' => 'error', 'message' => 'Client not found']);
        exit;
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM tbl_client WHERE id = :id");
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        // Delete logo file if exists
        if (!empty($client['client_logo'])) {
            delete_image($client['client_logo']);
        }
        echo json_encode(['status' => 'success', 'message' => 'Client deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete client']);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete client because it is referenced in other records']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>