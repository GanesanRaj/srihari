<?php
function normalizeBaseUrl($apiUrl)
{
    $apiUrl = trim((string) $apiUrl);
    if ($apiUrl === '') {
        return 'https://track.delhivery.com';
    }

    if (!preg_match('#^https?://#i', $apiUrl)) {
        $apiUrl = 'https://' . ltrim($apiUrl, '/');
    }

    $parts = parse_url($apiUrl);
    if (isset($parts['scheme']) && isset($parts['host'])) {
        $base = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $base .= ':' . $parts['port'];
        }
        return $base;
    }

    return rtrim($apiUrl, '/');
}

function findValueRecursive($data, $keys)
{
    if (!is_array($data)) {
        return null;
    }

    foreach ($keys as $k) {
        if (array_key_exists($k, $data) && $data[$k] !== '' && $data[$k] !== null) {
            return $data[$k];
        }
    }

    foreach ($data as $item) {
        if (is_array($item)) {
            $value = findValueRecursive($item, $keys);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
    }

    return null;
}

function getTatWithDelhivery($courierData, $tatInput)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API key is missing'];
    }

    $baseUrl = normalizeBaseUrl($courierData['api_url'] ?? '');
    $apiUrl = rtrim($baseUrl, '/') . '/api/dc/expected_tat';

    $params = [
        'origin_pin' => $tatInput['origin_pin'] ?? '',
        'destination_pin' => $tatInput['destination_pin'] ?? '',
        'mot' => $tatInput['mot'] ?? 'S',
    ];

    if (!empty($tatInput['pdt'])) {
        $params['pdt'] = $tatInput['pdt'];
    }

    if (!empty($tatInput['expected_pickup_date'])) {
        $params['expected_pickup_date'] = $tatInput['expected_pickup_date'];
    }

    $url = $apiUrl . '?' . http_build_query($params);

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
            'Accept: application/json',
            'Authorization: Token ' . $courierData['api_key'],
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => 'Delhivery connection error: ' . $err];
    }

    $responseData = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'success' => false,
            'message' => 'Delhivery TAT API failed (HTTP ' . $httpCode . ')',
            'response' => $responseData ?: $response
        ];
    }

    $tatDays = findValueRecursive($responseData, ['tat', 'expected_tat', 'tat_days', 'days', 'tat_in_days']);
    $expectedDate = findValueRecursive($responseData, ['expected_delivery_date', 'expected_date', 'delivery_date', 'expected_delivery_datetime', 'edd']);

    return [
        'success' => true,
        'message' => 'TAT fetched successfully',
        'tat_days' => $tatDays,
        'expected_delivery_date' => $expectedDate,
        'response' => $responseData ?: $response
    ];
}
?>
