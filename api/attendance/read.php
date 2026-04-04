<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    $employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    // Base query
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(e.name LIKE ? OR a.attendance_date LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($from_date)) {
        $where[] = "a.attendance_date >= ?";
        $params[] = $from_date;
    }

    if (!empty($to_date)) {
        $where[] = "a.attendance_date <= ?";
        $params[] = $to_date;
    }

    if (!empty($employee_id)) {
        $where[] = "a.employee_id = ?";
        $params[] = $employee_id;
    }

    if (!empty($status)) {
        $where[] = "a.status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total records
    $countQuery = "
        SELECT COUNT(*) as total
        FROM tbl_attendance a
        LEFT JOIN tbl_employees e ON a.employee_id = e.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch records
    $query = "
        SELECT
            a.*,
            e.name as employee_name,
            s.shift_name
        FROM tbl_attendance a
        LEFT JOIN tbl_employees e ON a.employee_id = e.id
        LEFT JOIN tbl_shifts s ON a.shift_id = s.id
        $whereClause
        ORDER BY a.attendance_date DESC, a.id DESC
        LIMIT $start, $length
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $attendance
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
