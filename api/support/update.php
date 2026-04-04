<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('support', 'is_edit');

try {
    $id       = isset($_POST['id'])       ? intval($_POST['id'])       : 0;
    $subject  = isset($_POST['subject'])  ? trim($_POST['subject'])    : '';
    $category = isset($_POST['category']) ? trim($_POST['category'])   : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority'])   : 'medium';
    $status   = isset($_POST['status'])   ? trim($_POST['status'])     : 'open';
    $message  = isset($_POST['message'])  ? trim($_POST['message'])    : '';

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
        exit;
    }

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

    if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'])) {
        $status = 'open';
    }

    $checkStmt = $pdo->prepare("SELECT id FROM tbl_support_tickets WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tbl_support_tickets SET
        subject = :subject,
        category = :category,
        priority = :priority,
        status = :status,
        message = :message,
        updated_at = NOW()
        WHERE id = :id");

    $stmt->execute([
        ':subject'  => $subject,
        ':category' => $category,
        ':priority' => $priority,
        ':status'   => $status,
        ':message'  => $message,
        ':id'       => $id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Support ticket updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
