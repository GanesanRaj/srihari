<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

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
    $roleFilter = isset($_GET['role_id']) ? $_GET['role_id'] : '';
    $designationFilter = isset($_GET['designation_id']) ? $_GET['designation_id'] : '';

    // Column mapping for ordering
    $columns = ['e.id', 'b.branch_name', 'r.name', 'd.designation', 'e.name', 'e.phone', 'e.email', 'e.status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'e.id';

    // Base query with JOINs (including shift info)
    $sql = "SELECT e.*, b.branch_name, r.name as role_name, d.designation,
            (SELECT s.shift_name FROM tbl_employee_shifts es
             LEFT JOIN tbl_shifts s ON es.shift_id = s.id
             WHERE es.employee_id = e.id AND es.status = 'active'
             ORDER BY es.assigned_date DESC LIMIT 1) as shift_name
            FROM tbl_employees e
            LEFT JOIN tbl_branch b ON e.branch_id = b.id
            LEFT JOIN roles r ON e.role_id = r.id
            LEFT JOIN tbl_designations d ON e.designation_id = d.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total
                 FROM tbl_employees e
                 LEFT JOIN tbl_branch b ON e.branch_id = b.id
                 LEFT JOIN roles r ON e.role_id = r.id
                 LEFT JOIN tbl_designations d ON e.designation_id = d.id
                 WHERE 1=1";

    $params = [];

    // Apply filters
    if (!empty($statusFilter)) {
        $sql .= " AND e.status = :status";
        $countSql .= " AND e.status = :status";
        $params[':status'] = $statusFilter;
    }

    if (!empty($branchFilter)) {
        $sql .= " AND e.branch_id = :branch_id";
        $countSql .= " AND e.branch_id = :branch_id";
        $params[':branch_id'] = $branchFilter;
    }

    if (!empty($roleFilter)) {
        $sql .= " AND e.role_id = :role_id";
        $countSql .= " AND e.role_id = :role_id";
        $params[':role_id'] = $roleFilter;
    }

    if (!empty($designationFilter)) {
        $sql .= " AND e.designation_id = :designation_id";
        $countSql .= " AND e.designation_id = :designation_id";
        $params[':designation_id'] = $designationFilter;
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $searchCond = " AND (e.name LIKE :search OR e.email LIKE :search OR e.phone LIKE :search OR b.branch_name LIKE :search OR r.name LIKE :search OR d.designation LIKE :search)";
        $sql .= $searchCond;
        $countSql .= $searchCond;
        $params[':search'] = "%$searchValue%";
    }

    // Get total count
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add ordering and pagination (-1 = show all)
    $sql .= " ORDER BY $orderColumn $orderDir";
    if ($length > 0) {
        $sql .= " LIMIT :start, :length";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    if ($length > 0) {
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    }
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
