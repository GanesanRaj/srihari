<?php
// bulk_upload.php
$streamMode = isset($_GET['stream']) && $_GET['stream'] === '1';
if ($streamMode) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', '0');
} else {
    header('Content-Type: application/json');
}
require '../../config/db.php';
session_start ();
require_once '../../config/helper.php';
$current_user = get_current_user_info ();
$userId       = $current_user ? $current_user[ 'id' ] : 1;
$username     = $current_user ? $current_user[ 'username' ] : 'system';
session_write_close();

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    if ($streamMode) {
        echo "EVENT:ERROR " . json_encode([ 'message' => 'Invalid Request' ]) . "\n";
    } else {
        echo json_encode([ 'status' => 'error', 'message' => 'Invalid Request' ]);
    }
    exit;
}

if ( ! isset ($_FILES[ 'bulk_file' ]) || $_FILES[ 'bulk_file' ][ 'error' ] !== UPLOAD_ERR_OK) {
    if ($streamMode) {
        echo "EVENT:ERROR " . json_encode([ 'message' => 'File upload failed' ]) . "\n";
    } else {
        echo json_encode([ 'status' => 'error', 'message' => 'File upload failed' ]);
    }
    exit;
}

$fileTmpPath = $_FILES[ 'bulk_file' ][ 'tmp_name' ];
$fileName    = $_FILES[ 'bulk_file' ][ 'name' ];

require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require_once __DIR__ . '/../booking/services/delhivery.php';

try {
    $internalErrors = libxml_use_internal_errors ( true );
    $spreadsheet    = IOFactory::load ( $fileTmpPath );
    libxml_use_internal_errors ( $internalErrors );
    $sheetData = $spreadsheet->getActiveSheet ()->toArray ( null, true, true, false );
    }
catch ( Exception $e ) {
    if ($streamMode) {
        echo "EVENT:ERROR " . json_encode([ 'message' => 'Error reading file: ' . $e->getMessage () ]) . "\n";
    } else {
        echo json_encode([ 'status' => 'error', 'message' => 'Error reading file: ' . $e->getMessage () ]);
    }
    exit;
}

if (empty ($sheetData) || count ( $sheetData ) < 2) {
    if ($streamMode) {
        echo "EVENT:ERROR " . json_encode([ 'message' => 'File is empty or has no data rows' ]) . "\n";
    } else {
        echo json_encode([ 'status' => 'error', 'message' => 'File is empty or has no data rows' ]);
    }
    exit;
}

// branch_name/client_name will be captured from the first successful shipment row
$jobBranchName = null;
$jobClientName = null;

// Create Job Record
try {
    $stmt = $pdo->prepare ( "INSERT INTO tbl_bulkupload_jobs (filename, status, created_by, created_at) VALUES (:fn, 'Processing', :uid, NOW())" );
    $stmt->execute ( [ ':fn' => $fileName, ':uid' => $userId ] );
    $jobId = $pdo->lastInsertId ();
    }
catch ( Exception $e ) {
    if ($streamMode) {
        echo "EVENT:ERROR " . json_encode([ 'message' => 'Database Error: ' . $e->getMessage () ]) . "\n";
    } else {
        echo json_encode([ 'status' => 'error', 'message' => 'Database Error: ' . $e->getMessage () ]);
    }
    exit;
}

$headers = array_shift ( $sheetData );
$headers = array_pad ( $headers, 37, '' );

$successCount = 0;
$failCount    = 0;
$resultMap    = [];

// Group rows by Ref ID (Index 5). Columns 33=Client Name, 34=AWB No
$groups = [];
foreach ($sheetData as $i => $data) {
    $data = array_pad ( $data, 37, '' );
    if (trim ( implode ( '', $data ) ) === '')
        continue;

    $refId = trim ( $data[ 5 ] );
    if (empty ($refId)) {
        $groupKey = '_AUTO_' . $i . '_' . bin2hex ( random_bytes ( 4 ) );
        } else {
        $groupKey = 'REF_' . $refId;
        }
    $groups[$groupKey][] = [ 'index' => $i, 'data' => $data ];
    }

// Use the CORRECT service router from booking module
require_once '../../api/booking/services/courier_service.php';

$totalGroups = count($groups);
$processedGroups = 0;
try {
    $pdo->prepare("UPDATE tbl_bulkupload_jobs SET total_records = :tot, updated_at = NOW() WHERE id = :id")
        ->execute([
            ':tot' => $totalGroups,
            ':id' => $jobId
        ]);
} catch (Exception $e) {
    // Non-blocking: still continue upload processing.
}
if ($streamMode) {
    echo "EVENT:JOB_CREATED " . json_encode([
        'job_id' => (int) $jobId,
        'total' => $totalGroups
    ]) . "\n";
    @ob_flush();
    @flush();
}

