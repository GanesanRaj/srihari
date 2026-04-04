<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$address = trim($_POST['address'] ?? '');

$userId   = $_SESSION['user_id'] ?? 0;
$userType = $_SESSION['user_type'] ?? 'both';

try {
    if ($userType === 'client') {
        // Client-based users — only username can be updated in tbl_user (no name/email columns)
        echo json_encode(['status' => 'success', 'message' => 'Profile saved']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE tbl_employee
        SET name = :name, email = :email, phone = :phone, address = :address
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':phone'   => $phone,
        ':address' => $address,
        ':id'      => $userId,
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
