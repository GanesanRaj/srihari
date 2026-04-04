<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $id         = (int) ($_GET[ 'id' ] ?? 0);
    $runsheetNo = trim ( $_GET[ 'runsheet_no' ] ?? '' );
    if ($id <= 0 && $runsheetNo === '')
        throw new Exception( 'id or runsheet_no required' );

    $where = $id > 0 ? "r.id = :id" : "r.runsheet_no = :runsheet_no";
    $param = $id > 0 ? [ ':id' => $id ] : [ ':runsheet_no' => $runsheetNo ];

    $stmt = $pdo->prepare (
        "SELECT r.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name
         FROM tbl_runsheet r
         LEFT JOIN tbl_user u1 ON u1.user_id = r.created_by
         LEFT JOIN tbl_user u2 ON u2.user_id = r.updated_by
         WHERE $where LIMIT 1"
    );
    $stmt->execute ( $param );
    $runsheet = $stmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $runsheet)
        throw new Exception( 'Run Sheet not found' );

    // Fetch detail rows
    $dStmt = $pdo->prepare (
        "SELECT * FROM tbl_runsheet_details WHERE runsheet_id = :id ORDER BY consignee_name ASC"
    );
    $dStmt->execute ( [ ':id' => $runsheet[ 'id' ] ] );
    $runsheet[ 'details' ] = $dStmt->fetchAll ( PDO::FETCH_ASSOC );

    echo json_encode ( [ 'status' => 'success', 'data' => $runsheet ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
