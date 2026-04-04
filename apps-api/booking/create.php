<?php
/**
 * Booking Create API (No Session)
 * Location: /apps-api/booking/create.php
 *
 * Aligned with api/booking/create.php for Own Courier (ID=2):
 * - branch_id required for Own Courier (serial allocation)
 * - client_id optional
 * - Serial allocation from tbl_serial_numbers; each box gets AWB (child_ewaybill_no)
 * - Initial tracking and serial cleanup for Own Courier
 *
 * Accepts: POST with form data (application/x-www-form-urlencoded or multipart/form-data)
 *          or JSON when Content-Type: application/json
 * - pickup_point_id optional when courier_id = 2 (Own Courier)
 * - Array fields: length[], width[], height[], boxes[], actual_weight[], etc.
 */

header ( 'Content-Type: application/json' );
header ( 'Access-Control-Allow-Origin: *' );
header ( 'Access-Control-Allow-Methods: POST, OPTIONS' );
header ( 'Access-Control-Allow-Headers: Content-Type' );

if ($_SERVER[ 'REQUEST_METHOD' ] === 'OPTIONS') {
    http_response_code ( 204 );
    exit;
    }

require_once __DIR__ . '/../../config/config.php';

function _sanitize ($v)
    {
    return is_string ( $v ) ? trim ( $v ) : (string) $v;
    }

/** Log to dedicated file: apps-api/logs/booking-create.log */
function _booking_create_log ($level, $message, array $context = [])
    {
    $logDir = __DIR__ . '/../logs';
    if ( ! is_dir ( $logDir ))
        @mkdir ( $logDir, 0755, true );
    $logFile = $logDir . '/booking-create.log';
    $ts      = date ( 'Y-m-d H:i:s' );
    $line    = $ts . ' [' . strtoupper ( $level ) . '] ' . $message;
    if ( ! empty ($context))
        $line .= ' ' . json_encode ( $context );
    $line .= "\n";
    @file_put_contents ( $logFile, $line, FILE_APPEND | LOCK_EX );
    }

// Use form data ($_POST) by default; parse JSON only when Content-Type is application/json (same pattern as consignor create)
$contentType = $_SERVER[ 'CONTENT_TYPE' ] ?? '';
if (strpos ( $contentType, 'application/json' ) !== false) {
    $json = file_get_contents ( 'php://input' );
    $data = json_decode ( $json, true ) ?? [];
    } else {
    $data = $_POST;
    }

