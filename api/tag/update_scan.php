<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $tagId     = (int) ($_POST[ 'tag_id' ] ?? 0);
    $awbNo     = trim ( $_POST[ 'awb_no' ] ?? '' );
    $newStatus = trim ( $_POST[ 'status' ] ?? '' ); // packed, hold, or verified
    $remarks   = sanitizeText ( $_POST[ 'remarks' ] ?? '' );

    if ($tagId <= 0 || $awbNo === '' || ! in_array ( $newStatus, [ 'packed', 'hold', 'verified' ] )) {
        throw new Exception( 'tag_id, awb_no, and valid status (packed/hold/verified) required' );
        }

    $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE id = :id LIMIT 1" );
    $tagStmt->execute ( [ ':id' => $tagId ] );
    $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    $entries = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
    $found   = false;
    foreach ($entries as &$entry) {
        if ($entry[ 'awb_no' ] === $awbNo) {
            $entry[ 'status' ]     = $newStatus;
            $entry[ 'remarks' ]    = $remarks;
            $entry[ 'updated_at' ] = date ( 'Y-m-d H:i:s' );
            $found                 = true;
            break;
            }
        }
    unset ( $entry );
    if ( ! $found)
        throw new Exception( 'AWB not found in this tag' );

    // Recalculate tag status based on individual shipment statuses
    $statuses    = array_column ( $entries, 'status' );
    $hasHold     = in_array ( 'hold', $statuses );
    $hasPacked   = in_array ( 'packed', $statuses );
    $hasVerified = in_array ( 'verified', $statuses );

    if ($hasVerified && ! $hasHold && ! $hasPacked) {
        $tagStatus = 'fully_verified';
        } elseif ($hasVerified || $hasHold && $hasPacked) {
        $tagStatus = 'partially_verified';
        } elseif ($hasHold) {
        $tagStatus = 'hold';
        } else {
        $tagStatus = 'packed';
        }

    $updStmt = $pdo->prepare ( "UPDATE tbl_tags SET json_data = :json, status = :status WHERE id = :id" );
    $updStmt->execute ( [ ':json' => json_encode ( $entries ), ':status' => $tagStatus, ':id' => $tagId ] );

    // ── If this tag is now fully_verified, check if all manifest tags are done ──
    if ( $tagStatus === 'fully_verified' ) {
        $curTagNo = $tag[ 'tag_no' ];
        // Find manifests that contain this tag_no (stored as comma-separated)
        $mfStmt = $pdo->prepare ( "SELECT id, tag_no FROM tbl_manifest WHERE FIND_IN_SET(:tn, tag_no) > 0" );
        $mfStmt->execute ( [ ':tn' => $curTagNo ] );
        $manifests = $mfStmt->fetchAll ( PDO::FETCH_ASSOC );

        foreach ( $manifests as $mf ) {
            $mfTagNos = array_filter ( array_map ( 'trim', explode ( ',', $mf[ 'tag_no' ] ) ) );
            if ( empty ( $mfTagNos ) ) continue;

            // Fetch status of all tags in this manifest
            $ph       = implode ( ',', array_fill ( 0, count ( $mfTagNos ), '?' ) );
            $tsStmt   = $pdo->prepare ( "SELECT status FROM tbl_tags WHERE tag_no IN ($ph)" );
            $tsStmt->execute ( array_values ( $mfTagNos ) );
            $tagStatuses = $tsStmt->fetchAll ( PDO::FETCH_COLUMN );

            // If all tags are fully_verified (none are packed/partially_verified/hold)
            $notDone = array_filter ( $tagStatuses, fn ( $s ) => $s !== 'fully_verified' );
            if ( empty ( $notDone ) && ! empty ( $tagStatuses ) ) {
                $pdo->prepare ( "UPDATE tbl_manifest SET status = 'Received', updated_at = NOW() WHERE id = :id" )
                    ->execute ( [ ':id' => $mf[ 'id' ] ] );
            }
        }
    }

    // ── When verified, insert "Received" tracking ────────────────────────────
    if ( $newStatus === 'verified' ) {
        $now = date ( 'Y-m-d H:i:s' );

        // Resolve branch names for scan_location
        $scanLocation = null;
        if ( ! empty( $tag[ 'from_branch' ] ) || ! empty( $tag[ 'to_branch' ] ) ) {
            $branchIds = array_filter ( [ (int) $tag[ 'from_branch' ], (int) $tag[ 'to_branch' ] ] );
            if ( ! empty( $branchIds ) ) {
                $bph   = implode ( ',', array_fill ( 0, count ( $branchIds ), '?' ) );
                $bStmt = $pdo->prepare ( "SELECT id, branch_name FROM tbl_branch WHERE id IN ($bph)" );
                $bStmt->execute ( array_values ( $branchIds ) );
                $branchMap = [];
                foreach ( $bStmt->fetchAll ( PDO::FETCH_ASSOC ) as $br ) {
                    $branchMap[ $br[ 'id' ] ] = $br[ 'branch_name' ];
                }
                $fromName = $branchMap[ (int) $tag[ 'from_branch' ] ] ?? null;
                $toName   = $branchMap[ (int) $tag[ 'to_branch' ] ] ?? null;
                $parts    = array_filter ( [ $fromName, $toName ] );
                if ( ! empty( $parts ) ) {
                    $scanLocation = implode ( ' → ', $parts );
                }
            }
        }

        // Look up booking by awb_no — try tbl_bookings first, then tbl_booking_packages
        $bookingId     = null;
        $waybillNo     = $awbNo;
        $currentStatus = null;

        $bkStmt = $pdo->prepare ( "SELECT id, waybill_no, last_status FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1" );
        $bkStmt->execute ( [ ':awb' => $awbNo ] );
        $bkRow = $bkStmt->fetch ( PDO::FETCH_ASSOC );

        if ( $bkRow ) {
            $bookingId     = $bkRow[ 'id' ];
            $waybillNo     = $bkRow[ 'waybill_no' ];
            $currentStatus = $bkRow[ 'last_status' ];
            } else {
            // Try as child package AWB
            $pkgStmt = $pdo->prepare (
                "SELECT bp.booking_id, b.waybill_no, b.last_status FROM tbl_booking_packages bp
                 JOIN tbl_bookings b ON b.id = bp.booking_id
                 WHERE bp.awb_no = :awb LIMIT 1"
                );
            $pkgStmt->execute ( [ ':awb' => $awbNo ] );
            $pkgRow = $pkgStmt->fetch ( PDO::FETCH_ASSOC );
            if ( $pkgRow ) {
                $bookingId     = $pkgRow[ 'booking_id' ];
                $waybillNo     = $pkgRow[ 'waybill_no' ];
                $currentStatus = $pkgRow[ 'last_status' ];
            }
        }

        // For MPS: find the booking_id in tag entries for this AWB,
        // then only write tracking when ALL entries for that booking are verified
        $tagBookingId   = null;
        $shouldWriteTracking = true;
        foreach ( $entries as $e ) {
            if ( ( $e[ 'awb_no' ] ?? '' ) === $awbNo ) {
                $tagBookingId = $e[ 'booking_id' ] ?? null;
                break;
            }
        }
        if ( $tagBookingId ) {
            foreach ( $entries as $e ) {
                if ( ( $e[ 'booking_id' ] ?? null ) == $tagBookingId && ( $e[ 'status' ] ?? '' ) !== 'verified' ) {
                    $shouldWriteTracking = false;
                    break;
                }
            }
        }

        if ( $bookingId && $shouldWriteTracking ) {
            // Fetch existing tracking and preserve history
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
                'status'   => 'Received',
                'datetime' => $now,
                'remarks'  => $remarks ?: 'Verified at destination',
                'location' => $scanLocation,
                'type'     => 'Tag Verify'
            ];
            $history[] = $newScan;

            $rawData = json_encode ( [
                'awb_no'               => $waybillNo,
                'tag_id'               => $tagId,
                'tag_no'               => $tag[ 'tag_no' ] ?? null,
                'from_branch'          => $tag[ 'from_branch' ] ?? null,
                'to_branch'            => $tag[ 'to_branch' ] ?? null,
                'scan_location'        => $scanLocation,
                'current_status'       => 'Received',
                'scan_details'         => $newScan,
                'scan_details_history' => $history
            ] );

            if ( $exTrack ) {
                $pdo->prepare (
                    "UPDATE tbl_tracking SET scan_type=:st, scan_location=:loc, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id"
                    )->execute ( [
                    ':st'  => 'Received',
                    ':loc' => $scanLocation,
                    ':dt'  => $now,
                    ':sc'  => 'Received',
                    ':rem' => $remarks ?: 'Verified at destination',
                    ':raw' => $rawData,
                    ':id'  => $exTrack[ 'id' ],
                ] );
                } else {
                $pdo->prepare (
                    "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response)
                     VALUES (:bid, :wn, :st, :loc, :dt, :sc, :rem, :raw)"
                    )->execute ( [
                    ':bid' => $bookingId,
                    ':wn'  => $waybillNo,
                    ':st'  => 'Received',
                    ':loc' => $scanLocation,
                    ':dt'  => $now,
                    ':sc'  => 'Received',
                    ':rem' => $remarks ?: 'Verified at destination',
                    ':raw' => $rawData,
                ] );
            }

            // Update booking last_status to Received (skip only if already Out for Delivery or Delivered)
            $protectedStatuses = [ 'Out for Delivery', 'Delivered' ];
            if ( ! in_array ( $currentStatus, $protectedStatuses ) ) {
                $pdo->prepare ( "UPDATE tbl_bookings SET last_status = 'Received', updated_at = NOW() WHERE id = :id" )
                    ->execute ( [ ':id' => $bookingId ] );
            }
        }
    }

    echo json_encode ( [ 'status' => 'success', 'tag_status' => $tagStatus, 'message' => 'AWB status updated' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
