<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('company', 'is_view');

try {
    // Get query parameters sent by DataTables
    $draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 0;
    $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int) $_GET['order'][0]['column'] : 0;
    $orderDir = isset($_GET['order'][0]['dir']) && in_array(strtolower($_GET['order'][0]['dir']), ['asc', 'desc'])
        ? $_GET['order'][0]['dir']
        : 'desc';

    // Custom Filter parameters
    $city = isset($_GET['city']) && !empty($_GET['city']) ? trim($_GET['city']) : null;
    $status = isset($_GET['status']) && !empty($_GET['status']) ? trim($_GET['status']) : null;

    // Additional Filters if needed (e.g. state)
    $state = isset($_GET['state']) && !empty($_GET['state']) ? trim($_GET['state']) : null;

    // Column mapping for sorting
    // Indices must match the columns defined in the frontend DataTable
    // 0: id, 1: company_name, 2: phone_number, 3: city, 4: state, 5: status, 6: action (not sortable)
    $columns = ['id', 'company_name', 'phone_number', 'city', 'state', 'status', null];
    $orderBy = isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null ? $columns[$orderColumnIndex] : 'id';

    // Base Query Construction
    $sql = "SELECT * FROM tbl_company WHERE 1=1";
    $bindings = [];

    // Apply Search Filter
    if (!empty($searchValue)) {
        $sql .= " AND (company_name LIKE :search OR phone_number LIKE :search OR city LIKE :search OR state LIKE :search OR gst_no LIKE :search)";
        $bindings[':search'] = "%$searchValue%";
    }

    // Apply Custom Filters
    if ($city) {
        $sql .= " AND city LIKE :city";
        $bindings[':city'] = "%$city%";
    }

    if ($state) {
        $sql .= " AND state LIKE :state";
        $bindings[':state'] = "%$state%";
    }

    if ($status) {
        $sql .= " AND status = :status";
        $bindings[':status'] = $status;
    }

    // Count Filtered Records
    $sqlFiltered = "SELECT COUNT(*) FROM tbl_company WHERE 1=1";
    // Reuse the exact same WHERE conditions
    if (!empty($searchValue)) {
        $sqlFiltered .= " AND (company_name LIKE :search OR phone_number LIKE :search OR city LIKE :search OR state LIKE :search OR gst_no LIKE :search)";
    }
    if ($city)
        $sqlFiltered .= " AND city LIKE :city";
    if ($state)
        $sqlFiltered .= " AND state LIKE :state";
    if ($status)
        $sqlFiltered .= " AND status = :status";

    $stmtFiltered = $pdo->prepare($sqlFiltered);
    foreach ($bindings as $key => $value) {
        $stmtFiltered->bindValue($key, $value); // PDO::PARAM_STR is default
    }
    $stmtFiltered->execute();
    $recordsFiltered = $stmtFiltered->fetchColumn();

    // Total Records (without filters)
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM tbl_company")->fetchColumn();

    // specific ordering and pagination
    $sql .= " ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind all previous bindings
    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind limit and offset
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => (int) $totalRecords,
        "recordsFiltered" => (int) $recordsFiltered,
        "data" => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>