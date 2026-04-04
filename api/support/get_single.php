<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('support', 'is_view');

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, ticket_number, subject, category, priority, status, message, created_at, updated_at
                           FROM tbl_support_tickets WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    echo json_encode(['status' => 'success', 'data' => $ticket]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
