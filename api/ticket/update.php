<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('ticket', 'is_edit');

try {
    $ticketId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $branchId = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
    $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'Medium';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Open';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validation
    if ($ticketId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
        exit;
    }

    if ($branchId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Branch is required']);
        exit;
    }

    if ($clientId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Client is required']);
        exit;
    }

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Title is required']);
        exit;
    }

    if (!in_array($priority, ['High', 'Medium', 'Low'])) {
        $priority = 'Medium';
    }

    if (!in_array($status, ['Open', 'In Progress', 'Resolved', 'Closed'])) {
        $status = 'Open';
    }

    // Check if ticket exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_tickets WHERE id = :id");
    $checkStmt->execute([':id' => $ticketId]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    // Update ticket
    $sql = "UPDATE tbl_tickets SET
                title = :title,
                description = :description,
                priority = :priority,
                status = :status,
                branch_id = :branch_id,
                client_id = :client_id,
                employee_id = :employee_id,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':priority' => $priority,
        ':status' => $status,
        ':branch_id' => $branchId,
        ':client_id' => $clientId,
        ':employee_id' => $employeeId,
        ':id' => $ticketId
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Ticket updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
