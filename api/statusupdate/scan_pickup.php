<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $scanValue = trim ( $_POST[ 'scan_value' ] ?? '' );

    if ($scanValue === '') {
        throw new Exception( 'Scan value required' );
        }

    $shipments = [];

    // Detect TAG
    $isTag = (bool) preg_match ( '/^TAG-/i', $scanValue );

    if ($isTag) {
        $tagStmt = $pdo->prepare ( "SELECT json_data FROM tbl_tags WHERE tag_no = :tag_no LIMIT 1" );
        $tagStmt->execute ( [ ':tag_no' => $scanValue ] );
        $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
        if ($tag) {
            $tagEntries = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
            if ( ! empty ($tagEntries)) {
                $awbs = array_column ( $tagEntries, 'awb_no' );
                if ( ! empty ($awbs)) {
                    $inQuery = implode ( ',', array_fill ( 0, count ( $awbs ), '?' ) );
                    $stmt    = $pdo->prepare ( "
                        SELECT b.id, b.waybill_no, b.booking_ref_id, b.last_status, b.created_at,
                               b.shipper_name as consignor_name, b.shipper_city as pickup_city, b.shipper_city,
                               c.partner_name as courier_name,
                               NULL as package_id, NULL as child_awb_no, 0 as is_child_package
                        FROM tbl_bookings b
                        LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
                        WHERE b.waybill_no IN ($inQuery)
                    " );
                    $stmt->execute ( $awbs );
                    $shipments = $stmt->fetchAll ( PDO::FETCH_ASSOC );
                    }
                }
            }
        } else {
        // Check if it's a pickup point ID
        $stmt          = $pdo->prepare ( "SELECT id FROM tbl_pickup_points WHERE id = :id LIMIT 1" );
        $pickupPointId = null;
        if (is_numeric ( $scanValue )) {
            $stmt->execute ( [ ':id' => $scanValue ] );
            $pickup = $stmt->fetch ( PDO::FETCH_ASSOC );
            if ($pickup) {
                $pickupPointId = $pickup[ 'id' ];
                }
            }

        if ($pickupPointId) {
            // Fetch all shipments for this pickup point
            $stmt = $pdo->prepare ( "
                SELECT b.id, b.waybill_no, b.booking_ref_id, b.last_status, b.created_at,
                       b.shipper_name as consignor_name, b.shipper_city as pickup_city, b.shipper_city,
                       c.partner_name as courier_name,
                       NULL as package_id, NULL as child_awb_no, 0 as is_child_package
                FROM tbl_bookings b
                LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.pickup_point_id = :pid
            " );
            $stmt->execute ( [ ':pid' => $pickupPointId ] );
            $shipments = $stmt->fetchAll ( PDO::FETCH_ASSOC );
            } else {
            // Fetch by AWB, Ref ID, or Booking ID (parent shipment)
            $stmt = $pdo->prepare ( "
                SELECT b.id, b.waybill_no, b.booking_ref_id, b.last_status, b.created_at,
                       b.shipper_name as consignor_name, b.shipper_city as pickup_city, b.shipper_city,
                       c.partner_name as courier_name,
                       NULL as package_id, NULL as child_awb_no, 0 as is_child_package
                FROM tbl_bookings b
                LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.waybill_no = :val OR b.booking_ref_id = :val OR b.id = :val
            " );
            $stmt->execute ( [ ':val' => $scanValue ] );
            $shipments = $stmt->fetchAll ( PDO::FETCH_ASSOC );

            // If not found as parent, try as a child box AWB in tbl_booking_packages
            if (empty ($shipments)) {
                $pkgStmt = $pdo->prepare ( "
                    SELECT b.id, b.waybill_no, b.booking_ref_id, b.last_status, b.created_at,
                           b.shipper_name as consignor_name, b.shipper_city as pickup_city, b.shipper_city,
                           c.partner_name as courier_name,
                           bp.id as package_id, bp.awb_no as child_awb_no, 1 as is_child_package
                    FROM tbl_booking_packages bp
                    JOIN tbl_bookings b ON bp.booking_id = b.id
                    LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
                    WHERE bp.awb_no = :val
                " );
                $pkgStmt->execute ( [ ':val' => $scanValue ] );
                $shipments = $pkgStmt->fetchAll ( PDO::FETCH_ASSOC );
                }
            }
        }

    if (empty ($shipments)) {
        throw new Exception( 'No shipments found' );
        }

    echo json_encode ( [ 'status' => 'success', 'data' => $shipments ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
