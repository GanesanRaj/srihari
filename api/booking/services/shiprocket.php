<?php
/**
 * Shiprocket booking handler
 *
 * Creates Shiprocket order using:
 * POST /v1/external/orders/create/adhoc
 */

function syncBookingWithShiprocket($pdo, $courierData, $shipmentData)
{
    try {
        $token = trim((string)($courierData['token'] ?? ''));
        if ($token === '') {
            throw new Exception('Shiprocket token missing for this courier partner');
        }
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $pickupLocation = trim((string)($shipmentData['pickup_location_name'] ?? ''));
        if ($pickupLocation === '') {
            throw new Exception('Missing pickup_location_name for Shiprocket');
        }

        $bookingRefId = trim((string)($shipmentData['booking_ref_id'] ?? ''));
        $orderId = preg_replace('/\D+/', '', $bookingRefId);
        if ($orderId === '') {
            $orderId = (string)time();
        }
        $orderId = substr($orderId, 0, 50);

        $orderDate = date('Y-m-d H:i:s');

        // Billing details: reuse consignee as Shiprocket billing/shipping for adhoc quick order.
        $billing_customer_name = trim((string)($shipmentData['consignee_name'] ?? ''));
        $billing_phone = preg_replace('/\D+/', '', (string)($shipmentData['consignee_phone'] ?? ''));
        $billing_address = trim((string)($shipmentData['consignee_address'] ?? ''));
        $billing_pincode = trim((string)($shipmentData['consignee_pin'] ?? ''));
        $billing_city = trim((string)($shipmentData['consignee_city'] ?? ''));
        $billing_state = trim((string)($shipmentData['consignee_state'] ?? ''));
        $billing_country = trim((string)($shipmentData['consignee_country'] ?? 'India'));
        $billing_email = trim((string)($shipmentData['consignee_email'] ?? ''));

        if ($billing_customer_name === '') throw new Exception('Missing consignee_name for Shiprocket');
        if ($billing_phone === '') throw new Exception('Missing consignee_phone for Shiprocket');
        if ($billing_address === '') throw new Exception('Missing consignee_address for Shiprocket');
        if ($billing_pincode === '') throw new Exception('Missing consignee_pin for Shiprocket');
        if ($billing_city === '') throw new Exception('Missing consignee_city for Shiprocket');
        if ($billing_state === '') throw new Exception('Missing consignee_state for Shiprocket');
        if ($billing_country === '') throw new Exception('Missing consignee_country for Shiprocket');
        if ($billing_email === '') throw new Exception('Missing consignee_email for Shiprocket');

        $paymentMode = strtoupper(trim((string)($shipmentData['payment_mode'] ?? 'Prepaid')));
        $codAmount = (float)($shipmentData['cod_amount'] ?? 0);
        $invoiceValue = (float)($shipmentData['invoice_value'] ?? 0);

        $payment_method = ($paymentMode === 'COD') ? 'COD' : 'Prepaid';
        $sellingTotal = ($payment_method === 'COD') ? $codAmount : $invoiceValue;

        if ($sellingTotal <= 0) {
            throw new Exception('Shiprocket selling price/sub_total requires invoice_value or cod_amount');
        }

        $units = (int)($shipmentData['quantity'] ?? 0);
        if ($units <= 0) $units = 1;

        $selling_price_per_unit = (int)floor($sellingTotal / max(1, $units));
        if ($selling_price_per_unit <= 0) $selling_price_per_unit = (int)$sellingTotal;

        $sub_total = (int)round($sellingTotal, 0);
        if ($sub_total <= 0) $sub_total = $selling_price_per_unit * $units;

        $len = (float)($shipmentData['length'] ?? 0);
        $wid = (float)($shipmentData['width'] ?? 0);
        $hei = (float)($shipmentData['height'] ?? 0);
        $weightGrams = (float)($shipmentData['weight'] ?? 0);
        $weightKg = $weightGrams > 0 ? ($weightGrams / 1000) : 0;

        if ($len <= 0 || $wid <= 0 || $hei <= 0) {
            throw new Exception('Shiprocket requires length/breadth/height (>0)');
        }
        if ($weightKg <= 0) {
            throw new Exception('Shiprocket requires weight (>0) in kg');
        }

        $productDesc = trim((string)($shipmentData['product_desc'] ?? ''));
        $itemName = $productDesc !== '' ? $productDesc : 'Shipment Item';
        $itemSku = $bookingRefId !== '' ? ('SKU-' . $bookingRefId) : ('SKU-' . $orderId);
        $itemSku = substr($itemSku, 0, 50);

        $rowCount = 1;
        $pkgDetails = $shipmentData['package_details'] ?? [];
        if (is_array($pkgDetails)) {
            $rowCount = max(1, count($pkgDetails));
        }

        // Build order_items in separate rows for multiple package entries
        // while keeping order-level info same.
        $orderItems = [];
        if (is_array($pkgDetails) && !empty($pkgDetails)) {
            foreach ($pkgDetails as $idx => $pkg) {
                $pkgUnits = (int)($pkg['boxes'] ?? 1);
                if ($pkgUnits <= 0) {
                    $pkgUnits = 1;
                }
                $itemLabel = $itemName;
                if (count($pkgDetails) > 1) {
                    $itemLabel .= ' - Item ' . ($idx + 1);
                }
                $itemSkuRow = substr($itemSku . '-' . ($idx + 1), 0, 50);
                $orderItems[] = [
                    'name' => $itemLabel,
                    'sku' => $itemSkuRow,
                    'units' => $pkgUnits,
                    'selling_price' => $selling_price_per_unit,
                    'discount' => 0,
                    'tax' => 0,
                    'hsn' => 0,
                ];
            }
        }
        if (empty($orderItems)) {
            $orderItems[] = [
                'name' => $itemName,
                'sku' => $itemSku,
                'units' => $units,
                'selling_price' => $selling_price_per_unit,
                'discount' => 0,
                'tax' => 0,
                'hsn' => 0,
            ];
        }

        $payload = [
            'order_id' => $orderId,
            'order_date' => $orderDate,
            'pickup_location' => $pickupLocation,
            'comment' => '',
            'billing_customer_name' => $billing_customer_name,
            'billing_last_name' => '',
            'billing_address' => $billing_address,
            'billing_address_2' => '',
            'billing_city' => $billing_city,
            'billing_pincode' => (int)$billing_pincode,
            'billing_state' => $billing_state,
            'billing_country' => $billing_country,
            'billing_email' => $billing_email,
            'billing_phone' => (int)$billing_phone,

            'shipping_is_billing' => true,
            'shipping_customer_name' => '',
            'shipping_last_name' => '',
            'shipping_address' => '',
            'shipping_address_2' => '',
            'shipping_city' => '',
            'shipping_pincode' => '',
            'shipping_country' => '',
            'shipping_state' => '',
            'shipping_email' => '',
            'shipping_phone' => '',

            'order_items' => $orderItems,

            'payment_method' => $payment_method,
            'shipping_charges' => 0,
            'giftwrap_charges' => 0,
            'transaction_charges' => 0,
            'total_discount' => 0,
            'sub_total' => $sub_total,

            'length' => $len,
            'breadth' => $wid,
            'height' => $hei,
            'weight' => round($weightKg, 3),
        ];

        $url = 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new Exception('Shiprocket order create cURL error: ' . $curlErr);
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded)) : substr((string)$response, 0, 800);
            throw new Exception('Shiprocket order create failed (HTTP ' . $httpCode . '): ' . $detail);
        }

        // Extract order_id and shipment_id from Shiprocket response.
        // Response often looks like: { "order_id": ..., "response": { "data": { "shipment_id": ..., "order_id": ... } } }
        $returnedOrderId = '';
        $returnedShipmentId = '';
        if (is_array($decoded)) {
            $returnedOrderId = trim((string)($decoded['order_id'] ?? $decoded['response']['data']['order_id'] ?? ''));
            $returnedShipmentId = trim((string)($decoded['shipment_id'] ?? $decoded['response']['data']['shipment_id'] ?? ''));
        }

        if ($returnedOrderId === '' && $returnedShipmentId === '') {
            // Without both, we can't reliably identify the created shipment.
            throw new Exception('Shiprocket order create success but response missing order_id/shipment_id');
        }

        // Keep previous behavior: store "waybill" as order_id initially (may be replaced by AWB after assign/awb).
        $waybillInitial = $returnedOrderId !== '' ? $returnedOrderId : $returnedShipmentId;
        $allWaybills = array_fill(0, $rowCount, $waybillInitial);

        return [
            'success' => true,
            'synced' => true,
            'waybill' => $waybillInitial,
            'order_id' => $returnedOrderId,
            'shipment_id' => $returnedShipmentId,
            'all_waybills' => $allWaybills,
            'api_response' => $decoded,
            'message' => 'Shiprocket order created (order_id: ' . ($returnedOrderId !== '' ? $returnedOrderId : '-') . ', shipment_id: ' . ($returnedShipmentId !== '' ? $returnedShipmentId : '-') . ')',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'synced' => false,
            'message' => $e->getMessage(),
            'api_response' => ['error' => $e->getMessage()],
        ];
    }
}

