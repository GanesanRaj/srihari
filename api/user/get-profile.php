<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $userId   = $_SESSION['user_id'] ?? 0;
    $userType = $_SESSION['user_type'] ?? 'both';
    // clientaccess=1 users with NULL user_type are also client users
    if ($userType !== 'client' && isset($_SESSION['client_ids'])) {
        $stmt = $pdo->prepare("SELECT clientaccess FROM tbl_user WHERE username = ? LIMIT 1");
        $stmt->execute([$_SESSION['username'] ?? '']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['clientaccess'] == 1) $userType = 'client';
    }

    if ($userType === 'client') {
        // Fetch directly from tbl_user by username (most reliable for client users)
        $stmt = $pdo->prepare("
            SELECT u.username, u.branch_ids, u.client_ids, r.name AS role
            FROM tbl_user u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.username = :username AND u.clientaccess = 1
            LIMIT 1
        ");
        $stmt->execute([':username' => $_SESSION['username'] ?? '']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Resolve branch names from tbl_user.branch_ids
        $branchNames = [];
        $rawB = $user['branch_ids'] ?? '';
        $bIds = $rawB !== '' ? array_filter(array_map('intval', explode(',', $rawB))) : [];
        if (!empty($bIds)) {
            $phs = implode(',', array_fill(0, count($bIds), '?'));
            $s   = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id IN ($phs) ORDER BY branch_name");
            $s->execute(array_values($bIds));
            $branchNames = $s->fetchAll(PDO::FETCH_COLUMN);
        }

        // Resolve client names from tbl_user.client_ids
        $clientNames = [];
        $rawC = $user['client_ids'] ?? '';
        $cIds = $rawC !== '' ? array_filter(array_map('intval', explode(',', $rawC))) : [];
        if (!empty($cIds)) {
            $phs = implode(',', array_fill(0, count($cIds), '?'));
            $s   = $pdo->prepare("SELECT client_name FROM tbl_client WHERE id IN ($phs) ORDER BY client_name");
            $s->execute(array_values($cIds));
            $clientNames = $s->fetchAll(PDO::FETCH_COLUMN);
        }

        echo json_encode(['status' => 'success', 'data' => [
            'name'       => $user['username'] ?? $_SESSION['username'] ?? '',
            'email'      => '',
            'phone'      => '',
            'address'    => '',
            'role'       => $user['role'] ?? '',
            'department' => !empty($clientNames) ? implode(', ', $clientNames) : 'All Clients',
            'branch'     => !empty($branchNames) ? implode(', ', $branchNames) : 'All Branches',
        ]]);

    } else {
        // Regular employee user — no phone column in tbl_employee
        $stmt = $pdo->prepare("
            SELECT e.name, e.email, e.address,
                   r.name AS role,
                   d.designation AS department,
                   b.branch_name AS branch
            FROM tbl_employee e
            LEFT JOIN roles r ON r.id = e.role_id
            LEFT JOIN tbl_designation d ON d.id = e.designation_id
            LEFT JOIN tbl_branch b ON b.id = e.branch_id
            WHERE e.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $data = $row ?: [
            'name' => $_SESSION['username'] ?? '', 'email' => '',
            'address' => '', 'role' => '', 'department' => '', 'branch' => '',
        ];
        $data['phone'] = '';  // no phone column — keep field empty

        echo json_encode(['status' => 'success', 'data' => $data]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
