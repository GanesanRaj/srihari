<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('status', 'is_view');

try {
    // DataTables parameters
    $draw         = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start        = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length       = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue  = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColIdx  = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir     = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'ASC';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

    // Column mapping for ordering
    $columns    = ['id', 'name', 'code', 'status', 'created_at'];
    $orderColumn = isset($columns[$orderColIdx]) ? $columns[$orderColIdx] : 'id';
    $orderDir    = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

    $baseSql   = "FROM tbl_master_status WHERE 1=1";
    $params    = [];

    // Status filter
    if ($statusFilter !== '') {
        $baseSql            .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }

    // Search filter
    if ($searchValue !== '') {
        $baseSql              .= " AND (name LIKE :search OR code LIKE :search OR remarks LIKE :search)";
        $params[':search'] = '%' . $searchValue . '%';
    }

    // Count total
    $countSql  = "SELECT COUNT(*) " . $baseSql;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetchColumn();

    // Fetch page
    $dataSql = "SELECT id, name, code, status, remarks, created_at, updated_at " . $baseSql .
        " ORDER BY {$orderColumn} {$orderDir} LIMIT :start, :length";
    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data'            => $data,
        'status'          => 'success'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