function assignAwbWithShiprocket($courierData, $shiprocketShipmentId, $shiprocketCourierCompanyId = null)
{
    try {
        $token = trim((string)($courierData['token'] ?? ''));
        if ($token === '') {
            throw new Exception('Shiprocket token missing for AWB assignment');
        }
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $shiprocketShipmentId = trim((string)$shiprocketShipmentId);
        if ($shiprocketShipmentId === '') {
            throw new Exception('Missing shiprocket shipment_id for AWB assignment');
        }

        $payload = [
            'shipment_id' => $shiprocketShipmentId,
        ];

        if ($shiprocketCourierCompanyId !== null && $shiprocketCourierCompanyId !== '') {
            $payload['courier_id'] = (int)$shiprocketCourierCompanyId;
        }

        // status is optional; omit unless you explicitly want reassignment

        $url = 'https://apiv2.shiprocket.in/v1/external/courier/assign/awb';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new Exception('Shiprocket assign/awb cURL error: ' . $curlErr);
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded)) : substr((string)$response, 0, 800);
            throw new Exception('Shiprocket assign/awb failed (HTTP ' . $httpCode . '): ' . $detail);
        }

        if (!is_array($decoded)) {
            throw new Exception('Shiprocket assign/awb returned non-JSON response');
        }

        $awbAssignStatus = (int)($decoded['awb_assign_status'] ?? 0);
        $awbCode = trim((string)($decoded['response']['data']['awb_code'] ?? $decoded['awb_code'] ?? ''));

        if ($awbAssignStatus !== 1 || $awbCode === '') {
            $detail = (string)($decoded['response']['message'] ?? $decoded['message'] ?? json_encode($decoded));
            throw new Exception('Shiprocket assign/awb unsuccessful: ' . $detail);
        }

        return [
            'success' => true,
            'synced' => true,
            'awb_code' => $awbCode,
            'api_response' => $decoded,
            'message' => 'Shiprocket AWB assigned (awb_code: ' . $awbCode . ')',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'synced' => false,
            'message' => $e->getMessage(),
            'api_response' => ['error' => $e->getMessage()],
        ];
    }
}