try {
    $createdBy = isset ($data[ 'user_id' ]) ? (int) $data[ 'user_id' ] : 1;
    if ($createdBy < 1)
        $createdBy = 1;

    $courierId     = isset ($data[ 'courier_id' ]) ? (int) $data[ 'courier_id' ] : 0;
    $pickupPointId = isset ($data[ 'pickup_point_id' ]) ? (int) $data[ 'pickup_point_id' ] : 0;
    $branchId      = isset ($data[ 'branch_id' ]) ? (int) $data[ 'branch_id' ] : null;
    $clientId      = isset ($data[ 'client_id' ]) ? (int) $data[ 'client_id' ] : null;
    $bookingType   = _sanitize ( $data[ 'booking_type' ] ?? 'Forward' );
    if ($bookingType !== 'Reverse')
        $bookingType = 'Forward';
    $bookingRefId = _sanitize ( $data[ 'booking_ref_id' ] ?? '' );
    if ($bookingRefId === '')
        $bookingRefId = 'ORD-' . time ();

    $consigneeName    = _sanitize ( $data[ 'consignee_name' ] ?? '' );
    $consigneePhone   = _sanitize ( $data[ 'consignee_phone' ] ?? '' );
    $consigneeAddress = _sanitize ( $data[ 'consignee_address' ] ?? '' );
    $consigneePin     = _sanitize ( $data[ 'consignee_pin' ] ?? '' );
    $consigneeCity    = _sanitize ( $data[ 'consignee_city' ] ?? '' );
    $consigneeState   = _sanitize ( $data[ 'consignee_state' ] ?? '' );
    $consigneeCountry = _sanitize ( $data[ 'consignee_country' ] ?? 'India' ) ?: 'India';
    $consigneeEmail   = _sanitize ( $data[ 'consignee_email' ] ?? '' );
    $consigneeGst     = _sanitize ( $data[ 'consignee_gst' ] ?? '' );

    $shipperName    = _sanitize ( $data[ 'shipper_name' ] ?? '' );
    $shipperPhone   = _sanitize ( $data[ 'shipper_phone' ] ?? '' );
    $shipperAddress = _sanitize ( $data[ 'shipper_address' ] ?? '' );
    $shipperPin     = _sanitize ( $data[ 'shipper_pin' ] ?? '' );
    $shipperCity    = _sanitize ( $data[ 'shipper_city' ] ?? '' );
    $shipperState   = _sanitize ( $data[ 'shipper_state' ] ?? '' );

    $invoiceNo    = _sanitize ( $data[ 'invoice_no' ] ?? '' );
    $invoiceValue = (float) ($data[ 'invoice_value' ] ?? 0);
    $ewaybillNo   = _sanitize ( $data[ 'ewaybill_no' ] ?? ($data[ 'eway_bill_no' ] ?? '') );
    $rtoName      = _sanitize ( $data[ 'rto_name' ] ?? '' );
    $rtoPhone     = _sanitize ( $data[ 'rto_phone' ] ?? '' );
    $rtoAddress   = _sanitize ( $data[ 'rto_address' ] ?? '' );
    if ($rtoName === '' && $rtoPhone === '' && $rtoAddress === '') {
        $rtoName    = $shipperName;
        $rtoPhone   = $shipperPhone;
        $rtoAddress = $shipperAddress;
        }

    $paymentMode  = _sanitize ( $data[ 'payment_mode' ] ?? 'Prepaid' ) ?: 'Prepaid';
    $codAmount    = (float) ($data[ 'cod_amount' ] ?? 0);
    $shippingMode = _sanitize ( $data[ 'shipping_mode' ] ?? 'Surface' ) ?: 'Surface';
    $productDesc  = _sanitize ( $data[ 'product_desc' ] ?? '' );

    $lengths          = isset ($data[ 'length' ]) ? (array) $data[ 'length' ] : [];
    $widths           = isset ($data[ 'width' ]) ? (array) $data[ 'width' ] : [];
    $heights          = isset ($data[ 'height' ]) ? (array) $data[ 'height' ] : [];
    $boxes            = isset ($data[ 'boxes' ]) ? (array) $data[ 'boxes' ] : [];
    $actualWeights    = isset ($data[ 'actual_weight' ]) ? (array) $data[ 'actual_weight' ] : [];
    $chargedWeights   = isset ($data[ 'charged_weight' ]) ? (array) $data[ 'charged_weight' ] : [];
    $volWeights       = isset ($data[ 'vol_weight' ]) ? (array) $data[ 'vol_weight' ] : (isset ($data[ 'volumetric' ]) ? (array) $data[ 'volumetric' ] : []);
    $pkgAwbNos        = isset ($data[ 'pkg_awb_no' ]) ? (array) $data[ 'pkg_awb_no' ] : [];
    $childEwaybillNos = isset ($data[ 'child_ewaybill_no' ]) ? (array) $data[ 'child_ewaybill_no' ] : (isset ($data[ 'pkg_ewaybill_no' ]) ? (array) $data[ 'pkg_ewaybill_no' ] : []);

    // If client sends package_details (JSON string or array) instead of length[], width[], etc., build arrays from it
    if (empty ($lengths) && ! empty ($data[ 'package_details' ])) {
        $pkgDetailsRaw = $data[ 'package_details' ];
        $pkgs          = is_string ( $pkgDetailsRaw ) ? (json_decode ( $pkgDetailsRaw, true ) ?? []) : (array) $pkgDetailsRaw;
        if ( ! empty ($pkgs)) {
            $lengths        = [];
            $widths         = [];
            $heights        = [];
            $boxes          = [];
            $actualWeights  = [];
            $chargedWeights = [];
            $volWeights     = [];
            $pkgAwbNos      = [];
            foreach ($pkgs as $p) {
                $lengths[]        = $p[ 'length' ] ?? 0;
                $widths[]         = $p[ 'width' ] ?? 0;
                $heights[]        = $p[ 'height' ] ?? 0;
                $boxes[]          = $p[ 'boxes' ] ?? 1;
                $actualWeights[]  = $p[ 'actual_weight' ] ?? 0;
                $chargedWeights[] = $p[ 'charged_weight' ] ?? ($p[ 'vol_weight' ] ?? 0);
                $volWeights[]     = $p[ 'vol_weight' ] ?? 0;
                $pkgAwbNos[]      = $p[ 'awb_no' ] ?? ($p[ 'awb_number' ] ?? '');
                }
            }
        }

    _booking_create_log ( 'info', 'Request', [
        'courier_id' => $courierId,
        'ref' => $bookingRefId,
        'consignee' => substr ( $consigneeName, 0, 30 ),
        'packages' => count ( $lengths )
    ] );

    $missing = [];
    if ($courierId <= 0)
        $missing[] = 'courier_id';
    $isOwnCourier = ($courierId === 2);
    if ( ! $isOwnCourier && $pickupPointId <= 0)
        $missing[] = 'pickup_point_id';
    if ($isOwnCourier && (int) $branchId <= 0)
        $missing[] = 'branch_id (required for Own Courier serial allocation)';
    if ($consigneeName === '')
        $missing[] = 'consignee_name';
    if ($consigneePhone === '')
        $missing[] = 'consignee_phone';
    if ($consigneeAddress === '')
        $missing[] = 'consignee_address';
    if ($consigneePin === '')
        $missing[] = 'consignee_pin';
    if ( ! empty ($missing))
        throw new Exception( 'Missing required fields: ' . implode ( ', ', $missing ) );
    if (strlen ( $consigneePhone ) < 10)
        throw new Exception( 'consignee_phone must be at least 10 digits' );
    if ($paymentMode === 'COD' && $codAmount <= 0)
        throw new Exception( 'cod_amount is required when payment_mode is COD' );
    if (empty ($lengths))
        throw new Exception( 'Package details are missing: provide length, width, height, boxes, actual_weight (arrays)' );
    $rowCount = count ( $lengths );
    if ($rowCount !== count ( $widths ) || $rowCount !== count ( $heights ))
        throw new Exception( 'Package arrays length, width, height must have same count' );

    $packageDetails     = [];
    $totalActualWeight  = 0.0;
    $totalChargedWeight = 0.0;
    $totalBoxes         = 0;
    $maxL               = 0.0;
    $maxW               = 0.0;
    $maxH               = 0.0;

    foreach ($lengths as $i => $len) {
        $qty   = max ( 1, (int) ($boxes[$i] ?? 1) );
        $actWt = max ( 0, (float) ($actualWeights[$i] ?? 0) );
        $chgWt = max ( 0, (float) ($chargedWeights[$i] ?? 0) );
        $l     = (float) $len;
        $w     = (float) ($widths[$i] ?? 0);
        $h     = (float) ($heights[$i] ?? 0);
        if ($l <= 0 || $w <= 0 || $h <= 0)
            throw new Exception( 'Package row ' . ($i + 1) . ': length, width, height must be > 0' );
        if ($actWt <= 0)
            throw new Exception( 'Package row ' . ($i + 1) . ': actual_weight must be > 0' );
        $volWt = (float) ($volWeights[$i] ?? 0);
        if ($volWt <= 0)
            $volWt = round ( ($l * $w * $h) / 5000, 3 );
        if ($chgWt <= 0)
            $chgWt = max ( $actWt, $volWt );
        $totalBoxes         += $qty;
        $totalActualWeight  += ($actWt * $qty);
        $totalChargedWeight += $chgWt;
        $pkgAwb              = isset ($pkgAwbNos[$i]) ? _sanitize ( $pkgAwbNos[$i] ) : '';
        $pkgEway             = isset ($childEwaybillNos[$i]) ? _sanitize ( $childEwaybillNos[$i] ) : '';
        $packageDetails[]    = [
            'awb_no' => $pkgAwb,
            'child_ewaybill_no' => $pkgEway !== '' ? $pkgEway : null,
            'length' => $l,
            'width' => $w,
            'height' => $h,
            'boxes' => $qty,
            'actual_weight' => $actWt,
            'vol_weight' => $volWt,
            'charged_weight' => $chgWt
        ];
        $maxL                = max ( $maxL, $l );
        $maxW                = max ( $maxW, $w );
        $maxH                = max ( $maxH, $h );
        }

    if ($totalBoxes <= 0)
        throw new Exception( 'At least one valid package is required' );

    $packageDetailsJson = json_encode ( $packageDetails );
    $weightGrams        = $totalActualWeight > 0 ? round ( $totalActualWeight * 1000, 2 ) : round ( $totalChargedWeight * 1000, 2 );

    $courierStmt = $pdo->prepare ( "SELECT id, partner_name, partner_code, api_key, api_url FROM tbl_courier_partner WHERE id = :id" );
    $courierStmt->execute ( [ ':id' => $courierId ] );
    $courierData = $courierStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $courierData)
        throw new Exception( 'Invalid Courier Partner' );

    // ─── Own Courier (ID=2): merge, expand, serial allocation (aligned with api/booking/create.php) ───
    $preferredWaybill = null;
    if ($courierId == 2) {
        // Merge consecutive rows where current has empty AWB into previous row
        $merged = [];
        foreach ($packageDetails as $row) {
            $awb  = trim ( (string) ($row[ 'awb_no' ] ?? '') );
            $prev = $merged ? $merged[count ( $merged ) - 1] : null;
            if ($prev !== null && $awb === '') {
                $prevIdx                              = count ( $merged ) - 1;
                $qtyPrev                              = max ( 1, (int) ($merged[$prevIdx][ 'boxes' ] ?? 1) );
                $qtyCur                               = max ( 1, (int) ($row[ 'boxes' ] ?? 1) );
                $merged[$prevIdx][ 'boxes' ]          = $qtyPrev + $qtyCur;
                $actPrev                              = (float) ($merged[$prevIdx][ 'actual_weight' ] ?? 0);
                $actCur                               = (float) ($row[ 'actual_weight' ] ?? 0);
                $merged[$prevIdx][ 'actual_weight' ]  = ($actPrev * $qtyPrev + $actCur * $qtyCur) / max ( 1, $qtyPrev + $qtyCur );
                $volPrev                              = (float) ($merged[$prevIdx][ 'vol_weight' ] ?? 0);
                $volCur                               = (float) ($row[ 'vol_weight' ] ?? 0);
                $merged[$prevIdx][ 'vol_weight' ]     = ($volPrev * $qtyPrev + $volCur * $qtyCur) / max ( 1, $qtyPrev + $qtyCur );
                $chgPrev                              = (float) ($merged[$prevIdx][ 'charged_weight' ] ?? 0);
                $chgCur                               = (float) ($row[ 'charged_weight' ] ?? 0);
                $merged[$prevIdx][ 'charged_weight' ] = ($chgPrev * $qtyPrev + $chgCur * $qtyCur) / max ( 1, $qtyPrev + $qtyCur );
                continue;
                }
            $merged[] = $row;
            }
        $packageDetails     = $merged;
        $totalBoxes         = 0;
        $totalActualWeight  = 0.0;
        $totalChargedWeight = 0.0;
        foreach ($packageDetails as $row) {
            $q                   = max ( 1, (int) ($row[ 'boxes' ] ?? 1) );
            $totalBoxes         += $q;
            $totalActualWeight  += (float) ($row[ 'actual_weight' ] ?? 0) * $q;
            $totalChargedWeight += (float) ($row[ 'charged_weight' ] ?? 0) * $q;
            }

        // Expand each row with boxes > 1 into one entry per physical box
        $expanded = [];
        foreach ($packageDetails as $rowIndex => $row) {
            $qty     = (int) $row[ 'boxes' ];
            $baseAwb = trim ( (string) ($row[ 'awb_no' ] ?? '') );
            for ($k = 0; $k < $qty; $k++) {
                $expanded[] = [
                    'awb_no' => ($k === 0) ? $baseAwb : '',
                    'child_ewaybill_no' => ($k === 0 && ($row[ 'child_ewaybill_no' ] ?? null) !== null) ? $row[ 'child_ewaybill_no' ] : null,
                    'length' => $row[ 'length' ],
                    'width' => $row[ 'width' ],
                    'height' => $row[ 'height' ],
                    'boxes' => 1,
                    'actual_weight' => $row[ 'actual_weight' ],
                    'vol_weight' => round ( $row[ 'vol_weight' ] / max ( 1, $qty ), 3 ),
                    'charged_weight' => round ( $row[ 'charged_weight' ] / max ( 1, $qty ), 3 ),
                    '_group_id' => $rowIndex,
                    '_box_index_in_group' => $k,
                    '_is_child' => ($k > 0),
                ];
                }
            }
        $packageDetails = $expanded;

        // Reject duplicate AWBs across base boxes
        $awbToBoxes = [];
        foreach ($packageDetails as $idx => $pkg) {
            if ( ! empty ($pkg[ '_is_child' ]))
                continue;
            $awbVal = trim ( (string) ($pkg[ 'awb_no' ] ?? '') );
            if ($awbVal === '')
                continue;
            $key = strtolower ( $awbVal );
            if ( ! isset ($awbToBoxes[$key]))
                $awbToBoxes[$key] = [];
            $awbToBoxes[$key][] = $idx + 1;
            }
        foreach ($awbToBoxes as $awb => $boxNums) {
            if (count ( $boxNums ) > 1) {
                throw new Exception( 'Same AWB/Serial cannot be used in more than one box (boxes: ' . implode ( ', ', $boxNums ) . '). Use unique serial per box or leave empty.' );
                }
            }

        $firstAwb     = isset ($packageDetails[ 0 ][ 'awb_no' ]) ? trim ( (string) $packageDetails[ 0 ][ 'awb_no' ] ) : '';
        $isSurface    = (strtolower ( $shippingMode ) === 'surface');
        $serviceTypes = $isSurface ? [ 'surface' ] : [ 'express', 'air' ];

        if ($firstAwb !== '') {
            $serStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
            $serStmt->execute ( [ ':sn' => $firstAwb ] );
            $ser = $serStmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $ser)
                throw new Exception( 'Invalid or already used AWB/Serial. Use a valid available serial or leave empty to assign from allocation.' );
            if ($branchId > 0 && (int) $ser[ 'branch_id' ] !== $branchId)
                throw new Exception( 'AWB/Serial does not belong to selected branch.' );
            $st                 = strtolower ( $ser[ 'service_type' ] ?? '' );
            $serialIsAirExpress = in_array ( $st, [ 'express', 'air' ], true );
            $serialIsSurface    = ($st === 'surface');
            $match              = ($isSurface && $serialIsSurface) || ( ! $isSurface && $serialIsAirExpress);
            if ( ! $match) {
                $serialFor   = $serialIsAirExpress ? 'Air/Express' : 'Surface';
                $youSelected = $isSurface ? 'Surface' : 'Air/Express';
                throw new Exception( "Shipping mode mismatch. Serial is for {$serialFor}; you selected {$youSelected}." );
                }
            $preferredWaybill = $firstAwb;
            } else {
            if ($branchId > 0) {
                $ph       = implode ( ',', array_fill ( 0, count ( $serviceTypes ), '?' ) );
                $nextStmt = $pdo->prepare ( "SELECT serial_number FROM tbl_serial_numbers WHERE branch_id = ? AND service_type IN ($ph) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled') ORDER BY serial_number ASC LIMIT 1" );
                $nextStmt->execute ( array_merge ( [ $branchId ], $serviceTypes ) );
                $nextRow = $nextStmt->fetch ( PDO::FETCH_ASSOC );
                if ($nextRow)
                    $preferredWaybill = $nextRow[ 'serial_number' ];
                }
            }

        // Validate every base box AWB
        foreach ($packageDetails as $idx => $pkg) {
            if ( ! empty ($pkg[ '_is_child' ]))
                continue;
            $awbVal = trim ( (string) ($pkg[ 'awb_no' ] ?? '') );
            if ($awbVal === '' || strtolower ( $awbVal ) === strtolower ( $firstAwb ))
                continue;
            $serStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
            $serStmt->execute ( [ ':sn' => $awbVal ] );
            $ser = $serStmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $ser)
                throw new Exception( "Serial \"{$awbVal}\" is not allocated or already used (box " . ($idx + 1) . ")." );
            if ($branchId > 0 && (int) $ser[ 'branch_id' ] !== $branchId)
                throw new Exception( "Serial \"{$awbVal}\" does not belong to selected branch (box " . ($idx + 1) . ")." );
            }

        if ($firstAwb === '' && ($preferredWaybill === null || $preferredWaybill === '')) {
            throw new Exception( 'No serials in allocation for this branch and shipping mode. Add serials or enter AWB for the box.' );
            }

        // Assign serials to empty base boxes only (skip child boxes)
        $emptyCount = 0;
        foreach ($packageDetails as $p) {
            if ( ! empty ($p[ '_is_child' ]))
                continue;
            if (trim ( (string) ($p[ 'awb_no' ] ?? '') ) === '')
                $emptyCount++;
            }
        if ($emptyCount > 0) {
            $firstRowEmpty = trim ( (string) ($packageDetails[ 0 ][ 'awb_no' ] ?? '') ) === '';
            if ($firstRowEmpty && ($preferredWaybill === null || $preferredWaybill === '')) {
                throw new Exception( 'No serials in allocation for this branch and shipping mode.' );
                }
            $needToFetch = $emptyCount;
            if ($firstRowEmpty && $preferredWaybill !== null && $preferredWaybill !== '') {
                $packageDetails[ 0 ][ 'child_ewaybill_no' ] = $preferredWaybill;
                $needToFetch                                = $emptyCount - 1;
                }
            if ($needToFetch > 0) {
                $ph        = implode ( ',', array_fill ( 0, count ( $serviceTypes ), '?' ) );
                $excludeSn = ($firstRowEmpty && $preferredWaybill !== null && $preferredWaybill !== '') ? trim ( $preferredWaybill ) : '';
                if ($excludeSn !== '') {
                    $nextStmt = $pdo->prepare ( "SELECT serial_number FROM tbl_serial_numbers WHERE branch_id = ? AND service_type IN ($ph) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled') AND LOWER(TRIM(serial_number)) != LOWER(TRIM(?)) ORDER BY serial_number ASC LIMIT " . (int) $needToFetch );
                    $nextStmt->execute ( array_merge ( [ $branchId ], $serviceTypes, [ $excludeSn ] ) );
                    } else {
                    $nextStmt = $pdo->prepare ( "SELECT serial_number FROM tbl_serial_numbers WHERE branch_id = ? AND service_type IN ($ph) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled') ORDER BY serial_number ASC LIMIT " . (int) $needToFetch );
                    $nextStmt->execute ( array_merge ( [ $branchId ], $serviceTypes ) );
                    }
                $nextSerials = $nextStmt->fetchAll ( PDO::FETCH_COLUMN );
                if (count ( $nextSerials ) < $needToFetch) {
                    throw new Exception( 'Not enough serials in allocation. Need ' . $needToFetch . ' more for empty box(es); only ' . count ( $nextSerials ) . ' available.' );
                    }
                $si = 0;
                foreach ($packageDetails as $idx => &$p) {
                    if ( ! empty ($p[ '_is_child' ]))
                        continue;
                    if (trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '') {
                        $p[ 'child_ewaybill_no' ] = $p[ 'awb_no' ];
                        continue;
                        }
                    if (isset ($p[ 'child_ewaybill_no' ]) && $p[ 'child_ewaybill_no' ] !== null && $p[ 'child_ewaybill_no' ] !== '')
                        continue;
                    $p[ 'child_ewaybill_no' ] = $nextSerials[$si++];
                    }
                unset ( $p );
                }
            foreach ($packageDetails as $idx => &$p) {
                if ( ! empty ($p[ '_is_child' ]))
                    continue;
                if ( ! isset ($p[ 'child_ewaybill_no' ]) || $p[ 'child_ewaybill_no' ] === null || $p[ 'child_ewaybill_no' ] === '') {
                    if (trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '')
                        $p[ 'child_ewaybill_no' ] = $p[ 'awb_no' ];
                    }
                }
            unset ( $p );
            $preferredWaybill = trim ( (string) ($packageDetails[ 0 ][ 'child_ewaybill_no' ] ?? $packageDetails[ 0 ][ 'awb_no' ] ?? '') ) ?: $preferredWaybill;
            }
        // Ensure every base package has child_ewaybill_no
        foreach ($packageDetails as $idx => &$p) {
            if ( ! empty ($p[ '_is_child' ]))
                continue;
            if ( ! isset ($p[ 'child_ewaybill_no' ]) || $p[ 'child_ewaybill_no' ] === null || $p[ 'child_ewaybill_no' ] === '') {
                $p[ 'child_ewaybill_no' ] = trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '' ? $p[ 'awb_no' ] : null;
                }
            }
        unset ( $p );
        // Derive child box AWBs: base-1, base-2, ...
        $groupBases = [];
        foreach ($packageDetails as $idx => $p) {
            $gid    = (int) ($p[ '_group_id' ] ?? $idx);
            $boxIdx = (int) ($p[ '_box_index_in_group' ] ?? 0);
            if ($boxIdx === 0) {
                $groupBases[$gid] = trim ( (string) ($p[ 'child_ewaybill_no' ] ?? $p[ 'awb_no' ] ?? '') );
                }
            }
        foreach ($packageDetails as $idx => &$p) {
            if (empty ($p[ '_is_child' ]))
                continue;
            $gid    = (int) ($p[ '_group_id' ] ?? $idx);
            $base   = $groupBases[$gid] ?? '';
            $suffix = (int) ($p[ '_box_index_in_group' ] ?? 0);
            if ($base !== '' && $suffix >= 1) {
                $p[ 'child_ewaybill_no' ] = $base . '-' . $suffix;
                $p[ 'awb_no' ]            = $base . '-' . $suffix;
                }
            }
        unset ( $p );
        $preferredWaybill = trim ( (string) ($packageDetails[ 0 ][ 'child_ewaybill_no' ] ?? $packageDetails[ 0 ][ 'awb_no' ] ?? '') ) ?: $preferredWaybill;
        // Build clean JSON for tbl_bookings (strip internal keys)
        $forJson            = array_map ( function ($p)
            {
            return array_diff_key ( $p, array_flip ( [ '_group_id', '_box_index_in_group', '_is_child' ] ) );
            }, $packageDetails );
        $packageDetailsJson = json_encode ( $forJson );
        }

    $pickupPoint = null;
    if ($courierId != 2 && $pickupPointId > 0) {
        $pickupStmt = $pdo->prepare ( "SELECT id, name FROM tbl_pickup_points WHERE id = :id" );
        $pickupStmt->execute ( [ ':id' => $pickupPointId ] );
        $pickupPoint = $pickupStmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $pickupPoint)
            throw new Exception( 'Invalid Pickup Point ID' );
        } elseif ($courierId == 2 && $pickupPointId > 0) {
        $pickupStmt = $pdo->prepare ( "SELECT id, name FROM tbl_pickup_points WHERE id = :id LIMIT 1" );
        $pickupStmt->execute ( [ ':id' => $pickupPointId ] );
        $pickupPoint = $pickupStmt->fetch ( PDO::FETCH_ASSOC );
        }

    $shipmentDataForSync = [
        'booking_ref_id' => $bookingRefId,
        'consignee_name' => $consigneeName,
        'consignee_phone' => $consigneePhone,
        'consignee_address' => $consigneeAddress,
        'consignee_pin' => $consigneePin,
        'consignee_city' => $consigneeCity,
        'consignee_state' => $consigneeState,
        'consignee_country' => $consigneeCountry,
        'payment_mode' => $paymentMode,
        'cod_amount' => $codAmount,
        'product_desc' => $productDesc,
        'quantity' => $totalBoxes,
        'weight' => $weightGrams,
        'length' => $maxL,
        'width' => $maxW,
        'height' => $maxH,
        'shipping_mode' => $shippingMode,
        'pickup_location_name' => $pickupPoint ? $pickupPoint[ 'name' ] : '',
        'invoice_no' => $invoiceNo,
        'invoice_date' => date ( 'Y-m-d' ),
        'invoice_value' => $invoiceValue,
        'ewaybill_no' => $ewaybillNo,
        'package_details' => $packageDetails
    ];
    if ($preferredWaybill !== null)
        $shipmentDataForSync[ 'preferred_waybill' ] = $preferredWaybill;

    require_once __DIR__ . '/../../api/booking/services/courier_service.php';
    $syncResult = syncBookingWithCourier ( $pdo, $courierData, $shipmentDataForSync );

    if (empty ($syncResult[ 'success' ]))
        throw new Exception( $syncResult[ 'message' ] ?? 'Courier sync failed' );

    $waybillNo       = $syncResult[ 'waybill' ] ?? null;
    $apiResponseJson = json_encode ( $syncResult[ 'api_response' ] ?? [] );

    $hasBranchClient = false;
    $hasBookingType  = false;
    try {
        $pdo->query ( "SELECT branch_id, client_id, booking_type FROM tbl_bookings LIMIT 1" );
        $hasBranchClient = true;
        $hasBookingType  = true;
        }
    catch ( Exception $e ) {
        try {
            $pdo->query ( "SELECT branch_id, client_id FROM tbl_bookings LIMIT 1" );
            $hasBranchClient = true;
            }
        catch ( Exception $e2 ) {
            }
        }

    if ($hasBranchClient && $hasBookingType) {
        $sql  = "INSERT INTO tbl_bookings (
            booking_ref_id, waybill_no, courier_id, pickup_point_id, branch_id, client_id, booking_type,
            consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
            consignee_city, consignee_state, consignee_country,
            shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
            payment_mode, cod_amount, weight, length, width, height,
            shipping_mode, product_desc, package_details, quantity, is_mps,
            invoice_no, invoice_value, ewaybill_no,
            rto_name, rto_phone, rto_address,
            api_response, last_status, created_by, created_at
        ) VALUES (
            :ref_id, :waybill, :c_id, :p_id, :branch_id, :client_id, :booking_type,
            :c_name, :c_phone, :c_email, :c_gst, :c_add, :c_pin,
            :c_city, :c_state, :c_country,
            :s_name, :s_phone, :s_add, :s_pin, :s_city, :s_state,
            :pay_mode, :cod, :w, :l, :wi, :h,
            :ship_mode, :prod_desc, :pkg_details, :qty, :mps,
            :inv_no, :inv_val, :eway,
            :rto_name, :rto_phone, :rto_address,
            :api_resp, 'Created', :user_id, NOW()
        )";
        $stmt = $pdo->prepare ( $sql );
        $stmt->execute ( [
            ':ref_id' => $bookingRefId,
            ':waybill' => $waybillNo,
            ':c_id' => $courierId,
            ':p_id' => $pickupPointId ?: null,
            ':branch_id' => $branchId > 0 ? $branchId : null,
            ':client_id' => $clientId > 0 ? $clientId : null,
            ':booking_type' => $bookingType,
            ':c_name' => $consigneeName,
            ':c_phone' => $consigneePhone,
            ':c_email' => $consigneeEmail,
            ':c_gst' => $consigneeGst,
            ':c_add' => $consigneeAddress,
            ':c_pin' => $consigneePin,
            ':c_city' => $consigneeCity,
            ':c_state' => $consigneeState,
            ':c_country' => $consigneeCountry,
            ':s_name' => $shipperName,
            ':s_phone' => $shipperPhone,
            ':s_add' => $shipperAddress,
            ':s_pin' => $shipperPin,
            ':s_city' => $shipperCity,
            ':s_state' => $shipperState,
            ':pay_mode' => $paymentMode,
            ':cod' => $codAmount,
            ':w' => $weightGrams,
            ':l' => $maxL,
            ':wi' => $maxW,
            ':h' => $maxH,
            ':ship_mode' => $shippingMode,
            ':prod_desc' => $productDesc,
            ':pkg_details' => $packageDetailsJson,
            ':qty' => $totalBoxes,
            ':mps' => ($totalBoxes > 1 ? 1 : 0),
            ':inv_no' => $invoiceNo,
            ':inv_val' => $invoiceValue,
            ':eway' => $ewaybillNo,
            ':rto_name' => $rtoName,
            ':rto_phone' => $rtoPhone,
            ':rto_address' => $rtoAddress,
            ':api_resp' => $apiResponseJson,
            ':user_id' => $createdBy
        ] );
        } elseif ($hasBranchClient) {
        $sql  = "INSERT INTO tbl_bookings (
            booking_ref_id, waybill_no, courier_id, pickup_point_id, branch_id, client_id,
            consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
            consignee_city, consignee_state, consignee_country,
            shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
            payment_mode, cod_amount, weight, length, width, height,
            shipping_mode, product_desc, package_details, quantity, is_mps,
            invoice_no, invoice_value, ewaybill_no,
            rto_name, rto_phone, rto_address,
            api_response, last_status, created_by, created_at
        ) VALUES (
            :ref_id, :waybill, :c_id, :p_id, :branch_id, :client_id,
            :c_name, :c_phone, :c_email, :c_gst, :c_add, :c_pin,
            :c_city, :c_state, :c_country,
            :s_name, :s_phone, :s_add, :s_pin, :s_city, :s_state,
            :pay_mode, :cod, :w, :l, :wi, :h,
            :ship_mode, :prod_desc, :pkg_details, :qty, :mps,
            :inv_no, :inv_val, :eway,
            :rto_name, :rto_phone, :rto_address,
            :api_resp, 'Created', :user_id, NOW()
        )";
        $stmt = $pdo->prepare ( $sql );
        $stmt->execute ( [
            ':ref_id' => $bookingRefId,
            ':waybill' => $waybillNo,
            ':c_id' => $courierId,
            ':p_id' => $pickupPointId ?: null,
            ':branch_id' => $branchId > 0 ? $branchId : null,
            ':client_id' => $clientId > 0 ? $clientId : null,
            ':c_name' => $consigneeName,
            ':c_phone' => $consigneePhone,
            ':c_email' => $consigneeEmail,
            ':c_gst' => $consigneeGst,
            ':c_add' => $consigneeAddress,
            ':c_pin' => $consigneePin,
            ':c_city' => $consigneeCity,
            ':c_state' => $consigneeState,
            ':c_country' => $consigneeCountry,
            ':s_name' => $shipperName,
            ':s_phone' => $shipperPhone,
            ':s_add' => $shipperAddress,
            ':s_pin' => $shipperPin,
            ':s_city' => $shipperCity,
            ':s_state' => $shipperState,
            ':pay_mode' => $paymentMode,
            ':cod' => $codAmount,
            ':w' => $weightGrams,
            ':l' => $maxL,
            ':wi' => $maxW,
            ':h' => $maxH,
            ':ship_mode' => $shippingMode,
            ':prod_desc' => $productDesc,
            ':pkg_details' => $packageDetailsJson,
            ':qty' => $totalBoxes,
            ':mps' => ($totalBoxes > 1 ? 1 : 0),
            ':inv_no' => $invoiceNo,
            ':inv_val' => $invoiceValue,
            ':eway' => $ewaybillNo,
            ':rto_name' => $rtoName,
            ':rto_phone' => $rtoPhone,
            ':rto_address' => $rtoAddress,
            ':api_resp' => $apiResponseJson,
            ':user_id' => $createdBy
        ] );
        } else {
        $sql  = "INSERT INTO tbl_bookings (
            booking_ref_id, waybill_no, courier_id, pickup_point_id,
            consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
            consignee_city, consignee_state, consignee_country,
            shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
            payment_mode, cod_amount, weight, length, width, height,
            shipping_mode, product_desc, package_details, quantity, is_mps,
            invoice_no, invoice_value, ewaybill_no,
            rto_name, rto_phone, rto_address,
            api_response, last_status, created_by, created_at
        ) VALUES (
            :ref_id, :waybill, :c_id, :p_id,
            :c_name, :c_phone, :c_email, :c_gst, :c_add, :c_pin,
            :c_city, :c_state, :c_country,
            :s_name, :s_phone, :s_add, :s_pin, :s_city, :s_state,
            :pay_mode, :cod, :w, :l, :wi, :h,
            :ship_mode, :prod_desc, :pkg_details, :qty, :mps,
            :inv_no, :inv_val, :eway,
            :rto_name, :rto_phone, :rto_address,
            :api_resp, 'Created', :user_id, NOW()
        )";
        $stmt = $pdo->prepare ( $sql );
        $stmt->execute ( [
            ':ref_id' => $bookingRefId,
            ':waybill' => $waybillNo,
            ':c_id' => $courierId,
            ':p_id' => $pickupPointId ?: null,
            ':c_name' => $consigneeName,
            ':c_phone' => $consigneePhone,
            ':c_email' => $consigneeEmail,
            ':c_gst' => $consigneeGst,
            ':c_add' => $consigneeAddress,
            ':c_pin' => $consigneePin,
            ':c_city' => $consigneeCity,
            ':c_state' => $consigneeState,
            ':c_country' => $consigneeCountry,
            ':s_name' => $shipperName,
            ':s_phone' => $shipperPhone,
            ':s_add' => $shipperAddress,
            ':s_pin' => $shipperPin,
            ':s_city' => $shipperCity,
            ':s_state' => $shipperState,
            ':pay_mode' => $paymentMode,
            ':cod' => $codAmount,
            ':w' => $weightGrams,
            ':l' => $maxL,
            ':wi' => $maxW,
            ':h' => $maxH,
            ':ship_mode' => $shippingMode,
            ':prod_desc' => $productDesc,
            ':pkg_details' => $packageDetailsJson,
            ':qty' => $totalBoxes,
            ':mps' => ($totalBoxes > 1 ? 1 : 0),
            ':inv_no' => $invoiceNo,
            ':inv_val' => $invoiceValue,
            ':eway' => $ewaybillNo,
            ':rto_name' => $rtoName,
            ':rto_phone' => $rtoPhone,
            ':rto_address' => $rtoAddress,
            ':api_resp' => $apiResponseJson,
            ':user_id' => $createdBy
        ] );
        }

    $bookingId = $pdo->lastInsertId ();

    if ($courierId == 2) {
        $serDelStmt = $pdo->prepare ( "SELECT id, allocation_id FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn))" );
        foreach ($packageDetails as $pkg) {
            if ( ! empty ($pkg[ '_is_child' ]))
                continue; // skip derived child AWBs
            $sn = trim ( (string) ($pkg[ 'child_ewaybill_no' ] ?? '') );
            if ($sn === '')
                continue;
            try {
                $serDelStmt->execute ( [ ':sn' => $sn ] );
                $serialRow = $serDelStmt->fetch ( PDO::FETCH_ASSOC );
                if ($serialRow) {
                    $pdo->prepare ( "DELETE FROM tbl_serial_numbers WHERE id = :id" )->execute ( [ ':id' => $serialRow[ 'id' ] ] );
                    $pdo->prepare ( "UPDATE tbl_serial_allocation SET total_serials = total_serials - 1, used_serials = GREATEST(0, used_serials - 1) WHERE id = :aid" )->execute ( [ ':aid' => $serialRow[ 'allocation_id' ] ] );
                    }
                }
            catch ( Exception $e ) {
                }
            }
        }

    // Insert per-box rows into tbl_booking_packages
    // If a row has boxes > 1, expand it: each physical box gets its own row with same dimensions
    // Sub-box AWBs: box1 = parent AWB, box2 = parent-1, box3 = parent-2, etc. (same as bulk_upload.php)
    $pkgStmt     = $pdo->prepare ( "INSERT INTO tbl_booking_packages (booking_id, waybill_no, row_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight) VALUES (:bid, :wn, :row_no, :awb_no, :child_eway, :len, :wid, :hei, :boxes, :act_wt, :vol_wt, :chg_wt)" );
    $globalRowNo = 0;
    foreach ($packageDetails as $idx => $pkg) {
        $pkgBoxCount = max ( 1, (int) ($pkg[ 'boxes' ] ?? 1) );
        // Determine the base AWB for this package row
        if ($courierId == 2 && ! empty ($waybillNo)) {
            $ce      = isset ($pkg[ 'child_ewaybill_no' ]) ? trim ( (string) $pkg[ 'child_ewaybill_no' ] ) : '';
            $baseAwb = $ce !== '' ? $ce : (($idx === 0) ? $waybillNo : $waybillNo . '-' . $idx);
            } else {
            $baseAwb = $pkg[ 'awb_no' ] ?? '';
            }
        // Expand into one DB row per physical box
        for ($b = 0; $b < $pkgBoxCount; $b++) {
            $globalRowNo++;
            if ($b === 0) {
                $rowAwb    = $baseAwb;
                $childEway = null;
                if ($courierId == 2 && $rowAwb !== '')
                    $childEway = $rowAwb;
                elseif ($courierId != 2 && isset ($pkg[ 'child_ewaybill_no' ]) && $pkg[ 'child_ewaybill_no' ] !== '' && $pkg[ 'child_ewaybill_no' ] !== null)
                    $childEway = $pkg[ 'child_ewaybill_no' ];
                } else {
                // Sub-box: derived AWB = baseAwb-N (not from serial pool)
                $rowAwb    = ($baseAwb !== '') ? $baseAwb . '-' . $b : '';
                $childEway = ($rowAwb !== '') ? $rowAwb : null;
                }
            $pkgStmt->execute ( [
                ':bid' => $bookingId,
                ':wn' => $waybillNo,
                ':row_no' => $globalRowNo,
                ':awb_no' => $rowAwb,
                ':child_eway' => $childEway,
                ':len' => $pkg[ 'length' ],
                ':wid' => $pkg[ 'width' ],
                ':hei' => $pkg[ 'height' ],
                ':boxes' => 1,
                ':act_wt' => $pkg[ 'actual_weight' ],
                ':vol_wt' => $pkg[ 'vol_weight' ],
                ':chg_wt' => $pkg[ 'charged_weight' ]
            ] );
            }
        }

    // Sync tbl_bookings from packages: weight in grams, charged_weight in kg
    try {
        $updWeight = $pdo->prepare ( "UPDATE tbl_bookings SET
            weight = (SELECT COALESCE(NULLIF(SUM(actual_weight), 0) * 1000, NULLIF(SUM(charged_weight), 0) * 1000, 0) FROM tbl_booking_packages WHERE booking_id = tbl_bookings.id),
            charged_weight = (SELECT COALESCE(SUM(charged_weight), 0) FROM tbl_booking_packages WHERE booking_id = tbl_bookings.id)
        WHERE id = :bid" );
        $updWeight->execute ( [ ':bid' => $bookingId ] );
        }
    catch ( Exception $e ) {
        // charged_weight column may not exist yet; update weight only
        $updWeight = $pdo->prepare ( "UPDATE tbl_bookings SET weight = (
            SELECT COALESCE(NULLIF(SUM(actual_weight), 0) * 1000, NULLIF(SUM(charged_weight), 0) * 1000, 0) FROM tbl_booking_packages WHERE booking_id = tbl_bookings.id
        ) WHERE id = :bid" );
        $updWeight->execute ( [ ':bid' => $bookingId ] );
        }

    if ($courierId == 2 && ! empty ($waybillNo)) {
        $initialTrackingRaw = json_encode ( [
            'awb_no' => $waybillNo,
            'shipment_details' => [ 'booking_ref_id' => $bookingRefId, 'consignee_name' => $consigneeName, 'consignee_phone' => $consigneePhone, 'payment_mode' => $paymentMode, 'cod_amount' => $codAmount ],
            'current_status' => 'Created',
            'scan_details' => [ 'status' => 'Booking Created', 'location' => $shipperCity, 'datetime' => date ( 'Y-m-d H:i:s' ), 'remarks' => 'Shipment created via API' ],
            'scan_details_history' => [ [ 'status' => 'Booking Created', 'location' => $shipperCity, 'datetime' => date ( 'Y-m-d H:i:s' ), 'remarks' => 'Shipment created via API', 'updated_by' => $createdBy, 'updated_at' => date ( 'Y-m-d H:i:s' ) ] ]
        ] );
        $trackSql           = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_location, scan_datetime, remarks, raw_response) VALUES (:bid, :wn, 'Booking Created', 'Created', :sl, NOW(), 'Shipment created locally', :raw)";
        $pdo->prepare ( $trackSql )->execute ( [ ':bid' => $bookingId, ':wn' => $waybillNo, ':sl' => $shipperCity, ':raw' => $initialTrackingRaw ] );
        } elseif ( ! empty ($waybillNo)) {
        $trackSql = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_datetime, remarks) VALUES (:bid, :wn, 'Booking Created', 'Created', NOW(), 'Shipment created and synced with carrier')";
        $pdo->prepare ( $trackSql )->execute ( [ ':bid' => $bookingId, ':wn' => $waybillNo ] );
        }

    $message = ! empty ($syncResult[ 'synced' ]) ? 'Shipment Booked!' : 'Shipment saved locally (courier sync not configured)';
    _booking_create_log ( 'info', 'Success', [ 'waybill' => $waybillNo, 'booking_id' => (int) $bookingId, 'ref' => $bookingRefId ] );
    echo json_encode ( [
        'status' => 'success',
        'message' => $message,
        'waybill' => $waybillNo,
        'booking_id' => (int) $bookingId,
        'synced' => ! empty ($syncResult[ 'synced' ])
    ] );

    }
catch ( Exception $e ) {
    _booking_create_log ( 'error', $e->getMessage (), [ 'ref' => $bookingRefId ?? ($data[ 'booking_ref_id' ] ?? '') ] );
    http_response_code ( 200 );
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
