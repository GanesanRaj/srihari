<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

// require_api_permission('shift', 'is_view');

try {
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    // Base query
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(shift_name LIKE ? OR start_time LIKE ? OR end_time LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total records
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_shifts $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch records
    $query = "SELECT * FROM tbl_shifts $whereClause ORDER BY id DESC LIMIT $start, $length";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $shifts
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
