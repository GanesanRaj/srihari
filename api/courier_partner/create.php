<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('courier_partner', 'is_add');

// Declare variables
$partner_name = $partner_code = $api_url = $api_key = $username = $password = $token = $client_id = $client_secret = $status = $remarks = '';
$preference_order = 0;
$errors = [];



// Validate POST data
$requiredFields = ['partner_name', 'partner_code'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$api_url = isset($_POST['api_url']) ? sanitizeText($_POST['api_url']) : '';
$api_key = isset($_POST['api_key']) ? sanitizeText($_POST['api_key']) : '';
$username = isset($_POST['username']) ? sanitizeText($_POST['username']) : '';
$password = isset($_POST['password']) ? sanitizeText($_POST['password']) : ''; // Not encrypted as per requirement
$token = isset($_POST['token']) ? sanitizeText($_POST['token']) : '';
$client_id = isset($_POST['client_id']) ? sanitizeText($_POST['client_id']) : '';
$client_secret = isset($_POST['client_secret']) ? sanitizeText($_POST['client_secret']) : '';
$preference_order = isset($_POST['preference_order']) ? intval($_POST['preference_order']) : 0;
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if partner_code already exists
    $checkSql = "SELECT id FROM tbl_courier_partner WHERE partner_code = :partner_code";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':partner_code', $partner_code);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Partner code already exists']);
        exit;
    }

    $sql = "INSERT INTO tbl_courier_partner (partner_name, partner_code, api_url, api_key, username, password, token, client_id, client_secret, preference_order, status, remarks, created_by, created_at) 
            VALUES (:partner_name, :partner_code, :api_url, :api_key, :username, :password, :token, :client_id, :client_secret, :preference_order, :status, :remarks, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':partner_name', $partner_name);
    $stmt->bindParam(':partner_code', $partner_code);
    $stmt->bindParam(':api_url', $api_url);
    $stmt->bindParam(':api_key', $api_key);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':client_id', $client_id);
    $stmt->bindParam(':client_secret', $client_secret);
    $stmt->bindParam(':preference_order', $preference_order);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':created_by', $_SESSION['employee_id']);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Courier partner created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create courier partner']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>