<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

if (!get_permission('client_based_user', 'is_view')) {
    require_api_permission('client_based_user', 'is_view');
}

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'ASC';
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

    $columns = ['u.id', 'u.username', 'r.name', 'u.branch_ids', 'u.client_ids', 'u.status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'u.id';

    $baseWhere = "u.clientaccess = 1";
    $sql = "SELECT u.id, u.username, u.role_id, u.branch_id, u.status, u.client_ids, u.branch_ids,
                   r.name AS role_name,
                   b.branch_name
            FROM tbl_user u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN tbl_branch b ON b.id = u.branch_id
            WHERE " . $baseWhere;
    $countSql = "SELECT COUNT(*) AS total FROM tbl_user u LEFT JOIN roles r ON r.id = u.role_id WHERE " . $baseWhere;
    $params = [];

    if (!empty($statusFilter)) {
        $sql .= " AND u.status = :status";
        $countSql .= " AND u.status = :status";
        $params[':status'] = $statusFilter;
    }

    if (!empty($searchValue)) {
        $sql .= " AND (u.username LIKE :search OR r.name LIKE :search)";
        $countSql .= " AND (u.username LIKE :search OR r.name LIKE :search)";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY $orderColumn $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