function shiprocketApiRequest($token, $endpoint, $payload = [])
{
    $token = trim((string)$token);
    if ($token === '') {
        return ['success' => false, 'message' => 'Shiprocket token missing', 'http_code' => 0, 'response' => null];
    }
    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $url = 'https://apiv2.shiprocket.in' . $endpoint;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        return ['success' => false, 'message' => $curlErr, 'http_code' => $httpCode, 'response' => null];
    }

    $decoded = json_decode((string)$response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = is_array($decoded)
            ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded))
            : substr((string)$response, 0, 1000);
        return ['success' => false, 'message' => $msg, 'http_code' => $httpCode, 'response' => $decoded];
    }

    return ['success' => true, 'message' => 'ok', 'http_code' => $httpCode, 'response' => $decoded];
}

function generateManifestWithShiprocket($courierData, array $shipmentIds)
{
    try {
        $ids = array_values(array_unique(array_filter(array_map('intval', $shipmentIds), function ($v) {
            return $v > 0;
        })));
        if (empty($ids)) {
            throw new Exception('No valid shipment_id found for manifest generation');
        }

        $req = shiprocketApiRequest($courierData['token'] ?? '', '/v1/external/manifests/generate', [
            'shipment_id' => $ids,
        ]);
        if (empty($req['success'])) {
            throw new Exception('Shiprocket manifest generate failed: ' . ($req['message'] ?? 'Unknown error'));
        }

        $resp = is_array($req['response']) ? $req['response'] : [];
        $manifestUrl = trim((string)($resp['manifest_url'] ?? ''));
        $statusFlag = isset($resp['status']) ? (int)$resp['status'] : null;
        $message = trim((string)($resp['message'] ?? ''));
        $checkIds = [];
        if (isset($resp['check_ids']) && is_array($resp['check_ids'])) {
            $checkIds = array_values(array_filter(array_map('intval', $resp['check_ids']), function ($v) {
                return $v > 0;
            }));
        }

        // Shiprocket sometimes returns HTTP 200 with business failure:
        // { "message": "Manifest not generated", "check_ids": [...] }
        $isBusinessFail = false;
        if ($statusFlag !== null && $statusFlag !== 1) {
            $isBusinessFail = true;
        }
        if ($message !== '' && stripos($message, 'not generated') !== false) {
            $isBusinessFail = true;
        }
        if (!empty($checkIds)) {
            $isBusinessFail = true;
        }
        if ($isBusinessFail) {
            $detail = $message !== '' ? $message : 'Manifest generation failed';
            if (!empty($checkIds)) {
                $detail .= ' (check shipment_ids: ' . implode(',', $checkIds) . ')';
            }
            throw new Exception($detail);
        }

        return [
            'success' => true,
            'manifest_url' => $manifestUrl,
            'api_response' => $resp,
            'message' => $manifestUrl !== '' ? 'Manifest generated' : 'Manifest generated',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'manifest_url' => '',
            'api_response' => ['error' => $e->getMessage()],
            'message' => $e->getMessage(),
        ];
    }
}

