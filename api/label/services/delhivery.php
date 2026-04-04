<?php
function normalizeDelhiveryLabelBaseUrl($apiUrl)
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

function extractLabelUrlFromApiResponse($decoded, $raw)
{
    if (is_string($raw) && filter_var(trim($raw), FILTER_VALIDATE_URL)) {
        return trim($raw);
    }

    if (is_array($decoded)) {
        $keys = ['link', 'url', 'pdf_url', 'label_url', 'download_url', 's3_url'];
        foreach ($keys as $key) {
            if (!empty($decoded[$key]) && filter_var($decoded[$key], FILTER_VALIDATE_URL)) {
                return $decoded[$key];
            }
        }

        foreach ($decoded as $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
            if (is_array($value)) {
                $nested = extractLabelUrlFromApiResponse($value, '');
                if (!empty($nested)) {
                    return $nested;
                }
            }
        }
    }

    return null;
}

function generateLabelWithDelhivery($courierData, $labelInput)
{
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API key is missing'];
    }

    $waybillNo = trim((string) ($labelInput['waybill'] ?? ''));
    if ($waybillNo === '') {
        return ['success' => false, 'message' => 'Waybill is required'];
    }

    $pdf = !empty($labelInput['pdf']);
    $pdfFlag = $pdf ? 'true' : 'false';

    $pdfSize = strtoupper(trim((string) ($labelInput['pdf_size'] ?? 'A4')));
    if (!in_array($pdfSize, ['A4', '4R'], true)) {
        $pdfSize = 'A4';
    }

    $baseUrl = normalizeDelhiveryLabelBaseUrl($courierData['api_url'] ?? '');
    $url = rtrim($baseUrl, '/') . '/api/p/packing_slip?' . http_build_query([
        'wbns' => $waybillNo,
        'pdf' => $pdfFlag,
        'pdf_size' => $pdfSize
    ]);

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
            'Authorization: Token ' . $courierData['api_key'],
            'Content-Type: application/json',
            'Accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => 'Delhivery connection error: ' . $err];
    }

    $decoded = json_decode($response, true);
    $labelUrl = extractLabelUrlFromApiResponse($decoded, $response);

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'label_url' => $labelUrl,
            'waybill' => $waybillNo,
            'pdf' => $pdf,
            'pdf_size' => $pdfSize,
            'response' => $decoded !== null ? $decoded : $response
        ];
    }

    return [
        'success' => false,
        'message' => 'Delhivery label API failed (HTTP ' . $httpCode . ')',
        'response' => $decoded !== null ? $decoded : $response
    ];
}
?>
