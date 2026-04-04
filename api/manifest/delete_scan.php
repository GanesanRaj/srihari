<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $manifestId = (int) ($_POST[ 'manifest_id' ] ?? 0);
    $awbNo      = trim ( $_POST[ 'awb_no' ] ?? '' );

    if ($manifestId <= 0 || $awbNo === '')
        throw new Exception( 'manifest_id and awb_no required' );

    $mStmt = $pdo->prepare ( "SELECT * FROM tbl_manifest WHERE id = :id LIMIT 1" );
    $mStmt->execute ( [ ':id' => $manifestId ] );
    $manifest = $mStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $manifest)
        throw new Exception( 'Manifest not found' );

    $entries = json_decode ( $manifest[ 'json_data' ] ?: '[]', true );
    $entries = array_values ( array_filter ( $entries, fn($e) => $e[ 'awb_no' ] !== $awbNo ) );

    $total    = count ( $entries );
    $tagNos   = array_unique ( array_filter ( array_column ( $entries, 'tag_no' ) ) );
    $tagNoStr = implode ( ',', $tagNos );
    $bagKeys  = [];
    foreach ( $entries as $e ) {
        $key = ! empty( $e[ 'tag_no' ] ) ? trim ( $e[ 'tag_no' ] ) : ( $e[ 'awb_no' ] ?? '' );
        if ( $key !== '' ) $bagKeys[ $key ] = true;
    }
    $bagCount = count ( $bagKeys );
    $totalBox = 0;
    $weightKg = 0.0;
    $bookingIds = array_unique ( array_filter ( array_column ( $entries, 'booking_id' ) ) );
    if ( ! empty( $bookingIds )) {
        $ph = implode ( ',', array_fill ( 0, count ( $bookingIds ), '?' ) );
        $sumStmt = $pdo->prepare ( "SELECT COALESCE(SUM(quantity),0) AS tot_qty, COALESCE(SUM(weight),0)/1000 AS tot_kg FROM tbl_bookings WHERE id IN ($ph)" );
        $sumStmt->execute ( array_values ( $bookingIds ) );
        $row = $sumStmt->fetch ( PDO::FETCH_ASSOC );
        $totalBox = (int) ( $row[ 'tot_qty' ] ?? 0 );
        $weightKg = round ( (float) ( $row[ 'tot_kg' ] ?? 0 ), 2 );
    }

    $updStmt = $pdo->prepare ( "UPDATE tbl_manifest SET json_data = :json, total_count = :cnt, tag_no = :tag_no, bag_count = :bag_count, total_box = :total_box, weight = :weight WHERE id = :id" );
    $updStmt->execute ( [
        ':json'      => json_encode ( $entries ),
        ':cnt'       => $total,
        ':tag_no'    => $tagNoStr ?: null,
        ':bag_count' => $bagCount,
        ':total_box' => $totalBox,
        ':weight'    => $weightKg,
        ':id'        => $manifestId,
    ] );

    echo json_encode ( [ 'status' => 'success', 'total_count' => $total, 'bag_count' => $bagCount, 'total_box' => $totalBox, 'weight' => $weightKg ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
