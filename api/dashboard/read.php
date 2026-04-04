<?php
header('Content-Type: application/json');
require '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-t');

    // Build client/branch restriction for client-type users
    $clientWhere = '';
    $branchWhere = '';
    $clientParams = [];
    $branchParams = [];

    $userType = $_SESSION['user_type'] ?? 'both';
    $dashUserId = (int) ($_SESSION['user_id'] ?? 0);
    $rawBranches = '';
    $rawClients  = '';

    // Detect clientaccess=1 users — always read branch_ids/client_ids from DB
    if (isset($_SESSION['username'])) {
        $chkD = $pdo->prepare("SELECT id, clientaccess, branch_ids, client_ids FROM tbl_user WHERE username = ? LIMIT 1");
        $chkD->execute([$_SESSION['username']]);
        $chkDRow = $chkD->fetch(PDO::FETCH_ASSOC);
        if ($chkDRow && $chkDRow['clientaccess'] == 1) {
            $userType    = 'client';
            $dashUserId  = (int) $chkDRow['id'];
            $rawBranches = $chkDRow['branch_ids'] ?? '';
            $rawClients  = $chkDRow['client_ids'] ?? '';
        }
    }

    if ($userType === 'client') {
        // Branch restriction
        if ($rawBranches !== '') {
            $bIds = array_values(array_filter(array_map('intval', explode(',', $rawBranches))));
            if (!empty($bIds)) {
                $phs = implode(',', array_fill(0, count($bIds), '?'));
                $branchWhere = " AND b.branch_id IN ($phs)";
                $branchParams = $bIds;
            }
        }
        // Client restriction — only add if specific clients are assigned
        if ($rawClients !== '') {
            $cIds = array_values(array_filter(array_map('intval', explode(',', $rawClients))));
            if (!empty($cIds)) {
                $phs = implode(',', array_fill(0, count($cIds), '?'));
                $clientWhere = " AND b.client_id IN ($phs)";
                $clientParams = $cIds;
            }
        }
        // If no specific clients assigned, all clients in the allowed branches are visible — no extra where needed
    }

    // Helper: merge positional params for prepared statements
    // Base booking filter = date + branch + client
    $baseBookingWhere = "DATE(b.created_at) BETWEEN ? AND ?" . $branchWhere . $clientWhere;
    $baseBookingParams = array_merge([$from, $to], $branchParams, $clientParams);

    // Alias-free variant (no 'b.' alias) for simple single-table queries
    $simpleDateWhere = "DATE(created_at) BETWEEN ? AND ?" . str_replace('b.branch_id', 'branch_id', $branchWhere) . str_replace('b.client_id', 'client_id', $clientWhere);
    $simpleDateParams = $baseBookingParams;

    $courierIdFilter = isset($_GET['courier_id']) ? (int)$_GET['courier_id'] : 0;
    
    $courierWhere = '';
    $courierParams = [];
    $courierWhereNoAlias = '';

    if ($courierIdFilter > 0) {
        $courierWhere = " AND b.courier_id = ?";
        $courierWhereNoAlias = " AND courier_id = ?";
        $courierParams[] = $courierIdFilter;
        
        $baseBookingWhere .= $courierWhere;
        $simpleDateWhere .= $courierWhereNoAlias;
        $baseBookingParams[] = $courierIdFilter;
        $simpleDateParams[] = $courierIdFilter;
    }

    // Stats - Total Shipments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings b WHERE $baseBookingWhere");
    $stmt->execute($baseBookingParams);
    $total_shipments = $stmt->fetchColumn();

    // COD Total
    $stmt = $pdo->prepare("SELECT SUM(b.cod_amount) FROM tbl_bookings b WHERE $baseBookingWhere");
    $stmt->execute($baseBookingParams);
    $cod_total = (float) ($stmt->fetchColumn() ?: 0);

    // Active Counts — always global (not client-restricted)
    $active_branches = $pdo->query("SELECT COUNT(*) FROM tbl_branch WHERE status='active'")->fetchColumn();
    $active_employees = $pdo->query("SELECT COUNT(*) FROM tbl_employees WHERE status='active'")->fetchColumn();
    $active_companies = $pdo->query("SELECT COUNT(*) FROM tbl_company WHERE status='active'")->fetchColumn();

    // Today's Pickups
    $today = date('Y-m-d');
    $todayBranchWhere = str_replace('b.branch_id', 'branch_id', $branchWhere);
    $todayClientWhere = str_replace('b.client_id', 'client_id', $clientWhere);
    $stmt = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN last_status = 'Created' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN last_status != 'Created' THEN 1 ELSE 0 END) as picked
        FROM tbl_bookings WHERE DATE(created_at) = ?" . $todayBranchWhere . $todayClientWhere . $courierWhereNoAlias);
    $stmt->execute(array_merge([$today], $branchParams, $clientParams, $courierParams));
    $pickup_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_pickups = (int) $pickup_data['total'];
    $today_picked  = (int) $pickup_data['picked'];
    $today_pending = (int) $pickup_data['pending'];

    // Upcoming Pickups
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings WHERE DATE(created_at) > ? AND DATE(created_at) <= ?" . $todayBranchWhere . $todayClientWhere . $courierWhereNoAlias);
    $stmt->execute(array_merge([$today, date('Y-m-d', strtotime('+3 days'))], $branchParams, $clientParams, $courierParams));
    $upcoming_pickups = $stmt->fetchColumn();

    // NDR Shipments
    $ndrBranchWhere = str_replace('b.branch_id', 'branch_id', $branchWhere);
    $ndrClientWhere = str_replace('b.client_id', 'client_id', $clientWhere);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings WHERE last_status IN ('RTO', 'Failed', 'Returned', 'Delivery Failed')" . $ndrBranchWhere . $ndrClientWhere . $courierWhereNoAlias);
    $stmt->execute(array_merge($branchParams, $clientParams, $courierParams));
    $ndr_count = $stmt->fetchColumn();

    // Top 5 Clients
    $stmt = $pdo->prepare("SELECT cl.client_name as company_name, COUNT(b.id) as count
                            FROM tbl_bookings b
                            JOIN tbl_client cl ON b.client_id = cl.id
                            WHERE $baseBookingWhere
                            GROUP BY cl.id, cl.client_name
                            ORDER BY count DESC
                            LIMIT 5");
    $stmt->execute($baseBookingParams);
    $top_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats - By Status (date range)
    $stmt = $pdo->prepare("SELECT last_status, COUNT(*) as count FROM tbl_bookings b WHERE $baseBookingWhere GROUP BY last_status");
    $stmt->execute($baseBookingParams);
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats - By Status (Today)
    $stmt = $pdo->prepare("SELECT last_status, COUNT(*) as count FROM tbl_bookings WHERE DATE(created_at) = ?" . $todayBranchWhere . $todayClientWhere . $courierWhereNoAlias . " GROUP BY last_status");
    $stmt->execute(array_merge([$today], $branchParams, $clientParams, $courierParams));
    $today_status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today_mapped = [];
    foreach ($today_status_counts as $sc) {
        $today_mapped[$sc['last_status']] = (int) $sc['count'];
    }

    // Timeline - Daily Shipments for Chart
    $stmt = $pdo->prepare("SELECT DATE(b.created_at) as date, COUNT(*) as count,
        SUM(CASE WHEN b.last_status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN b.last_status IN ('RTO','Failed','Returned','Delivery Failed') THEN 1 ELSE 0 END) as rto
        FROM tbl_bookings b WHERE $baseBookingWhere
        GROUP BY DATE(b.created_at) ORDER BY date ASC");
    $stmt->execute($baseBookingParams);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $calendar_events = [];
    foreach ($daily_stats as $day) {
        $calendar_events[] = [
            'title' => $day['count'] . ' Shipments',
            'start' => $day['date'],
            'allDay' => true,
            'className' => 'bg-primary'
        ];
    }

    // Branch-wise Shipments
    $stmt = $pdo->prepare("SELECT br.branch_name, COUNT(b.id) as count
        FROM tbl_bookings b
        JOIN tbl_branch br ON b.branch_id = br.id
        WHERE $baseBookingWhere
        GROUP BY br.id, br.branch_name
        ORDER BY count DESC LIMIT 10");
    $stmt->execute($baseBookingParams);
    $branch_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Runsheet by Branch
    $rsClientWhere = str_replace('b.client_id', 'bk.client_id', $clientWhere);
    $rsBranchWhere = str_replace('b.branch_id', 'bk.branch_id', $branchWhere);
    $stmt = $pdo->prepare("SELECT br.branch_name,
        COUNT(DISTINCT r.id) as runsheet_count,
        COUNT(rd.id) as total_shipments,
        SUM(CASE WHEN rd.status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN rd.status = 'Out For Delivery' THEN 1 ELSE 0 END) as ofd,
        SUM(CASE WHEN rd.status = 'Picked Up' THEN 1 ELSE 0 END) as picked
        FROM tbl_runsheet r
        JOIN tbl_runsheet_details rd ON r.id = rd.runsheet_id
        JOIN tbl_bookings bk ON rd.booking_id = bk.id
        JOIN tbl_branch br ON bk.branch_id = br.id
        WHERE DATE(r.runsheet_date) BETWEEN ? AND ?" . $rsBranchWhere . $rsClientWhere . "
        GROUP BY br.id, br.branch_name
        ORDER BY total_shipments DESC LIMIT 10");
    $stmt->execute(array_merge([$from, $to], $branchParams, $clientParams));
    $runsheet_branch_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Activity
    $recentBranchWhere = str_replace('b.branch_id', 'b.branch_id', $branchWhere);
    $recentClientWhere = str_replace('b.client_id', 'b.client_id', $clientWhere);
    $recentBase = (!empty($branchWhere) || !empty($clientWhere) || !empty($courierWhere))
        ? "WHERE 1=1" . $recentBranchWhere . $recentClientWhere . $courierWhere
        : "";
    $stmt = $pdo->prepare("SELECT b.waybill_no, b.consignee_name, b.consignee_city,
        b.last_status, b.created_at, br.branch_name
        FROM tbl_bookings b
        LEFT JOIN tbl_branch br ON b.branch_id = br.id
        $recentBase
        ORDER BY b.created_at DESC LIMIT 25");
    $stmt->execute(array_merge($branchParams, $clientParams, $courierParams));
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Bulk Uploads — client users see only their own
    if ($userType === 'client' && $dashUserId > 0) {
        $stmt = $pdo->prepare("SELECT j.id, j.filename, j.total_records, j.success_count, j.failure_count, j.status, j.created_at, j.created_by, j.result_file, u.username
            FROM tbl_bulkupload_jobs j
            LEFT JOIN tbl_user u ON j.created_by = u.id
            WHERE j.created_by = ?
            ORDER BY j.created_at DESC LIMIT 5");
        $stmt->execute([$dashUserId]);
    } else {
        $stmt = $pdo->query("SELECT j.id, j.filename, j.total_records, j.success_count, j.failure_count, j.status, j.created_at, j.created_by, j.result_file, u.username
            FROM tbl_bulkupload_jobs j
            LEFT JOIN tbl_user u ON j.created_by = u.id
            ORDER BY j.created_at DESC LIMIT 5");
    }
    $recent_bulk_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attendance Summary (Today) — always global
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tbl_attendance WHERE attendance_date = ? GROUP BY status");
    $stmt->execute([$today]);
    $attendance_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendance_summary = [
        'present' => 0,
        'absent' => 0,
        'leave' => 0,
        'half_day' => 0
    ];
    foreach ($attendance_counts as $ac) {
        $status = strtolower($ac['status']);
        if (isset($attendance_summary[$status])) {
            $attendance_summary[$status] = (int) $ac['count'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_shipments' => (int) $total_shipments,
            'cod_total' => (float) $cod_total,
            'active_branches' => (int) $active_branches,
            'active_employees' => (int) $active_employees,
            'active_companies' => (int) $active_companies,
            'today_pickups' => (int) $today_pickups,
            'today_picked' => (int) $today_picked,
            'today_pending' => (int) $today_pending,
            'upcoming_pickups' => (int) $upcoming_pickups,
            'today_status_counts' => $today_mapped,
            'ndr_count' => (int) $ndr_count,
            'top_clients' => $top_clients,
            'status_counts' => $status_counts,
            'daily_stats' => $daily_stats,
            'calendar_events' => $calendar_events,
            'branch_stats' => $branch_stats,
            'runsheet_branch_stats' => $runsheet_branch_stats,
            'recent_activity' => $recent_activity,
            'recent_bulk_jobs' => $recent_bulk_jobs,
            'attendance_summary' => $attendance_summary
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>