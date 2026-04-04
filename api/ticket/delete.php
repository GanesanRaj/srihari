<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('ticket', 'is_delete');

try {
    $ticketId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($ticketId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tbl_tickets WHERE id = :id");
    $stmt->execute([':id' => $ticketId]);

    echo json_encode(['status' => 'success', 'message' => 'Ticket deleted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
