<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, PUT');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

if (isset($_POST['id']) && $_POST['id'] !== '') {
    require_api_permission('coloader', 'is_edit');
} else {
    require_api_permission('coloader', 'is_add');
}

$data = $_POST;

$required = ['name' => 'Name', 'mobile_number' => 'Mobile Number', 'email' => 'Email', 'address' => 'Address'];
foreach ($required as $key => $label) {
    if (empty(trim((string) ($data[$key] ?? '')))) {
        echo json_encode(['status' => 'error', 'message' => $label . ' is required']);
        exit;
    }
}

if (!function_exists('sanitizeText')) {
    require_once __DIR__ . '/../../config/helper.php';
}

$user_id = 1;
if (function_exists('get_current_user_id')) {
    $user_id = get_current_user_id();
} elseif (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
}

try {
    $name = sanitizeText($data['name']);
    $mobile_number = isset($data['mobile_number']) ? sanitizeText($data['mobile_number']) : null;
    $email = isset($data['email']) ? sanitizeText($data['email']) : null;
    $address = isset($data['address']) ? sanitizeText($data['address']) : null;
    $status = isset($data['status']) && in_array($data['status'], ['active', 'inactive'], true) ? $data['status'] : 'active';
    $remarks = isset($data['remarks']) ? sanitizeText($data['remarks']) : null;

    if (isset($data['id']) && $data['id'] !== '') {
        $id = (int) $data['id'];
        $sql = "UPDATE tbl_coloader SET name = :name, mobile_number = :mobile_number, email = :email, address = :address, status = :status, remarks = :remarks, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':updated_by', $user_id);
    } else {
        $sql = "INSERT INTO tbl_coloader (name, mobile_number, email, address, status, remarks, created_by) VALUES (:name, :mobile_number, :email, :address, :status, :remarks, :created_by)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':created_by', $user_id);
    }

    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':mobile_number', $mobile_number);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':address', $address);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':remarks', $remarks);

    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => isset($data['id']) && $data['id'] !== '' ? 'Coloader updated successfully' : 'Coloader created successfully'
    ]);
} catch (PDOException $e) {
    error_log('Coloader create/update: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
