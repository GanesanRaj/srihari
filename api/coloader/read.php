<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

require_api_permission('coloader', 'is_view');

try {
    $draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 1;
    $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && strtoupper($_GET['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

    $columns = ['id', 'name', 'mobile_number', 'email', 'address', 'status', 'remarks', 'created_at'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    $sql = "SELECT id, name, mobile_number, email, address, status, remarks, created_at, created_by, updated_at, updated_by FROM tbl_coloader WHERE 1=1";
    $countSql = "SELECT COUNT(*) AS total FROM tbl_coloader WHERE 1=1";
    $params = [];

    if ($statusFilter !== '') {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchValue !== '') {
        $sql .= " AND (name LIKE :search OR mobile_number LIKE :search OR email LIKE :search OR address LIKE :search OR remarks LIKE :search)";
        $countSql .= " AND (name LIKE :search OR mobile_number LIKE :search OR email LIKE :search OR address LIKE :search OR remarks LIKE :search)";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $orderCol = preg_replace('/[^a-z0-9_]/', '', $orderColumn);
if ($orderCol === '') {
    $orderCol = 'id';
}
$sql .= " ORDER BY " . $orderCol . " " . $orderDir . " LIMIT :start, :length";
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
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
