<?php
/**
 * Tag API – List Tags
 * Location: /apps-api/tag/read.php
 * Method: GET
 * Params: user_id (opt), from_branch/branch_id (opt), branch_name (opt), status (opt), search (opt), start (opt), length (opt)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

try {
    $start       = (int)($req['start']  ?? 0);
    $length      = (int)($req['length'] ?? 25);
    $search      = trim($req['search']      ?? '');
    $status      = trim($req['status']      ?? '');
    $branch_name = trim($req['branch_name'] ?? '');
    $user_id     = !empty($req['user_id'])     ? (int)$req['user_id']     : null;
    $from_branch = !empty($req['from_branch']) ? (int)$req['from_branch']
                 : (!empty($req['branch_id'])  ? (int)$req['branch_id']  : null);

    if ($length <= 0) $length = 99999;

    $where  = ' WHERE 1=1';
    $params = [];

    if ($user_id !== null) {
        $where .= ' AND t.created_by = :user_id';
        $params[':user_id'] = $user_id;
    }

    if ($from_branch !== null) {
        $where .= ' AND t.from_branch = :from_branch';
        $params[':from_branch'] = $from_branch;
    }

    if ($branch_name !== '') {
        $where .= ' AND br_from.branch_name LIKE :branch_name';
        $params[':branch_name'] = "%$branch_name%";
    }

    if ($search !== '') {
        $where .= ' AND t.tag_no LIKE :search';
        $params[':search'] = "%$search%";
    }

    if ($status !== '') {
        $where .= ' AND t.status = :status';
        $params[':status'] = $status;
    }

    $countSql = "SELECT COUNT(*) FROM tbl_tags t
                 LEFT JOIN tbl_branch br_from ON br_from.id = t.from_branch"
              . $where;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT t.id, t.tag_no, t.from_branch, t.to_branch, t.total_count,
                   t.status, t.created_by, t.verified_by, t.verified_at,
                   t.received_by, t.received_at, t.remarks, t.created_at,
                   u1.username AS created_by_name,
                   u2.username AS verified_by_name,
                   u3.username AS received_by_name,
                   br_from.branch_name AS from_branch_name,
                   br_to.branch_name   AS to_branch_name
            FROM tbl_tags t
            LEFT JOIN tbl_user u1   ON u1.user_id  = t.created_by
            LEFT JOIN tbl_user u2   ON u2.user_id  = t.verified_by
            LEFT JOIN tbl_user u3   ON u3.user_id  = t.received_by
            LEFT JOIN tbl_branch br_from ON br_from.id = t.from_branch
            LEFT JOIN tbl_branch br_to   ON br_to.id   = t.to_branch"
        . $where
        . " ORDER BY t.created_at DESC LIMIT :start, :length";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':start',  $start,  PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'          => 'success',
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
