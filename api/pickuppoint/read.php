<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking/shipment users)
if ( ! get_permission('pickuppoint', 'is_view') && ! get_permission('whms_booking', 'is_view') && ! get_permission('whms_shipment', 'is_view') ) {
    require_api_permission('pickuppoint', 'is_view');
}

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
    $companyFilter = isset($_GET['company_id']) ? $_GET['company_id'] : '';
    $courierFilter = isset($_GET['courier_id']) ? $_GET['courier_id'] : '';
    $syncFilter = isset($_GET['delhivery_synced']) ? $_GET['delhivery_synced'] : '';

    // Column mapping for ordering
    $columns = ['id', 'company_name', 'branch_name', 'name', 'pickup_point_code', 'phone', 'city', 'pin', 'courier_name', 'delhivery_synced', 'status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query with JOIN to get company and courier names + API credentials
    $sql = "SELECT p.*, c.company_name, br.branch_name, cp.partner_name as courier_name, cp.api_key as courier_token, cp.api_url as courier_api_url 
            FROM tbl_pickup_points p
            LEFT JOIN tbl_company c ON p.company_id = c.id
            LEFT JOIN tbl_branch br ON p.branch_id = br.id
            LEFT JOIN tbl_courier_partner cp ON p.courier_id = cp.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total 
                 FROM tbl_pickup_points p
                 LEFT JOIN tbl_company c ON p.company_id = c.id
                 LEFT JOIN tbl_branch br ON p.branch_id = br.id
                 LEFT JOIN tbl_courier_partner cp ON p.courier_id = cp.id
                 WHERE 1=1";

    $params = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $sql .= " AND p.status = :status";
        $countSql .= " AND p.status = :status";
        $params[':status'] = $statusFilter;
    }

    // Apply company filter
    if (!empty($companyFilter)) {
        $sql .= " AND p.company_id = :company_id";
        $countSql .= " AND p.company_id = :company_id";
        $params[':company_id'] = $companyFilter;
    }

    // Apply courier filter
    if (!empty($courierFilter)) {
        $sql .= " AND p.courier_id = :courier_id";
        $countSql .= " AND p.courier_id = :courier_id";
        $params[':courier_id'] = $courierFilter;
    }

    // Apply sync filter
    if ($syncFilter !== '') {
        $sql .= " AND p.delhivery_synced = :delhivery_synced";
        $countSql .= " AND p.delhivery_synced = :delhivery_synced";
        $params[':delhivery_synced'] = $syncFilter;
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $sql .= " AND (p.name LIKE :search OR p.pickup_point_code LIKE :search OR p.phone LIKE :search OR p.city LIKE :search OR p.pin LIKE :search OR c.company_name LIKE :search OR br.branch_name LIKE :search OR cp.partner_name LIKE :search)";
        $countSql .= " AND (p.name LIKE :search OR p.pickup_point_code LIKE :search OR p.phone LIKE :search OR p.city LIKE :search OR p.pin LIKE :search OR c.company_name LIKE :search OR br.branch_name LIKE :search OR cp.partner_name LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Get total count
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add ordering and pagination
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
