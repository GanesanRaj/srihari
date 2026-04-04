<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('ticket', 'is_view');

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
    $clientId = isset($_GET['client_id']) ? $_GET['client_id'] : '';
    $employeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $priority = isset($_GET['priority']) ? $_GET['priority'] : '';
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    $sql = "SELECT
                t.id,
                t.ticket_number,
                t.title,
                t.description,
                t.priority,
                t.status,
                t.branch_id,
                t.client_id,
                t.employee_id,
                t.created_at,
                t.updated_at,
                b.branch_name,
                c.client_name,
                e.name as employee_name
            FROM tbl_tickets t
            LEFT JOIN tbl_branch b ON t.branch_id = b.id
            LEFT JOIN tbl_client c ON t.client_id = c.id
            LEFT JOIN tbl_employee e ON t.employee_id = e.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total
                 FROM tbl_tickets t
                 LEFT JOIN tbl_branch b ON t.branch_id = b.id
                 LEFT JOIN tbl_client c ON t.client_id = c.id
                 LEFT JOIN tbl_employee e ON t.employee_id = e.id
                 WHERE 1=1";

    $params = [];

    if (!empty($branchId)) {
        $sql .= " AND t.branch_id = :branch_id";
        $countSql .= " AND t.branch_id = :branch_id";
        $params[':branch_id'] = $branchId;
    }

    if (!empty($clientId)) {
        $sql .= " AND t.client_id = :client_id";
        $countSql .= " AND t.client_id = :client_id";
        $params[':client_id'] = $clientId;
    }

    if (!empty($employeeId)) {
        $sql .= " AND t.employee_id = :employee_id";
        $countSql .= " AND t.employee_id = :employee_id";
        $params[':employee_id'] = $employeeId;
    }

    if (!empty($status)) {
        $sql .= " AND t.status = :status";
        $countSql .= " AND t.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($priority)) {
        $sql .= " AND t.priority = :priority";
        $countSql .= " AND t.priority = :priority";
        $params[':priority'] = $priority;
    }

    if (!empty($fromDate)) {
        $sql .= " AND DATE(t.created_at) >= :from_date";
        $countSql .= " AND DATE(t.created_at) >= :from_date";
        $params[':from_date'] = $fromDate;
    }

    if (!empty($toDate)) {
        $sql .= " AND DATE(t.created_at) <= :to_date";
        $countSql .= " AND DATE(t.created_at) <= :to_date";
        $params[':to_date'] = $toDate;
    }

    if (!empty($searchValue)) {
        $sql .= " AND (t.title LIKE :search OR t.ticket_number LIKE :search OR t.description LIKE :search)";
        $countSql .= " AND (t.title LIKE :search OR t.ticket_number LIKE :search OR t.description LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY t.created_at DESC LIMIT :start, :length";

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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
?>
