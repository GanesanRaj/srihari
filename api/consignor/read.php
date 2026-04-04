<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('consignor', 'is_view');

try {
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

    // Filter parameters
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $branchFilter = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
    $clientFilter = isset($_GET['client_id']) ? $_GET['client_id'] : '';

    // Column mapping for ordering
    $columns = ['id', 'branch_name', 'client_name', 'name', 'contact_no', 'email', 'city', 'status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query with JOIN to get branch name and client name
    $sql = "SELECT c.*, b.branch_name, cl.client_name 
            FROM tbl_consignor c
            LEFT JOIN tbl_branch b ON c.branch_id = b.id
            LEFT JOIN tbl_client cl ON c.client_id = cl.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total 
                 FROM tbl_consignor c
                 LEFT JOIN tbl_branch b ON c.branch_id = b.id
                 LEFT JOIN tbl_client cl ON c.client_id = cl.id
                 WHERE 1=1";

    $params = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $filterSql = " AND c.status = :status";
        $sql .= $filterSql;
        $countSql .= $filterSql;
        $params[':status'] = $statusFilter;
    }

    // Apply branch filter
    if (!empty($branchFilter)) {
        $filterSql = " AND c.branch_id = :branch_id";
        $sql .= $filterSql;
        $countSql .= $filterSql;
        $params[':branch_id'] = $branchFilter;
    }

    // Apply client filter
    if (!empty($clientFilter)) {
        $filterSql = " AND c.client_id = :client_id";
        $sql .= $filterSql;
        $countSql .= $filterSql;
        $params[':client_id'] = $clientFilter;
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $searchSql = " AND (c.name LIKE :search 
                        OR c.contact_no LIKE :search 
                        OR c.email LIKE :search 
                        OR c.city LIKE :search 
                        OR b.branch_name LIKE :search 
                        OR cl.client_name LIKE :search)";
        $sql .= $searchSql;
        $countSql .= $searchSql;
        $params[':search'] = "%$searchValue%";
    }

    // Get total count (before pagination)
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
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
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>