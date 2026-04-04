<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission ( 'serial_allocation', 'is_view' );

try {
    // DataTables parameters
    $draw             = isset ($_GET[ 'draw' ]) ? intval ( $_GET[ 'draw' ] ) : 1;
    $start            = isset ($_GET[ 'start' ]) ? intval ( $_GET[ 'start' ] ) : 0;
    $length           = isset ($_GET[ 'length' ]) ? intval ( $_GET[ 'length' ] ) : 10;
    $searchValue      = isset ($_GET[ 'search' ][ 'value' ]) ? $_GET[ 'search' ][ 'value' ] : '';
    $orderColumnIndex = isset ($_GET[ 'order' ][ 0 ][ 'column' ]) ? intval ( $_GET[ 'order' ][ 0 ][ 'column' ] ) : 0;
    $orderDir         = isset ($_GET[ 'order' ][ 0 ][ 'dir' ]) ? $_GET[ 'order' ][ 0 ][ 'dir' ] : 'DESC';

    // Filter parameters
    $branchFilter      = isset ($_GET[ 'branch_id' ]) ? $_GET[ 'branch_id' ] : '';
    $serviceTypeFilter = isset ($_GET[ 'service_type' ]) ? $_GET[ 'service_type' ] : '';
    $statusFilter      = isset ($_GET[ 'status' ]) ? $_GET[ 'status' ] : '';
    $fromDate          = isset ($_GET[ 'from_date' ]) ? $_GET[ 'from_date' ] : '';
    $toDate            = isset ($_GET[ 'to_date' ]) ? $_GET[ 'to_date' ] : '';
    $warningLow        = isset ($_GET[ 'warning_low' ]) ? $_GET[ 'warning_low' ] : '';

    // Column mapping for ordering
    $columns     = [ 'id', 'branch_name', 'service_type', 'serial_number', 'serial_from', 'serial_to', 'total_serials', 'used_serials', 'available_serials', 'allocation_date', 'status' ];
    $orderColumn = isset ($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';

    // Base query with JOIN to get branch name
    $sql = "SELECT sa.*, b.branch_name, b.branch_code,
            CASE
                WHEN sa.service_type = 'air' THEN 'Air'
                WHEN sa.service_type = 'surface' THEN 'Surface'
                WHEN sa.service_type = 'express' THEN 'Express'
            END AS service_type_display,
            (SELECT SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END) 
             FROM tbl_serial_numbers sn 
             LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci 
             WHERE sn.allocation_id = sa.id) AS used_serials,
            (SELECT SUM(CASE WHEN b.id IS NULL THEN 1 ELSE 0 END) 
             FROM tbl_serial_numbers sn 
             LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci 
             WHERE sn.allocation_id = sa.id) AS available_serials,
            (SELECT COUNT(*) 
             FROM tbl_serial_numbers sn 
             WHERE sn.allocation_id = sa.id) AS total_serials
            FROM tbl_serial_allocation sa
            LEFT JOIN tbl_branch b ON sa.branch_id = b.id
            WHERE 1=1";

    $countSql = "SELECT COUNT(*) as total
                 FROM tbl_serial_allocation sa
                 LEFT JOIN tbl_branch b ON sa.branch_id = b.id
                 WHERE 1=1";

    $params = [];

    // Apply branch filter
    if ( ! empty ($branchFilter)) {
        $sql                    .= " AND sa.branch_id = :branch_id";
        $countSql               .= " AND sa.branch_id = :branch_id";
        $params[ ':branch_id' ]  = $branchFilter;
        }

    // Apply service type filter (Air = show both express and air)
    if ( ! empty ($serviceTypeFilter)) {
        if ($serviceTypeFilter === 'express') {
            $sql      .= " AND sa.service_type IN ('express', 'air')";
            $countSql .= " AND sa.service_type IN ('express', 'air')";
            } else {
            $sql                       .= " AND sa.service_type = :service_type";
            $countSql                  .= " AND sa.service_type = :service_type";
            $params[ ':service_type' ]  = $serviceTypeFilter;
            }
        }

    // Apply status filter
    if ( ! empty ($statusFilter)) {
        $sql                 .= " AND sa.status = :status";
        $countSql            .= " AND sa.status = :status";
        $params[ ':status' ]  = $statusFilter;
        }

    // Apply date range filter
    if ( ! empty ($fromDate) && ! empty ($toDate)) {
        $sql                    .= " AND DATE(sa.allocation_date) BETWEEN :from_date AND :to_date";
        $countSql               .= " AND DATE(sa.allocation_date) BETWEEN :from_date AND :to_date";
        $params[ ':from_date' ]  = $fromDate;
        $params[ ':to_date' ]    = $toDate;
        }

    // Warning low: available < 20% of total (active only)
    if ($warningLow === '1' || $warningLow === 'true') {
        $dynamicAvailable  = "(SELECT SUM(CASE WHEN b.id IS NULL THEN 1 ELSE 0 END) FROM tbl_serial_numbers sn LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci WHERE sn.allocation_id = sa.id)";
        $dynamicTotal      = "(SELECT COUNT(*) FROM tbl_serial_numbers sn WHERE sn.allocation_id = sa.id)";
        $sql              .= " AND sa.status = 'active' AND $dynamicTotal > 0 AND ($dynamicAvailable / $dynamicTotal) < 0.20";
        $countSql         .= " AND sa.status = 'active' AND $dynamicTotal > 0 AND ($dynamicAvailable / $dynamicTotal) < 0.20";
        }

    // Apply search filter
    if ( ! empty ($searchValue)) {
        $sql                 .= " AND (b.branch_name LIKE :search OR sa.serial_number LIKE :search OR sa.serial_from LIKE :search OR sa.serial_to LIKE :search)";
        $countSql            .= " AND (b.branch_name LIKE :search OR sa.serial_number LIKE :search OR sa.serial_from LIKE :search OR sa.serial_to LIKE :search)";
        $params[ ':search' ]  = "%$searchValue%";
        }

    // Get total count
    $countStmt = $pdo->prepare ( $countSql );
    $countStmt->execute ( $params );
    $totalRecords = $countStmt->fetch ( PDO::FETCH_ASSOC )[ 'total' ];

    // Add ordering and pagination
    $sql .= " ORDER BY $orderColumn $orderDir";

    // Only apply limit if length is not -1
    if ($length != -1) {
        $sql .= " LIMIT :start, :length";
        }

    $stmt = $pdo->prepare ( $sql );
    foreach ($params as $key => $value) {
        $stmt->bindValue ( $key, $value );
        }

    if ($length != -1) {
        $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
        $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
        }

    $stmt->execute ();

    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    echo json_encode ( [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ] );

    }
catch ( PDOException $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage () ] );
    }
?>

