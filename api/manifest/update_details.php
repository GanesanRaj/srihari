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
    $userId      = $currentUser[ 'id' ] ?? ($_SESSION[ 'user_id' ] ?? 1);

    $manifestId = (int) ($_POST[ 'manifest_id' ] ?? 0);
    if ($manifestId <= 0)
        throw new Exception( 'manifest_id required' );

    $fields = [ 'updated_by = :updated_by' ];
    $params = [ ':updated_by' => $userId, ':id' => $manifestId ];

    if (array_key_exists ( 'from_branch', $_POST )) {
        $fields[]                 = 'from_branch = :from_branch';
        $params[ ':from_branch' ] = $_POST[ 'from_branch' ] !== '' ? (int) $_POST[ 'from_branch' ] : null;
        }
    if (array_key_exists ( 'to_branch', $_POST )) {
        $fields[]               = 'to_branch = :to_branch';
        $params[ ':to_branch' ] = $_POST[ 'to_branch' ] !== '' ? (int) $_POST[ 'to_branch' ] : null;
        }
    if (array_key_exists ( 'coloader', $_POST )) {
        $fields[]              = 'coloader = :coloader';
        $params[ ':coloader' ] = sanitizeText ( $_POST[ 'coloader' ] );
        }
    if (array_key_exists ( 'coloader_id', $_POST )) {
        $fields[]                 = 'coloader_id = :coloader_id';
        $params[ ':coloader_id' ] = $_POST[ 'coloader_id' ] !== '' ? (int) $_POST[ 'coloader_id' ] : null;
        }
    if (array_key_exists ( 'cd_no', $_POST )) {
        $fields[]           = 'cd_no = :cd_no';
        $params[ ':cd_no' ] = sanitizeText ( $_POST[ 'cd_no' ] );
        }
    if (array_key_exists ( 'vehicle_no', $_POST )) {
        $fields[]                = 'vehicle_no = :vehicle_no';
        $params[ ':vehicle_no' ] = sanitizeText ( $_POST[ 'vehicle_no' ] );
        }
    if (array_key_exists ( 'driver_name', $_POST )) {
        $fields[]                 = 'driver_name = :driver_name';
        $params[ ':driver_name' ] = sanitizeText ( $_POST[ 'driver_name' ] );
        }
    if (array_key_exists ( 'mobile_no', $_POST )) {
        $fields[]               = 'mobile_no = :mobile_no';
        $params[ ':mobile_no' ] = sanitizeText ( $_POST[ 'mobile_no' ] );
        }
    if (array_key_exists ( 'bag_count', $_POST )) {
        $fields[]               = 'bag_count = :bag_count';
        $params[ ':bag_count' ] = (int) $_POST[ 'bag_count' ];
        }
    if (array_key_exists ( 'weight', $_POST )) {
        $fields[]            = 'weight = :weight';
        $params[ ':weight' ] = (float) $_POST[ 'weight' ];
        }
    if (array_key_exists ( 'total_box', $_POST )) {
        $fields[]               = 'total_box = :total_box';
        $params[ ':total_box' ] = (int) $_POST[ 'total_box' ];
        }
    if (array_key_exists ( 'status', $_POST )) {
        $fields[]            = 'status = :status';
        $params[ ':status' ] = sanitizeText ( $_POST[ 'status' ] );
        }
    if (array_key_exists ( 'dispatch_mode', $_POST )) {
        $fields[]                   = 'dispatch_mode = :dispatch_mode';
        $params[ ':dispatch_mode' ] = sanitizeText ( $_POST[ 'dispatch_mode' ] );
        }

    $stmt = $pdo->prepare ( "UPDATE tbl_manifest SET " . implode ( ', ', $fields ) . " WHERE id = :id" );
    $stmt->execute ( $params );

    // If status changed, propagate to all AWBs in this manifest
    if (array_key_exists ( 'status', $_POST )) {
        $newStatus = sanitizeText ( $_POST[ 'status' ] );

        // Fetch manifest to get shipments + branch info
        $mStmt = $pdo->prepare ( "SELECT json_data, from_branch, to_branch, manifest_no FROM tbl_manifest WHERE id = :id" );
        $mStmt->execute ( [ ':id' => $manifestId ] );
        $manifest = $mStmt->fetch ( PDO::FETCH_ASSOC );

        if ($manifest && ! empty ($manifest[ 'json_data' ])) {
            // Resolve from/to branch names for scan_location
            $scanLocation = null;
            $branchIds    = array_filter ( [ (int) $manifest[ 'from_branch' ], (int) $manifest[ 'to_branch' ] ] );
            if ( ! empty ( $branchIds )) {
                $bph    = implode ( ',', array_fill ( 0, count ( $branchIds ), '?' ) );
                $brStmt = $pdo->prepare ( "SELECT id, branch_name FROM tbl_branch WHERE id IN ($bph)" );
                $brStmt->execute ( array_values ( $branchIds ) );
                $branchMap = [];
                foreach ( $brStmt->fetchAll ( PDO::FETCH_ASSOC ) as $br ) {
                    $branchMap[ $br[ 'id' ] ] = $br[ 'branch_name' ];
                }
                $fromName = $branchMap[ (int) $manifest[ 'from_branch' ] ] ?? null;
                $toName   = $branchMap[ (int) $manifest[ 'to_branch' ] ] ?? null;
                $parts    = array_filter ( [ $fromName, $toName ] );
                if ( ! empty ( $parts )) {
                    $scanLocation = implode ( ' → ', $parts );
                }
            }
            $scanLocation = $scanLocation ?: 'Manifest Location';

            $shipments = json_decode ( $manifest[ 'json_data' ], true );
            if (is_array ( $shipments )) {
                $username          = $currentUser[ 'username' ] ?? 'system';
                $statusForBooking  = trim ( $newStatus ) !== '' ? ucfirst ( $newStatus ) : null;
                $processedBookings = [];

                // One manifest = one status update: update booking last_status only, do NOT insert into tracking
                foreach ($shipments as $shipment) {
                    $awbNo = $shipment[ 'awb_no' ] ?? '';
                    if ( ! $awbNo) continue;

                    $bStmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1" );
                    $bStmt->execute ( [ ':awb' => $awbNo ] );
                    $booking = $bStmt->fetch ( PDO::FETCH_ASSOC );

                    if ( ! $booking) {
                        $pkgStmt = $pdo->prepare ( "SELECT booking_id AS id FROM tbl_booking_packages WHERE awb_no = :awb LIMIT 1" );
                        $pkgStmt->execute ( [ ':awb' => $awbNo ] );
                        $booking = $pkgStmt->fetch ( PDO::FETCH_ASSOC );
                    }

                    if ($booking && ! in_array ( $booking[ 'id' ], $processedBookings )) {
                        $processedBookings[] = $booking[ 'id' ];
                        if ($statusForBooking) {
                            $pdo->prepare ( "UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id" )
                                ->execute ( [ ':status' => $statusForBooking, ':uid' => $userId, ':id' => $booking[ 'id' ] ] );
                            syncShipmentStatusAcrossTables ( $pdo, $booking[ 'id' ], $awbNo, $statusForBooking, '', $username );
                            if (strtolower ( $statusForBooking ) === 'in transit') {
                                updateTrackingAndStatus ( $pdo, $booking[ 'id' ], $statusForBooking, $scanLocation, '', $userId, $username );
                            }
                        }
                    }
                }
            }
        }
    }

    echo json_encode ( [ 'status' => 'success', 'message' => 'Manifest and associated shipments updated' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
