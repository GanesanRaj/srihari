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

    // Detect client-type user — filter tags to allowed branches
    $tagBranchWhere  = '';
    $tagBranchParams = [];
    if (isset($_SESSION['username'])) {
        $chkT = $pdo->prepare("SELECT clientaccess, branch_ids FROM tbl_user WHERE username = ? LIMIT 1");
        $chkT->execute([$_SESSION['username']]);
        $chkTRow = $chkT->fetch(PDO::FETCH_ASSOC);
        if ($chkTRow && $chkTRow['clientaccess'] == 1) {
            $rawB = $chkTRow['branch_ids'] ?? '';
            $bIds = $rawB !== '' ? array_values(array_filter(array_map('intval', explode(',', $rawB)))) : [];
            if (!empty($bIds)) {
                $keys = [];
                foreach ($bIds as $i => $id) {
                    $key = ':tb' . $i;
                    $keys[] = $key;
                    $tagBranchParams[$key] = $id;
                }
                $tagBranchWhere = " AND t.from_branch IN (" . implode(',', $keys) . ")";
            }
        }
    }

    $sql = "SELECT t.*,
                u1.username AS created_by_name,
                u2.username AS verified_by_name,
                u3.username AS received_by_name
            FROM tbl_tags t
            LEFT JOIN tbl_user u1 ON u1.id = t.created_by
            LEFT JOIN tbl_user u2 ON u2.id = t.verified_by
            LEFT JOIN tbl_user u3 ON u3.id = t.received_by
            WHERE 1=1" . $tagBranchWhere;

    $countSql = "SELECT COUNT(*) FROM tbl_tags t WHERE 1=1" . $tagBranchWhere;
    $params   = [];

    if ($search !== '') {
        $sql      .= " AND t.tag_no LIKE :search";
        $countSql .= " AND t.tag_no LIKE :search";
        $params[':search'] = "%$search%";
    }
    if ($status !== '') {
        $sql      .= " AND t.status = :status";
        $countSql .= " AND t.status = :status";
        $params[':status'] = $status;
    }
    $sql .= " ORDER BY t.created_at DESC LIMIT :start, :length";

    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v)
        $countStmt->bindValue($k, $v);
    foreach ($tagBranchParams as $k => $v)
        $countStmt->bindValue($k, $v, PDO::PARAM_INT);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare ( $sql );
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    foreach ($tagBranchParams as $k => $v)
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
    $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
    $stmt->execute ();
    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    // Don't send full json_data in list — just send count and status
    foreach ($data as &$row) {
        unset ( $row[ 'json_data' ] );
        }
    unset ( $row );

    echo json_encode ( [
        'draw' => (int) ($_GET[ 'draw' ] ?? 1),
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data,
        'status' => 'success'
    ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
