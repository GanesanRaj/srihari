<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking/shipment users)
if ( ! get_permission('branch', 'is_view') && ! get_permission('whms_booking', 'is_view') && ! get_permission('whms_shipment', 'is_view') ) {
    require_api_permission('branch', 'is_view');
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
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    // Column mapping for ordering
    $columns = ['id', 'company_name', 'branch_name', 'branch_code', 'contact_no', 'state', 'status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query with JOIN to get company name
    $sql = "SELECT b.*, c.company_name 
            FROM tbl_branch b
            LEFT JOIN tbl_company c ON b.company_id = c.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total 
                 FROM tbl_branch b
                 LEFT JOIN tbl_company c ON b.company_id = c.id
                 WHERE 1=1";

    $params = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $sql .= " AND b.status = :status";
        $countSql .= " AND b.status = :status";
        $params[':status'] = $statusFilter;
    }

    // Apply company filter
    if (!empty($companyFilter)) {
        $sql .= " AND b.company_id = :company_id";
        $countSql .= " AND b.company_id = :company_id";
        $params[':company_id'] = $companyFilter;
    }

    // Session-based branch restriction for client-type users
    $sessionUserType = $_SESSION['user_type'] ?? 'both';
    if ($sessionUserType === 'client') {
        $rawSB = $_SESSION['branch_ids'] ?? '';
        $allowedBIds = $rawSB !== '' ? array_filter(array_map('intval', explode(',', $rawSB))) : [];
        if (!empty($allowedBIds)) {
            $phs = [];
            foreach (array_values($allowedBIds) as $i => $id) {
                $key = ':sbranch_' . $i;
                $phs[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND b.id IN (" . implode(',', $phs) . ")";
            $countSql .= " AND b.id IN (" . implode(',', $phs) . ")";
        }
    }

    // Apply date range filter
    if (!empty($fromDate) && !empty($toDate)) {
        $sql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
        $countSql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $fromDate;
        $params[':to_date'] = $toDate;
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $sql .= " AND (b.branch_name LIKE :search OR b.branch_code LIKE :search OR b.contact_no LIKE :search OR c.company_name LIKE :search OR b.state LIKE :search)";
        $countSql .= " AND (b.branch_name LIKE :search OR b.branch_code LIKE :search OR b.contact_no LIKE :search OR c.company_name LIKE :search OR b.state LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }

    // Get total count
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add ordering and pagination
    $sql .= " ORDER BY $orderColumn $orderDir";

    // Only apply limit if length is not -1
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
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>