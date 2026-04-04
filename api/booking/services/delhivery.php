<?php

function _fetchFromApi($url)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => 'GET'
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err || !$response)
        return [];

    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        if (!empty($decoded['packages']) && is_array($decoded['packages'])) {
            return $decoded['packages'];
        }
        if (isset($decoded[0]) && is_string($decoded[0])) {
            return $decoded;
        }
    }

    $clean = trim($response, '"');
    if (strpos($clean, ',') !== false) {
        return explode(',', $clean);
    }
    if (preg_match('/^[A-Z0-9]{5,}$/', $clean)) {
        return [$clean];
    }
    return [];
}

function fetchDelhiveryWaybills($baseUrl, $apiToken, $count = 1)
{
    if ($count < 1)
        return [];

    $collected = [];
    $attempts = 0;
    // Safety limit: max attempts = count + 5
    $maxAttempts = $count + 5;

    while (count($collected) < $count && $attempts < $maxAttempts) {
        $attempts++;
        $needed = $count - count($collected);

        $fetchUrl = rtrim($baseUrl, '/') . '/waybill/api/fetch/json/?token=' . $apiToken . '&fetch=' . $needed;
        $batch = _fetchFromApi($fetchUrl);

        if (!empty($batch)) {
            foreach ($batch as $wb) {
                $wb = trim($wb);
                if ($wb !== '') {
                    $collected[] = $wb;
                }
            }
        } else {
            // If we got nothing, wait briefly before small retry
            usleep(200000);
        }

        // If we got *some* (e.g. 1) but not enough, the loop will continue to get more.
    }

    return array_slice($collected, 0, $count);
}

