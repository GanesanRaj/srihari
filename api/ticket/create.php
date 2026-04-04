<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('ticket', 'is_add');

try {
    $branchId = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
    $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'Medium';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Open';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validation
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

    // Generate unique ticket number
    $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Check if ticket number exists (unlikely but just in case)
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_tickets WHERE ticket_number = :ticket_number");
    $checkStmt->execute([':ticket_number' => $ticketNumber]);
    if ($checkStmt->fetch()) {
        // Generate a new one with random suffix
        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    // Insert ticket
    $sql = "INSERT INTO tbl_tickets (
                ticket_number,
                title,
                description,
                priority,
                status,
                branch_id,
                client_id,
                employee_id,
                created_at,
                updated_at
            ) VALUES (
                :ticket_number,
                :title,
                :description,
                :priority,
                :status,
                :branch_id,
                :client_id,
                :employee_id,
                NOW(),
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ticket_number' => $ticketNumber,
        ':title' => $title,
        ':description' => $description,
        ':priority' => $priority,
        ':status' => $status,
        ':branch_id' => $branchId,
        ':client_id' => $clientId,
        ':employee_id' => $employeeId
    ]);

    $ticketId = $pdo->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'Ticket created successfully',
        'ticket_id' => $ticketId,
        'ticket_number' => $ticketNumber
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
