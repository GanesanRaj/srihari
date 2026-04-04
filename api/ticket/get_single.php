<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('ticket', 'is_view');

try {
    $ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($ticketId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
        exit;
    }

    $sql = "SELECT
                t.id,
                t.ticket_number,
                t.title,
                t.description,
                t.priority,
                t.status,
                t.branch_id,
                t.client_id,
                t.employee_id,
                t.created_at,
                t.updated_at,
                b.branch_name,
                c.client_name,
                e.name as employee_name
            FROM tbl_tickets t
            LEFT JOIN tbl_branch b ON t.branch_id = b.id
            LEFT JOIN tbl_client c ON t.client_id = c.id
            LEFT JOIN tbl_employee e ON t.employee_id = e.id
            WHERE t.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $ticket
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