function syncBookingWithDelhivery($pdo, $courierData, $shipmentData)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'synced' => false, 'message' => 'Delhivery API Key is missing'];
    }
    if (empty($courierData['api_url'])) {
        return ['success' => false, 'synced' => false, 'message' => 'Delhivery API URL is missing'];
    }

    $apiToken = $courierData['api_key'];
    $baseUrl = rtrim($courierData['api_url'], '/');
    $apiUrl = $baseUrl . '/api/cmu/create.json';

    $packages = $shipmentData['package_details'] ?? [];
    // Fallback if package_details missing (legacy calls)
    if (empty($packages)) {
        $packages = [
            [
                'length' => $shipmentData['length'] ?? 10,
                'width' => $shipmentData['width'] ?? 10,
                'height' => $shipmentData['height'] ?? 10,
                'actual_weight' => $shipmentData['weight'] ? ($shipmentData['weight'] / 1000) : 0.5,
                'boxes' => $shipmentData['quantity'] ?? 1
            ]
        ];
    }

    // Flatten packages (expand 'boxes' count into individual items)
    $flatPackages = [];
    foreach ($packages as $pkg) {
        $count = max(1, (int) ($pkg['boxes'] ?? 1));
        for ($i = 0; $i < $count; $i++) {
            $flatPackages[] = $pkg;
        }
    }

    $totalPackages = count($flatPackages);
    $isMps = ($totalPackages > 1);

    $paymentMode  = $shipmentData['payment_mode'] ?? 'Prepaid';
    $codTotal     = ($paymentMode === 'COD') ? (float) ($shipmentData['cod_amount'] ?? 0) : 0;
    $invoiceValue = (float) ($shipmentData['invoice_value'] ?? 0);

    // MPS: pre-fetch one waybill per box (mandatory per Delhivery docs)
    $prefetchedWaybills = [];
    if ($isMps) {
        $prefetchedWaybills = fetchDelhiveryWaybills($baseUrl, $apiToken, $totalPackages);
        if (count($prefetchedWaybills) < $totalPackages) {
            return [
                'success'  => false,
                'synced'   => false,
                'message'  => 'Failed to pre-fetch waybills for MPS from Delhivery (Got ' . count($prefetchedWaybills) . ', need ' . $totalPackages . ')'
            ];
        }
    }

    $masterWaybillId = $prefetchedWaybills[0] ?? '';  // used as master_id for all MPS boxes
    $shipments = [];

    foreach ($flatPackages as $index => $pkg) {
        $isMasterBox = ($index === 0);
        $pkgCod      = $isMasterBox ? $codTotal : 0;

        $item = [
            'name'           => $shipmentData['consignee_name'],
            'add'            => $shipmentData['consignee_address'],
            'pin'            => $shipmentData['consignee_pin'],
            'city'           => $shipmentData['consignee_city'],
            'state'          => $shipmentData['consignee_state'],
            'country'        => $shipmentData['consignee_country'] ?? 'India',
            'phone'          => $shipmentData['consignee_phone'],
            'order'          => $shipmentData['booking_ref_id'] . ($index > 0 ? '-' . $index : ''),
            'payment_mode'   => $paymentMode,
            'return_pin'     => '',
            'return_city'    => '',
            'return_phone'   => '',
            'return_add'     => $shipmentData['rto_address'] ?? '',
            'return_state'   => '',
            'return_country' => '',
            'products_desc'  => $shipmentData['product_desc'] ?? 'Package',
            'hsn_code'       => '',
            'cod_amount'     => $pkgCod,
            'order_date'     => null,
            'total_amount'   => ($invoiceValue > 0) ? $invoiceValue : $codTotal,
            'seller_add'     => $shipmentData['shipper_address'] ?? '',
            'seller_name'    => $shipmentData['shipper_name'] ?? '',
            'seller_inv'     => $shipmentData['invoice_no'] ?? '',
            'quantity'       => '1',
            'waybill'        => $isMps ? ($prefetchedWaybills[$index] ?? '') : '',
            'shipment_width'  => (string) ($pkg['width']  ?? 10),
            'shipment_height' => (string) ($pkg['height'] ?? 10),
            'shipment_length' => (string) ($pkg['length'] ?? 10),
            'weight'          => (string) (($pkg['actual_weight'] ?? 0.5) * 1000),
            'shipping_mode'   => $shipmentData['shipping_mode'] ?? 'Surface',
            'address_type'    => 'home',
        ];

        // MPS-specific fields
        if ($isMps) {
            $item['shipment_type'] = 'MPS';
            $item['mps_children']  = $totalPackages;
            $item['mps_amount']    = $isMasterBox ? $codTotal : 0;
            $item['master_id']     = $masterWaybillId;
        }

        // E-way bill
        $pkgEway = trim((string)($pkg['child_ewaybill_no'] ?? ''));
        if ($pkgEway !== '') {
            $item['ewbn'] = $pkgEway;
        } elseif ($isMasterBox) {
            $masterEway = trim((string)($shipmentData['ewaybill_no'] ?? ''));
            if ($masterEway !== '') {
                $item['ewbn'] = $masterEway;
            }
        }

        $shipments[] = $item;
    }

    $shipmentPayload = [
        'shipments' => $shipments,
        'pickup_location' => [
            'name' => $shipmentData['pickup_location_name']
        ]
    ];

    // Delhivery expects format=json&data=<json>
    $postFields = 'format=json&data=' . json_encode($shipmentPayload);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Token ' . $apiToken,
            'Accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'synced' => false, 'message' => 'cURL Error: ' . $err];
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        $packagesResp = $responseData['packages'] ?? [];
        if (!empty($packagesResp)) {
            $first = $packagesResp[0];

            // For MPS all boxes must succeed; for SPS just the first
            $failedRemarks = [];
            foreach ($packagesResp as $pr) {
                if (($pr['status'] ?? '') !== 'Success') {
                    $remark = is_array($pr['remarks'] ?? null)
                        ? implode('; ', $pr['remarks'])
                        : ($pr['remarks'] ?? 'Unknown error');
                    $failedRemarks[] = $remark;
                }
            }
            if (!empty($failedRemarks)) {
                return [
                    'success'      => false,
                    'synced'       => false,
                    'api_response' => $responseData,
                    'message'      => 'Delhivery Error: ' . implode(' | ', array_unique($failedRemarks))
                ];
            }

            // Extract all waybills from response; for MPS use pre-fetched order
            $allWaybills = [];
            foreach ($packagesResp as $pr) {
                $wb = trim($pr['waybill'] ?? '');
                if ($wb !== '') {
                    $allWaybills[] = $wb;
                }
            }
            // For MPS the master is always the first pre-fetched waybill
            $masterWaybill = $isMps
                ? ($masterWaybillId ?: ($allWaybills[0] ?? ($first['waybill'] ?? '')))
                : ($allWaybills[0] ?? ($first['waybill'] ?? ''));

            // For MPS, if response didn't echo back all waybills, fall back to pre-fetched list
            if ($isMps && count($allWaybills) < $totalPackages) {
                $allWaybills = $prefetchedWaybills;
            }

            return [
                'success' => true,
                'synced' => true,
                'waybill' => $masterWaybill,
                'all_waybills' => $allWaybills,
                'api_response' => $responseData,
                'message' => 'Booking synced with Delhivery'
            ];
        }
    }

    $errMsg = '';
    if (!empty($responseData['packages'][0]['remarks'])) {
        $r = $responseData['packages'][0]['remarks'];
        $errMsg = is_array($r) ? implode('; ', $r) : $r;
    }
    if (!$errMsg) {
        $errMsg = 'Delhivery API error (HTTP ' . $httpCode . '): ' . $response;
    }
    return [
        'success'      => false,
        'synced'       => false,
        'api_response' => $responseData,
        'message'      => $errMsg
    ];
}

