<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $month = isset($_GET['month']) ? $_GET['month'] : '';
    $employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    // Base query
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(e.name LIKE ? OR p.salary_month LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($month)) {
        $where[] = "DATE_FORMAT(p.salary_month, '%Y-%m') = ?";
        $params[] = $month;
    }

    if (!empty($employee_id)) {
        $where[] = "p.employee_id = ?";
        $params[] = $employee_id;
    }

    if (!empty($status)) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total records
    $countQuery = "
        SELECT COUNT(*) as total
        FROM tbl_payroll p
        LEFT JOIN tbl_employees e ON p.employee_id = e.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch records
    $query = "
        SELECT
            p.*,
            e.name as employee_name
        FROM tbl_payroll p
        LEFT JOIN tbl_employees e ON p.employee_id = e.id
        $whereClause
        ORDER BY p.salary_month DESC, p.id DESC
        LIMIT $start, $length
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payroll = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $payroll
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
