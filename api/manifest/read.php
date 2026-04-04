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

    // Detect client-type user — filter manifests to allowed branches
    $manifestBranchWhere  = '';
    $manifestBranchParams = [];
    if (isset($_SESSION['username'])) {
        $chkM = $pdo->prepare("SELECT clientaccess, branch_ids FROM tbl_user WHERE username = ? LIMIT 1");
        $chkM->execute([$_SESSION['username']]);
        $chkMRow = $chkM->fetch(PDO::FETCH_ASSOC);
        if ($chkMRow && $chkMRow['clientaccess'] == 1) {
            $rawB = $chkMRow['branch_ids'] ?? '';
            $bIds = $rawB !== '' ? array_values(array_filter(array_map('intval', explode(',', $rawB)))) : [];
            if (!empty($bIds)) {
                $keys = [];
                foreach ($bIds as $i => $id) {
                    $key = ':mb' . $i;
                    $keys[] = $key;
                    $manifestBranchParams[$key] = $id;
                }
                $manifestBranchWhere = " AND m.from_branch IN (" . implode(',', $keys) . ")";
            }
        }
    }

    $sql = "SELECT m.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name,
                b1.branch_name AS from_branch_name,
                b2.branch_name AS to_branch_name
            FROM tbl_manifest m
            LEFT JOIN tbl_user u1 ON u1.user_id = m.created_by
            LEFT JOIN tbl_user u2 ON u2.user_id = m.updated_by
            LEFT JOIN tbl_branch b1 ON b1.id = m.from_branch
            LEFT JOIN tbl_branch b2 ON b2.id = m.to_branch
            WHERE 1=1" . $manifestBranchWhere;

    $countSql = "SELECT COUNT(*) FROM tbl_manifest m WHERE 1=1" . $manifestBranchWhere;
    $params   = [];


    if ($search !== '') {
        $sql                 .= " AND (m.manifest_no LIKE :search OR m.vehicle_no LIKE :search OR m.driver_name LIKE :search OR m.coloader LIKE :search)";
        $countSql            .= " AND (m.manifest_no LIKE :search OR m.vehicle_no LIKE :search OR m.driver_name LIKE :search OR m.coloader LIKE :search)";
        $params[ ':search' ]  = "%$search%";
        }
    if ($status !== '') {
        $sql                 .= " AND m.status = :status";
        $countSql            .= " AND m.status = :status";
        $params[ ':status' ]  = $status;
        }

    $sql .= " ORDER BY m.created_at DESC LIMIT :start, :length";

    $countStmt = $pdo->prepare ( $countSql );
    foreach ($params as $k => $v)
        $countStmt->bindValue ( $k, $v );
    foreach ($manifestBranchParams as $k => $v)
        $countStmt->bindValue ( $k, $v, PDO::PARAM_INT );
    $countStmt->execute ();
    $total = (int) $countStmt->fetchColumn ();

    $stmt = $pdo->prepare ( $sql );
    foreach ($params as $k => $v)
        $stmt->bindValue ( $k, $v );
    foreach ($manifestBranchParams as $k => $v)
        $stmt->bindValue ( $k, $v, PDO::PARAM_INT );
    $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
    $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
    $stmt->execute ();
    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    // Extract AWB info from json_data, then strip the heavy raw field
    foreach ($data as &$row) {
        $shipments        = json_decode ( $row[ 'json_data' ] ?? '[]', true );
        $row[ 'awb_count' ] = is_array ( $shipments ) ? count ( $shipments ) : 0;
        $row[ 'awb_list' ]  = [];
        if (is_array ( $shipments )) {
            foreach ($shipments as $s) {
                if ( ! empty ($s[ 'awb_no' ])) {
                    $row[ 'awb_list' ][] = $s[ 'awb_no' ];
                    }
                }
            }
        unset ( $row[ 'json_data' ] );
        }
    unset ( $row );

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