function trackBookingWithDelhivery($pdo, $courierData, $waybillNo)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing'];
    }
    if (empty($courierData['api_url'])) {
        return ['success' => false, 'message' => 'Delhivery API URL is missing'];
    }

    $apiToken = $courierData['api_key'];
    $url = rtrim($courierData['api_url'], '/') . '/api/v1/packages/json/?waybill=' . urlencode($waybillNo) . '&verbose=0';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Token ' . $apiToken,
            'Accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => 'cURL Error: ' . $err];
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($responseData['ShipmentData'])) {
        return [
            'success' => true,
            'data' => $responseData['ShipmentData'][0] ?? [],
            'full_response' => $responseData
        ];
    }

    return [
        'success' => false,
        'message' => 'Tracking failed or no data',
        'response' => $responseData
    ];
}

function updateDelhiveryEwaybill($courierData, $waybillNo, $invoiceNo, $ewaybillNo)
{
    $waybillNo = trim((string)$waybillNo);
    $invoiceNo = trim((string)$invoiceNo);
    $ewaybillNo = trim((string)$ewaybillNo);

    if ($waybillNo === '' || $invoiceNo === '' || $ewaybillNo === '') {
        return [
            'success' => false,
            'message' => 'Waybill, invoice number and e-waybill number are required'
        ];
    }
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing'];
    }

    $apiBase = strtolower(trim((string)($courierData['api_url'] ?? '')));
    $isStaging = (strpos($apiBase, 'staging') !== false);
    $baseUrl = $isStaging ? 'https://staging-express.delhivery.com' : 'https://track.delhivery.com';
    $url = $baseUrl . '/api/rest/ewaybill/' . rawurlencode($waybillNo) . '/';

    $payload = json_encode([
        'data' => [
            [
                'dcn' => $invoiceNo,
                'ewbn' => $ewaybillNo
            ]
        ]
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Token ' . $courierData['api_key'],
            'Content-Type: application/json',
            'Accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => 'cURL Error: ' . $err];
    }

    $decoded = json_decode((string)$response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        $respPayload = is_array($decoded) ? $decoded : ['raw' => $response];
        $apiSuccessKnown = is_array($decoded) && array_key_exists('success', $decoded);
        $apiSuccess = $apiSuccessKnown ? (bool)$decoded['success'] : true;

        if ($apiSuccess) {
            return [
                'success' => true,
                'message' => 'E-waybill updated successfully',
                'response' => $respPayload,
                'http_code' => $httpCode
            ];
        }

        $apiMsg = '';
        if (is_array($decoded) && !empty($decoded['message'])) {
            $apiMsg = trim((string)$decoded['message']);
        }
        if ($apiMsg === '') {
            $apiMsg = 'E-waybill update failed';
        }
        return [
            'success' => false,
            'message' => $apiMsg,
            'response' => $respPayload,
            'http_code' => $httpCode
        ];
    }

    $msg = 'E-waybill update failed';
    if (is_array($decoded) && !empty($decoded['message'])) {
        $msg = (string)$decoded['message'];
    } elseif (is_string($response) && trim($response) !== '') {
        $msg = trim($response);
    }
    return [
        'success' => false,
        'message' => $msg,
        'response' => is_array($decoded) ? $decoded : ['raw' => $response],
        'http_code' => $httpCode
    ];
}

?>
