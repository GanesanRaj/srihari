<?php
/**
 * Generic Shipment Info for Status Update (apps-api)
 * Location: /apps-api/statusupdate/info.php
 * Fetches basic booking details and lists MPS packages if applicable.
 */

header ( 'Content-Type: application/json' );
header ( 'Access-Control-Allow-Origin: *' );
header ( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
header ( 'Access-Control-Allow-Headers: Content-Type' );

if ($_SERVER[ 'REQUEST_METHOD' ] === 'OPTIONS') {
    exit (0);
    }

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER[ 'REQUEST_METHOD' ] === 'POST'
    ? (json_decode ( file_get_contents ( 'php://input' ), true ) ?? $_POST)
    : $_GET;

$awb_no = trim ( $req[ 'awb_no' ] ?? '' );

if ($awb_no === '') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'awb_no is required' ] );
    exit;
    }

try {
    // 1. Resolve awb_no
    $stmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1" );
    $stmt->execute ( [ ':awb' => $awb_no ] );
    $booking = $stmt->fetch ( PDO::FETCH_ASSOC );

    $bookingId = null;

    if ($booking) {
        $bookingId = (int) $booking[ 'id' ];
        } else {
        // Try package level if not master
        $pkgStmt = $pdo->prepare ( "SELECT booking_id FROM tbl_booking_packages WHERE LOWER(TRIM(child_ewaybill_no)) = LOWER(TRIM(:awb)) OR LOWER(TRIM(awb_no)) = LOWER(TRIM(:awb)) LIMIT 1" );
        $pkgStmt->execute ( [ ':awb' => $awb_no ] );
        $package = $pkgStmt->fetch ( PDO::FETCH_ASSOC );
        if ($package) {
            $bookingId = (int) $package[ 'booking_id' ];
            }
        }

    if ( ! $bookingId) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'AWB Number not found' ] );
        exit;
        }

    // 2. Fetch Booking Data
    $bookStmt = $pdo->prepare ( "SELECT 
        b.id, b.waybill_no, b.booking_ref_id, b.last_status, b.quantity,
        b.consignee_name, b.consignee_city, b.consignee_phone, b.shipper_name, b.shipper_city,
        c.partner_name as courier_name
        FROM tbl_bookings b
        LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
        WHERE b.id = :bid LIMIT 1" );
    $bookStmt->execute ( [ ':bid' => $bookingId ] );
    $bookData = $bookStmt->fetch ( PDO::FETCH_ASSOC );

    // 3. Fetch Packages (MPS)
    $isChildScan = ! empty ($package) && strtolower ( $awb_no ) !== strtolower ( $bookData[ 'waybill_no' ] );

    $stmtSQL = "SELECT id as package_id, row_no, awb_no, child_ewaybill_no, status, status_date, boxes,
                       length, width, height, actual_weight, vol_weight, charged_weight
                FROM tbl_booking_packages
                WHERE booking_id = :bid ";

    // If they scanned a specific child AWB, only show that child.
    if ($isChildScan) {
        $stmtSQL .= " AND (LOWER(TRIM(child_ewaybill_no)) = LOWER(TRIM(:awb)) OR LOWER(TRIM(awb_no)) = LOWER(TRIM(:awb))) ";
        }

    $stmtSQL .= " ORDER BY row_no ASC";

    $pkgStmt = $pdo->prepare ( $stmtSQL );
    $params  = [ ':bid' => $bookingId ];
    if ($isChildScan) {
        $params[ ':awb' ] = $awb_no;
        }
    $pkgStmt->execute ( $params );
    $packages = $pkgStmt->fetchAll ( PDO::FETCH_ASSOC );

    // Only keep volumetric data for the scanned AWB's package
    $volFields = ['length', 'width', 'height', 'actual_weight', 'vol_weight', 'charged_weight'];
    foreach ($packages as &$pkg) {
        $matchesScanned = strtolower ( trim ( $pkg['awb_no'] ) ) === strtolower ( trim ( $awb_no ) )
                       || strtolower ( trim ( $pkg['child_ewaybill_no'] ) ) === strtolower ( trim ( $awb_no ) );
        if ( ! $matchesScanned) {
            foreach ($volFields as $f) { unset ( $pkg[$f] ); }
        }
    }
    unset ($pkg);

    echo json_encode ( [
        'status' => 'success',
        'data' => [
            'booking_id' => $bookData[ 'id' ],
            'parent_awb' => $awb_no ,
            'ref_id' => $bookData[ 'booking_ref_id' ] ?? '-',
            'courier' => $bookData[ 'courier_name' ] ?? '-',
            'consignee' => $bookData[ 'consignee_name' ] ?? '-',
            'origin' => $bookData[ 'shipper_city' ] ?? '-',
            'destination' => $bookData[ 'consignee_city' ] ?? '-',
            'total_boxes' => $bookData[ 'quantity' ] ?? 1,
            'current_status' => $bookData[ 'last_status' ] ?? 'Pending',
            'is_mps' => count ( $packages ) > 1,
            'packages' => $packages
        ]
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [
        'status' => 'error',
        'message' => $e->getMessage ()
    ] );
    }
