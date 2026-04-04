<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

require_api_permission('client_based_user', 'is_add');

$errors = [];

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role_id  = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

$client_ids = [];
if (isset($_POST['client_ids'])) {
    $client_ids = is_array($_POST['client_ids']) ? $_POST['client_ids'] : explode(',', $_POST['client_ids']);
    $client_ids = array_filter(array_map('intval', $client_ids));
}
$branch_ids = [];
if (isset($_POST['branch_ids'])) {
    $branch_ids = is_array($_POST['branch_ids']) ? $_POST['branch_ids'] : explode(',', $_POST['branch_ids']);
    $branch_ids = array_filter(array_map('intval', $branch_ids));
}

if ($username === '') {
    $errors[] = 'Username is required';
}
if ($password === '') {
    $errors[] = 'Password is required';
}
if ($role_id <= 0) {
    $errors[] = 'Role is required';
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM tbl_user WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    $branch_id_first = !empty($branch_ids) ? (int) $branch_ids[0] : null;
    $client_ids_str  = implode(',', $client_ids);
    $branch_ids_str  = implode(',', $branch_ids);

    $sql = "INSERT INTO tbl_user (username, password, branch_id, role_id, status, user_type, user_id, clientaccess, client_ids, branch_ids) 
            VALUES (:username, :password, :branch_id, :role_id, 'active', 'client', NULL, 1, :client_ids, :branch_ids)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username'   => $username,
        ':password'   => $password,
        ':branch_id'  => $branch_id_first,
        ':role_id'    => $role_id,
        ':client_ids' => $client_ids_str === '' ? null : $client_ids_str,
        ':branch_ids' => $branch_ids_str === '' ? null : $branch_ids_str,
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Client-based user created successfully', 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'client_ids') !== false || strpos($e->getMessage(), 'branch_ids') !== false || strpos($e->getMessage(), 'clientaccess') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Database columns missing. Run migration: database/migrations/client_based_user_setup.sql']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
