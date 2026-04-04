<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking/shipment users to load courier list)
if ( ! get_permission('courier_partner', 'is_view') && ! get_permission('whms_booking', 'is_view') && ! get_permission('whms_shipment', 'is_view') ) {
    require_api_permission('courier_partner', 'is_view');
}

try {
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'ASC';

    // Filter parameters
    $filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    // Column mapping
    $columns = ['id', 'partner_name', 'partner_code', 'username', 'preference_order', 'status'];
    $orderColumn = $columns[$orderColumnIndex] ?? 'preference_order';

    // Base query
    $sql = "SELECT * FROM tbl_courier_partner WHERE 1=1";
    $countSql = "SELECT COUNT(*) as total FROM tbl_courier_partner WHERE 1=1";

    // Apply filters
    $params = [];

    if (!empty($filterStatus)) {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $filterStatus;
    }

    if (!empty($fromDate) && !empty($toDate)) {
        $sql .= " AND DATE(created_at) BETWEEN :from_date AND :to_date";
        $countSql .= " AND DATE(created_at) BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $fromDate;
        $params[':to_date'] = $toDate;
    }

    if (!empty($searchValue)) {
        $sql .= " AND (partner_name LIKE :search OR partner_code LIKE :search OR username LIKE :search)";
        $countSql .= " AND (partner_name LIKE :search OR partner_code LIKE :search OR username LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Get total count
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply ordering and pagination
    $sql .= " ORDER BY $orderColumn $orderDir";

    if ($length != -1) {
        $sql .= " LIMIT :start, :length";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if ($length != -1) {
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
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>