function printManifestWithShiprocket($courierData, array $orderIds)
{
    try {
        $ids = array_values(array_unique(array_filter(array_map('intval', $orderIds), function ($v) {
            return $v > 0;
        })));
        if (empty($ids)) {
            throw new Exception('No valid order_ids found for manifest print');
        }

        $req = shiprocketApiRequest($courierData['token'] ?? '', '/v1/external/manifests/print', [
            'order_ids' => $ids,
        ]);
        if (empty($req['success'])) {
            throw new Exception('Shiprocket manifest print failed: ' . ($req['message'] ?? 'Unknown error'));
        }

        $resp = is_array($req['response']) ? $req['response'] : [];
        $manifestUrl = trim((string)($resp['manifest_url'] ?? ''));

        return [
            'success' => true,
            'manifest_url' => $manifestUrl,
            'api_response' => $resp,
            'message' => $manifestUrl !== '' ? 'Manifest print URL generated' : 'Manifest print URL empty',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'manifest_url' => '',
            'api_response' => ['error' => $e->getMessage()],
            'message' => $e->getMessage(),
        ];
    }
}

function generateInvoiceWithShiprocket($courierData, array $orderIds)
{
    try {
        $ids = array_values(array_unique(array_filter(array_map('strval', $orderIds), function ($v) {
            return trim($v) !== '' && trim($v) !== '0';
        })));
        if (empty($ids)) {
            throw new Exception('No valid order_ids found for invoice generation');
        }

        $req = shiprocketApiRequest($courierData['token'] ?? '', '/v1/external/orders/print/invoice', [
            'ids' => $ids,
        ]);
        
        $resp = is_array($req['response']) ? $req['response'] : [];
        $invoiceUrl = trim((string)($resp['invoice_url'] ?? ''));
        $isCreated = isset($resp['is_invoice_created']) && $resp['is_invoice_created'];
        
        if (empty($req['success']) && !$isCreated) {
            throw new Exception('Shiprocket invoice generate failed: ' . ($req['message'] ?? 'Unknown error'));
        }

        if (!$isCreated && empty($invoiceUrl)) {
             $msg = $resp['message'] ?? 'Invoice could not be created';
             throw new Exception($msg);
        }

        return [
            'success' => true,
            'invoice_url' => $invoiceUrl,
            'api_response' => $resp,
            'message' => $invoiceUrl !== '' ? 'Invoice generated' : 'Invoice generated successfully',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'invoice_url' => '',
            'api_response' => ['error' => $e->getMessage()],
            'message' => $e->getMessage(),
        ];
    }
}

