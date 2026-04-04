<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $id         = (int) ($_GET[ 'id' ] ?? 0);
    $manifestNo = trim ( $_GET[ 'manifest_no' ] ?? '' );
    $cdNo       = trim ( $_GET[ 'cd_no' ] ?? '' );
    if ($id <= 0 && $manifestNo === '' && $cdNo === '')
        throw new Exception( 'id, manifest_no or cd_no required' );

    $sql  = "SELECT m.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name,
                b1.branch_name AS from_branch_name,
                b2.branch_name AS to_branch_name,
                c.name AS coloader_name
             FROM tbl_manifest m
             LEFT JOIN tbl_user u1 ON u1.user_id = m.created_by
             LEFT JOIN tbl_user u2 ON u2.user_id = m.updated_by
             LEFT JOIN tbl_branch b1 ON b1.id = m.from_branch
             LEFT JOIN tbl_branch b2 ON b2.id = m.to_branch
             LEFT JOIN tbl_coloader c ON c.id = m.coloader_id
             WHERE 1=1";
    $params = [];
    if ($id > 0) {
        $sql .= " AND m.id = :id";
        $params[ ':id' ] = $id;
    } elseif ($manifestNo !== '') {
        $sql .= " AND m.manifest_no = :manifest_no";
        $params[ ':manifest_no' ] = $manifestNo;
    } else {
        $sql .= " AND m.cd_no = :cd_no";
        $params[ ':cd_no' ] = $cdNo;
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare ( $sql );
    $stmt->execute ( $params );
    $manifest = $stmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $manifest)
        throw new Exception( 'Manifest not found' );

    $manifest[ 'json_data' ] = json_decode ( $manifest[ 'json_data' ] ?: '[]', true );

    // Auto-fill Bags, Wt (kg), Boxes; tags_count and shipment_count for tag-verify
    $entries = is_array ( $manifest[ 'json_data' ] ) ? $manifest[ 'json_data' ] : [];
    $bagKeys = [];
    $tagNos  = [];
    foreach ( $entries as $e ) {
        $key = ! empty( $e[ 'tag_no' ] ) ? trim ( $e[ 'tag_no' ] ) : ( $e[ 'awb_no' ] ?? '' );
        if ( $key !== '' ) $bagKeys[ $key ] = true;
        if ( ! empty( $e[ 'tag_no' ] )) $tagNos[ trim( $e[ 'tag_no' ] ) ] = true;
    }
    $manifest[ 'bag_count' ]      = count ( $bagKeys );
    $manifest[ 'tags_count' ]    = count ( $tagNos );
    $manifest[ 'shipment_count' ] = count ( $entries );

    // Per-tag verified counts for tooltip (tags inside this manifest, how many verified in each)
    $manifest[ 'tag_verified_counts' ] = [];
    $manifest[ 'total_verified_count' ] = 0;
    if ( ! empty( $tagNos )) {
        $tagList = array_keys( $tagNos );
        $ph = implode( ',', array_fill( 0, count( $tagList ), '?' ) );
        $tStmt = $pdo->prepare( "SELECT tag_no, json_data FROM tbl_tags WHERE tag_no IN ($ph)" );
        $tStmt->execute( $tagList );
        while ( $row = $tStmt->fetch( PDO::FETCH_ASSOC )) {
            $arr = json_decode( $row[ 'json_data' ] ?: '[]', true );
            $verified = 0;
            if ( is_array( $arr )) {
                foreach ( $arr as $item ) {
                    if ( ( $item[ 'status' ] ?? '' ) === 'verified' ) $verified++;
                }
            }
            $manifest[ 'tag_verified_counts' ][] = [ 'tag_no' => $row[ 'tag_no' ], 'verified_count' => $verified ];
            $manifest[ 'total_verified_count' ] += $verified;
        }
    }

    $manifest[ 'total_box' ] = 0;
    $manifest[ 'weight' ]    = 0.0;
    if ( ! empty( $entries )) {
        $bookingIds = array_unique ( array_filter ( array_column ( $entries, 'booking_id' ) ) );
        if ( ! empty( $bookingIds )) {
            $placeholders = implode ( ',', array_fill ( 0, count ( $bookingIds ), '?' ) );
            $sumStmt = $pdo->prepare ( "SELECT COALESCE(SUM(quantity),0) AS tot_qty, COALESCE(SUM(weight),0)/1000 AS tot_kg FROM tbl_bookings WHERE id IN ($placeholders)" );
            $sumStmt->execute ( array_values ( $bookingIds ) );
            $row = $sumStmt->fetch ( PDO::FETCH_ASSOC );
            $manifest[ 'total_box' ] = (int) ( $row[ 'tot_qty' ] ?? 0 );
            $manifest[ 'weight' ]   = round ( (float) ( $row[ 'tot_kg' ] ?? 0 ), 2 );
        }
    }

    echo json_encode ( [ 'status' => 'success', 'data' => $manifest ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
