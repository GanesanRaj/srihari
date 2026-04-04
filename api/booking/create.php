<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';
require_once __DIR__ . '/services/delhivery.php';

// require_api_permission('shipment', 'is_add');

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid request method' ] );
    exit;
    }

try {
    $ewbThreshold = 5000.0;
    $currentUser = get_current_user_info ();
    $createdBy   = $currentUser[ 'id' ] ?? ($_SESSION[ 'user_id' ] ?? 1);

    // Required base fields
    $courierId     = isset ($_POST[ 'courier_id' ]) ? (int) $_POST[ 'courier_id' ] : 0;
    $pickupPointId = isset ($_POST[ 'pickup_point_id' ]) ? (int) $_POST[ 'pickup_point_id' ] : 0;
    $branchId      = isset ($_POST[ 'branch_id' ]) ? (int) $_POST[ 'branch_id' ] : null;
    $clientId      = isset ($_POST[ 'client_id' ]) ? (int) $_POST[ 'client_id' ] : null;
    $bookingType   = sanitizeText ( $_POST[ 'booking_type' ] ?? 'Forward' );
    if ($bookingType !== 'Reverse')
        $bookingType = 'Forward';
    $bookingRefId = trim ( (string) ($_POST[ 'booking_ref_id' ] ?? '') );
    if ($bookingRefId === '')
        $bookingRefId = 'ORD-' . time ();

    // Consignee
    $consigneeName    = sanitizeText ( $_POST[ 'consignee_name' ] ?? '' );
    $consigneePhone   = sanitizeText ( $_POST[ 'consignee_phone' ] ?? '' );
    $consigneeAddress = sanitizeText ( $_POST[ 'consignee_address' ] ?? '' );
    $consigneePin     = sanitizeText ( $_POST[ 'consignee_pin' ] ?? '' );
    $consigneeCity    = sanitizeText ( $_POST[ 'consignee_city' ] ?? '' );
    $consigneeState   = sanitizeText ( $_POST[ 'consignee_state' ] ?? '' );
    $consigneeCountry = sanitizeText ( $_POST[ 'consignee_country' ] ?? 'India' );
    $consigneeEmail   = sanitizeText ( $_POST[ 'consignee_email' ] ?? '' );
    $consigneeGst     = sanitizeText ( $_POST[ 'consignee_gst' ] ?? '' );

    // Optional: Shiprocket courier service details (used when Delhivery/Shiprocket flow requires it)
    $shiprocketCourierCompanyId = isset($_POST['shiprocket_courier_company_id']) && $_POST['shiprocket_courier_company_id'] !== ''
        ? (int) $_POST['shiprocket_courier_company_id']
        : null;
    $shiprocketCourierCompanyName = sanitizeText ( $_POST[ 'shiprocket_courier_company_name' ] ?? '' );

    // Consignor
    $shipperName    = sanitizeText ( $_POST[ 'shipper_name' ] ?? '' );
    $shipperPhone   = sanitizeText ( $_POST[ 'shipper_phone' ] ?? '' );
    $shipperAddress = sanitizeText ( $_POST[ 'shipper_address' ] ?? '' );
    $shipperPin     = sanitizeText ( $_POST[ 'shipper_pin' ] ?? '' );
    $shipperCity    = sanitizeText ( $_POST[ 'shipper_city' ] ?? '' );
    $shipperState   = sanitizeText ( $_POST[ 'shipper_state' ] ?? '' );

    // Invoice & RTO
    $invoiceNo    = sanitizeText ( $_POST[ 'invoice_no' ] ?? '' );
    $invoiceValue = (float) ($_POST[ 'invoice_value' ] ?? 0);
    $ewaybillNo   = sanitizeText ( $_POST[ 'ewaybill_no' ] ?? '' );
    $expectedTat  = sanitizeText ( $_POST[ 'expected_tat' ] ?? '' );

    $rtoName    = sanitizeText ( $_POST[ 'rto_name' ] ?? '' );
    $rtoPhone   = sanitizeText ( $_POST[ 'rto_phone' ] ?? '' );
    $rtoAddress = sanitizeText ( $_POST[ 'rto_address' ] ?? '' );
    if ($rtoName === '' && $rtoPhone === '' && $rtoAddress === '') {
        $rtoName    = $shipperName;
        $rtoPhone   = $shipperPhone;
        $rtoAddress = $shipperAddress;
        }

    // Shipment details
    $paymentMode  = sanitizeText ( $_POST[ 'payment_mode' ] ?? 'Prepaid' );
    $codAmount    = (float) ($_POST[ 'cod_amount' ] ?? 0);
    $shippingMode = sanitizeText ( $_POST[ 'shipping_mode' ] ?? 'Surface' );
    $productDesc  = sanitizeText ( $_POST[ 'product_desc' ] ?? '' );

    // Package arrays (length/width/height/boxes/actual_weight required per row; charged_weight, vol_weight, pkg_awb_no, child_ewaybill_no optional)
    $lengths          = $_POST[ 'length' ] ?? [];
    $widths           = $_POST[ 'width' ] ?? [];
    $heights          = $_POST[ 'height' ] ?? [];
    $boxes            = $_POST[ 'boxes' ] ?? [];
    $actualWeights    = $_POST[ 'actual_weight' ] ?? [];
    $chargedWeights   = $_POST[ 'charged_weight' ] ?? [];
    $volWeights       = $_POST[ 'vol_weight' ] ?? ($_POST[ 'volumetric' ] ?? []);
    $pkgAwbNos        = $_POST[ 'pkg_awb_no' ] ?? [];
    $childEwaybillNos = $_POST[ 'child_ewaybill_no' ] ?? ($_POST[ 'pkg_ewaybill_no' ] ?? []);

    // Pickup Point is optional for Own Courier (ID=2), required for others
    if ($courierId <= 0 || $consigneeName === '' || $consigneePhone === '' || $consigneeAddress === '' || $consigneePin === '') {
        throw new Exception( 'Missing required fields (Courier, Consignee details)' );
        }

    if ($courierId != 2 && $pickupPointId <= 0) {
        throw new Exception( 'Missing required fields (Pickup Point)' );
        }

    if (empty ($lengths)) {
        throw new Exception( 'Package rows are missing: provide length, width, height, boxes, actual_weight (arrays)' );
        }
    $rowCount = count ( $lengths );
    if ($rowCount !== count ( $widths ) || $rowCount !== count ( $heights )) {
        throw new Exception( 'Package arrays length, width, height must have same count' );
        }

    // Prepare package payload (charged_weight, vol_weight optional – auto-computed if empty/0)
    $packageDetails     = [];
    $totalActualWeight  = 0.0;
    $totalChargedWeight = 0.0;
    $totalBoxes         = 0;
    $maxL               = 0.0;
    $maxW               = 0.0;
    $maxH               = 0.0;

    for ($i = 0; $i < $rowCount; $i++) {
        $qty   = max ( 1, (int) ($boxes[$i] ?? 1) );
        $actWt = max ( 0, (float) ($actualWeights[$i] ?? 0) );
        $chgWt = max ( 0, (float) ($chargedWeights[$i] ?? 0) );
        $len   = max ( 0, (float) ($lengths[$i] ?? 0) );
        $wid   = max ( 0, (float) ($widths[$i] ?? 0) );
        $hei   = max ( 0, (float) ($heights[$i] ?? 0) );

        $volWt = (float) ($volWeights[$i] ?? 0);
        if ($volWt <= 0)
            $volWt = round ( ($len * $wid * $hei) / 5000, 3 );
        if ($chgWt <= 0)
            $chgWt = max ( $actWt, $volWt );

        $totalBoxes         += $qty;
        $totalActualWeight  += ($actWt * $qty);
        $totalChargedWeight += $chgWt;

        $pkgAwb  = isset ($pkgAwbNos[$i]) ? sanitizeText ( $pkgAwbNos[$i] ) : '';
        $pkgEway = isset ($childEwaybillNos[$i]) ? sanitizeText ( $childEwaybillNos[$i] ) : '';

        $packageDetails[] = [
            'awb_no' => $pkgAwb,
            'child_ewaybill_no' => $pkgEway !== '' ? $pkgEway : null,
            'length' => $len,
            'width' => $wid,
            'height' => $hei,
            'boxes' => $qty,
            'actual_weight' => $actWt,
            'vol_weight' => $volWt,
            'charged_weight' => $chgWt
        ];

        if ($len > $maxL) {
            $maxL = $len;
            }
        if ($wid > $maxW) {
            $maxW = $wid;
            }
        if ($hei > $maxH) {
            $maxH = $hei;
            }
        }

    if ($totalBoxes <= 0) {
        throw new Exception( 'At least one valid package is required' );
        }

    // Own Courier: merge consecutive rows where current has empty AWB into the previous row (regardless of whether prev has AWB or not)
    // This handles both: row-per-box with parent AWB in row 1, AND all-empty rows (auto-assign 1 serial, derive children base-1, base-2)
    if ($courierId == 2) {
        $merged = [];
        foreach ($packageDetails as $row) {
            $awb     = trim ( (string) ($row[ 'awb_no' ] ?? '') );
            $prev    = $merged ? $merged[count ( $merged ) - 1] : null;
            $prevAwb = $prev !== null ? trim ( (string) ($prev[ 'awb_no' ] ?? '') ) : '';
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
        }

    // Own Courier: expand each row with boxes > 1 into one entry per physical box
    // Each physical box gets its own serial from the pool
    if ($courierId == 2) {
        $expanded = [];
        foreach ($packageDetails as $rowIndex => $row) {
            $qty     = (int) $row[ 'boxes' ];
            $baseAwb = trim ( (string) ($row[ 'awb_no' ] ?? '') );
            for ($k = 0; $k < $qty; $k++) {
                $entry      = [
                    'awb_no' => ($k === 0) ? $baseAwb : '', // child AWBs assigned later from serial pool
                    'child_ewaybill_no' => ($k === 0 && $row[ 'child_ewaybill_no' ] !== null) ? $row[ 'child_ewaybill_no' ] : null,
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
                $expanded[] = $entry;
                }
            }
        $packageDetails = $expanded;
        }

    $packageDetailsJson = ($courierId != 2) ? json_encode ( $packageDetails ) : null;
    $weightGrams        = $totalActualWeight > 0 ? round ( $totalActualWeight * 1000, 2 ) : round ( $totalChargedWeight * 1000, 2 );

    // Fetch courier config
    $courierStmt = $pdo->prepare ( "SELECT id, partner_name, partner_code, api_key, api_url, token FROM tbl_courier_partner WHERE id = :id" );
    $courierStmt->execute ( [ ':id' => $courierId ] );
    $courierData = $courierStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $courierData) {
        throw new Exception( 'Invalid Courier Partner ID' );
        }

    // Own Courier (ID=2): AWB from serial allocation – branch-based; Air and Express are same, Surface is separate
    $preferredWaybill = null;
    if ($courierId == 2) {
        // Reject if same AWB number is used in more than one box (case-insensitive)
        $awbToBoxes = [];
        foreach ($packageDetails as $idx => $pkg) {
            $awbVal = trim ( (string) ($pkg[ 'awb_no' ] ?? '') );
            if ($awbVal === '')
                continue;
            $key = strtolower ( $awbVal );
            if ( ! isset ($awbToBoxes[$key])) {
                $awbToBoxes[$key] = [];
                }
            $awbToBoxes[$key][] = $idx + 1;
            }
        foreach ($awbToBoxes as $awb => $boxNums) {
            if (count ( $boxNums ) > 1) {
                $boxesStr = implode ( ', ', $boxNums );
                throw new Exception( "Same AWB/Serial cannot be used in more than one box. It appears in boxes: {$boxesStr}. Use a unique serial per box or leave empty." );
                }
            }

        $firstAwb             = isset ($packageDetails[ 0 ][ 'awb_no' ]) ? trim ( $packageDetails[ 0 ][ 'awb_no' ] ) : '';
        $isSurface            = (strtolower ( $shippingMode ) === 'surface');
        $serviceTypeForSerial = $isSurface ? 'surface' : 'express';
        $serviceTypes         = $isSurface ? [ 'surface' ] : [ 'express', 'air' ];

        if ($firstAwb !== '') {
            $serStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
            $serStmt->execute ( [ ':sn' => $firstAwb ] );
            $ser = $serStmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $ser) {
                throw new Exception( 'Invalid or already used AWB/Serial. Enter a valid available serial or leave empty to assign from allocation.' );
                }
            if ($branchId > 0 && (int) $ser[ 'branch_id' ] !== $branchId) {
                throw new Exception( 'AWB/Serial does not belong to selected branch. Allocation is branch-based.' );
                }
            $st                 = strtolower ( $ser[ 'service_type' ] ?? '' );
            $serialIsAirExpress = in_array ( $st, [ 'express', 'air' ], true );
            $serialIsSurface    = ($st === 'surface');
            $match              = ($isSurface && $serialIsSurface) || ( ! $isSurface && $serialIsAirExpress);
            if ( ! $match) {
                $serialFor   = $serialIsAirExpress ? 'Air/Express' : 'Surface';
                $youSelected = $isSurface ? 'Surface' : 'Air/Express';
                throw new Exception( "Shipping mode mismatch. Serial is from {$serialFor} allocation; you selected {$youSelected}. Use branch-based allocation for the selected mode or leave AWB empty." );
                }
            $preferredWaybill = $firstAwb;
            } else {
            if ($branchId > 0) {
                $ph       = implode ( ',', array_fill ( 0, count ( $serviceTypes ), '?' ) );
                $nextStmt = $pdo->prepare ( "SELECT serial_number FROM tbl_serial_numbers WHERE branch_id = ? AND service_type IN ($ph) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled') ORDER BY serial_number ASC LIMIT 1" );
                $nextStmt->execute ( array_merge ( [ $branchId ], $serviceTypes ) );
                $nextRow = $nextStmt->fetch ( PDO::FETCH_ASSOC );
                if ($nextRow) {
                    $preferredWaybill = $nextRow[ 'serial_number' ];
                    }
                }
            }
        // Validate every base box AWB: if non-empty, must be allocated and available (skip child boxes; they get base-1, base-2)
        $boxNum = 0;
        foreach ($packageDetails as $pkg) {
            $boxNum++;
            if ( ! empty ($pkg[ '_is_child' ]))
                continue;
            $awbVal = trim ( (string) ($pkg[ 'awb_no' ] ?? '') );
            if ($awbVal === '')
                continue;
            $serStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
            $serStmt->execute ( [ ':sn' => $awbVal ] );
            $ser = $serStmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $ser) {
                throw new Exception( "Serial \"{$awbVal}\" is not allocated or already used (box {$boxNum}). Remove it or use a valid serial from allocation." );
                }
            if ($branchId > 0 && (int) $ser[ 'branch_id' ] !== $branchId) {
                throw new Exception( "Serial \"{$awbVal}\" does not belong to selected branch (box {$boxNum})." );
                }
            $st                 = strtolower ( $ser[ 'service_type' ] ?? '' );
            $serialIsAirExpress = in_array ( $st, [ 'express', 'air' ], true );
            $serialIsSurface    = ($st === 'surface');
            $match              = ($isSurface && $serialIsSurface) || ( ! $isSurface && $serialIsAirExpress);
            if ( ! $match) {
                $serialFor   = $serialIsAirExpress ? 'Air/Express' : 'Surface';
                $youSelected = $isSurface ? 'Surface' : 'Air/Express';
                throw new Exception( "Serial \"{$awbVal}\" is for {$serialFor} (box {$boxNum}); you selected {$youSelected}." );
                }
            }
        // When first box AWB is left empty, we need at least one serial (same as bulk: parent box = only child)
        if ($firstAwb === '' && ($preferredWaybill === null || $preferredWaybill === '')) {
            throw new Exception( 'No serials in allocation for this branch and shipping mode. Add serials or enter AWB for the box.' );
            }
        // Assign next available serial to each empty base box only; never assign to child boxes (_is_child) — children get base-1, base-2 in groupBases below
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
                throw new Exception( 'No serials in allocation for this branch and shipping mode. Add serials or enter AWB for the box.' );
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
                    throw new Exception( 'Not enough serials in allocation. Need ' . $needToFetch . ' more for empty box(es); only ' . count ( $nextSerials ) . ' available. Add more serials or enter AWB.' );
                    }
                $si = 0;
                foreach ($packageDetails as $idx => &$p) {
                    if ( ! empty ($p[ '_is_child' ]))
                        continue;
                    if (trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '') {
                        $p[ 'child_ewaybill_no' ] = $p[ 'awb_no' ];
                        continue;
                        }
                    if (isset ($p[ 'child_ewaybill_no' ]) && $p[ 'child_ewaybill_no' ] !== null && $p[ 'child_ewaybill_no' ] !== '') {
                        continue;
                        }
                    $p[ 'child_ewaybill_no' ] = $nextSerials[$si++];
                    }
                unset ( $p );
                }
            foreach ($packageDetails as $idx => &$p) {
                if ( ! empty ($p[ '_is_child' ]))
                    continue;
                if ( ! isset ($p[ 'child_ewaybill_no' ]) || $p[ 'child_ewaybill_no' ] === null || $p[ 'child_ewaybill_no' ] === '') {
                    if (trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '') {
                        $p[ 'child_ewaybill_no' ] = $p[ 'awb_no' ];
                        }
                    }
                }
            unset ( $p );
            $preferredWaybill = trim ( (string) ($packageDetails[ 0 ][ 'child_ewaybill_no' ] ?? $packageDetails[ 0 ][ 'awb_no' ] ?? '') ) ?: $preferredWaybill;
            }
        // Ensure every base package has child_ewaybill_no (user-entered or assigned)
        foreach ($packageDetails as $idx => &$p) {
            if ( ! empty ($p[ '_is_child' ]))
                continue;
            if ( ! isset ($p[ 'child_ewaybill_no' ]) || $p[ 'child_ewaybill_no' ] === null || $p[ 'child_ewaybill_no' ] === '') {
                $p[ 'child_ewaybill_no' ] = trim ( (string) ($p[ 'awb_no' ] ?? '') ) !== '' ? $p[ 'awb_no' ] : null;
                }
            }
        unset ( $p );
        // Set child box AWBs: derive as base-1, base-2, ... (same as bulk_upload.php)
        // Only 1 real serial is consumed per booking (the parent). Child AWBs are derived.
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
        // Build JSON for tbl_bookings (strip internal keys)
        $forJson            = array_map ( function ($p)
            {
            $p = array_diff_key ( $p, array_flip ( [ '_group_id', '_box_index_in_group', '_is_child' ] ) );
            return $p;
            }, $packageDetails );
        $packageDetailsJson = json_encode ( $forJson );
        }
    if ($packageDetailsJson === null) {
        $packageDetailsJson = json_encode ( $packageDetails );
        }

    // Fetch pickup point - optional for Own Courier (ID=2)
    $pickupPoint = null;
    if ($courierId != 2 && $pickupPointId > 0) {
        $pickupStmt = $pdo->prepare ( "SELECT id, name, email FROM tbl_pickup_points WHERE id = :id" );
        $pickupStmt->execute ( [ ':id' => $pickupPointId ] );
        $pickupPoint = $pickupStmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $pickupPoint) {
            throw new Exception( 'Invalid Pickup Point ID' );
            }
        } else if ($courierId == 2) {
        // For Own Courier, try to get a default pickup point but don't fail if not found
        $pickupStmt = $pdo->prepare ( "SELECT id, name, email FROM tbl_pickup_points WHERE id = :id LIMIT 1" );
        $pickupStmt->execute ( [ ':id' => $pickupPointId ?: 0 ] );
        $pickupPoint = $pickupStmt->fetch ( PDO::FETCH_ASSOC );
        }

    // Shiprocket requires an email in the order payload.
    // If the UI didn't send consignee_email, fall back to pickup point email.
    if (empty($consigneeEmail) && is_array($pickupPoint) && !empty($pickupPoint['email'])) {
        $consigneeEmail = (string)$pickupPoint['email'];
    }

    $shipmentData = [
        'booking_ref_id' => $bookingRefId,
        'consignee_name' => $consigneeName,
        'consignee_phone' => $consigneePhone,
        'consignee_email' => $consigneeEmail,
        'consignee_gst' => $consigneeGst,
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
        'package_details' => $packageDetails,
        'shipper_name' => $shipperName,
        'shipper_phone' => $shipperPhone,
        'shipper_address' => $shipperAddress,
        'shipper_pin' => $shipperPin,
        'shipper_city' => $shipperCity,
        'shipper_state' => $shipperState,
        'rto_address' => $rtoAddress,
    ];
    if ($preferredWaybill !== null) {
        $shipmentData[ 'preferred_waybill' ] = $preferredWaybill;
        }

    // Courier sync via router (same flow as api/pickuppoint/services/courier_service.php)
    require_once __DIR__ . '/services/courier_service.php';
    $syncResult = syncBookingWithCourier ( $pdo, $courierData, $shipmentData );

    if (empty ($syncResult[ 'success' ])) {
        throw new Exception( $syncResult[ 'message' ] ?? 'Courier sync failed' );
        }

    $waybillNo       = $syncResult[ 'waybill' ] ?? null;
    $apiResponseRaw  = $syncResult[ 'api_response' ] ?? null;
    // If Shiprocket order creation returned a shipment_id, automatically assign AWB.
    // If AWB assignment fails, we stop the flow so the shipment is not stored.
    if (!empty($syncResult['shipment_id'])) {
        $shiprocketShipmentId = $syncResult['shipment_id'];
        $awbResult = assignAwbWithShiprocket($courierData, $shiprocketShipmentId, $shiprocketCourierCompanyId);
        if (empty($awbResult['success'])) {
            throw new Exception($awbResult['message'] ?? 'Shiprocket AWB assignment failed');
        }
        $awbCode = trim((string)($awbResult['awb_code'] ?? ''));
        if ($awbCode === '') {
            throw new Exception('Shiprocket AWB assignment returned empty awb_code');
        }

        $waybillNo = $awbCode;

        // Attach AWB assignment response to api_response for debugging.
        if (is_array($apiResponseRaw)) {
            $apiResponseRaw['awb_assign'] = $awbResult['api_response'] ?? $awbResult;
        }
    }

    $apiResponseJson = is_array ( $apiResponseRaw ) ? json_encode ( $apiResponseRaw ) : $apiResponseRaw;

    $sql = "INSERT INTO tbl_bookings (
        booking_ref_id, waybill_no, courier_id, pickup_point_id, branch_id, client_id, booking_type,
        consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
        consignee_city, consignee_state, consignee_country,
        shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
        payment_mode, cod_amount, weight, length, width, height,
        shipping_mode, product_desc, package_details, quantity, is_mps,
        invoice_no, invoice_value, ewaybill_no, expected_tat,
        rto_name, rto_phone, rto_address,
        shiprocket_courier_company_name, shiprocket_courier_company_id,
        api_response, last_status, created_by, created_at
    ) VALUES (
        :ref_id, :waybill, :c_id, :p_id, :branch_id, :client_id, :booking_type,
        :c_name, :c_phone, :c_email, :c_gst, :c_add, :c_pin,
        :c_city, :c_state, :c_country,
        :s_name, :s_phone, :s_add, :s_pin, :s_city, :s_state,
        :pay_mode, :cod, :w, :l, :wi, :h,
        :ship_mode, :prod_desc, :pkg_details, :qty, :mps,
        :inv_no, :inv_val, :eway, :expected_tat,
        :rto_name, :rto_phone, :rto_add,
        :shiprocket_courier_company_name, :shiprocket_courier_company_id,
        :api_resp, 'Created', :user_id, NOW()
    )";

    $stmt = $pdo->prepare ( $sql );
    $stmt->execute ( [
        ':ref_id' => $bookingRefId,
        ':waybill' => $waybillNo,
        ':c_id' => $courierId,
        ':p_id' => $pickupPointId > 0 ? $pickupPointId : null,
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
        ':expected_tat' => $expectedTat !== '' ? $expectedTat : null,
        ':rto_name' => $rtoName,
        ':rto_phone' => $rtoPhone,
        ':rto_add' => $rtoAddress,
        ':shiprocket_courier_company_name' => $shiprocketCourierCompanyName !== '' ? $shiprocketCourierCompanyName : null,
        ':shiprocket_courier_company_id' => $shiprocketCourierCompanyId,
        ':api_resp' => $apiResponseJson,
        ':user_id' => $createdBy
    ] );

    $bookingId = $pdo->lastInsertId ();

    $ewbUpdateStatus = 'not_required';
    $ewbUpdatePayload = null;
    $courierPartnerCode = strtolower(trim((string)($courierData['partner_code'] ?? '')));
    $courierPartnerName = strtolower(trim((string)($courierData['partner_name'] ?? '')));
    $isDelhivery = ($courierId == 1) || (strpos($courierPartnerCode, 'del') === 0) || (strpos($courierPartnerName, 'delhivery') !== false);
    $isShiprocket = (strpos($courierPartnerCode, 'sr') === 0) || (strpos($courierPartnerName, 'shiprocket') !== false);
    if ($isDelhivery && (float)$invoiceValue > $ewbThreshold) {
        if (trim((string)$invoiceNo) === '' || trim((string)$ewaybillNo) === '' || trim((string)$waybillNo) === '') {
            $ewbUpdateStatus = 'failed';
            $ewbUpdatePayload = [
                'success' => false,
                'message' => 'EWB update skipped: waybill, invoice no and e-waybill are required for invoice value > 5000'
            ];
        } else {
            $ewbResult = updateDelhiveryEwaybill($courierData, $waybillNo, $invoiceNo, $ewaybillNo);
            $ewbUpdateStatus = !empty($ewbResult['success']) ? 'success' : 'failed';
            $ewbUpdatePayload = $ewbResult;
        }
    }

    try {
        $ewbStmt = $pdo->prepare("UPDATE tbl_bookings
            SET ewb_update_status = :st,
                ewb_update_response = :resp,
                ewb_update_at = NOW()
            WHERE id = :id");
        $ewbStmt->execute([
            ':st' => $ewbUpdateStatus,
            ':resp' => json_encode($ewbUpdatePayload ?? ['status' => 'not_required']),
            ':id' => $bookingId
        ]);
    } catch ( Exception $e ) {
        // Non-blocking: booking success should not fail if schema update is pending.
    }

    // Own Courier: delete only parent/base serials from tbl_serial_numbers
    // Child derived AWBs (base-1, base-2...) are NOT in tbl_serial_numbers, same as bulk_upload.php
    if ($courierId == 2) {
        $serDelStmt = $pdo->prepare ( "SELECT id, allocation_id FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn))" );
        foreach ($packageDetails as $pkg) {
            if ( ! empty ($pkg[ '_is_child' ]))
                continue; // skip derived child AWBs
            $sn = trim ( (string) ($pkg[ 'child_ewaybill_no' ] ?? $pkg[ 'awb_no' ] ?? '') );
            if ($sn === '')
                continue;
            try {
                $serDelStmt->execute ( [ ':sn' => $sn ] );
                $serialRow = $serDelStmt->fetch ( PDO::FETCH_ASSOC );
                if ($serialRow) {
                    $pdo->prepare ( "UPDATE tbl_serial_numbers SET status = 'used', is_used = 1 WHERE id = :id" )->execute ( [ ':id' => $serialRow[ 'id' ] ] );
                    $pdo->prepare ( "UPDATE tbl_serial_allocation SET used_serials = used_serials + 1 WHERE id = :aid" )
                        ->execute ( [ ':aid' => $serialRow[ 'allocation_id' ] ] );
                    }
                }
            catch ( Exception $e ) {
                // Log but don't fail booking
                }
            }
        }

    // Insert per-row volumetric details into tbl_booking_packages
    // For Own Courier (ID=2): awb_no and child_ewaybill_no use assigned serial when set; else waybillNo / waybillNo-idx
    $pkgInsertSql = "INSERT INTO tbl_booking_packages
        (booking_id, waybill_no, row_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight)
        VALUES
        (:bid, :wn, :row_no, :awb_no, :child_eway, :len, :wid, :hei, :boxes, :act_wt, :vol_wt, :chg_wt)";
    $pkgStmt      = $pdo->prepare ( $pkgInsertSql );

    // For Delhivery: use waybills returned from API (all_waybills array)
    $delhiveryWaybills = $syncResult[ 'all_waybills' ] ?? [];

    foreach ($packageDetails as $idx => $pkg) {
        if ($courierId == 2 && ! empty ($waybillNo)) {
            // Own Courier: use assigned serial / derived AWBs
            $ce     = isset ($pkg[ 'child_ewaybill_no' ]) ? trim ( (string) $pkg[ 'child_ewaybill_no' ] ) : '';
            $rowAwb = $ce !== '' ? $ce : (($idx === 0) ? $waybillNo : $waybillNo . '-' . $idx);
            } elseif ( ! empty ($delhiveryWaybills)) {
            // Delhivery: use the waybill fetched from Delhivery API for this package
            $rowAwb = $delhiveryWaybills[$idx] ?? (($idx === 0) ? $waybillNo : $waybillNo . '-' . $idx);
            } else {
            // Fallback: use master waybill
            $rowAwb = ($idx === 0 && ! empty ($waybillNo)) ? $waybillNo : ($pkg[ 'awb_no' ] ?? '');
            }

        // child_ewaybill_no = same as awb_no
        $childEway = ($rowAwb !== '') ? $rowAwb : null;

        $pkgStmt->execute ( [
            ':bid' => $bookingId,
            ':wn' => $waybillNo,
            ':row_no' => $idx + 1,
            ':awb_no' => $rowAwb,
            ':child_eway' => $childEway,
            ':len' => $pkg[ 'length' ],
            ':wid' => $pkg[ 'width' ],
            ':hei' => $pkg[ 'height' ],
            ':boxes' => $pkg[ 'boxes' ],
            ':act_wt' => $pkg[ 'actual_weight' ],
            ':vol_wt' => $pkg[ 'vol_weight' ],
            ':chg_wt' => $pkg[ 'charged_weight' ],
        ] );
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
        $updWeight = $pdo->prepare ( "UPDATE tbl_bookings SET weight = (
            SELECT COALESCE(NULLIF(SUM(actual_weight), 0) * 1000, NULLIF(SUM(charged_weight), 0) * 1000, 0) FROM tbl_booking_packages WHERE booking_id = tbl_bookings.id
        ) WHERE id = :bid" );
        $updWeight->execute ( [ ':bid' => $bookingId ] );
        }

    // For "Own Courier" (ID 2), insert detailed initial tracking record with structured raw_response
    if ($courierId == 2 && ! empty ($waybillNo)) {
        $initialTrackingRaw = json_encode ( [
            'awb_no' => $waybillNo,
            'shipment_details' => [
                'booking_ref_id' => $bookingRefId,
                'consignee_name' => $consigneeName,
                'consignee_phone' => $consigneePhone,
                'payment_mode' => $paymentMode,
                'cod_amount' => $codAmount
            ],
            'current_status' => 'Created',
            'scan_details' => [
                'status' => 'Booking Created',
                'location' => $shipperCity,
                'datetime' => date ( 'Y-m-d H:i:s' ),
                'remarks' => 'Shipment created locally via Own Booking'
            ],
            'scan_details_history' => [
                [
                    'status' => 'Booking Created',
                    'location' => $shipperCity,
                    'datetime' => date ( 'Y-m-d H:i:s' ),
                    'remarks' => 'Shipment created locally via Own Booking',
                    'updated_by' => $createdBy,
                    'updated_at' => date ( 'Y-m-d H:i:s' )
                ]
            ]
        ] );

        $trackSql = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_location, scan_datetime, remarks, raw_response) 
                     VALUES (:bid, :wn, 'Booking Created', 'Created', :sl, NOW(), 'Shipment created locally', :raw)";

        $pdo->prepare ( $trackSql )->execute ( [
            ':bid' => $bookingId,
            ':wn' => $waybillNo,
            ':sl' => $shipperCity,
            ':raw' => $initialTrackingRaw
        ] );
        } else if ( ! empty ($waybillNo)) {
        // Standard couriers (e.g. Delhivery): insert tracking record with scan_details_history
        $initialTrackingRaw = json_encode ( [
            'awb_no' => $waybillNo,
            'shipment_details' => [
                'booking_ref_id' => $bookingRefId,
                'consignee_name' => $consigneeName,
                'consignee_phone' => $consigneePhone,
                'payment_mode' => $paymentMode,
                'cod_amount' => $codAmount
            ],
            'current_status' => 'Created',
            'scan_details' => [
                'status' => 'Booking Created',
                'location' => $shipperCity,
                'datetime' => date ( 'Y-m-d H:i:s' ),
                'remarks' => 'Shipment created and synced with carrier'
            ],
            'scan_details_history' => [
                [
                    'status' => 'Booking Created',
                    'location' => $shipperCity,
                    'datetime' => date ( 'Y-m-d H:i:s' ),
                    'remarks' => 'Shipment created and synced with carrier',
                    'updated_by' => $createdBy,
                    'updated_at' => date ( 'Y-m-d H:i:s' )
                ]
            ]
        ] );
        $trackSql           = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_location, scan_datetime, remarks, raw_response)
                     VALUES (:bid, :wn, 'Booking Created', 'Created', :sl, NOW(), 'Shipment created and synced with carrier', :raw)";
        $pdo->prepare ( $trackSql )->execute ( [ ':bid' => $bookingId, ':wn' => $waybillNo, ':sl' => $shipperCity, ':raw' => $initialTrackingRaw ] );
        }

    if ( ! empty ($waybillNo) && $courierId == 1) {
        // Trigger Delhivery Tracking Update immediately so UI shows "Manifested" or real status
        try {
            if ( ! defined ( 'IN_CREATION' ))
                define ( 'IN_CREATION', true );
            // We call it via include but mock $_GET['waybill']
            $_GET[ 'waybill' ] = $waybillNo;
            ob_start (); // Capture output to avoid breaking API JSON response
            include __DIR__ . '/../../cron-delhivery.php';
            ob_end_clean ();
            }
        catch ( Exception $e ) {
            // Ignore tracking trigger errors to not break booking creation
            }
        }
    if ( ! empty ($waybillNo) && $isShiprocket) {
        // Trigger Shiprocket tracking update immediately after booking/AWB assignment
        try {
            if ( ! defined ( 'IN_CREATION' ))
                define ( 'IN_CREATION', true );
            $_GET[ 'waybill' ] = $waybillNo;
            ob_start ();
            include __DIR__ . '/../../cron-shiprocket.php';
            ob_end_clean ();
            }
        catch ( Exception $e ) {
            // Non-blocking: booking should still succeed even if tracking sync fails
            }
        }

    // ── Auto Pickup Request for Delhivery ──────────────────────────────────────
    // After a successful Delhivery booking, auto-raise a pickup request for today
    if ( ! empty ($syncResult[ 'success' ]) && ! empty ($waybillNo) && $pickupPointId > 0 && $pickupPoint) {

        if ($isDelhivery && ! empty ($courierData[ 'api_key' ]) && ! empty ($courierData[ 'api_url' ])) {
            try {
                // Always same day; time = current + 1 hour, minimum 11:00 AM
                $prDate   = date ( 'Y-m-d' );
                $nextHour = (int) date ( 'H', strtotime ( '+1 hour' ) );
                $prHour   = max ( 11, $nextHour );
                $prTime   = str_pad ( $prHour, 2, '0', STR_PAD_LEFT ) . ':00:00';
                $prLocName = $pickupPoint[ 'name' ];

                // Check if a pickup request already exists for this location + date (not Failed/Cancelled)
                $existStmt = $pdo->prepare (
                    "SELECT id, expected_package_count FROM tbl_pickup_requests
                     WHERE pickup_point_id = :ppid AND pickup_date = :pdate
                       AND status NOT IN ('Failed','Cancelled')
                     ORDER BY id DESC LIMIT 1"
                );
                $existStmt->execute ( [ ':ppid' => $pickupPointId, ':pdate' => $prDate ] );
                $existRow = $existStmt->fetch ( PDO::FETCH_ASSOC );

                if ($existRow) {
                    // Already raised — just add package count to existing record
                    $newCount = (int) $existRow[ 'expected_package_count' ] + $totalBoxes;
                    $pdo->prepare (
                        "UPDATE tbl_pickup_requests SET expected_package_count = :cnt, updated_at = NOW() WHERE id = :id"
                    )->execute ( [ ':cnt' => $newCount, ':id' => $existRow[ 'id' ] ] );
                } else {
                    // No existing request — call Delhivery API and insert new record
                    $prApiUrl  = rtrim ( $courierData[ 'api_url' ], '/' ) . '/fm/request/new/';
                    $prPayload = json_encode ( [
                        'pickup_location'        => $prLocName,
                        'pickup_date'            => $prDate,
                        'pickup_time'            => $prTime,
                        'expected_package_count' => $totalBoxes
                    ] );

                    $ch = curl_init ( $prApiUrl );
                    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt ( $ch, CURLOPT_POST, true );
                    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $prPayload );
                    curl_setopt ( $ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Token ' . $courierData[ 'api_key' ],
                        'Content-Type: application/json',
                    ] );
                    curl_setopt ( $ch, CURLOPT_TIMEOUT, 15 );
                    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
                    $prResp     = curl_exec ( $ch );
                    $prHttpCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
                    curl_close ( $ch );

                    $prDecoded = @json_decode ( $prResp, true ) ?? [];
                    $prStatus  = ($prHttpCode >= 200 && $prHttpCode < 300) ? 'Confirmed' : 'Failed';
                    $prReqId   = $prDecoded[ 'pickup_id' ] ?? ($prDecoded[ 'id' ] ?? null);

                    $pdo->prepare (
                        "INSERT INTO tbl_pickup_requests
                         (pickup_point_id, courier_id, pickup_location_name, pickup_date, pickup_time,
                          expected_package_count, status, request_id, api_response, created_by, created_at)
                         VALUES
                         (:ppid, :cid, :loc, :pdate, :ptime, :pkgcnt, :status, :reqid, :apiresp, :uid, NOW())"
                    )->execute ( [
                        ':ppid'    => $pickupPointId,
                        ':cid'     => $courierId,
                        ':loc'     => $prLocName,
                        ':pdate'   => $prDate,
                        ':ptime'   => $prTime,
                        ':pkgcnt'  => $totalBoxes,
                        ':status'  => $prStatus,
                        ':reqid'   => $prReqId,
                        ':apiresp' => $prResp,
                        ':uid'     => $createdBy,
                    ] );
                }
            } catch ( Exception $e ) {
                // Silent fail — auto pickup error must not block booking completion
            }
        }
    }
    // ── End Auto Pickup Request ─────────────────────────────────────────────────

    $message = ! empty ($syncResult[ 'synced' ]) ? 'Shipment Booked!' : 'Shipment saved locally (courier sync not configured)';
    echo json_encode ( [
        'status' => 'success',
        'message' => $message,
        'waybill' => $waybillNo,
        'booking_id' => $bookingId,
        'synced' => ! empty ($syncResult[ 'synced' ]),
        'ewb_update_status' => $ewbUpdateStatus
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
?>
