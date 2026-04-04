<?php
/**
 * Pickup – Get booking info by AWB (child or parent) and branch_id
 * Location: /apps-api/pickup/get-booking-info.php
 * Params: branch_id, awb_no (AWB = child_ewaybill_no/awb_no in packages, or parent waybill_no in tbl_bookings)
 * Returns: master_awb (parent), sender, receiver, booking_packages (each child with status).
 */

header ( 'Content-Type: application/json' );
header ( 'Access-Control-Allow-Origin: *' );
header ( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
header ( 'Access-Control-Allow-Headers: Content-Type' );

if ($_SERVER[ 'REQUEST_METHOD' ] === 'OPTIONS') {
    exit (0);
    }

require_once __DIR__ . '/../../config/config.php';

$branch_id = trim ( $_REQUEST[ 'branch_id' ] ?? '' );
$awb_no    = trim ( $_REQUEST[ 'awb_no' ] ?? ($_REQUEST[ 'apwb_no' ] ?? '') );

if ($awb_no === '') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'awb_no is required' ] );
    exit;
    }

if ($branch_id === '') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'branch_id is required' ] );
    exit;
    }

$branch_id_int = (int) $branch_id;

try {
    // Find by child AWB (child_ewaybill_no or awb_no) or by parent AWB (tbl_bookings.waybill_no)
    $pkgSql  = "SELECT bp.id as package_id, bp.booking_id, bp.row_no, bp.waybill_no, bp.awb_no, bp.child_ewaybill_no,
                       bp.length, bp.width, bp.height, bp.boxes, bp.actual_weight, bp.vol_weight, bp.charged_weight
                FROM tbl_booking_packages bp
                WHERE LOWER(TRIM(bp.child_ewaybill_no)) = LOWER(TRIM(:awb))
                   OR LOWER(TRIM(bp.awb_no)) = LOWER(TRIM(:awb))
                LIMIT 1";
    $pkgStmt = $pdo->prepare ( $pkgSql );
    $pkgStmt->execute ( [ ':awb' => $awb_no ] );
    $package = $pkgStmt->fetch ( PDO::FETCH_ASSOC );

    $bookingId = null;
    if ($package) {
        $bookingId = (int) $package[ 'booking_id' ];
        } else {
        // Try parent/master AWB
        $parentStmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1" );
        $parentStmt->execute ( [ ':awb' => $awb_no ] );
        $parent = $parentStmt->fetch ( PDO::FETCH_ASSOC );
        if ($parent) {
            $bookingId = (int) $parent[ 'id' ];
            }
        }

    if ( ! $bookingId) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'AWB number not found' ] );
        exit;
        }

    // Get booking with branch: prefer tbl_bookings.branch_id, else from pickup_point
    $bookSql  = "SELECT b.id, b.booking_ref_id, b.waybill_no, b.courier_id,
                       b.consignee_name, b.consignee_phone, b.consignee_email, b.consignee_gst,
                       b.consignee_address, b.consignee_pin, b.consignee_city, b.consignee_state, b.consignee_country,
                       b.shipper_name, b.shipper_phone, b.shipper_address, b.shipper_pin, b.shipper_city, b.shipper_state,
                       b.invoice_no, b.invoice_value, b.ewaybill_no, b.last_status,
                       COALESCE(b.branch_id, p.branch_id) AS booking_branch_id,
                       br.branch_name
                FROM tbl_bookings b
                LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                LEFT JOIN tbl_branch br ON br.id = COALESCE(b.branch_id, p.branch_id)
                WHERE b.id = :bid
                LIMIT 1";
    $bookStmt = $pdo->prepare ( $bookSql );
    $bookStmt->execute ( [ ':bid' => $bookingId ] );
    $booking = $bookStmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $booking) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Booking not found' ] );
        exit;
        }

    $bookingBranchId = (int) ($booking[ 'booking_branch_id' ] ?? 0);

    // Branch check: if AWB belongs to another branch, reject pickup here
    if ($bookingBranchId > 0 && $bookingBranchId !== $branch_id_int) {
        echo json_encode ( [
            'status' => 'error',
            'message' => 'This number is used by another branch. Can\'t pickup here.'
        ] );
        exit;
        }

    // All packages for this booking (child AWBs with status)
    $boxesSql = "SELECT id, row_no, waybill_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight FROM tbl_booking_packages WHERE booking_id = :bid ORDER BY row_no ASC";
    try {
        $boxesStmt = $pdo->prepare ( "SELECT id, row_no, waybill_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight, status as package_status, status_date as package_status_date FROM tbl_booking_packages WHERE booking_id = :bid ORDER BY row_no ASC" );
        $boxesStmt->execute ( [ ':bid' => $bookingId ] );
        $boxes = $boxesStmt->fetchAll ( PDO::FETCH_ASSOC );
        }
    catch ( PDOException $e ) {
        $boxesStmt = $pdo->prepare ( $boxesSql );
        $boxesStmt->execute ( [ ':bid' => $bookingId ] );
        $boxes = $boxesStmt->fetchAll ( PDO::FETCH_ASSOC );
        }
    $totalBox      = count ( $boxes );
    $pendingCount  = 0;
    $pickedCount   = 0;
    $bookingStatus = $booking[ 'last_status' ] ?? '';
    foreach ($boxes as $row) {
        $status = isset ($row[ 'package_status' ]) && $row[ 'package_status' ] !== '' && $row[ 'package_status' ] !== null
            ? trim ( $row[ 'package_status' ] )
            : $bookingStatus;
        if ($status === '' || strtolower ( $status ) === 'pending') {
            $pendingCount++;
            } else {
            $pickedCount++;
            }
        }

    $sender = [
        'name' => $booking[ 'shipper_name' ] ?? '',
        'phone' => $booking[ 'shipper_phone' ] ?? '',
        'address' => $booking[ 'shipper_address' ] ?? '',
        'pin' => $booking[ 'shipper_pin' ] ?? '',
        'city' => $booking[ 'shipper_city' ] ?? '',
        'state' => $booking[ 'shipper_state' ] ?? '',
    ];

    $receiver = [
        'name' => $booking[ 'consignee_name' ] ?? '',
        'phone' => $booking[ 'consignee_phone' ] ?? '',
        'email' => $booking[ 'consignee_email' ] ?? '',
        'gst' => $booking[ 'consignee_gst' ] ?? '',
        'address' => $booking[ 'consignee_address' ] ?? '',
        'pin' => $booking[ 'consignee_pin' ] ?? '',
        'city' => $booking[ 'consignee_city' ] ?? '',
        'state' => $booking[ 'consignee_state' ] ?? '',
        'country' => $booking[ 'consignee_country' ] ?? 'India',
    ];

    $masterAwb  = $booking[ 'waybill_no' ] ?? '';
    $lastStatus = trim ( $booking[ 'last_status' ] ?? '' );
    // Already picked = all packages picked (no pending). Use package counts, not booking last_status.
    $alreadyPickedUp = ($totalBox > 0 && $pickedCount >= $totalBox);
    // pickup_status: true = pickup ready (can pick), false = already picked up
    $pickupStatus        = ! $alreadyPickedUp;
    $pickupStatusMessage = $pickupStatus ? 'Pickup ready' : 'Already picked up';
    $pickupDate          = date ( 'Y-m-d H:i:s' );

    echo json_encode ( [
        'status' => 'success',
        'data' => [
            'pickup_date' => $pickupDate,
            'pickup_status' => $pickupStatus,
            'pickup_status_message' => $pickupStatusMessage,
            'booking_id' => $booking[ 'id' ],
            'booking_ref_id' => $booking[ 'booking_ref_id' ] ?? '',
            'master_awb' => $masterAwb,
            'waybill_no' => $masterAwb,
            'last_status' => $lastStatus,
            'already_picked_up' => $alreadyPickedUp,
            'message' => $pickupStatusMessage,
            'invoice_no' => $booking[ 'invoice_no' ] ?? '',
            'invoice_value' => $booking[ 'invoice_value' ] ?? '',
            'ewaybill_no' => $booking[ 'ewaybill_no' ] ?? '',
            'branch_id' => $bookingBranchId,
            'branch_name' => $booking[ 'branch_name' ] ?? '',
            'sender' => $sender,
            'receiver' => $receiver,
            'total_box' => $totalBox,
            'pending_count' => $pendingCount,
            'picked_count' => $pickedCount,
            'booking_packages' => $boxes,
        ]
    ] );

    }
catch ( PDOException $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage () ] );
    }
