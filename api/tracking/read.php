<?php
header ( 'Content-Type: application/json' );
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

/**
 * Read tracking for a booking.
 * GET ?id=<booking_id> | ?waybill=<awb> [&live=1 to fetch from courier and update DB]
 */
if ($_SERVER[ 'REQUEST_METHOD' ] !== 'GET') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid request method' ] );
    exit;
    }

try {
    $bookingId    = isset ($_GET[ 'id' ]) ? (int) $_GET[ 'id' ] : 0;
    $waybillParam = isset ($_GET[ 'waybill' ]) ? trim ( $_GET[ 'waybill' ] ) : '';
    $live         = isset ($_GET[ 'live' ]) && ($_GET[ 'live' ] === '1' || $_GET[ 'live' ] === 'true');

    if ($bookingId <= 0 && $waybillParam === '') {
        throw new Exception( 'Missing Booking ID or Waybill Number' );
        }

    // Resolve child AWB → parent booking_id if waybill not found directly
    $childAwb = null;
    if ($bookingId <= 0 && $waybillParam !== '') {
        $chkParent = $pdo->prepare("SELECT booking_id, awb_no FROM tbl_booking_packages WHERE awb_no = :awb LIMIT 1");
        $chkParent->execute([':awb' => $waybillParam]);
        $pkgRow = $chkParent->fetch(PDO::FETCH_ASSOC);
        if ($pkgRow) {
            $childAwb  = $waybillParam;
            $bookingId = (int) $pkgRow['booking_id'];
        }
    }

    if ($live) {
        // Live fetch from courier, update DB, return
        $sql  = "SELECT b.id, b.waybill_no, b.courier_id, b.last_status, c.api_key, c.api_url, c.partner_name, c.partner_code
                FROM tbl_bookings b
                JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE " . ($bookingId > 0 ? 'b.id = :id' : 'b.waybill_no = :waybill');
        $stmt = $pdo->prepare ( $sql );
        if ($bookingId > 0) {
            $stmt->execute ( [ ':id' => $bookingId ] );
            } else {
            $stmt->execute ( [ ':waybill' => $waybillParam ] );
            }
        $booking = $stmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $booking) {
            throw new Exception( 'Booking not found' );
            }
        if (empty ($booking[ 'waybill_no' ])) {
            throw new Exception( 'No Waybill Number associated with this booking' );
            }

        require_once __DIR__ . '/services/courier_service.php';
        $trackResult = trackWithCourier ( $pdo, $booking, $booking[ 'waybill_no' ] );
        if (empty ($trackResult[ 'success' ])) {
            throw new Exception( 'Tracking API failed: ' . ($trackResult[ 'message' ] ?? 'Unknown error') );
            }

        $payload       = $trackResult[ 'data' ];
        $shipment      = $payload[ 'Shipment' ] ?? $payload;
        $scans         = $shipment[ 'Scans' ] ?? [];
        $currentStatus = $shipment[ 'Status' ][ 'Status' ] ?? $booking[ 'last_status' ];

        if ($currentStatus !== $booking[ 'last_status' ]) {
            $updStmt = $pdo->prepare ( "UPDATE tbl_bookings SET last_status = :status, api_response = :api_resp, updated_at = NOW() WHERE id = :id" );
            $updStmt->execute ( [ ':status' => $currentStatus, ':api_resp' => json_encode ( $payload ), ':id' => $booking[ 'id' ] ] );
            }

        $checkScanStmt  = $pdo->prepare ( "SELECT id FROM tbl_tracking WHERE booking_id = :bid AND scan_datetime = :dt AND scan_type = :st" );
        $insertScanStmt = $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)" );
        foreach ($scans as $scan) {
            $scanDetail = $scan[ 'ScanDetail' ] ?? $scan;
            $scanTime   = $scanDetail[ 'ScanDateTime' ] ?? null;
            if ( ! $scanTime)
                continue;
            $scanType     = $scanDetail[ 'ScanType' ] ?? 'Unknown';
            $location     = $scanDetail[ 'ScannedLocation' ] ?? $scanDetail[ 'ScanLocation' ] ?? '';
            $statusCode   = $scanDetail[ 'StatusCode' ] ?? $scanDetail[ 'Status' ] ?? '';
            $instructions = $scanDetail[ 'Instructions' ] ?? '';
            $mysqlTime    = (new DateTime( $scanTime ))->format ( 'Y-m-d H:i:s' );
            $checkScanStmt->execute ( [ ':bid' => $booking[ 'id' ], ':dt' => $mysqlTime, ':st' => $scanType ] );
            if ( ! $checkScanStmt->fetch ()) {
                $insertScanStmt->execute ( [
                    ':bid' => $booking[ 'id' ],
                    ':wn' => $booking[ 'waybill_no' ],
                    ':st' => $scanType,
                    ':sl' => $location,
                    ':dt' => $mysqlTime,
                    ':sc' => $statusCode,
                    ':rem' => $instructions,
                    ':raw' => json_encode ( $scan )
                ] );
                }
            }

        $data = $payload;
        if (isset ($payload[ 'Shipment' ]) && ! isset ($data[ 'Scans' ])) {
            $data[ 'Scans' ] = $payload[ 'Shipment' ][ 'Scans' ] ?? [];
            }
        echo json_encode ( [
            'status' => 'success',
            'current_status' => $currentStatus,
            'scans_count' => count ( $scans ),
            'child_awb' => $childAwb,
            'data' => $data
        ] );
        exit;
        }

    // DB only
    $sql  = "SELECT b.id, b.waybill_no, b.last_status, b.api_response FROM tbl_bookings b WHERE " . ($bookingId > 0 ? 'b.id = :id' : 'b.waybill_no = :waybill');
    $stmt = $pdo->prepare ( $sql );
    if ($bookingId > 0) {
        $stmt->execute ( [ ':id' => $bookingId ] );
    } else {
        $stmt->execute ( [ ':waybill' => $waybillParam ] );
    }
    $booking = $stmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $booking) {
        throw new Exception( 'Booking not found' );
        }

    $scansStmt = $pdo->prepare ( "SELECT scan_type, scan_location, scan_datetime, status_code, remarks, raw_response FROM tbl_tracking WHERE booking_id = :bid ORDER BY scan_datetime DESC" );
    $scansStmt->execute ( [ ':bid' => $booking[ 'id' ] ] );
    $rows  = $scansStmt->fetchAll ( PDO::FETCH_ASSOC );
    $scans = [];

    // Check if we have history in the JSON of the first record (since we only keep one record per waybill now)
    if ( ! empty ($rows) && ! empty ($rows[ 0 ][ 'raw_response' ])) {
        $decoded = json_decode ( $rows[ 0 ][ 'raw_response' ], true );
        if (isset ($decoded[ 'scan_details_history' ]) && is_array ( $decoded[ 'scan_details_history' ] )) {
            // Use the JSON history array (Sort by date descending)
            $history = $decoded[ 'scan_details_history' ];
            usort ( $history, function ($a, $b)
                {
                return strtotime ( $b[ 'datetime' ] ) - strtotime ( $a[ 'datetime' ] );
                } );

            $seenKeys = [];
            foreach ($history as $s) {
                $st   = trim ( $s[ 'status' ] ?? '' );
                $dt   = $s[ 'datetime' ] ?? '';
                $loc  = trim ( $s[ 'location' ] ?? '' );
                $key  = strtolower ( $st ) . '|' . strtolower ( $loc );
                if (isset ( $seenKeys[ $key ] )) continue;
                $seenKeys[ $key ] = true;
                $scans[] = [
                    'ScanDetail' => [
                        'Scan' => $st,
                        'ScanDateTime' => $dt,
                        'ScanType' => $st,
                        'ScannedLocation' => $loc,
                        'StatusCode' => $st,
                        'Instructions' => $s[ 'remarks' ] ?? ''
                    ]
                ];
                }
            }
        }

    // Fallback: Use database rows if JSON history is empty or not present
    if (empty ($scans)) {
        foreach ($rows as $row) {
            $scans[] = [
                'ScanDetail' => [
                    'Scan' => $row[ 'scan_type' ],
                    'ScanDateTime' => $row[ 'scan_datetime' ],
                    'ScanType' => $row[ 'scan_type' ],
                    'ScannedLocation' => $row[ 'scan_location' ],
                    'StatusCode' => $row[ 'status_code' ],
                    'Instructions' => $row[ 'remarks' ]
                ]
            ];
            }
        }

    $shipment = null;
    if ( ! empty ($booking[ 'api_response' ])) {
        $decoded  = is_string ( $booking[ 'api_response' ] ) ? json_decode ( $booking[ 'api_response' ], true ) : $booking[ 'api_response' ];
        $shipment = $decoded[ 'Shipment' ] ?? $decoded;
        }

    echo json_encode ( [
        'status' => 'success',
        'current_status' => $booking[ 'last_status' ],
        'scans_count' => count ( $scans ),
        'child_awb' => $childAwb,
        'data' => [ 'Shipment' => $shipment, 'Scans' => $scans ]
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
