<?php

function createShipmentDelhivery($pdo, $courierData, $shipmentData)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing'];
    }
    if (empty($courierData['api_url'])) {
        return ['success' => false, 'message' => 'Delhivery API URL is missing'];
    }

    $apiToken = $courierData['api_key'];

    // Build API URL dynamic from database
    $apiUrl = rtrim($courierData['api_url'], '/') . '/api/cmu/create.json';

    $shipmentPayload = [
        "shipments" => [
            [
                "name" => $shipmentData['consignee_name'],
                "add" => $shipmentData['consignee_address'],
                "pin" => $shipmentData['consignee_pin'],
                "city" => $shipmentData['consignee_city'],
                "state" => $shipmentData['consignee_state'],
                "country" => $shipmentData['consignee_country'] ?? 'India',
                "phone" => $shipmentData['consignee_phone'],
                "order" => $shipmentData['booking_ref_id'],
                "payment_mode" => $shipmentData['payment_mode'],
                "return_pin" => "",
                "return_city" => "",
                "return_phone" => "",
                "return_add" => "",
                "return_state" => "",
                "return_country" => "",
                "products_desc" => $shipmentData['product_desc'] ?? '',
                "hsn_code" => "",
                "cod_amount" => ($shipmentData['payment_mode'] === 'COD') ? $shipmentData['cod_amount'] : "0",
                "order_date" => null,
                "total_amount" => $shipmentData['invoice_value'] ?? $shipmentData['cod_amount'] ?? "0",
                "seller_add" => "",
                "seller_name" => "",
                "seller_inv" => $shipmentData['invoice_no'] ?? "",
                "quantity" => $shipmentData['quantity'] ?? "1",
                "waybill" => "",
                "shipment_width" => $shipmentData['width'] ?? "10",
                "shipment_height" => $shipmentData['height'] ?? "10",
                "shipment_length" => $shipmentData['length'] ?? "10",
                "weight" => $shipmentData['weight'] ?? "500", // grams
                "shipping_mode" => $shipmentData['shipping_mode'] ?? 'Surface',
                "address_type" => "home"
            ]
        ],
        "pickup_location" => [
            "name" => $shipmentData['pickup_location_name'] // Must match exactly with registered warehouse name
        ]
    ];

    // Include e-way bill number if provided
    $ewaybill = trim((string)($shipmentData['ewaybill_no'] ?? ''));
    if ($ewaybill !== '') {
        $shipmentPayload['shipments'][0]['ewbn'] = $ewaybill;
    }

    // Encode JSON
    $jsonPayload = json_encode($shipmentPayload);

    // D:\xampp\htdocs\steve\ Fix: Delhivery requires format=json&data={json_string} for this endpoint, NOT raw JSON body.
    $postFields = 'format=json&data=' . $jsonPayload;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            "Authorization: Token " . $apiToken,
            "Accept: application/json"
            // "Content-Type: application/json" // REMOVED: Must be x-www-form-urlencoded (default) for format=json
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => "cURL Error: $err"];
    }

    $responseData = json_decode($response, true);

    // Check if successful
    // Delhivery success response usually has 'packages' array with status 'Success'
    // or 'cash_pickups_count' etc.
    // Example success: {"cash_pickups_count": 0, "package_count": 1, "upload_wbn": "...", "packages": [...]}

    if ($httpCode >= 200 && $httpCode < 300 && !empty($responseData['packages'])) {
        $firstPackage = $responseData['packages'][0];
        if ($firstPackage['status'] === 'Success') {
            return [
                'success' => true,
                'waybill' => $firstPackage['waybill'],
                'ref_id' => $firstPackage['ref_id'] ?? $shipmentData['booking_ref_id'], // or remarks[0]
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Delhivery API Error: ' . ($firstPackage['remarks'][0] ?? 'Unknown error'),
                'response' => $responseData
            ];
        }
    }

    // Fallback error
    return [
        'success' => false,
        'message' => 'Delhivery Sync Failed: ' . $response,
        'response' => $responseData
    ];
}

