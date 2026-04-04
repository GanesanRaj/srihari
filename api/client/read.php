<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking/shipment users)
if ( ! get_permission('client', 'is_view') && ! get_permission('whms_booking', 'is_view') && ! get_permission('whms_shipment', 'is_view') ) {
    require_api_permission('client', 'is_view');
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
    $branchFilter = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
    $branchIdsFilter = isset($_GET['branch_ids']) ? trim($_GET['branch_ids']) : ''; // comma-separated for multiselect

    // Column mapping for ordering
    $columns = ['id', 'branch_name', 'client_name', 'contact_no', 'email', 'city', 'status'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query with JOIN to get branch name
    $sql = "SELECT c.*, b.branch_name 
            FROM tbl_client c
            LEFT JOIN tbl_branch b ON c.branch_id = b.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total 
                 FROM tbl_client c
                 LEFT JOIN tbl_branch b ON c.branch_id = b.id
                 WHERE 1=1";

    $params = [];

    // Apply status filter
    if (!empty($statusFilter)) {
        $sql .= " AND c.status = :status";
        $countSql .= " AND c.status = :status";
        $params[':status'] = $statusFilter;
    }

    // Apply branch filter (single or multiple)
    if (!empty($branchIdsFilter)) {
        $ids = array_filter(array_map('intval', explode(',', $branchIdsFilter)));
        if (!empty($ids)) {
            $phs = [];
            foreach ($ids as $i => $id) {
                $key = ':branch_' . $i;
                $phs[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND c.branch_id IN (" . implode(',', $phs) . ")";
            $countSql .= " AND c.branch_id IN (" . implode(',', $phs) . ")";
        }
    } elseif (!empty($branchFilter)) {
        $sql .= " AND c.branch_id = :branch_id";
        $countSql .= " AND c.branch_id = :branch_id";
        $params[':branch_id'] = $branchFilter;
    }

    // Session-based client restriction for client-type users
    $sessionUserType = $_SESSION['user_type'] ?? 'both';
    if ($sessionUserType === 'client') {
        $rawSC = $_SESSION['client_ids'] ?? '';
        $allowedCIds = $rawSC !== '' ? array_filter(array_map('intval', explode(',', $rawSC))) : [];
        if (!empty($allowedCIds)) {
            $phs = [];
            foreach (array_values($allowedCIds) as $i => $id) {
                $key = ':sclient_' . $i;
                $phs[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND c.id IN (" . implode(',', $phs) . ")";
            $countSql .= " AND c.id IN (" . implode(',', $phs) . ")";
        }
    }

    // Apply search filter
    if (!empty($searchValue)) {
        $sql .= " AND (c.client_name LIKE :search OR c.contact_no LIKE :search OR c.email LIKE :search OR c.city LIKE :search OR b.branch_name LIKE :search)";
        $countSql .= " AND (c.client_name LIKE :search OR c.contact_no LIKE :search OR c.email LIKE :search OR c.city LIKE :search OR b.branch_name LIKE :search)";
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
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>