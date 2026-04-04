<?php
header ( 'Content-Type: application/json' );
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

try {
    if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
        throw new Exception( 'Invalid request method' );
        }

    // Get current user
    $current_user = get_current_user_info ();
    $userId       = $current_user ? $current_user[ 'id' ] : 1;
    $username     = $current_user ? $current_user[ 'username' ] : 'system';

    // Required fields
    $bookingId  = isset ($_POST[ 'booking_id' ]) ? intval ( $_POST[ 'booking_id' ] ) : 0;
    $status     = isset ($_POST[ 'status' ]) ? trim ( $_POST[ 'status' ] ) : '';
    $statusDate = isset ($_POST[ 'status_date' ]) ? trim ( $_POST[ 'status_date' ] ) : '';
    $location   = isset ($_POST[ 'location' ]) ? trim ( $_POST[ 'location' ] ) : '';
    $remarks    = isset ($_POST[ 'remarks' ]) ? trim ( $_POST[ 'remarks' ] ) : '';

    // Validate required fields
    if ($bookingId <= 0) {
        throw new Exception( 'Booking ID is required' );
        }
    if (empty ($status)) {
        throw new Exception( 'Status is required' );
        }
    if (empty ($statusDate)) {
        throw new Exception( 'Status date is required' );
        }

    // Check if booking exists
    $checkStmt = $pdo->prepare ( "SELECT id, waybill_no, last_status, courier_id, booking_ref_id FROM tbl_bookings WHERE id = :id" );
    $checkStmt->execute ( [ ':id' => $bookingId ] );
    $booking = $checkStmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $booking) {
        throw new Exception( 'Booking not found' );
        }

    // Standardize status format
    $status = trim ( $status );

    // Convert datetime-local format to MySQL datetime
    if (strpos ( $statusDate, 'T' ) !== false) {
        $statusDateTime = str_replace ( 'T', ' ', $statusDate );
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else if (strpos ( $statusDate, ' ' ) !== false) {
        $statusDateTime = $statusDate;
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else {
        $statusDateTime = $statusDate . ' 00:00:00';
        }

    // Begin transaction
    $pdo->beginTransaction ();

    try {
        // Use centralized helper function to update booking, tracking, and cross-table sync
        updateTrackingAndStatus ( $pdo, $bookingId, $status, $location, $remarks, $userId, $username );

        $pdo->commit ();

        echo json_encode ( [
            'status' => 'success',
            'message' => 'Status updated successfully',
            'booking_id' => $bookingId,
            'new_status' => $status
        ] );
        }
    catch ( Exception $transactionError ) {
        $pdo->rollBack ();
        throw $transactionError;
        }

    }
catch ( Exception $e ) {
    echo json_encode ( [
        'status' => 'error',
        'message' => $e->getMessage ()
    ] );
    }