foreach ($groups as $groupKey => $groupRows) {
    $errorMsg                = '';
    $errCol                  = -1;
    $status                  = 'Success';
    $waybillNo               = '';
    $packageDetailsForResult = null;

    $ewayNo = '';
    try {
        $firstData = $groupRows[ 0 ][ 'data' ];
        $ewayNo    = trim ( $firstData[ 31 ] ?? '' );

        $branchName  = trim ( $firstData[ 0 ] );
        $bookingType = trim ( $firstData[ 1 ] );
        $dd          = trim ( $firstData[ 2 ] );
        $mm          = trim ( $firstData[ 3 ] );
        $yyyy        = trim ( $firstData[ 4 ] );
        $date        = $yyyy . '-' . str_pad ( $mm, 2, '0', STR_PAD_LEFT ) . '-' . str_pad ( $dd, 2, '0', STR_PAD_LEFT );
        $refId       = trim ( $firstData[ 5 ] );
        if (empty ($refId))
            $refId = 'BULK-' . time () . '-' . rand ( 100, 999 );
        $courierName = trim ( $firstData[ 6 ] );

        // Shipper
        $sName  = trim ( $firstData[ 7 ] );
        $sPhone = trim ( $firstData[ 8 ] );
        $sPin   = trim ( $firstData[ 9 ] );
        $sAddr  = trim ( $firstData[ 10 ] );
        $sCity  = trim ( $firstData[ 11 ] );
        $sState = trim ( $firstData[ 12 ] );

        // Consignee
        $cName  = trim ( $firstData[ 13 ] );
        $cPhone = trim ( $firstData[ 14 ] );
        $cEmail = trim ( $firstData[ 15 ] );
        $cGst   = trim ( $firstData[ 16 ] );
        $cAddr  = trim ( $firstData[ 17 ] );
        $cPin   = trim ( $firstData[ 18 ] );
        $cCity  = trim ( $firstData[ 19 ] );
        $cState = trim ( $firstData[ 20 ] );

        $payMode  = trim ( $firstData[ 21 ] );
        $codAmt   = floatval ( $firstData[ 22 ] );
        $prodDesc = trim ( $firstData[ 23 ] );

        $invNo    = trim ( $firstData[ 29 ] );
        $invVal   = floatval ( $firstData[ 30 ] );
        $shipMode = trim ( $firstData[ 32 ] );
        if (empty ($shipMode))
            $shipMode = 'Surface';
        $shipMode = ucfirst ( strtolower ( $shipMode ) );
        if ($shipMode === 'Air')
            $shipMode = 'Express';
        $clientName      = isset ($firstData[ 33 ]) ? trim ( $firstData[ 33 ] ) : '';
        $firstAwb        = isset ($firstData[ 34 ]) ? trim ( $firstData[ 34 ] ) : '';
        $pickupPointName = isset ($firstData[ 35 ]) ? trim ( $firstData[ 35 ] ) : '';
        $srCourierCompanyId = isset ($firstData[ 36 ]) ? (int) trim ( (string) $firstData[ 36 ] ) : 0;
        // Backward-compatible: if older files still send courier name in next col, read it.
        $srCourierCompanyName = isset ($firstData[ 37 ]) ? trim ( $firstData[ 37 ] ) : '';

        // Aggregate Packages
        $totalBoxes         = 0;
        $totalActualWeight  = 0;
        $totalChargedWeight = 0;
        $maxL               = 0;
        $maxW               = 0;
        $maxH               = 0;
        $packageDetails     = [];

        // Validate every child box first; if any row is wrong, fail entire group (all parent/child rows)
        $boxIndex = 0;
        foreach ($groupRows as $gr) {
            $boxIndex++;
            $d        = $gr[ 'data' ];
            $l        = floatval ( $d[ 24 ] );
            $w        = floatval ( $d[ 25 ] );
            $h        = floatval ( $d[ 26 ] );
            $wtPerBox = floatval ( $d[ 27 ] );
            $box      = intval ( $d[ 28 ] );
            if ($box < 1)
                $box = 1;

            if ($l <= 0 || $w <= 0 || $h <= 0 || $wtPerBox <= 0) {
                $errCol = 24;
                throw new Exception( "Invalid dimensions/weight in child box {$boxIndex}. All rows in this shipment marked failed." );
                }
            }

        foreach ($groupRows as $gr) {
            $d        = $gr[ 'data' ];
            $l        = floatval ( $d[ 24 ] );
            $w        = floatval ( $d[ 25 ] );
            $h        = floatval ( $d[ 26 ] );
            $wtPerBox = floatval ( $d[ 27 ] );
            $box      = intval ( $d[ 28 ] );
            if ($box < 1)
                $box = 1;

            $volWtPerBox = ($l * $w * $h) / 5000;
            $chgWtPerBox = max ( $wtPerBox, $volWtPerBox );

            $totalBoxes         += $box;
            $totalActualWeight  += ($wtPerBox * $box);
            $totalChargedWeight += ($chgWtPerBox * $box);

            if ($l > $maxL)
                $maxL = $l;
            if ($w > $maxW)
                $maxW = $w;
            if ($h > $maxH)
                $maxH = $h;

            $packageDetails[] = [
                'length' => $l,
                'width' => $w,
                'height' => $h,
                'boxes' => $box,
                'actual_weight' => $wtPerBox,
                'vol_weight' => round ( $volWtPerBox, 3 ),
                'charged_weight' => $chgWtPerBox,
                'child_ewaybill_no' => trim ( $d[ 34 ] ?? '' )
            ];
            }

        // Validation
        if ( ! $branchName) {
            $errCol = 0;
            throw new Exception( "Missing Branch Name" );
            }
        if ( ! $courierName) {
            $errCol = 6;
            throw new Exception( "Missing Courier Name" );
            }
        if ( ! $cName) {
            $errCol = 13;
            throw new Exception( "Missing Consignee Name" );
            }
        if ( ! $cPhone) {
            $errCol = 14;
            throw new Exception( "Missing Consignee Phone" );
            }
        if ( ! $cAddr) {
            $errCol = 17;
            throw new Exception( "Missing Consignee Address" );
            }
        if ( ! $cPin) {
            $errCol = 18;
            throw new Exception( "Missing Consignee Pin" );
            }

        $tempPayMode = strtoupper ( $payMode );
        if ($tempPayMode === 'COD')
            $payMode = 'COD';
        elseif ($tempPayMode === 'PREPAID')
            $payMode = 'Prepaid';
        else {
            $errCol = 21;
            throw new Exception( "Invalid Payment Mode" );
            }
        if ($payMode !== 'COD')
            $codAmt = 0;

        // --- NEW VALIDATION: Invoice Value > 50,000 requires E-Way Bill ---
        if ($invVal > 50000 && empty ($ewayNo)) {
            $errCol = 31;
            throw new Exception( "E-Way Bill Number is mandatory for Invoice Value greater than 50,000" );
            }

        // Resolve IDs
        $stmt = $pdo->prepare ( "SELECT id FROM tbl_branch WHERE branch_name LIKE :name OR branch_code = :code LIMIT 1" );
        $stmt->execute ( [ ':name' => "%$branchName%", ':code' => $branchName ] );
        $branch = $stmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $branch) {
            $errCol = 0;
            throw new Exception( "Branch not found" );
            }

        $stmt = $pdo->prepare ( "SELECT * FROM tbl_courier_partner WHERE partner_name LIKE :name OR partner_code = :code LIMIT 1" );
        $stmt->execute ( [ ':name' => "%$courierName%", ':code' => $courierName ] );
        $courierData = $stmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $courierData) {
            $errCol = 6;
            throw new Exception( "Courier not found" );
            }

        $branchId = $branch[ 'id' ];

        // Access restriction for client-type users — read from tbl_user (handles NULL user_type)
        $sessionUserType = $_SESSION[ 'user_type' ] ?? 'both';
        $isClientUpload  = ($sessionUserType === 'client');
        if ( ! $isClientUpload && isset ($_SESSION[ 'username' ])) {
            $chkU = $pdo->prepare ( "SELECT clientaccess, branch_ids, client_ids FROM tbl_user WHERE username = ? LIMIT 1" );
            $chkU->execute ( [ $_SESSION[ 'username' ] ] );
            $chkURow = $chkU->fetch ( PDO::FETCH_ASSOC );
            if ($chkURow && $chkURow[ 'clientaccess' ] == 1) {
                $isClientUpload = true;
                // Override session values with DB values
                $_SESSION[ 'branch_ids' ] = $chkURow[ 'branch_ids' ] ?? '';
                $_SESSION[ 'client_ids' ] = $chkURow[ 'client_ids' ] ?? '';
                }
            }
        if ($isClientUpload) {
            $rawSB       = $_SESSION[ 'branch_ids' ] ?? '';
            $allowedBIds = $rawSB !== '' ? array_filter ( array_map ( 'intval', explode ( ',', $rawSB ) ) ) : [];
            if ( ! empty ($allowedBIds) && ! in_array ( (int) $branchId, $allowedBIds, true )) {
                $errCol = 0;
                throw new Exception( "Access denied: you are not allowed to book under branch '{$branchName}'." );
                }
            }

        $clientId = null;
        if ($clientName !== '') {
            $stmt = $pdo->prepare ( "SELECT id FROM tbl_client WHERE (client_name LIKE :name OR contact_no = :name) AND branch_id = :bid AND status = 'active' LIMIT 1" );
            $stmt->execute ( [ ':name' => "%$clientName%", ':bid' => $branchId ] );
            $clientRow = $stmt->fetch ( PDO::FETCH_ASSOC );
            if ($clientRow) {
                $clientId = $clientRow[ 'id' ];
                if ($isClientUpload) {
                    $rawSC       = $_SESSION[ 'client_ids' ] ?? '';
                    $allowedCIds = $rawSC !== '' ? array_filter ( array_map ( 'intval', explode ( ',', $rawSC ) ) ) : [];
                    if ( ! empty ($allowedCIds) && ! in_array ( (int) $clientId, $allowedCIds, true )) {
                        $errCol = 33;
                        throw new Exception( "Access denied: you are not allowed to book under client '{$clientName}'." );
                        }
                    }
                }
            }

        // Capture branch/client name from first successful row
        if ($jobBranchName === null)
            $jobBranchName = $branchName;
        if ($jobClientName === null && $clientName !== '')
            $jobClientName = $clientName;


        // Own Courier only: AWB validation or get next from allocation (branch-based; Air/Express same, Surface separate)
        $preferredWaybill = null;
        if ($courierData[ 'id' ] == 2) {
            $isSurface            = (strtolower ( $shipMode ) === 'surface');
            $serviceTypeForSerial = $isSurface ? 'surface' : 'express';
            $serviceTypes         = $isSurface ? [ 'surface' ] : [ 'express', 'air' ];

            if ($firstAwb !== '') {
                $serStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
                $serStmt->execute ( [ ':sn' => $firstAwb ] );
                $ser = $serStmt->fetch ( PDO::FETCH_ASSOC );
                if ( ! $ser) {
                    $errCol = 34;
                    throw new Exception( "Invalid or already used AWB/Serial. Enter a valid available serial or leave empty to assign from allocation." );
                    }
                if ((int) $ser[ 'branch_id' ] !== $branchId) {
                    $errCol = 34;
                    throw new Exception( "AWB/Serial does not belong to selected branch. Allocation is branch-based." );
                    }
                $st                 = strtolower ( $ser[ 'service_type' ] ?? '' );
                $serialIsAirExpress = in_array ( $st, [ 'express', 'air' ], true );
                $serialIsSurface    = ($st === 'surface');
                $match              = ($isSurface && $serialIsSurface) || ( ! $isSurface && $serialIsAirExpress);
                if ( ! $match) {
                    $errCol      = 34;
                    $serialFor   = $serialIsAirExpress ? 'Air/Express' : 'Surface';
                    $youSelected = $isSurface ? 'Surface' : 'Air/Express';
                    throw new Exception( "Shipping mode mismatch. Serial is from {$serialFor} allocation; you selected {$youSelected}. Use branch-based allocation for the selected mode or leave AWB empty." );
                    }
                $preferredWaybill = $firstAwb;
                } else {
                $ph       = implode ( ',', array_fill ( 0, count ( $serviceTypes ), '?' ) );
                $nextStmt = $pdo->prepare ( "SELECT serial_number FROM tbl_serial_numbers WHERE branch_id = ? AND service_type IN ($ph) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled') ORDER BY serial_number ASC LIMIT 1" );
                $nextStmt->execute ( array_merge ( [ $branchId ], $serviceTypes ) );
                $nextRow = $nextStmt->fetch ( PDO::FETCH_ASSOC );
                if ($nextRow) {
                    $preferredWaybill = $nextRow[ 'serial_number' ];
                    }
                }
            // Validate every child box AWB (column 34): if non-empty, must be allocated and available
            $boxNum = 0;
            foreach ($groupRows as $gr) {
                $boxNum++;
                $awbVal = trim ( (string) ($gr[ 'data' ][ 34 ] ?? '') );
                if ($awbVal === '')
                    continue;
                $chkStmt = $pdo->prepare ( "SELECT id, branch_id, service_type FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) AND is_used = 0 AND status IN ('available', 'reserved', 'cancelled')" );
                $chkStmt->execute ( [ ':sn' => $awbVal ] );
                $chkRow = $chkStmt->fetch ( PDO::FETCH_ASSOC );
                if ( ! $chkRow) {
                    $errCol = 34;
                    throw new Exception( "Serial \"{$awbVal}\" is not allocated or already used (child box {$boxNum}). Remove it or use a valid serial from allocation." );
                    }
                if ((int) $chkRow[ 'branch_id' ] !== $branchId) {
                    $errCol = 34;
                    throw new Exception( "Serial \"{$awbVal}\" does not belong to selected branch (child box {$boxNum})." );
                    }
                $st         = strtolower ( $chkRow[ 'service_type' ] ?? '' );
                $serialAir  = in_array ( $st, [ 'express', 'air' ], true );
                $serialSurf = ($st === 'surface');
                $match      = ($isSurface && $serialSurf) || ( ! $isSurface && $serialAir);
                if ( ! $match) {
                    $errCol  = 34;
                    $forMode = $serialAir ? 'Air/Express' : 'Surface';
                    throw new Exception( "Serial \"{$awbVal}\" is for {$forMode} (child box {$boxNum}); shipping mode does not match." );
                    }
                }
            // When column 34 is empty: only the first row gets the one serial from allocation.
            // Subsequent rows with empty column 34 keep it empty — the insert loop derives their AWBs as parent-1, parent-2, etc.
            // Only 1 serial is consumed per booking regardless of how many rows the group has.
            $firstRowEmpty = trim ( (string) ($packageDetails[ 0 ][ 'child_ewaybill_no' ] ?? '') ) === '';
            if ($firstRowEmpty) {
                if ($preferredWaybill === null || $preferredWaybill === '') {
                    $errCol = 34;
                    throw new Exception( "No serials in allocation for this branch and shipping mode. Add serials or enter AWB in column 34." );
                    }
                $packageDetails[ 0 ][ 'child_ewaybill_no' ] = $preferredWaybill;
                }
            $preferredWaybill = trim ( (string) ($packageDetails[ 0 ][ 'child_ewaybill_no' ] ?? '') ) ?: $preferredWaybill;
            }

        // Pickup Point: resolve by col 35 name first, then fallback to branch default
        $pPoint      = null;
        $isOwnCourier = ($courierData[ 'id' ] == 2);

        // Detect Delhivery
        $bulkPartnerCode = strtolower ( trim ( $courierData[ 'partner_code' ] ?? '' ) );
        $bulkPartnerName = strtolower ( trim ( $courierData[ 'partner_name' ] ?? '' ) );
        $isBulkDelhivery = (strpos ( $bulkPartnerCode, 'del' ) === 0 || strpos ( $bulkPartnerName, 'delhivery' ) !== false);

        // Delhivery validation: order id comes from Ref ID and must be unique.
        if ($isBulkDelhivery) {
            $dupRefStmt = $pdo->prepare ( "SELECT id FROM tbl_bookings WHERE courier_id = :cid AND LOWER(TRIM(booking_ref_id)) = LOWER(TRIM(:ref)) LIMIT 1" );
            $dupRefStmt->execute ( [ ':cid' => $courierData[ 'id' ], ':ref' => $refId ] );
            if ($dupRefStmt->fetch ( PDO::FETCH_ASSOC )) {
                $errCol = 5;
                throw new Exception( "Duplicate Ref ID / Order ID for Delhivery. Change Ref ID in column 6 and upload again." );
                }
            }

        if ($pickupPointName !== '') {
            // Search by exact name first, then LIKE
            $stmt = $pdo->prepare ( "SELECT id, name FROM tbl_pickup_points WHERE branch_id = :bid AND LOWER(TRIM(name)) = LOWER(TRIM(:pname)) LIMIT 1" );
            $stmt->execute ( [ ':bid' => $branch[ 'id' ], ':pname' => $pickupPointName ] );
            $pPoint = $stmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $pPoint) {
                $stmt = $pdo->prepare ( "SELECT id, name FROM tbl_pickup_points WHERE branch_id = :bid AND name LIKE :pname LIMIT 1" );
                $stmt->execute ( [ ':bid' => $branch[ 'id' ], ':pname' => "%$pickupPointName%" ] );
                $pPoint = $stmt->fetch ( PDO::FETCH_ASSOC );
                }
            if ( ! $pPoint) {
                $errCol = 35;
                throw new Exception( "Pickup Point '{$pickupPointName}' not found for this branch." );
                }
            } elseif ( ! $isOwnCourier) {
            // No name given — fallback to branch default pickup point
            $stmt = $pdo->prepare ( "SELECT id, name, email FROM tbl_pickup_points WHERE branch_id = :bid LIMIT 1" );
            $stmt->execute ( [ ':bid' => $branch[ 'id' ] ] );
            $pPoint = $stmt->fetch ( PDO::FETCH_ASSOC );
            if ( ! $pPoint) {
                $errCol = 35;
                throw new Exception( "No Pickup Point found for this branch. Add one in Pickup Point setup or specify name in column 36." );
                }
            } else {
            // Own Courier — pickup point optional
            $stmt = $pdo->prepare ( "SELECT id, name, email FROM tbl_pickup_points WHERE branch_id = :bid LIMIT 1" );
            $stmt->execute ( [ ':bid' => $branch[ 'id' ] ] );
            $pPoint = $stmt->fetch ( PDO::FETCH_ASSOC );
            }

        // Shiprocket requires billing/shipping email.
        // If consignee email is missing from upload/row, fallback to pickup point email.
        if (empty($cEmail) && is_array($pPoint) && !empty($pPoint['email'])) {
            $cEmail = (string)$pPoint['email'];
        }

        // API Call using proper syncBookingWithCourier
        $shipmentRequest = [
            'booking_ref_id' => $refId,
            'consignee_name' => $cName,
            'consignee_phone' => $cPhone,
            'consignee_email' => $cEmail,
            'consignee_gst' => $cGst,
            'consignee_address' => $cAddr,
            'consignee_pin' => $cPin,
            'consignee_city' => ucfirst ( strtolower ( $cCity ) ),
            'consignee_state' => ucfirst ( strtolower ( $cState ) ),
            'consignee_country' => 'India',
            'payment_mode' => $payMode,
            'cod_amount' => $codAmt,
            'product_desc' => $prodDesc,
            'quantity' => $totalBoxes,
            'weight' => round ( $totalActualWeight * 1000, 2 ),
            'length' => $maxL,
            'width' => $maxW,
            'height' => $maxH,
            'shipping_mode' => $shipMode,
            'pickup_location_name' => $pPoint ? $pPoint[ 'name' ] : '',
            'invoice_no' => $invNo,
            'invoice_date' => ( ! empty ($date) && strtotime ( $date )) ? date ( 'Y-m-d', strtotime ( $date ) ) : date ( 'Y-m-d' ),
            'invoice_value' => $invVal,
            'ewaybill_no' => $ewayNo,
            'package_details' => $packageDetails,
            'shipper_name' => $sName,
            'shipper_phone' => $sPhone,
            'shipper_address' => $sAddr,
            'shipper_pin' => $sPin,
            'shipper_city' => $sCity,
            'shipper_state' => $sState,
            'rto_address' => $sAddr,
            'shiprocket_courier_company_id' => $srCourierCompanyId > 0 ? $srCourierCompanyId : null,
            'shiprocket_courier_company_name' => $srCourierCompanyName,
        ];
        if ($preferredWaybill !== null) {
            $shipmentRequest[ 'preferred_waybill' ] = $preferredWaybill;
            }

        $syncResult = syncBookingWithCourier ( $pdo, $courierData, $shipmentRequest );
        if (empty ($syncResult[ 'success' ]) && $isBulkDelhivery) {
            $syncMsg = strtolower ( (string) ($syncResult[ 'message' ] ?? '') );
            if (strpos ( $syncMsg, 'duplicate order id' ) !== false || strpos ( $syncMsg, 'order id already exists' ) !== false) {
                // Safety retry for race/remote mismatch: append short suffix to make order id unique.
                $baseRef = preg_replace ( '/[^A-Za-z0-9\-_]/', '-', (string) $refId );
                $baseRef = trim ( $baseRef, '-_' );
                if ($baseRef === '') {
                    $baseRef = 'BULK';
                    }
                $suffix = date ( 'His' ) . rand ( 10, 99 );
                $refId  = substr ( $baseRef, 0, 40 ) . '-' . $suffix;
                $shipmentRequest[ 'booking_ref_id' ] = $refId;
                $syncResult = syncBookingWithCourier ( $pdo, $courierData, $shipmentRequest );
                }
            }
        if (empty ($syncResult[ 'success' ])) {
            throw new Exception( $syncResult[ 'message' ] ?? 'Courier sync failed' );
            }

        $waybillNo = $syncResult[ 'waybill' ];

        // If Shiprocket created a remote shipment_id, auto-assign AWB.
        // If AWB assignment fails, stop before inserting the booking row.
        if (!empty($syncResult['shipment_id'])) {
            $awbResult = assignAwbWithShiprocket(
                $courierData,
                $syncResult['shipment_id'],
                ($srCourierCompanyId > 0 ? $srCourierCompanyId : null)
            );
            if (empty($awbResult['success'])) {
                throw new Exception($awbResult['message'] ?? 'Shiprocket AWB assignment failed');
            }

            // Auto-fill Shiprocket courier service name from assign/awb response
            // so bulk upload stores service name even when only courier ID is given.
            if (trim((string)$srCourierCompanyName) === '') {
                $srCourierCompanyName = trim((string)(
                    $awbResult['api_response']['response']['data']['courier_name']
                    ?? $awbResult['api_response']['courier_name']
                    ?? ''
                ));
            }

            $awbCode = trim((string)($awbResult['awb_code'] ?? ''));
            if ($awbCode === '') {
                throw new Exception('Shiprocket AWB assignment returned empty awb_code');
            }

            $waybillNo = $awbCode;

            if (is_array($syncResult['api_response'] ?? null)) {
                $syncResult['api_response']['awb_assign'] = $awbResult['api_response'] ?? $awbResult;
            }
        }

        // Fallback: try to pick courier name from order-create response if still empty.
        if (trim((string)$srCourierCompanyName) === '' && is_array($syncResult['api_response'] ?? null)) {
            $srCourierCompanyName = trim((string)(
                $syncResult['api_response']['response']['data']['courier_name']
                ?? $syncResult['api_response']['courier_name']
                ?? ''
            ));
        }

        $apiResponseJson = json_encode ( $syncResult[ 'api_response' ] ?? [] );

        // DB Insert (branch_id, client_id for client-based branch and booking)
        $sql = "INSERT INTO tbl_bookings (
            booking_ref_id, waybill_no, courier_id, pickup_point_id, branch_id, client_id,
            consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
            consignee_city, consignee_state, consignee_country,
            shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
            payment_mode, cod_amount, weight, length, width, height,
            shipping_mode, product_desc, package_details, quantity, is_mps,
            invoice_no, invoice_value, ewaybill_no,
            rto_name, rto_phone, rto_address,
            shiprocket_courier_company_name, shiprocket_courier_company_id,
            api_response, last_status, created_at, created_by
        ) VALUES (
            :ref, :wb, :cid, :pid, :branch_id, :client_id,
            :cname, :cphone, :cemail, :cgst, :caddr, :cpin,
            :ccity, :cstate, 'India',
            :sname, :sphone, :saddr, :spin, :scity, :sstate,
            :pmode, :cod, :wt, :len, :wid, :hgt,
            :smode, :desc, :pkg, :qty, :mps,
            :invNo, :invVal, :eway,
            :rtname, :rtphone, :rtaddr,
            :sr_courier_name, :sr_courier_id,
            :api, 'Created', NOW(), :uid
        )";

        $stmt = $pdo->prepare ( $sql );
        $stmt->execute ( [
            ':ref' => $refId,
            ':wb' => $waybillNo,
            ':cid' => $courierData[ 'id' ],
            ':pid' => $pPoint ? $pPoint[ 'id' ] : null,
            ':branch_id' => $branchId,
            ':client_id' => $clientId,
            ':cname' => $cName,
            ':cphone' => $cPhone,
            ':cemail' => $cEmail,
            ':cgst' => $cGst,
            ':caddr' => $cAddr,
            ':cpin' => $cPin,
            ':ccity' => ucfirst ( strtolower ( $cCity ) ),
            ':cstate' => ucfirst ( strtolower ( $cState ) ),
            ':sname' => $sName,
            ':sphone' => $sPhone,
            ':saddr' => $sAddr,
            ':spin' => $sPin,
            ':scity' => $sCity,
            ':sstate' => $sState,
            ':pmode' => $payMode,
            ':cod' => $codAmt,
            ':wt' => round ( $totalActualWeight * 1000, 2 ),
            ':len' => $maxL,
            ':wid' => $maxW,
            ':hgt' => $maxH,
            ':smode' => $shipMode,
            ':desc' => $prodDesc,
            ':pkg' => json_encode ( $packageDetails ),
            ':qty' => $totalBoxes,
            ':mps' => ($totalBoxes > 1 ? 1 : 0),
            ':invNo' => $invNo,
            ':invVal' => $invVal,
            ':eway' => $ewayNo,
            ':rtname' => $sName,
            ':rtphone' => $sPhone,
            ':rtaddr' => $sAddr,
            ':sr_courier_name' => $srCourierCompanyName !== '' ? $srCourierCompanyName : null,
            ':sr_courier_id' => $srCourierCompanyId > 0 ? $srCourierCompanyId : null,
            ':api' => $apiResponseJson,
            ':uid' => $userId
        ] );

        $bookingId = $pdo->lastInsertId ();

        // Delhivery E-waybill update for invoice value > 5000.
        $ewbUpdateStatus = 'not_required';
        $ewbUpdatePayload = null;
        if ($isBulkDelhivery && (float)$invVal > 5000) {
            if (trim((string)$invNo) === '' || trim((string)$ewayNo) === '' || trim((string)$waybillNo) === '') {
                $ewbUpdateStatus = 'failed';
                $ewbUpdatePayload = [
                    'success' => false,
                    'message' => 'EWB update skipped: waybill, invoice no and e-waybill are required for invoice value > 5000'
                ];
            } else {
                $ewbResult = updateDelhiveryEwaybill($courierData, $waybillNo, $invNo, $ewayNo);
                $ewbUpdateStatus = !empty($ewbResult['success']) ? 'success' : 'failed';
                $ewbUpdatePayload = $ewbResult;
            }
        }
        try {
            $pdo->prepare("UPDATE tbl_bookings
                SET ewb_update_status = :st,
                    ewb_update_response = :resp,
                    ewb_update_at = NOW()
                WHERE id = :id")
                ->execute([
                    ':st' => $ewbUpdateStatus,
                    ':resp' => json_encode($ewbUpdatePayload ?? ['status' => 'not_required']),
                    ':id' => $bookingId
                ]);
        } catch ( Exception $e ) {
            // Non-blocking: keep booking flow successful if schema update is pending.
        }

        // Own Courier: delete only the parent serial from tbl_serial_numbers (child-derived AWBs like BNG-013-1 are not in the table)
        if ($courierData[ 'id' ] == 2) {
            $serDelStmt = $pdo->prepare ( "SELECT id, allocation_id FROM tbl_serial_numbers WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn))" );
            foreach ($packageDetails as $pkg) {
                $sn = trim ( (string) ($pkg[ 'child_ewaybill_no' ] ?? '') );
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
                    // don't fail bulk row
                    }
                }
            }

        // Insert per-box rows into tbl_booking_packages
        $pkgInsertSql = "INSERT INTO tbl_booking_packages
            (booking_id, waybill_no, row_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight)
            VALUES
            (:bid, :wn, :row_no, :awb_no, :child_eway, :len, :wid, :hei, :boxes, :act_wt, :vol_wt, :chg_wt)";
        $pkgStmt         = $pdo->prepare ( $pkgInsertSql );
        $globalRowNo     = 0;
        $delhiveryWaybills = $syncResult[ 'all_waybills' ] ?? [];
        foreach ($packageDetails as $idx => $pkg) {
            $pkgBoxCount = max ( 1, (int) ($pkg[ 'boxes' ] ?? 1) );
            // Determine the base AWB for this package row
            if ($courierData[ 'id' ] == 2 && ! empty ($waybillNo)) {
                // Own Courier: use assigned serial / derived AWBs
                $baseAwb = (trim ( (string) ($pkg[ 'child_ewaybill_no' ] ?? '') ) !== '')
                    ? $pkg[ 'child_ewaybill_no' ]
                    : ($idx === 0 ? $waybillNo : $waybillNo . '-' . $idx);
                } elseif ( ! empty ($delhiveryWaybills)) {
                // Delhivery MPS: each package gets its own pre-fetched waybill
                $baseAwb = $delhiveryWaybills[$idx] ?? (($idx === 0) ? $waybillNo : $waybillNo . '-' . $idx);
                } else {
                $baseAwb = ($idx === 0 && ! empty ($waybillNo)) ? $waybillNo : ($pkg[ 'awb_no' ] ?? '');
                }
            // Expand into one DB row per physical box
            for ($b = 0; $b < $pkgBoxCount; $b++) {
                $globalRowNo++;
                if ($b === 0) {
                    $rowAwb    = $baseAwb;
                    $childEway = $rowAwb !== '' ? $rowAwb : null;
                    } else {
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
                    ':boxes' => 1,  // each row = 1 physical box
                    ':act_wt' => $pkg[ 'actual_weight' ],
                    ':vol_wt' => $pkg[ 'vol_weight' ],
                    ':chg_wt' => $pkg[ 'charged_weight' ],
                ] );
                }
            }

        // Insert initial tracking record (same pattern as create.php)
        if ( ! empty ($waybillNo)) {
            $initialTrackingRaw = json_encode ( [
                'awb_no' => $waybillNo,
                'shipment_details' => [
                    'booking_ref_id' => $refId,
                    'consignee_name' => $cName,
                    'consignee_phone' => $cPhone,
                    'payment_mode' => $payMode,
                ],
                'current_status' => 'Created',
                'scan_details' => [
                    'status' => 'Booking Created',
                    'location' => $sCity,
                    'datetime' => date ( 'Y-m-d H:i:s' ),
                    'remarks' => 'Shipment created via Bulk Upload',
                ],
                'scan_details_history' => [
                    [
                        'status' => 'Booking Created',
                        'location' => $sCity,
                        'datetime' => date ( 'Y-m-d H:i:s' ),
                        'remarks' => 'Shipment created via Bulk Upload',
                        'updated_by' => $userId,
                        'updated_at' => date ( 'Y-m-d H:i:s' ),
                    ]
                ]
            ] );
            $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_location, scan_datetime, remarks, raw_response)
                           VALUES (:bid, :wn, 'Booking Created', 'Created', :sl, NOW(), 'Shipment created via Bulk Upload', :raw)" )
                ->execute ( [ ':bid' => $bookingId, ':wn' => $waybillNo, ':sl' => $sCity, ':raw' => $initialTrackingRaw ] );
            }

        // Cross-table synchronization (Manifest, Run Sheet, etc.)
        syncShipmentStatusAcrossTables ( $pdo, $bookingId, $waybillNo, 'Created', 'Shipment created via Bulk Upload', $username );

        // ── Auto Pickup Request for Delhivery ──────────────────────────────────
        if ($isBulkDelhivery && $pPoint && ! empty ($courierData[ 'api_key' ]) && ! empty ($courierData[ 'api_url' ])) {
            try {
                $prDate   = date ( 'Y-m-d' );
                $nextHour = (int) date ( 'H', strtotime ( '+1 hour' ) );
                $prHour   = max ( 11, $nextHour );
                $prTime   = str_pad ( $prHour, 2, '0', STR_PAD_LEFT ) . ':00:00';
                $prLocName = $pPoint[ 'name' ];

                // Check if pickup already raised today for this pickup point
                $existStmt = $pdo->prepare (
                    "SELECT id, expected_package_count FROM tbl_pickup_requests
                     WHERE pickup_point_id = :ppid AND pickup_date = :pdate
                       AND status NOT IN ('Failed','Cancelled')
                     ORDER BY id DESC LIMIT 1"
                );
                $existStmt->execute ( [ ':ppid' => $pPoint[ 'id' ], ':pdate' => $prDate ] );
                $existRow = $existStmt->fetch ( PDO::FETCH_ASSOC );

                if ($existRow) {
                    $newCount = (int) $existRow[ 'expected_package_count' ] + $totalBoxes;
                    $pdo->prepare ( "UPDATE tbl_pickup_requests SET expected_package_count = :cnt, updated_at = NOW() WHERE id = :id" )
                        ->execute ( [ ':cnt' => $newCount, ':id' => $existRow[ 'id' ] ] );
                    } else {
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
                         VALUES (:ppid, :cid, :loc, :pdate, :ptime, :pkgcnt, :status, :reqid, :apiresp, :uid, NOW())"
                    )->execute ( [
                        ':ppid'    => $pPoint[ 'id' ],
                        ':cid'     => $courierData[ 'id' ],
                        ':loc'     => $prLocName,
                        ':pdate'   => $prDate,
                        ':ptime'   => $prTime,
                        ':pkgcnt'  => $totalBoxes,
                        ':status'  => $prStatus,
                        ':reqid'   => $prReqId,
                        ':apiresp' => $prResp,
                        ':uid'     => $userId,
                    ] );
                    }
                } catch ( Exception $e ) {
                // Silent fail — pickup error must not block bulk booking
                }
            }
        // ── End Auto Pickup Request ─────────────────────────────────────────────

        $successCount++;   // 1 per booking (group), not per Excel row
        $status                   = 'Success';
        $packageDetailsForResult  = $packageDetails;
        }
    catch ( Exception $e ) {
        $status    = 'Failed';
        $errorMsg  = $e->getMessage ();
        $failCount++;      // 1 per booking (group), not per Excel row
        }

    foreach ($groupRows as $gri => $gr) {
        $idx     = $gr[ 'index' ];
        $rowData = array_pad ( $gr[ 'data' ], 37, '' );
        $rowData[ 5 ] = $refId;
        // Column 31 = Ewaybill No (always show the value we used from first row). Do NOT put AWB/waybill here.
        $rowData[ 31 ] = $ewayNo;
        if ($status === 'Success' && $packageDetailsForResult !== null && isset ($packageDetailsForResult[$gri][ 'child_ewaybill_no' ]) && trim ( (string) $packageDetailsForResult[$gri][ 'child_ewaybill_no' ] ) !== '') {
            $rowData[ 34 ] = $packageDetailsForResult[$gri][ 'child_ewaybill_no' ];
            }
        $rowData[]       = $waybillNo;   // appended column 36 = Waybill (AWB)
        $rowData[]       = $status;
        $rowData[]       = $errorMsg;
        $rowData[]       = ($status === 'Failed') ? $errCol : -1;
        $resultMap[$idx] = $rowData;
        }

    $processedGroups++;
    try {
        $pdo->prepare("UPDATE tbl_bulkupload_jobs
            SET success_count = :suc,
                failure_count = :fail,
                status = 'Processing',
                updated_at = NOW()
            WHERE id = :id")
            ->execute([
                ':suc' => $successCount,
                ':fail' => $failCount,
                ':id' => $jobId
            ]);
    } catch (Exception $e) {
        // Non-blocking: UI can still use stream progress.
    }
    if ($streamMode) {
        echo "EVENT:PROGRESS " . json_encode([
            'job_id' => (int) $jobId,
            'completed' => $processedGroups,
            'total' => $totalGroups
        ]) . "\n";
        @ob_flush();
        @flush();
    }
    }

