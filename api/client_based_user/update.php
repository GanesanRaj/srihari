<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

require_api_permission('client_based_user', 'is_edit');

$id       = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role_id  = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
$status   = isset($_POST['status']) ? trim($_POST['status']) : 'active';

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

$errors = [];
if ($id <= 0)        $errors[] = 'Invalid user ID';
if ($username === '') $errors[] = 'Username is required';
if ($role_id <= 0)   $errors[] = 'Role is required';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check username unique (excluding current record)
    $stmt = $pdo->prepare("SELECT id FROM tbl_user WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    $branch_id_first = !empty($branch_ids) ? (int) $branch_ids[0] : null;
    $client_ids_str  = implode(',', $client_ids);
    $branch_ids_str  = implode(',', $branch_ids);

    if ($password !== '') {
        $stmt = $pdo->prepare("UPDATE tbl_user SET username=:username, password=:password, branch_id=:branch_id, role_id=:role_id, status=:status, client_ids=:client_ids, branch_ids=:branch_ids WHERE id=:id AND clientaccess=1");
        $stmt->execute([
            ':username'   => $username,
            ':password'   => $password,
            ':branch_id'  => $branch_id_first,
            ':role_id'    => $role_id,
            ':status'     => $status,
            ':client_ids' => $client_ids_str === '' ? null : $client_ids_str,
            ':branch_ids' => $branch_ids_str === '' ? null : $branch_ids_str,
            ':id'         => $id,
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE tbl_user SET username=:username, branch_id=:branch_id, role_id=:role_id, status=:status, client_ids=:client_ids, branch_ids=:branch_ids WHERE id=:id AND clientaccess=1");
        $stmt->execute([
            ':username'   => $username,
            ':branch_id'  => $branch_id_first,
            ':role_id'    => $role_id,
            ':status'     => $status,
            ':client_ids' => $client_ids_str === '' ? null : $client_ids_str,
            ':branch_ids' => $branch_ids_str === '' ? null : $branch_ids_str,
            ':id'         => $id,
        ]);
    }

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found or no changes made']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
