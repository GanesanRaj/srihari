<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $currentUser = get_current_user_info ();
    $scannedBy   = $currentUser[ 'username' ] ?? ($_SESSION[ 'username' ] ?? 'system');

    $manifestId = (int) ($_POST[ 'manifest_id' ] ?? 0);
    $scanValue  = trim ( $_POST[ 'scan_value' ] ?? '' );

    if ($manifestId <= 0 || $scanValue === '')
        throw new Exception( 'manifest_id and scan_value required' );

    // Fetch manifest
    $mStmt = $pdo->prepare ( "SELECT * FROM tbl_manifest WHERE id = :id LIMIT 1" );
    $mStmt->execute ( [ ':id' => $manifestId ] );
    $manifest = $mStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $manifest)
        throw new Exception( 'Manifest not found' );

    $existing    = json_decode ( $manifest[ 'json_data' ] ?: '[]', true );
    $existingAwbs = array_column ( $existing, 'awb_no' );
    $addedEntries = [];
    $now          = date ( 'Y-m-d H:i:s' );

    // Detect TAG (starts with TAG-)
    $isTag = (bool) preg_match ( '/^TAG-/i', $scanValue ); 
    $isTag=true;

    if ($isTag) {
        // ── TAG scan: expand all shipments from the tag ──────────────────────
        $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE tag_no = :tag_no LIMIT 1" );
        $tagStmt->execute ( [ ':tag_no' => $scanValue ] );
        $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $tag)
            throw new Exception( 'Tag not found: ' . $scanValue );

        $tagEntries = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
        if (empty ( $tagEntries ))
            throw new Exception( 'Tag has no shipments: ' . $scanValue );

        foreach ($tagEntries as $te) {
            $awb = $te[ 'awb_no' ];
            if (in_array ( $awb, $existingAwbs ))
                continue; // Skip already-scanned

            $entry = [
                'awb_no'        => $awb,
                'booking_id'    => $te[ 'booking_id' ] ?? null,
                'consignee_name' => $te[ 'consignee_name' ] ?? '',
                'consignee_city' => $te[ 'consignee_city' ] ?? '',
                'tag_no'        => $tag[ 'tag_no' ],
                'scanned_at'    => $now,
                'scanned_by'    => $scannedBy,
                ];
            $existing[]     = $entry;
            $existingAwbs[] = $awb;
            $addedEntries[] = $entry;
            }

        if (empty ( $addedEntries ))
            throw new Exception( 'All shipments from ' . $scanValue . ' are already in this manifest' );
        } else {
        // ── Single AWB scan ──────────────────────────────────────────────────
        if (in_array ( $scanValue, $existingAwbs ))
            throw new Exception( 'AWB ' . $scanValue . ' already in this manifest' );

        // Lookup in packages first
        $pkgStmt = $pdo->prepare ( "SELECT bp.*, b.consignee_name, b.consignee_city, b.courier_id
                                   FROM tbl_booking_packages bp
                                   JOIN tbl_bookings b ON b.id = bp.booking_id
                                   WHERE bp.awb_no = :awb LIMIT 1" );
        $pkgStmt->execute ( [ ':awb' => $scanValue ] );
        $pkgRow = $pkgStmt->fetch ( PDO::FETCH_ASSOC );

        $booking = null;
        if ( ! $pkgRow) {
            $bkStmt = $pdo->prepare ( "SELECT id, consignee_name, consignee_city, courier_id FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1" );
            $bkStmt->execute ( [ ':awb' => $scanValue ] );
            $booking = $bkStmt->fetch ( PDO::FETCH_ASSOC );
            }

        if ( ! $pkgRow && ! $booking)
            throw new Exception( 'Shipment not found: ' . $scanValue );

        $courierId = $pkgRow[ 'courier_id' ] ?? ($booking[ 'courier_id' ] ?? null);
        if ($courierId != 2)
            throw new Exception( 'Only Own Courier (ID 2) shipments can be manifested' );

        $bookingId     = $pkgRow[ 'booking_id' ] ?? ($booking[ 'id' ] ?? null);
        $consigneeName = $pkgRow[ 'consignee_name' ] ?? ($booking[ 'consignee_name' ] ?? '');
        $consigneeCity = $pkgRow[ 'consignee_city' ] ?? ($booking[ 'consignee_city' ] ?? '');

        $entry = [
            'awb_no'        => $scanValue,
            'booking_id'    => $bookingId,
            'consignee_name' => $consigneeName,
            'consignee_city' => $consigneeCity,
            'tag_no'        => null,
            'scanned_at'    => $now,
            'scanned_by'    => $scannedBy,
            ];
        $existing[]   = $entry;
        $addedEntries[] = $entry;
        }

    // Recalculate tag_no and totals (Bags = unique tags; same tag = 1 bag)
    $total     = count ( $existing );
    $tagNos    = array_unique ( array_filter ( array_column ( $existing, 'tag_no' ) ) );
    $tagNoStr  = implode ( ',', $tagNos );
    $bagKeys   = [];
    foreach ( $existing as $e ) {
        $key = ! empty( $e[ 'tag_no' ] ) ? trim ( $e[ 'tag_no' ] ) : ( $e[ 'awb_no' ] ?? '' );
        if ( $key !== '' ) $bagKeys[ $key ] = true;
    }
    $bagCount  = count ( $bagKeys );
    $totalBox  = 0;
    $weightKg  = 0.0;
    $bookingIds = array_unique ( array_filter ( array_column ( $existing, 'booking_id' ) ) );
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
        ':json'       => json_encode ( $existing ),
        ':cnt'        => $total,
        ':tag_no'     => $tagNoStr ?: null,
        ':bag_count'  => $bagCount,
        ':total_box'  => $totalBox,
        ':weight'     => $weightKg,
        ':id'         => $manifestId,
    ] );

    // ── Write tracking for newly added entries ──────────────────────────────
    // Resolve from/to branch names once
    $scanLocation = null;
    if ( ! empty( $manifest[ 'from_branch' ] ) || ! empty( $manifest[ 'to_branch' ] ) ) {
        $branchIds = array_filter ( [ (int) $manifest[ 'from_branch' ], (int) $manifest[ 'to_branch' ] ] );
        if ( ! empty( $branchIds ) ) {
            $bph      = implode ( ',', array_fill ( 0, count ( $branchIds ), '?' ) );
            $bStmt    = $pdo->prepare ( "SELECT id, branch_name FROM tbl_branch WHERE id IN ($bph)" );
            $bStmt->execute ( array_values ( $branchIds ) );
            $branchMap = [];
            foreach ( $bStmt->fetchAll ( PDO::FETCH_ASSOC ) as $br ) {
                $branchMap[ $br[ 'id' ] ] = $br[ 'branch_name' ];
            }
            $fromName    = $branchMap[ (int) $manifest[ 'from_branch' ] ] ?? null;
            $toName      = $branchMap[ (int) $manifest[ 'to_branch' ] ] ?? null;
            $locationParts = array_filter ( [ $fromName, $toName ] );
            if ( ! empty( $locationParts ) ) {
                $scanLocation = implode ( ' → ', $locationParts );
            }
        }
    }

    // For each newly added entry, upsert a tracking record
    foreach ( $addedEntries as $ae ) {
        $aeBookingId = $ae[ 'booking_id' ] ?? null;
        $aeAwb       = $ae[ 'awb_no' ] ?? '';
        if ( ! $aeBookingId ) continue;

        // Fetch waybill_no from bookings if we only have booking_id
        $wbStmt = $pdo->prepare ( "SELECT waybill_no FROM tbl_bookings WHERE id = :id LIMIT 1" );
        $wbStmt->execute ( [ ':id' => $aeBookingId ] );
        $wbRow    = $wbStmt->fetch ( PDO::FETCH_ASSOC );
        $waybillNo = $wbRow[ 'waybill_no' ] ?? $aeAwb;

        $rawData = json_encode ( [
            'awb_no'         => $waybillNo,
            'manifest_id'    => $manifestId,
            'manifest_ref'   => $manifest[ 'manifest_no' ] ?? null,
            'from_branch'    => $manifest[ 'from_branch' ] ?? null,
            'to_branch'      => $manifest[ 'to_branch' ] ?? null,
            'scan_location'  => $scanLocation,
            'current_status' => 'Manifested',
            'scan_details'   => [
                'status'     => 'Manifested',
                'datetime'   => $now,
                'remarks'    => 'Added to manifest',
                'type'       => 'Manifest'
            ]
        ] );

        // Check existing tracking record and preserve history
        $exStmt = $pdo->prepare ( "SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1" );
        $exStmt->execute ( [ ':wn' => $waybillNo ] );
        $exTrack = $exStmt->fetch ( PDO::FETCH_ASSOC );

        $history = [];
        if ( $exTrack && ! empty( $exTrack[ 'raw_response' ] ) ) {
            $decoded = json_decode ( $exTrack[ 'raw_response' ], true );
            if ( isset( $decoded[ 'scan_details_history' ] ) ) {
                $history = $decoded[ 'scan_details_history' ];
            } elseif ( isset( $decoded[ 'scan_details' ] ) ) {
                $history = [ $decoded[ 'scan_details' ] ];
            }
        }

        $newScan = [
            'status'     => 'Manifested',
            'datetime'   => $now,
            'remarks'    => 'Added to manifest',
            'location'   => $scanLocation,
            'type'       => 'Manifest'
        ];
        $history[] = $newScan;

        $rawData = json_encode ( [
            'awb_no'               => $waybillNo,
            'manifest_id'          => $manifestId,
            'manifest_ref'         => $manifest[ 'manifest_no' ] ?? null,
            'from_branch'          => $manifest[ 'from_branch' ] ?? null,
            'to_branch'            => $manifest[ 'to_branch' ] ?? null,
            'scan_location'        => $scanLocation,
            'current_status'       => 'Manifested',
            'scan_details'         => $newScan,
            'scan_details_history' => $history
        ] );

        if ( $exTrack ) {
            $pdo->prepare (
                "UPDATE tbl_tracking SET scan_type = :st, scan_location = :loc, scan_datetime = :dt, status_code = :sc, remarks = :rem, raw_response = :raw WHERE id = :id"
                )->execute ( [
                ':st'  => 'Manifested',
                ':loc' => $scanLocation,
                ':dt'  => $now,
                ':sc'  => 'Manifested',
                ':rem' => 'Added to manifest',
                ':raw' => $rawData,
                ':id'  => $exTrack[ 'id' ],
            ] );
            } else {
            $pdo->prepare (
                "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response)
                 VALUES (:bid, :wn, :st, :loc, :dt, :sc, :rem, :raw)"
                )->execute ( [
                ':bid' => $aeBookingId,
                ':wn'  => $waybillNo,
                ':st'  => 'Manifested',
                ':loc' => $scanLocation,
                ':dt'  => $now,
                ':sc'  => 'Manifested',
                ':rem' => 'Added to manifest',
                ':raw' => $rawData,
            ] );
            }

        // Update booking last_status
        $pdo->prepare ( "UPDATE tbl_bookings SET last_status = 'Manifested', updated_at = NOW() WHERE id = :id" )
            ->execute ( [ ':id' => $aeBookingId ] );
    }

    $manifestStatus = $manifest[ 'status' ] ?: 'draft';

    echo json_encode ( [
        'status'          => 'success',
        'entries'         => $addedEntries,
        'total_count'     => $total,
        'manifest_status' => $manifestStatus,
        'message'         => count ( $addedEntries ) . ' shipment(s) added',
        'bag_count'       => $bagCount,
        'total_box'       => $totalBox,
        'weight'          => $weightKg,
    ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
