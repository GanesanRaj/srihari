<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('support', 'is_add');

try {
    $subject  = isset($_POST['subject'])  ? trim($_POST['subject'])  : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'medium';
    $message  = isset($_POST['message'])  ? trim($_POST['message'])  : '';
    $status   = 'open';

    if (empty($subject)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject is required']);
        exit;
    }

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message is required']);
        exit;
    }

    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $priority = 'medium';
    }

    // Generate unique ticket number
    $ticketNumber = 'SUP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $checkStmt = $pdo->prepare("SELECT id FROM tbl_support_tickets WHERE ticket_number = :ticket_number");
    $checkStmt->execute([':ticket_number' => $ticketNumber]);
    if ($checkStmt->fetch()) {
        $ticketNumber = 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    $stmt = $pdo->prepare("INSERT INTO tbl_support_tickets
        (ticket_number, subject, category, priority, status, message, created_at, updated_at)
        VALUES (:ticket_number, :subject, :category, :priority, :status, :message, NOW(), NOW())");

    $stmt->execute([
        ':ticket_number' => $ticketNumber,
        ':subject'       => $subject,
        ':category'      => $category,
        ':priority'      => $priority,
        ':status'        => $status,
        ':message'       => $message
    ]);

    $newId = $pdo->lastInsertId();

    echo json_encode([
        'status'        => 'success',
        'message'       => 'Support ticket created successfully',
        'id'            => $newId,
        'ticket_number' => $ticketNumber
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
