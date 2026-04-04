<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $start  = (int) ($_GET[ 'start' ] ?? 0);
    $length = (int) ($_GET[ 'length' ] ?? 25);
    $search = trim ( $_GET[ 'search' ][ 'value' ] ?? $_GET[ 'search' ] ?? '' );
    $status = trim ( $_GET[ 'status' ] ?? '' );
    if ($length <= 0)
        $length = 99999;

    // Detect client-type user — filter runsheets to allowed branches
    $rsBranchWhere  = '';
    $rsBranchParams = [];
    if (isset ($_SESSION[ 'username' ])) {
        $chkR = $pdo->prepare ( "SELECT clientaccess, branch_ids FROM tbl_user WHERE username = ? LIMIT 1" );
        $chkR->execute ( [ $_SESSION[ 'username' ] ] );
        $chkRRow = $chkR->fetch ( PDO::FETCH_ASSOC );
        if ($chkRRow && $chkRRow[ 'clientaccess' ] == 1) {
            $rawB = $chkRRow[ 'branch_ids' ] ?? '';
            $bIds = $rawB !== '' ? array_values ( array_filter ( array_map ( 'intval', explode ( ',', $rawB ) ) ) ) : [];
            if ( ! empty ($bIds)) {
                $keys = [];
                foreach ($bIds as $i => $id) {
                    $key                  = ':rb' . $i;
                    $keys[]               = $key;
                    $rsBranchParams[$key] = $id;
                    }
                $inClause      = implode ( ',', $keys );
                $rsBranchWhere = " AND r.id IN (SELECT DISTINCT rd.runsheet_id FROM tbl_runsheet_details rd JOIN tbl_bookings bk ON bk.id = rd.booking_id WHERE bk.branch_id IN ($inClause))";
                }
            }
        }

    // $sql = "SELECT r.*,
    //             u1.username AS created_by_name,
    //             u2.username AS updated_by_name,
    //             (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%out for delivery%') AS cnt_delivered,
    //             (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) LIKE '%attempt%') AS cnt_attempted,
    //             (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%attempt%') AS cnt_pending
    //         FROM tbl_runsheet r
    //         LEFT JOIN tbl_user u1 ON u1.user_id = r.created_by
    //         LEFT JOIN tbl_user u2 ON u2.user_id = r.updated_by
    //         WHERE 1=1" . $rsBranchWhere;

    // $countSql = "SELECT COUNT(*) FROM tbl_runsheet r WHERE 1=1" . $rsBranchWhere;
    // $params   = [];

    // if ($search !== '') {
    //     $sql                 .= " AND (r.runsheet_no LIKE :search OR r.driver_name LIKE :search OR r.mobile_number LIKE :search)";
    //     $countSql            .= " AND (r.runsheet_no LIKE :search OR r.driver_name LIKE :search OR r.mobile_number LIKE :search)";
    //     $params[ ':search' ]  = "%$search%";
    //     }
    // if ($status !== '') {
    //     if ($status === 'completed') {
    //         $sql      .= " AND r.status != 'draft' AND r.shipment_count > 0 AND (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%attempt%') = 0";
    //         $countSql .= " AND r.status != 'draft' AND r.shipment_count > 0 AND (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%attempt%') = 0";
    //         } else if ($status === 'dispatched') {
    //         $sql      .= " AND r.status = 'dispatched' AND (r.shipment_count = 0 OR (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%attempt%') > 0)";
    //         $countSql .= " AND r.status = 'dispatched' AND (r.shipment_count = 0 OR (SELECT COUNT(*) FROM tbl_runsheet_details rd
    //                 LEFT JOIN tbl_bookings b ON b.waybill_no = rd.awb_no
    //                 WHERE rd.runsheet_id = r.id
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%deliver%'
    //                   AND LOWER(COALESCE(b.last_status,'')) NOT LIKE '%attempt%') > 0)";
    //         } else {
    //         $sql                 .= " AND r.status = :status";
    //         $countSql            .= " AND r.status = :status";
    //         $params[ ':status' ]  = $status;
    //         }
    //     }


    // 1. Breakdown counts from tbl_runsheet_details.status (exact match)
    // Delivered  = status is exactly 'Delivered' (case-insensitive)
    // Attempted  = status is 'Attempted','Undelivered','Returned','RTO' etc.
    // Pending    = everything else (Created, Picked Up, Out For Delivery, In Transit, Pending, etc.)
    $sql = "SELECT r.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name,
                (SELECT COUNT(*) FROM tbl_runsheet_details rd
                    LEFT JOIN tbl_bookings bk ON bk.id = rd.booking_id
                    WHERE rd.runsheet_id = r.id
                      AND LOWER(TRIM(COALESCE(bk.last_status, rd.status, ''))) = 'delivered') AS cnt_delivered,
                (SELECT COUNT(*) FROM tbl_runsheet_details rd
                    LEFT JOIN tbl_bookings bk ON bk.id = rd.booking_id
                    WHERE rd.runsheet_id = r.id
                      AND LOWER(TRIM(COALESCE(bk.last_status, rd.status, ''))) IN ('attempted','undelivered','returned','rto','return')) AS cnt_attempted,
                (SELECT COUNT(*) FROM tbl_runsheet_details rd
                    LEFT JOIN tbl_bookings bk ON bk.id = rd.booking_id
                    WHERE rd.runsheet_id = r.id
                      AND LOWER(TRIM(COALESCE(bk.last_status, rd.status, ''))) NOT IN ('delivered','attempted','undelivered','returned','rto','return')) AS cnt_pending
            FROM tbl_runsheet r
            LEFT JOIN tbl_user u1 ON u1.id = r.created_by
            LEFT JOIN tbl_user u2 ON u2.id = r.updated_by
            WHERE 1=1" . $rsBranchWhere;

    $countSql = "SELECT COUNT(*) FROM tbl_runsheet r WHERE 1=1" . $rsBranchWhere;
    $params   = [];

    if ($search !== '') {
        $sql                 .= " AND (r.runsheet_no LIKE :search OR r.driver_name LIKE :search OR r.mobile_number LIKE :search)";
        $countSql            .= " AND (r.runsheet_no LIKE :search OR r.driver_name LIKE :search OR r.mobile_number LIKE :search)";
        $params[ ':search' ]  = "%$search%";
        }

    // 2. Status filter — now uses r.status directly since backend auto-updates to 'completed'
    if ($status !== '') {
        if ($status === 'completed') {
            // DB completed OR dynamically completed (dispatched with no pending)
            $completedCond = " AND (r.status = 'completed' OR (r.status = 'dispatched' AND r.shipment_count > 0 AND (SELECT COUNT(*) FROM tbl_runsheet_details rd2 LEFT JOIN tbl_bookings bk2 ON bk2.id = rd2.booking_id WHERE rd2.runsheet_id = r.id AND LOWER(TRIM(COALESCE(bk2.last_status, rd2.status, ''))) NOT IN ('delivered','attempted','undelivered','returned','rto','return')) = 0))";
            $sql      .= $completedCond;
            $countSql .= $completedCond;
            } else if ($status === 'dispatched') {
            // Only truly dispatched (still has pending shipments)
            $dispatchedCond = " AND r.status = 'dispatched' AND (r.shipment_count = 0 OR (SELECT COUNT(*) FROM tbl_runsheet_details rd2 LEFT JOIN tbl_bookings bk2 ON bk2.id = rd2.booking_id WHERE rd2.runsheet_id = r.id AND LOWER(TRIM(COALESCE(bk2.last_status, rd2.status, ''))) NOT IN ('delivered','attempted','undelivered','returned','rto','return')) > 0)";
            $sql      .= $dispatchedCond;
            $countSql .= $dispatchedCond;
            } else {
            $sql                 .= " AND r.status = :status";
            $countSql            .= " AND r.status = :status";
            $params[ ':status' ]  = $status;
            }
        }

    // Date range filter (by runsheet_date)
    $fromDate = trim ( $_GET[ 'from_date' ] ?? '' );
    $toDate   = trim ( $_GET[ 'to_date' ] ?? '' );
    if ($fromDate !== '' && $toDate !== '') {
        $sql                    .= " AND DATE(r.runsheet_date) BETWEEN :from_date AND :to_date";
        $countSql               .= " AND DATE(r.runsheet_date) BETWEEN :from_date AND :to_date";
        $params[ ':from_date' ]  = $fromDate;
        $params[ ':to_date' ]    = $toDate;
        }

    $sql .= " ORDER BY r.created_at DESC LIMIT :start, :length";

    $countStmt = $pdo->prepare ( $countSql );
    foreach ($params as $k => $v)
        $countStmt->bindValue ( $k, $v );
    foreach ($rsBranchParams as $k => $v)
        $countStmt->bindValue ( $k, $v, PDO::PARAM_INT );
    $countStmt->execute ();
    $total = (int) $countStmt->fetchColumn ();

    $stmt = $pdo->prepare ( $sql );
    foreach ($params as $k => $v)
        $stmt->bindValue ( $k, $v );
    foreach ($rsBranchParams as $k => $v)
        $stmt->bindValue ( $k, $v, PDO::PARAM_INT );
    $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
    $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
    $stmt->execute ();
    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    // Dynamic completed status mapping
    foreach ($data as &$row) {
        if ($row[ 'status' ] === 'dispatched' && $row[ 'shipment_count' ] > 0 && isset ($row[ 'cnt_pending' ]) && $row[ 'cnt_pending' ] == 0) {
            $row[ 'status' ] = 'completed';
            }
        }

    echo json_encode ( [
        'draw' => (int) ($_GET[ 'draw' ] ?? 1),
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data,
        'status' => 'success',
    ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
