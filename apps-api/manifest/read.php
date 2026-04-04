<?php
/**
 * Manifest API – List Manifests
 * Location: /apps-api/manifest/read.php
 * Method: GET
 * Params:
 *   user_id     (opt) – filter by created_by
 *   branch_id   (opt) – filter by from_branch
 *   branch_name (opt) – partial name search on from_branch
 *   status      (opt) – draft | dispatched | received
 *   search      (opt) – search by manifest_no
 *   start       (opt) – pagination offset (default 0)
 *   length      (opt) – page size (default 25)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

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
    $user_id     = !empty($req['user_id'])   ? (int)$req['user_id']   : null;
    $branch_id   = !empty($req['branch_id']) ? (int)$req['branch_id'] : null;

    if ($length <= 0) $length = 99999;

    $where  = ' WHERE 1=1';
    $params = [];

    if ($user_id !== null) {
        $where .= ' AND m.created_by = :user_id';
        $params[':user_id'] = $user_id;
    }

    if ($branch_id !== null) {
        $where .= ' AND m.from_branch = :branch_id';
        $params[':branch_id'] = $branch_id;
    }

    if ($branch_name !== '') {
        $where .= ' AND br_from.branch_name LIKE :branch_name';
        $params[':branch_name'] = "%$branch_name%";
    }

    if ($status !== '') {
        $where .= ' AND m.status = :status';
        $params[':status'] = $status;
    }

    if ($search !== '') {
        $where .= ' AND m.manifest_no LIKE :search';
        $params[':search'] = "%$search%";
    }

    $countSql = "SELECT COUNT(*) FROM tbl_manifest m
                 LEFT JOIN tbl_branch br_from ON br_from.id = m.from_branch"
              . $where;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT m.id, m.manifest_no, m.from_branch, m.to_branch, m.coloader,
                   m.vehicle_no, m.driver_name, m.mobile_no, m.bag_count,
                   m.weight, m.total_box, m.total_count, m.status,
                   m.created_by, m.created_at, m.updated_at, m.cd_no,
                   u1.username AS created_by_name,
                   br_from.branch_name AS from_branch_name,
                   br_to.branch_name   AS to_branch_name
            FROM tbl_manifest m
            LEFT JOIN tbl_user u1      ON u1.user_id  = m.created_by
            LEFT JOIN tbl_branch br_from ON br_from.id = m.from_branch
            LEFT JOIN tbl_branch br_to   ON br_to.id   = m.to_branch"
        . $where
        . " ORDER BY m.created_at DESC LIMIT :start, :length";

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
