<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    // Role check removed — any user with runsheet access can update status

    $currentUser = get_current_user_info ();
    $userId      = $currentUser[ 'id' ] ?? ($_SESSION[ 'user_id' ] ?? 1);
    $username    = $currentUser[ 'username' ] ?? ($_SESSION[ 'username' ] ?? 'system');

    $detailId  = (int) ($_POST[ 'detail_id' ] ?? 0);
    $newStatus = trim ( $_POST[ 'new_status' ] ?? '' );
    $remarks   = trim ( $_POST[ 'remarks' ] ?? '' );

    if ($detailId <= 0 || $newStatus === '') {
        throw new Exception( 'detail_id and new_status required' );
        }

    // Fetch runsheet detail row
    $rdStmt = $pdo->prepare (
        "SELECT rd.*, r.runsheet_date, r.runsheet_no
         FROM tbl_runsheet_details rd
         JOIN tbl_runsheet r ON r.id = rd.runsheet_id
         WHERE rd.id = :id LIMIT 1"
    );
    $rdStmt->execute ( [ ':id' => $detailId ] );
    $detail = $rdStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $detail)
        throw new Exception( 'Shipment not found in runsheet' );

    $bookingId    = $detail[ 'booking_id' ];
    $runsheetDate = $detail[ 'runsheet_date' ] ?: date ( 'Y-m-d' );
    $scanDatetime = $runsheetDate . ' ' . date ( 'H:i:s' );

    if ($remarks === '') {
        $remarks = "Status changed to {$newStatus} via Run Sheet ({$detail[ 'runsheet_no' ]}) by {$username}";
        }

    // 1. Update tbl_runsheet_details.status
    $updRd = $pdo->prepare (
        "UPDATE tbl_runsheet_details SET status = :status, remarks = :remarks, scanned_by = :uname WHERE id = :id"
    );
    $updRd->execute ( [
        ':status' => $newStatus,
        ':remarks' => $remarks,
        ':uname' => $username,
        ':id' => $detailId
    ] );

    // 2. Update tbl_bookings.last_status
    if ($bookingId > 0) {
        $pdo->prepare ( "UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id" )
            ->execute ( [ ':status' => $newStatus, ':uid' => $userId, ':id' => $bookingId ] );
        }

    // 3. Update tbl_tracking (own courier = JSON history, using runsheet_date)
    if ($bookingId > 0) {
        $bkStmt = $pdo->prepare ( "SELECT id, waybill_no, courier_id, booking_ref_id FROM tbl_bookings WHERE id = :id" );
        $bkStmt->execute ( [ ':id' => $bookingId ] );
        $booking = $bkStmt->fetch ( PDO::FETCH_ASSOC );

        if ($booking && $booking[ 'courier_id' ] == 2) {
            // Own Courier: JSON history
            $tStmt = $pdo->prepare ( "SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1" );
            $tStmt->execute ( [ ':wn' => $booking[ 'waybill_no' ] ] );
            $existingTrack = $tStmt->fetch ( PDO::FETCH_ASSOC );

            $history = [];
            if ($existingTrack && ! empty ($existingTrack[ 'raw_response' ])) {
                $decoded = json_decode ( $existingTrack[ 'raw_response' ], true );
                if (isset ($decoded[ 'scan_details_history' ])) {
                    $history = $decoded[ 'scan_details_history' ];
                    } elseif (isset ($decoded[ 'scan_details' ])) {
                    $history = [ $decoded[ 'scan_details' ] ];
                    }
                }

            $newScan   = [
                'status' => $newStatus,
                'location' => 'Run Sheet',
                'datetime' => $scanDatetime,
                'remarks' => $remarks,
                'updated_by' => $userId,
                'updated_at' => $scanDatetime
            ];
            $history[] = $newScan;

            $rawData = json_encode ( [
                'awb_no' => $booking[ 'waybill_no' ],
                'shipment_details' => [ 'id' => $bookingId, 'booking_ref_id' => $booking[ 'booking_ref_id' ] ],
                'current_status' => $newStatus,
                'scan_details' => $newScan,
                'scan_details_history' => $history
            ] );

            if ($existingTrack) {
                $pdo->prepare ( "UPDATE tbl_tracking SET scan_type = :st, scan_location = :sl, scan_datetime = :dt, status_code = :sc, remarks = :rem, raw_response = :raw WHERE id = :id" )
                    ->execute ( [ ':id' => $existingTrack[ 'id' ], ':st' => $newStatus, ':sl' => 'Run Sheet', ':dt' => $scanDatetime, ':sc' => $newStatus, ':rem' => $remarks, ':raw' => $rawData ] );
                } else {
                $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)" )
                    ->execute ( [ ':bid' => $bookingId, ':wn' => $booking[ 'waybill_no' ], ':st' => $newStatus, ':sl' => 'Run Sheet', ':dt' => $scanDatetime, ':sc' => $newStatus, ':rem' => $remarks, ':raw' => $rawData ] );
                }
            } elseif ($booking) {
            // Other courier: new tracking row
            $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)" )
                ->execute ( [ ':bid' => $bookingId, ':wn' => $booking[ 'waybill_no' ], ':st' => $newStatus, ':sl' => 'Run Sheet', ':dt' => $scanDatetime, ':sc' => $newStatus, ':rem' => $remarks, ':raw' => json_encode ( [ 'manual_update' => true, 'user_id' => $userId ] ) ] );
            }
        }

    // 4. Auto-update runsheet status: if all shipments are delivered/attempted, mark runsheet completed
    $runsheetId = $detail[ 'runsheet_id' ];
    $pendStmt   = $pdo->prepare (
        "SELECT COUNT(*) FROM tbl_runsheet_details
         WHERE runsheet_id = :rsid
           AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('delivered','attempted','undelivered','returned','rto','return')"
    );
    $pendStmt->execute ( [ ':rsid' => $runsheetId ] );
    $pendingCount = (int) $pendStmt->fetchColumn ();

    $runsheetCompleted = false;
    if ($pendingCount === 0) {
        // All shipments are delivered/attempted → mark runsheet completed
        $pdo->prepare ( "UPDATE tbl_runsheet SET status = 'completed', updated_by = :uid WHERE id = :id AND status != 'completed'" )
            ->execute ( [ ':uid' => $userId, ':id' => $runsheetId ] );
        $runsheetCompleted = true;
        }

    echo json_encode ( [ 'status' => 'success', 'message' => 'Shipment status updated to ' . $newStatus, 'runsheet_completed' => $runsheetCompleted ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
