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
    $username    = $currentUser[ 'username' ] ?? 'system';

    $tagId  = (int) ($_POST[ 'tag_id' ] ?? 0);
    $status = trim ( $_POST[ 'status' ] ?? '' );

    if ($tagId <= 0) {
        throw new Exception( 'Tag ID is required' );
        }

    $allowed = [ 'packed', 'fully_verified', 'partially_verified', 'hold', 'in_transit' ];
    if ( ! in_array ( $status, $allowed )) {
        throw new Exception( 'Invalid status' );
        }

    $stmt = $pdo->prepare ( "UPDATE tbl_tags SET status = :status, verified_by = :uid, verified_at = NOW() WHERE id = :id" );
    $stmt->execute ( [
        ':status' => $status,
        ':uid' => $userId,
        ':id' => $tagId
    ] );

    if ( $status === 'in_transit' ) {
        $tagStmt = $pdo->prepare ( "SELECT json_data FROM tbl_tags WHERE id = :id LIMIT 1" );
        $tagStmt->execute ( [ ':id' => $tagId ] );
        $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
        if ( $tag && ! empty ( $tag[ 'json_data' ] )) {
            $entries = json_decode ( $tag[ 'json_data' ], true );
            if ( is_array ( $entries )) {
                $processed = [];
                foreach ( $entries as $e ) {
                    $awb = trim ( $e[ 'awb_no' ] ?? '' );
                    if ( $awb === '' ) continue;
                    $bStmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1" );
                    $bStmt->execute ( [ ':awb' => $awb ] );
                    $booking = $bStmt->fetch ( PDO::FETCH_ASSOC );
                    if ( ! $booking ) {
                        $pkgStmt = $pdo->prepare ( "SELECT booking_id AS id FROM tbl_booking_packages WHERE awb_no = :awb LIMIT 1" );
                        $pkgStmt->execute ( [ ':awb' => $awb ] );
                        $booking = $pkgStmt->fetch ( PDO::FETCH_ASSOC );
                    }
                    if ( $booking && ! in_array ( $booking[ 'id' ], $processed )) {
                        $processed[] = $booking[ 'id' ];
                        updateTrackingAndStatus ( $pdo, $booking[ 'id' ], 'In Transit', 'Tag Verify', '', $userId, $username );
                    }
                }
            }
        }
    }

    echo json_encode ( [ 'status' => 'success', 'message' => 'Tag status updated successfully' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
