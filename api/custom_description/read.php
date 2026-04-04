<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

    $columns = ['id', 'description', 'status', 'created_at'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    $sql = "SELECT id, description, status, created_at FROM tbl_custom_description WHERE 1=1";
    $countSql = "SELECT COUNT(*) as total FROM tbl_custom_description WHERE 1=1";

    $params = [];

    if (!empty($statusFilter)) {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    if (!empty($searchValue)) {
        $sql .= " AND (description LIKE :search)";
        $countSql .= " AND (description LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY $orderColumn $orderDir LIMIT :start, :length";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
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
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