// Fixed result header with optional Shiprocket courier fields before output columns.
$resultHeader = [
    'Branch Name',
    'Booking Type',
    'Date (DD)',
    'Month (MM)',
    'Year (YYYY)',
    'Ref ID',
    'Courier',
    'Shipper Name',
    'Shipper Phone',
    'Shipper Pin',
    'Shipper Address',
    'Shipper City',
    'Shipper State',
    'Consignee Name',
    'Consignee Phone',
    'Consignee Email',
    'Consignee GST',
    'Consignee Address',
    'Consignee Pin',
    'Consignee City',
    'Consignee State',
    'Payment Mode (Prepaid/COD)',
    'COD Amount',
    'Product Desc',
    'Length (cm)',
    'Width (cm)',
    'Height (cm)',
    'Weight (kg/box)',
    'Boxes',
    'Invoice No',
    'Invoice Value',
    'Ewaybill No',
    'Shipping Mode (Surface/Air/Express)',
    'Client Name',
    'AWB No (Own Courier)',
    'Pickup Point Name (for Delhivery)',
    'Shiprocket Courier Company ID (Optional)',
    'Waybill',
    'Status',
    'Remarks'
];
$resultRows   = [ $resultHeader ];
ksort ( $resultMap );
foreach ($resultMap as $row) {
    $resultRows[] = $row;
    }

$finalStatus = ($failCount > 0) ? (($successCount > 0) ? 'Completed with Errors' : 'Failed') : 'Completed';
$pdo->prepare ( "UPDATE tbl_bulkupload_jobs SET status = :st, total_records = :tot, success_count = :suc, failure_count = :fail, result_file = :res, branch_name = :bn, client_name = :cn, updated_at = NOW() WHERE id = :id" )
    ->execute ( [
        ':st' => $finalStatus,
        ':tot' => ($successCount + $failCount),
        ':suc' => $successCount,
        ':fail' => $failCount,
        ':res' => json_encode ( $resultRows ),
        ':bn' => $jobBranchName,
        ':cn' => $jobClientName,
        ':id' => $jobId
    ] );

if ($streamMode) {
    echo "EVENT:COMPLETE " . json_encode([
        'status' => 'success',
        'message' => "Processed " . ($successCount + $failCount) . " shipments",
        'job_id' => (int) $jobId,
        'completed' => $totalGroups,
        'total' => $totalGroups
    ]) . "\n";
} else {
    echo json_encode([ 'status' => 'success', 'message' => "Processed " . ($successCount + $failCount) . " shipments", 'job_id' => $jobId ]);
}
exit;