function trackBookingWithShiprocket($pdo, $courierData, $waybillNo)
{
    try {
        $token = trim((string)($courierData['token'] ?? ''));
        $waybillNo = trim((string)$waybillNo);

        if ($token === '') {
            throw new Exception('Shiprocket token missing for tracking');
        }
        if ($waybillNo === '') {
            throw new Exception('Waybill is required for Shiprocket tracking');
        }
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $url = 'https://apiv2.shiprocket.in/v1/external/courier/track/awb/' . rawurlencode($waybillNo);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new Exception('Shiprocket tracking cURL error: ' . $curlErr);
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            throw new Exception('Shiprocket tracking returned invalid JSON');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = (string)($decoded['message'] ?? $decoded['error'] ?? 'Tracking request failed');
            throw new Exception('Shiprocket tracking failed (HTTP ' . $httpCode . '): ' . $detail);
        }

        $trackingData = $decoded['tracking_data'] ?? [];
        if (!is_array($trackingData)) {
            $trackingData = [];
        }

        $shipmentTrack = [];
        if (isset($trackingData['shipment_track']) && is_array($trackingData['shipment_track'])) {
            $shipmentTrack = $trackingData['shipment_track'][0] ?? [];
        }
        $activities = $trackingData['shipment_track_activities'] ?? [];
        if (!is_array($activities)) {
            $activities = [];
        }

        $currentStatus = trim((string)($shipmentTrack['current_status'] ?? ''));
        if ($currentStatus === '' && !empty($activities)) {
            $currentStatus = trim((string)($activities[0]['sr-status-label'] ?? $activities[0]['activity'] ?? ''));
        }
        if ($currentStatus === '') {
            $currentStatus = 'Tracking Pending';
        }

        // Map Shiprocket activities into the scan structure expected by existing track consumers.
        $mappedScans = [];
        foreach ($activities as $ev) {
            $mappedScans[] = [
                'ScanDetail' => [
                    'ScanDateTime' => (string)($ev['date'] ?? ''),
                    'ScanType' => (string)($ev['sr-status-label'] ?? $ev['activity'] ?? $ev['status'] ?? 'Unknown'),
                    'ScannedLocation' => (string)($ev['location'] ?? ''),
                    'Status' => (string)($ev['sr-status'] ?? $ev['status'] ?? ''),
                    'Instructions' => (string)($ev['activity'] ?? ''),
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'Shipment' => [
                    'AWB' => $waybillNo,
                    'Status' => [
                        'Status' => $currentStatus,
                    ],
                ],
                'Scans' => $mappedScans,
                'shiprocket_tracking_data' => $trackingData,
            ],
            'message' => 'Shiprocket tracking fetched',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

/**
 * Request Pickup for Shiprocket shipments.
 *
 * Shiprocket requires pickup to be requested BEFORE generating manifests.
 * Workflow: Create Order → Assign AWB → Generate Pickup → Generate Manifest
 *
 * POST /v1/external/courier/generate/pickup
 * Body: { "shipment_id": [123, 456] }
 *
 * @param array $courierData  Must contain 'token'
 * @param array $shipmentIds  Array of Shiprocket shipment_id(s)
 * @return array  ['success' => bool, 'message' => string, 'api_response' => array]
 */
function requestPickupWithShiprocket($courierData, array $shipmentIds)
{
    try {
        $ids = array_values(array_unique(array_filter(array_map('intval', $shipmentIds), function ($v) {
            return $v > 0;
        })));
        if (empty($ids)) {
            throw new Exception('No valid shipment_id found for pickup request');
        }

        $token = trim((string)($courierData['token'] ?? ''));
        if ($token === '') {
            throw new Exception('Shiprocket token missing for pickup request');
        }
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $url = 'https://apiv2.shiprocket.in/v1/external/courier/generate/pickup';
        
        $successCount = 0;
        $allResponses = [];
        $lastError = '';

        foreach ($ids as $id) {
            // Shiprocket expects shipment_id to be an array but it may only process one effectively
            $payload = ['shipment_id' => [$id]];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr !== '') {
                $lastError = 'Shiprocket pickup request cURL error: ' . $curlErr;
                $allResponses[$id] = ['error' => $lastError];
                continue;
            }

            $decoded = json_decode((string)$response, true);

            if ($httpCode < 200 || $httpCode >= 300) {
                $detail = is_array($decoded)
                    ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded))
                    : substr((string)$response, 0, 800);
                $lastError = 'HTTP ' . $httpCode . ': ' . $detail;
                $allResponses[$id] = $decoded ?: ['error' => $lastError];
                continue;
            }

            if (!is_array($decoded)) {
                $lastError = 'Shiprocket pickup request returned non-JSON response';
                $allResponses[$id] = ['error' => $lastError];
                continue;
            }

            $pickupStatus = (int)($decoded['pickup_status'] ?? 0);
            $message = trim((string)($decoded['message'] ?? ''));

            $alreadyRequested = (stripos($message, 'already') !== false && stripos($message, 'pickup') !== false);
            if ($pickupStatus === 1 || $alreadyRequested) {
                $successCount++;
                $allResponses[$id] = $decoded;
            } else {
                $lastError = $message !== '' ? $message : 'Unexpected response';
                $allResponses[$id] = $decoded;
            }
        }

        if ($successCount > 0 || count($ids) === 0) {
            return [
                'success' => true,
                'message' => "Pickup requested successfully for $successCount/" . count($ids) . " shipments.",
                'api_response' => $allResponses,
            ];
        }

        throw new Exception($lastError !== '' ? $lastError : 'Pickup request failed for all shipments');
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'api_response' => ['error' => $e->getMessage()],
        ];
    }
}

function cancelBookingWithShiprocket($pdo, $courierData, $bookingData)
{
    try {
        $token = trim((string)($courierData['token'] ?? ''));
        $awb = $bookingData['waybill_no'] ?? $bookingData['shiprocket_awb_code'] ?? '';

        if (empty($token) || empty($awb)) {
            return ['success' => false, 'error' => 'Missing token or AWB'];
        }

        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $url = 'https://apiv2.shiprocket.in/v1/external/orders/cancel/shipment/awbs';
        $payload = ['awbs' => [$awb]];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            return ['success' => false, 'error' => 'cURL error: ' . $curlErr];
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded))
                : substr($response, 0, 800);
            return ['success' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $detail];
        }

        return ['success' => true, 'api_response' => $decoded];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>

