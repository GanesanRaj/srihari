<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';
require_once '../booking/services/shiprocket.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    http_response_code ( 405 );
    echo json_encode ( [ 'status' => 'error', 'message' => 'Method not allowed' ] );
    exit;
}

if ( ! get_permission ( 'shipment', 'is_add' )) {
    require_api_permission ( 'shipment', 'is_add' );
}

$raw = file_get_contents ( 'php://input' );
$body = json_decode ( $raw, true );
if ( ! is_array ( $body )) {
    $body = $_POST;
}

$bookingIds = $body[ 'booking_ids' ] ?? [];
$pickupPointId = isset ( $body[ 'pickup_point_id' ] ) && $body[ 'pickup_point_id' ] !== '' ? (int) $body[ 'pickup_point_id' ] : 0;

if ( ! is_array ( $bookingIds ) || empty ( $bookingIds )) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Select at least one booking to manifest.' ] );
    exit;
}

$bookingIds = array_values ( array_unique ( array_filter ( array_map ( 'intval', $bookingIds ), function ($v)
    {
    return $v > 0;
    } ) ) );
if (empty ( $bookingIds )) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid booking ids.' ] );
    exit;
}

try {
    $roleId = (int) ($_SESSION[ 'role_id' ] ?? 0);
    $userId = (int) ($_SESSION[ 'user_id' ] ?? $_SESSION[ 'id' ] ?? 0);
    if ($userId <= 0 && $roleId === 1) {
        $userId = 1;
    }

    $ph = implode ( ',', array_fill ( 0, count ( $bookingIds ), '?' ) );
    $sql = "SELECT b.id, b.waybill_no, b.api_response, b.pickup_point_id, b.courier_id,
                   c.partner_name AS courier_name, c.token AS courier_token, p.name AS pickup_point_name
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON c.id = b.courier_id
            LEFT JOIN tbl_pickup_points p ON p.id = b.pickup_point_id
            WHERE b.id IN ($ph)";
    $stmt = $pdo->prepare ( $sql );
    foreach ($bookingIds as $i => $bid) {
        $stmt->bindValue ( $i + 1, $bid, PDO::PARAM_INT );
    }
    $stmt->execute ();
    $rows = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    if (empty ( $rows )) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'No bookings found for selected ids.' ] );
        exit;
    }

    $awbs = [];
    $matchedIds = [];
    $shipmentIds = [];
    $shipmentIdToAwb = [];
    $orderIds = [];
    $orderIdToAwb = [];
    $courierData = null;
    foreach ($rows as $r) {
        $matchedIds[] = (int) ($r[ 'id' ] ?? 0);
        $courierName = strtolower ( (string) ($r[ 'courier_name' ] ?? '') );
        if ($courierName !== '' && strpos ( $courierName, 'shiprocket' ) === false && (int) ($r[ 'courier_id' ] ?? 0) !== 4) {
            continue;
        }
        if ($courierData === null) {
            $courierData = [
                'token' => (string) ($r['courier_token'] ?? ''),
            ];
        }
        $wb = trim ( (string) ($r[ 'waybill_no' ] ?? '') );
        if ($wb !== '') {
            $awbs[] = $wb;
        }

        $apiRespRaw = $r['api_response'] ?? '';
        $apiResp = is_string($apiRespRaw) ? json_decode((string)$apiRespRaw, true) : (is_array($apiRespRaw) ? $apiRespRaw : null);
        if (is_array($apiResp)) {
            $sid = (int) trim((string)($apiResp['shipment_id'] ?? $apiResp['response']['data']['shipment_id'] ?? ($apiResp['awb_assign']['response']['data']['shipment_id'] ?? '0')));
            $oid = (int) trim((string)($apiResp['order_id'] ?? $apiResp['response']['data']['order_id'] ?? ($apiResp['awb_assign']['response']['data']['order_id'] ?? '0')));
            if ($sid > 0) {
                $shipmentIds[] = $sid;
                if ($wb !== '') {
                    $shipmentIdToAwb[(string)$sid] = $wb;
                }
            }
            if ($oid > 0) {
                $orderIds[] = $oid;
                if ($wb !== '') {
                    $orderIdToAwb[(string)$oid] = $wb;
                }
            }
        }
    }

    if (empty ( $awbs )) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'No Shiprocket AWBs found in selected bookings.' ] );
        exit;
    }
    if ($courierData === null || trim((string)($courierData['token'] ?? '')) === '') {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Shiprocket token missing for selected bookings.' ] );
        exit;
    }
    if (empty($shipmentIds)) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Missing shipment_id in selected bookings. Generate AWB/Pickup first.' ] );
        exit;
    }
    if (empty($orderIds)) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Missing order_id in selected bookings.' ] );
        exit;
    }

    $pickupPointName = '';
    if ($pickupPointId > 0) {
        $pp = $pdo->prepare ( "SELECT name FROM tbl_pickup_points WHERE id = :id LIMIT 1" );
        $pp->execute ( [ ':id' => $pickupPointId ] );
        $pickupPointName = trim ( (string) ($pp->fetchColumn () ?: '') );
    }
    if ($pickupPointName === '') {
        $first = $rows[0] ?? [];
        $pickupPointName = trim ( (string) ($first[ 'pickup_point_name' ] ?? '') );
    }

    $manifestedId = 'SRM-' . date ( 'YmdHis' ) . '-' . str_pad ( (string) random_int ( 1, 9999 ), 4, '0', STR_PAD_LEFT );
    $manifestDate = date ( 'Y-m-d' );

    // ── Step 1: Auto-request pickup (required before manifest) ──
    // Shiprocket workflow: Create Order → Assign AWB → Generate Pickup → Generate Manifest
    // Without pickup requested, manifest generation will fail.
    $pickupResult = requestPickupWithShiprocket($courierData, $shipmentIds);
    $pickupResponse = $pickupResult['api_response'] ?? null;
    // Pickup is non-blocking: if already requested, we still proceed.
    // Only block if pickup explicitly fails with a hard error (not "already requested").
    if (empty($pickupResult['success'])) {
        $pickupMsg = strtolower((string)($pickupResult['message'] ?? ''));
        $isAlreadyOk = (strpos($pickupMsg, 'already') !== false);
        if (!$isAlreadyOk) {
            // Log pickup failure but still try manifest (some shipments may already have pickup)
            // If manifest also fails, user gets both error details.
        }
    }

    // ── Step 2: Generate Manifest ──
    $genManifest = generateManifestWithShiprocket($courierData, $shipmentIds);

    if (empty($genManifest['success'])) {
        $genResp = is_array($genManifest['api_response'] ?? null) ? $genManifest['api_response'] : [];
        $failedShipmentIds = [];
        if (isset($genResp['check_ids']) && is_array($genResp['check_ids'])) {
            $failedShipmentIds = array_values(array_filter(array_map('intval', $genResp['check_ids']), function ($v) {
                return $v > 0;
            }));
        }
        // Fallback parse for plain-text error:
        // "Manifest not generated (check shipment_ids: 1259,1260)"
        if (empty($failedShipmentIds)) {
            $rawErr = trim((string)($genResp['error'] ?? $genManifest['message'] ?? ''));
            if ($rawErr !== '' && preg_match('/check\s+shipment_ids\s*:\s*([0-9,\s]+)/i', $rawErr, $m)) {
                $parts = preg_split('/\s*,\s*/', trim((string)$m[1]));
                if (is_array($parts)) {
                    $failedShipmentIds = array_values(array_filter(array_map('intval', $parts), function ($v) {
                        return $v > 0;
                    }));
                }
            }
        }
        $failedAwbs = [];
        foreach ($failedShipmentIds as $sid) {
            $key = (string)$sid;
            if (isset($shipmentIdToAwb[$key]) && $shipmentIdToAwb[$key] !== '') {
                $failedAwbs[] = $shipmentIdToAwb[$key];
            }
        }
        $failedAwbs = array_values(array_unique($failedAwbs));
        $errMsg = $genManifest['message'] ?? 'Manifest generation failed';
        if (!empty($failedAwbs)) {
            $errMsg .= ' | Failed AWB: ' . implode(', ', $failedAwbs);
        }
        echo json_encode([
            'status' => 'error',
            'message' => $errMsg,
            'failed_awbs' => $failedAwbs,
            'failed_shipment_ids' => $failedShipmentIds,
            'api_response' => $genResp,
            'pickup_response' => $pickupResponse ?? null,
            'pickup_success' => !empty($pickupResult['success']),
        ]);
        exit;
    }

    $printManifest = printManifestWithShiprocket($courierData, $orderIds);
    if (empty($printManifest['success'])) {
        echo json_encode([ 'status' => 'error', 'message' => $printManifest['message'] ?? 'Manifest print failed', 'generate_response' => $genManifest['api_response'] ?? null, 'api_response' => $printManifest['api_response'] ?? null ]);
        exit;
    }

    $responsePayload = [
        'booking_ids' => $matchedIds,
        'awb_count' => count ( $awbs ),
        'awbs' => $awbs,
        'shipment_ids' => array_values(array_unique(array_map('intval', $shipmentIds))),
        'order_ids' => array_values(array_unique(array_map('intval', $orderIds))),
        'pickup_request' => $pickupResponse ?? null,
        'generate_manifest' => $genManifest['api_response'] ?? null,
        'print_manifest' => $printManifest['api_response'] ?? null,
        'generate_manifest_url' => (string)($genManifest['manifest_url'] ?? ''),
        'print_manifest_url' => (string)($printManifest['manifest_url'] ?? ''),
    ];

    // ── Step 3: Generate Invoice (Non-blocking) ──
    $invoiceResult = generateInvoiceWithShiprocket($courierData, $orderIds);
    $invoiceResp = $invoiceResult['api_response'] ?? [];
    $invoiceGeneratedAwbs = [];
    $invoiceFailedAwbs = [];
    
    if (!empty($invoiceResult['success'])) {
        $notCreated = $invoiceResp['not_created'] ?? [];
        if (!is_array($notCreated)) $notCreated = [];
        $failedOids = array_map('intval', $notCreated);
        
        foreach ($orderIds as $oid) {
            $wb = $orderIdToAwb[(string)$oid] ?? '';
            if ($wb === '') continue;
            
            if (!in_array($oid, $failedOids)) {
                $invoiceGeneratedAwbs[] = $wb;
            } else {
                $invoiceFailedAwbs[] = $wb;
            }
        }
        $responsePayload['invoice_url'] = $invoiceResult['invoice_url'] ?? '';
    }
    $responsePayload['invoice_response'] = $invoiceResp;
    $responsePayload['invoice_generated_awb'] = array_values(array_unique($invoiceGeneratedAwbs));


    $pdo->beginTransaction ();

    $ins = $pdo->prepare ( "INSERT INTO shiprocket_manifest
        (manifest_date, manifested_id, pickuppoint, manifstered_awb, created_by, response, invoice_response, invoice_generated_awb)
        VALUES (:manifest_date, :manifested_id, :pickuppoint, :manifstered_awb, :created_by, :response, :invoice_response, :invoice_generated_awb)" );
    $ins->execute ( [
        ':manifest_date' => $manifestDate,
        ':manifested_id' => $manifestedId,
        ':pickuppoint' => $pickupPointName !== '' ? $pickupPointName : null,
        ':manifstered_awb' => json_encode ( $awbs ),
        ':created_by' => $userId > 0 ? $userId : null,
        ':response' => json_encode ( $responsePayload ),
        ':invoice_response' => json_encode ( $invoiceResp ),
        ':invoice_generated_awb' => json_encode ( array_values(array_unique($invoiceGeneratedAwbs)) ),
    ] );

    $up = $pdo->prepare ( "UPDATE tbl_bookings SET is_manifest = 1 WHERE id IN ($ph)" );
    foreach ($matchedIds as $i => $bid) {
        $up->bindValue ( $i + 1, $bid, PDO::PARAM_INT );
    }
    $up->execute ();

    $pdo->commit ();

    // Do not include cron script here; it prints logs and can corrupt JSON API output.
    // Tracking sync continues via normal cron schedule.
    $pickupDetails = [];
    if (is_array($pickupResponse)) {
        foreach ($pickupResponse as $sid => $pData) {
            $key = (string)$sid;
            if (isset($shipmentIdToAwb[$key]) && $shipmentIdToAwb[$key] !== '') {
                $rawToken = $pData['pickup_token_number'] ?? ($pData['message'] ?? 'N/A');
                $cleanToken = trim(str_ireplace(['Reference No:', 'Reference No', 'Ref No:', 'Ref No', 'Reference:', 'Reference'], '', $rawToken));
                // Remove any non-alphanumeric characters except basic spaces if needed, but keeping it simple first.
                // The user said "alpha numeric only integer only", so let's strip anything that isn't a digit or letter.
                if (preg_match('/[0-9]+/', $cleanToken)) {
                    $cleanToken = preg_replace('/[^a-zA-Z0-9]/', '', $cleanToken);
                }

                $pickupDetails[] = [
                    'awb' => $shipmentIdToAwb[$key],
                    'pickup_scheduled_date' => $pData['pickup_scheduled_date'] ?? 'N/A',
                    'pickup_token_number' => $cleanToken
                ];
            }
        }
    }

    echo json_encode ( [
        'status' => 'success',
        'message' => 'Manifest generated successfully.',
        'manifested_id' => $manifestedId,
        'manifest_date' => $manifestDate,
        'pickuppoint' => $pickupPointName,
        'awb_count' => count ( $awbs ),
        'awbs' => $awbs,
        'pickup_details' => $pickupDetails,
        'manifest_url' => (string)($printManifest['manifest_url'] ?? $genManifest['manifest_url'] ?? ''),
        'generate_manifest_url' => (string)($genManifest['manifest_url'] ?? ''),
        'print_manifest_url' => (string)($printManifest['manifest_url'] ?? ''),
        'invoice_url' => (string)($invoiceResult['invoice_url'] ?? ''),
        'invoice_generated_awb' => array_values(array_unique($invoiceGeneratedAwbs)),
    ] );
} catch (Throwable $e) {
    try {
        if ($pdo->inTransaction ()) {
            $pdo->rollBack ();
        }
    } catch (Throwable $ignore) {
    }
    http_response_code ( 500 );
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
}
?>
