<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $runsheetId = (int) ($_GET[ 'id' ] ?? 0);
    if ($runsheetId <= 0)
        throw new Exception( 'Runsheet ID required' );

    // Fetch runsheet header
    $rStmt = $pdo->prepare ( "SELECT id, runsheet_no, runsheet_date, status, attachments FROM tbl_runsheet WHERE id = :id LIMIT 1" );
    $rStmt->execute ( [ ':id' => $runsheetId ] );
    $runsheet = $rStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $runsheet)
        throw new Exception( 'Run Sheet not found' );

    // Fetch shipments with their status from rd + booking last_status
    $dStmt = $pdo->prepare (
        "SELECT rd.id AS detail_id, rd.awb_no, rd.booking_id,
                rd.consignee_name, rd.consignee_city, rd.consignee_phone,
                rd.status AS rd_status, rd.remarks,
                b.waybill_no AS parent_awb, b.last_status AS booking_status
         FROM tbl_runsheet_details rd
         LEFT JOIN tbl_bookings b ON b.id = rd.booking_id
         WHERE rd.runsheet_id = :id
         ORDER BY rd.id ASC"
    );
    $dStmt->execute ( [ ':id' => $runsheetId ] );
    $shipments = $dStmt->fetchAll ( PDO::FETCH_ASSOC );

    // Check if current user is super admin
    $isSuperAdmin = ((int) ($_SESSION[ 'role_id' ] ?? 0)) === 1;

    echo json_encode ( [
        'status' => 'success',
        'runsheet' => $runsheet,
        'shipments' => $shipments,
        'is_super_admin' => $isSuperAdmin
    ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
