<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('support', 'is_view');

try {
    $draw       = isset($_GET['draw'])   ? intval($_GET['draw'])   : 1;
    $start      = isset($_GET['start'])  ? intval($_GET['start'])  : 0;
    $length     = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search     = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $category = isset($_GET['category'])  ? $_GET['category']  : '';
    $priority = isset($_GET['priority'])  ? $_GET['priority']  : '';
    $status   = isset($_GET['status'])    ? $_GET['status']    : '';
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $toDate   = isset($_GET['to_date'])   ? $_GET['to_date']   : '';

    $sql = "SELECT id, ticket_number, subject, category, priority, status, message, created_at
            FROM tbl_support_tickets WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total FROM tbl_support_tickets WHERE 1=1";

    $params = [];

    if (!empty($category)) {
        $sql .= " AND category = :category";
        $countSql .= " AND category = :category";
        $params[':category'] = $category;
    }

    if (!empty($priority)) {
        $sql .= " AND priority = :priority";
        $countSql .= " AND priority = :priority";
        $params[':priority'] = $priority;
    }

    if (!empty($status)) {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $status;
    }

    if (!empty($fromDate)) {
        $sql .= " AND DATE(created_at) >= :from_date";
        $countSql .= " AND DATE(created_at) >= :from_date";
        $params[':from_date'] = $fromDate;
    }

    if (!empty($toDate)) {
        $sql .= " AND DATE(created_at) <= :to_date";
        $countSql .= " AND DATE(created_at) <= :to_date";
        $params[':to_date'] = $toDate;
    }

    if (!empty($search)) {
        $sql .= " AND (subject LIKE :search OR ticket_number LIKE :search OR message LIKE :search)";
        $countSql .= " AND (subject LIKE :search OR ticket_number LIKE :search OR message LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY created_at DESC LIMIT :start, :length";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'draw'            => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => $e->getMessage()
    ]);
}
?>
