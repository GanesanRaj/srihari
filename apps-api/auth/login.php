<?php
/**
 * Mobile Login API
 * Location: /apps-api/auth/login.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$input = $_POST;
if (empty($input)) {
    $json = file_get_contents('php://input');
    $input = json_decode($json, true) ?? [];
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
    exit();
}

try {
    $stmtUser = $pdo->prepare("SELECT u.*, b.branch_name, r.name as role_name 
                              FROM tbl_user u
                              LEFT JOIN tbl_branch b ON u.branch_id = b.id
                              LEFT JOIN roles r ON u.role_id = r.id
                              WHERE u.username = :username AND u.status = 'active'
                              LIMIT 1");
    $stmtUser->execute([':username' => $username]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged in successfully as user.',
            'user_data' => [
                'id' => $user['id'],
                'name' => $user['username'],
                'email' => $user['username'],
                'branch_ids' => $user['branch_ids'],
                'client_ids' => $user['client_ids'],
                'clientaccess' => $user['clientaccess'],
                
                
                'branch_id' => $user['branch_id'],
                'branch_name' => $user['branch_name'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'user_type' => $user['user_type'] ?? 'user'
            ]
        ]);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials or inactive account.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