function updateShipmentDelhivery($pdo, $courierData, $shipmentData)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing'];
    }

    // Check status first
    $lastStatus = strtoupper($shipmentData['last_status'] ?? '');
    $paymentMode = $shipmentData['existing_payment_mode'] ?? '';

    $allowed = false;
    if ($paymentMode === 'Prepaid' || $paymentMode === 'COD') {
        if (in_array($lastStatus, ['MANIFESTED', 'IN TRANSIT', 'PENDING'])) {
            $allowed = true;
        }
    } elseif ($paymentMode === 'Pickup') {
        if (in_array($lastStatus, ['SCHEDULED'])) {
            $allowed = true;
        }
    } elseif ($paymentMode === 'REPL') {
        if (in_array($lastStatus, ['MANIFESTED', 'IN TRANSIT', 'PENDING'])) {
            $allowed = true;
        }
    }

    // If no status yet (Still in manifest phase locally), allow it
    if (empty($lastStatus)) {
        $allowed = true;
    }

    if (!$allowed) {
        return [
            'success' => false,
            'message' => "Edit not allowed for Delhivery shipment in status: $lastStatus"
        ];
    }

    $apiToken = $courierData['api_key'];
    // Endpoint: /api/p/edit
    $apiUrl = "https://track.delhivery.com/api/p/edit"; // Production
    if (strpos($courierData['api_url'] ?? '', 'staging') !== false) {
        $apiUrl = "https://staging-express.delhivery.com/api/p/edit";
    }

    $payload = [
        'waybill' => $shipmentData['waybill_no'],
        'name' => $shipmentData['consignee_name'],
        'add' => $shipmentData['consignee_address'],
        'products_desc' => $shipmentData['product_desc'] ?? '',
        'weight' => $shipmentData['weight'] ?? 0,
        'shipment_height' => $shipmentData['height'] ?? 0,
        'shipment_width' => $shipmentData['width'] ?? 0,
        'shipment_length' => $shipmentData['length'] ?? 0
    ];

    if (!empty($shipmentData['consignee_phone'])) {
        // Delhivery edit API expects phone as list
        $payload['phone'] = [$shipmentData['consignee_phone']];
    }

    // Payment mode update (pt)
    if (isset($shipmentData['payment_mode']) && $shipmentData['payment_mode'] !== $shipmentData['existing_payment_mode']) {
        $newPT = $shipmentData['payment_mode'];
        $oldPT = $shipmentData['existing_payment_mode'];

        // Apply Delhivery conversion rules
        if (($oldPT === 'COD' && $newPT === 'Prepaid') || ($oldPT === 'Prepaid' && $newPT === 'COD')) {
            // For B2C Edit API, 'Pre-paid' is the standard value with hyphen.
            $payload['pt'] = ($newPT === 'Prepaid') ? 'Pre-paid' : $newPT;

            if ($newPT === 'COD') {
                $payload['cod'] = (float) ($shipmentData['cod_amount'] ?? 0);
            } else {
                // Converting to Prepaid: cod should be 0
                $payload['cod'] = 0;
            }
        }
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Token " . $apiToken,
            "Content-Type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => "cURL Error: $err"];
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        // The edit API might return {status: true, message: "..."} or {status: "success"}
        return [
            'success' => true,
            'response' => $responseData,
            'message' => 'Delhivery API update success'
        ];
    }

    return [
        'success' => false,
        'message' => 'Delhivery API update failed: ' . ($responseData['message'] ?? $response),
        'response' => $responseData
    ];
}

function trackShipmentDelhivery($pdo, $courierData, $waybillNo)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing'];
    }

    // Determine API URL
    // Tracking uses /api/v1/packages/json/
    $baseUrl = rtrim($courierData['api_url'], '/') . '/api/v1/packages/json/';

    $url = $baseUrl . '?waybill=' . $waybillNo . '&verbose=0';
    $apiToken = $courierData['api_key'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Token " . $apiToken,
            "Accept: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => "cURL Error: $err"];
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($responseData['ShipmentData'])) {
        // Prepare tracking data
        return [
            'success' => true,
            'data' => $responseData['ShipmentData'][0] ?? [],
            'full_response' => $responseData
        ];
    }

    return [
        'success' => false,
        'message' => 'Tracking Failed or No Data',
        'response' => $responseData
    ];
}
?>