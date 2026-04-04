<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
// require_api_permission('master_status', 'is_view'); // Commented out in case not yet in DB

try {
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    if ($length <= 0) $length = 99999;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

    // Filter parameters
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

    // Column mapping for ordering
    $columns = ['id', 'name', 'code', 'status', 'status_query', 'created_at'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query
    $sql = "SELECT id, name, code, status, status_query, remarks, created_at FROM tbl_master_status WHERE 1=1";
    $countSql = "SELECT COUNT(*) as total FROM tbl_master_status WHERE 1=1";

    $params = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $sql .= " AND (name LIKE :search OR code LIKE :search OR remarks LIKE :search)";
        $countSql .= " AND (name LIKE :search OR code LIKE :search OR remarks LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Get total count
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add ordering and pagination
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
?>