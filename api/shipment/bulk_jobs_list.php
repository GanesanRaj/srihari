<?php
header ( 'Content-Type: application/json' );
require '../../config/db.php';
require_once '../../config/helper.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

try {
    // DataTables-style parameters
    $start       = isset ($_GET[ 'start' ]) ? (int) $_GET[ 'start' ] : 0;
    $length      = isset ($_GET[ 'length' ]) ? (int) $_GET[ 'length' ] : 10;
    $searchValue = isset ($_GET[ 'search' ][ 'value' ]) ? trim ( $_GET[ 'search' ][ 'value' ] ) : '';

    if ($length <= 0) {
        $length = 10;
        }

    // Detect client-type user (handles NULL user_type with clientaccess=1)
    $isClientUser = (($_SESSION['user_type'] ?? '') === 'client');
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    if (!$isClientUser && isset($_SESSION['username'])) {
        $chk = $pdo->prepare("SELECT id, clientaccess FROM tbl_user WHERE username = ? LIMIT 1");
        $chk->execute([$_SESSION['username']]);
        $chkRow = $chk->fetch(PDO::FETCH_ASSOC);
        if ($chkRow && $chkRow['clientaccess'] == 1) {
            $isClientUser = true;
            $currentUserId = (int) $chkRow['id'];
        }
    }

    // Base query (alias j for jobs, join user for created_by name)
    $baseSql = "FROM tbl_bulkupload_jobs j LEFT JOIN tbl_user u ON u.id = j.created_by WHERE j.filename NOT LIKE 'StatusUpdate_%'";
    $params  = [];

    // Client users only see their own uploads
    if ($isClientUser && $currentUserId > 0) {
        $baseSql .= " AND j.created_by = :uid";
        $params[':uid'] = $currentUserId;
    }

    $latestProcessing = isset($_GET['latest_processing']) ? (int) $_GET['latest_processing'] : 0;
    if ($latestProcessing === 1) {
        $sql = "SELECT j.id, j.filename, j.status, j.total_records, j.success_count, j.failure_count, j.result_file, j.created_at, j.created_by, j.branch_name, j.client_name, u.username AS created_by_name
                FROM tbl_bulkupload_jobs j
                LEFT JOIN tbl_user u ON u.id = j.created_by
                WHERE j.filename NOT LIKE 'StatusUpdate_%'";
        $qParams = [];
        if ($isClientUser && $currentUserId > 0) {
            $sql .= " AND j.created_by = :uid";
            $qParams[':uid'] = $currentUserId;
        } elseif ($currentUserId > 0) {
            // For non-client users, prefer own latest running job first.
            $sql .= " AND j.created_by = :uid";
            $qParams[':uid'] = $currentUserId;
        }
        $sql .= " AND j.status = 'Processing' ORDER BY j.id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        foreach ($qParams as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([ 'status' => 'success', 'data' => $job ?: null ]);
        exit;
    }

    $jobId = isset ($_GET[ 'job_id' ]) ? (int) $_GET[ 'job_id' ] : 0;
    if ($jobId > 0) {
        $stmt = $pdo->prepare ( "SELECT j.id, j.filename, j.status, j.total_records, j.success_count, j.failure_count, j.result_file, j.created_at, j.created_by, j.branch_name, j.client_name, u.username AS created_by_name FROM tbl_bulkupload_jobs j LEFT JOIN tbl_user u ON u.id = j.created_by WHERE j.id = :id" );
        $stmt->execute ( [ ':id' => $jobId ] );
        $job = $stmt->fetch ( PDO::FETCH_ASSOC );
        echo json_encode ( [ 'status' => 'success', 'data' => $job ] );
        exit;
        }

    // Simple search on filename, status, id, username
    if ($searchValue !== '') {
        $baseSql           .= " AND (j.filename LIKE :search OR j.status LIKE :search OR j.id LIKE :search OR u.username LIKE :search)";
        $params[ ':search' ]  = '%' . $searchValue . '%';
        }


    // Count total (filtered) records
    $countSql  = "SELECT COUNT(*) " . $baseSql;
    $countStmt = $pdo->prepare ( $countSql );
    foreach ($params as $key => $val) {
        $countStmt->bindValue ( $key, $val );
        }
    $countStmt->execute ();
    $totalRecords = (int) $countStmt->fetchColumn ();

    // Fetch page of data
    $dataSql = "SELECT
                    j.id,
                    j.filename,
                    j.status,
                    j.total_records,
                    j.success_count,
                    j.failure_count,
                    j.result_file,
                    j.created_at,
                    j.created_by,
                    j.branch_name,
                    j.client_name,
                    u.username AS created_by_name
                " . $baseSql . "
                ORDER BY j.created_at DESC, j.id DESC
                LIMIT :start, :length";

    $stmt = $pdo->prepare ( $dataSql );
    foreach ($params as $key => $val) {
        $stmt->bindValue ( $key, $val );
        }
    $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
    $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
    $stmt->execute ();

    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    echo json_encode ( [
        'draw' => (int) ($_GET[ 'draw' ] ?? 1),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
        'status' => 'success'
    ] );
    }
catch ( PDOException $e ) {
    http_response_code ( 500 );
    echo json_encode ( [
        'status' => 'error',
        'message' => 'Database error',
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ] );
    }
?>
