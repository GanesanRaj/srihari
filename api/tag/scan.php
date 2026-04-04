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

    $tagId      = (int) ($_POST[ 'tag_id' ] ?? 0);
    $awbNo      = trim ( $_POST[ 'awb_no' ] ?? '' );
    $remarks    = sanitizeText ( $_POST[ 'remarks' ] ?? '' );
    $ewayBillNo = trim ( $_POST[ 'eway_bill_no' ] ?? '' );

    if ($tagId <= 0 || $awbNo === '') {
        throw new Exception( 'tag_id and awb_no are required' );
        }

    // Fetch tag
    $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE id = :id LIMIT 1" );
    $tagStmt->execute ( [ ':id' => $tagId ] );
    $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    // Lookup AWB from tbl_bookings or tbl_booking_packages
    $booking = null;
    $pkgStmt = $pdo->prepare ( "SELECT bp.*, b.consignee_name, b.consignee_city, b.consignee_phone, b.courier_id, b.invoice_value, b.ewaybill_no
                               FROM tbl_booking_packages bp
                               JOIN tbl_bookings b ON b.id = bp.booking_id
                               WHERE bp.awb_no = :awb LIMIT 1" );
    $pkgStmt->execute ( [ ':awb' => $awbNo ] );
    $pkgRow = $pkgStmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $pkgRow) {
        // Try main waybill
        $bkStmt = $pdo->prepare ( "SELECT id, consignee_name, consignee_city, consignee_phone, courier_id, invoice_value, ewaybill_no FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1" );
        $bkStmt->execute ( [ ':awb' => $awbNo ] );
        $booking = $bkStmt->fetch ( PDO::FETCH_ASSOC );
        }

    if ( ! $pkgRow && ! $booking) {
        throw new Exception( 'Wrong AWB No: Shipment not found' );
        }

    $bookingId = $pkgRow[ 'booking_id' ] ?? ($booking[ 'id' ] ?? null);
    $courierId = $pkgRow[ 'courier_id' ] ?? ($booking[ 'courier_id' ] ?? null);

    if ($bookingId && $courierId != 2) {
        throw new Exception( 'Only Own Courier (ID 2) shipments can be scanned into Tags' );
        }

    $invoiceValue       = $pkgRow[ 'invoice_value' ] ?? ($booking[ 'invoice_value' ] ?? 0);
    $existingEwayBillNo = $pkgRow[ 'ewaybill_no' ] ?? ($booking[ 'ewaybill_no' ] ?? '');

    if (floatval ( $invoiceValue ) > 50000) {
        if (empty ($existingEwayBillNo) && empty ($ewayBillNo)) {
            echo json_encode ( [
                'status' => 'require_ewaybill',
                'message' => 'Invoice value is over ₹50,000. E-waybill is required.',
                'invoice_value' => $invoiceValue
            ] );
            exit;
            }

        if ( ! empty ($ewayBillNo)) {
            if ($bookingId) {
                $updEwayStmt = $pdo->prepare ( "UPDATE tbl_bookings SET ewaybill_no = :eway WHERE id = :id" );
                $updEwayStmt->execute ( [ ':eway' => $ewayBillNo, ':id' => $bookingId ] );
                $existingEwayBillNo = $ewayBillNo;
                }
            }
        }

    $scanStatus    = 'packed'; // First status is packed
    $consigneeName = $pkgRow[ 'consignee_name' ] ?? ($booking[ 'consignee_name' ] ?? '');
    $consigneeCity = $pkgRow[ 'consignee_city' ] ?? ($booking[ 'consignee_city' ] ?? '');

    // Append to JSON
    $existing = json_decode ( $tag[ 'json_data' ] ?: '[]', true );

    // Check duplicate
    foreach ($existing as $entry) {
        if ($entry[ 'awb_no' ] === $awbNo) {
            throw new Exception( 'AWB ' . $awbNo . ' already scanned in this tag' );
            }
        }

    $existing[] = [
        'awb_no' => $awbNo,
        'booking_id' => $bookingId,
        'consignee_name' => $consigneeName,
        'consignee_city' => $consigneeCity,
        'status' => $scanStatus,
        'timestamp' => date ( 'Y-m-d H:i:s' ),
        'scanned_by' => $scannedBy,
        'remarks' => $remarks,
        'ewaybill_no' => $existingEwayBillNo
    ];

    // Recalculate tag status
    $statuses = array_column ( $existing, 'status' );
    $hasHold  = in_array ( 'hold', $statuses );
    $total    = count ( $existing );

    // While scanning, the tag should remain 'packed'.
    // If there is any hold, it can be marked as 'hold'. Otherwise, if just scanning, it stays packed.
    // The "Verify All & Save" action will transition it to 'fully_verified'.
    if ($hasHold) {
        $tagStatus = 'hold';
        } else {
        $tagStatus = 'packed'; // First status is packed
        }

    $updStmt = $pdo->prepare ( "UPDATE tbl_tags SET json_data = :json, total_count = :cnt, status = :status WHERE id = :id" );
    $updStmt->execute ( [
        ':json' => json_encode ( $existing ),
        ':cnt' => $total,
        ':status' => $tagStatus,
        ':id' => $tagId
    ] );

    echo json_encode ( [
        'status' => 'success',
        'scan_status' => $scanStatus,
        'tag_status' => $tagStatus,
        'total_count' => $total,
        'entry' => end ( $existing ),
        'message' => 'AWB scanned and added (Packed)'
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
