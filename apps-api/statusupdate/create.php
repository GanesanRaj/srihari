<?php
/**
 * Generic Shipment Status Update (apps-api)
 * Location: /apps-api/statusupdate/create.php
 */

header ( 'Content-Type: application/json' );
header ( 'Access-Control-Allow-Origin: *' );
header ( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
header ( 'Access-Control-Allow-Headers: Content-Type' );

if ($_SERVER[ 'REQUEST_METHOD' ] === 'OPTIONS') {
    exit (0);
    }

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

$req = $_SERVER[ 'REQUEST_METHOD' ] === 'POST'
    ? (json_decode ( file_get_contents ( 'php://input' ), true ) ?? $_POST)
    : $_GET;

$awb_no_input = $req[ 'awb_no' ] ?? '';
$status       = trim ( $req[ 'status' ] ?? '' );
$status_date  = trim ( $req[ 'status_date' ] ?? '' );
$location     = trim ( $req[ 'location' ] ?? '' );
$remarks      = trim ( $req[ 'remarks' ] ?? '' );
$user_id      = isset ($req[ 'user_id' ]) ? (int) $req[ 'user_id' ] : 1;
$username     = trim ( $req[ 'username' ] ?? 'api-user' );

$awbs = [];
if (is_array ( $awb_no_input )) {
    $awbs = $awb_no_input;
    } else if (is_string ( $awb_no_input )) {
    // Allow comma-separated string of AWBs
    $awbs = array_filter ( array_map ( 'trim', explode ( ',', $awb_no_input ) ) );
    }

if (empty ($awbs)) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'awb_no is required (can be comma-separated or an array)' ] );
    exit;
    }
if ($status === '') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'status is required' ] );
    exit;
    }
if ($status_date === '') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'status_date is required' ] );
    exit;
    }

try {
    // Convert datetime format
    if (strpos ( $status_date, 'T' ) !== false) {
        $statusDateTime = str_replace ( 'T', ' ', $status_date );
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else if (strpos ( $status_date, ' ' ) !== false) {
        $statusDateTime = $status_date;
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else {
        $statusDateTime = $status_date . ' 00:00:00';
        }

    $results            = [];
    $success_count      = 0;
    $has_error          = false;
    $processed_bookings = [];

    // Begin transaction
    $pdo->beginTransaction ();

    try {
        foreach ($awbs as $awb) {
            try {
                // 1. Resolve awb_no
                $stmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1" );
                $stmt->execute ( [ ':awb' => $awb ] );
                $booking = $stmt->fetch ( PDO::FETCH_ASSOC );

                $bookingId = null;

                if ($booking) {
                    $bookingId = (int) $booking[ 'id' ];
                    } else {
                    $pkgStmt = $pdo->prepare ( "SELECT booking_id FROM tbl_booking_packages WHERE LOWER(TRIM(child_ewaybill_no)) = LOWER(TRIM(:awb)) OR LOWER(TRIM(awb_no)) = LOWER(TRIM(:awb)) LIMIT 1" );
                    $pkgStmt->execute ( [ ':awb' => $awb ] );
                    $package = $pkgStmt->fetch ( PDO::FETCH_ASSOC );
                    if ($package) {
                        $bookingId = (int) $package[ 'booking_id' ];
                        }
                    }

                if ( ! $bookingId) {
                    throw new Exception( "AWB Number not found" );
                    }

                // If we've already updated this parent booking in this request (e.g. multiple child AWBs scanned), don't duplicate it.
                if ( ! in_array ( $bookingId, $processed_bookings )) {
                    // Use centralized helper function to update booking, tracking, and cross-table sync
                    updateTrackingAndStatus ( $pdo, $bookingId, $status, $location, $remarks, $user_id, $username );
                    $processed_bookings[] = $bookingId;
                    }

                $results[] = [
                    'awb_no' => $awb,
                    'status' => 'success'
                ];
                $success_count++;

                }
            catch ( Exception $innerErr ) {
                $has_error = true;
                $results[] = [
                    'awb_no' => $awb,
                    'status' => 'error',
                    'message' => $innerErr->getMessage ()
                ];
                }
            }

        $pdo->commit ();

        $overall_status = 'success';
        if ($has_error) {
            $overall_status = ($success_count > 0) ? 'partial' : 'error';
            }

        echo json_encode ( [
            'status' => $overall_status,
            'message' => "Successfully updated $success_count out of " . count ( $awbs ) . " shipment(s).",
            'new_status' => $status,
            'status_date' => $statusDateTime,
            'results' => $results
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
