<?php
/**
 * Shiprocket Cancel Service
 */

function cancelBookingWithShiprocket($pdo, $courierData, $bookingData)
{
    try {
        $token = trim((string)($courierData['token'] ?? ''));
        $awb = $bookingData['waybill_no'] ?? '';

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
