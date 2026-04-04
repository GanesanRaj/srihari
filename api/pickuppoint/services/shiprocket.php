<?php
/**
 * Shiprocket Pickup Point Sync Handler
 *
 * Uses:
 * POST https://apiv2.shiprocket.in/v1/external/settings/company/addpickup
 * Authorization: Bearer <token>
 *
 * We only sync on "create" to avoid duplicates in Shiprocket.
 */

function syncWithShiprocket($pdo, $courierData, $pickupPointData, $pickupPointId, $action = 'create')
{
    $saveSyncStatus = function (bool $synced, string $responseForDb) use ($pdo, $pickupPointId) {
        // UI currently shows sync status using `delhivery_synced` / `delhivery_response`.
        // We reuse the same columns for Shiprocket pickup sync visibility.
        $updateSql = "UPDATE tbl_pickup_points
                      SET delhivery_synced = :synced,
                          delhivery_response = :response,
                          updated_at = NOW()
                      WHERE id = :id";
        $stmt = $pdo->prepare($updateSql);
        $stmt->bindValue(':synced', $synced ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':response', $responseForDb, PDO::PARAM_STR);
        $stmt->bindValue(':id', $pickupPointId, PDO::PARAM_INT);
        $stmt->execute();
    };

    // Basic token check: if token missing, do not break local save flow.
    $token = trim((string)($courierData['token'] ?? ''));
    if ($token === '') {
        return [
            'success' => false,
            'synced' => false,
            'message' => 'Shiprocket token missing for this courier partner. Pickup point not created.',
        ];
    }

    // Strip "Bearer " prefix if user stored it that way.
    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    if ($action !== 'create') {
        // Update sync intentionally not implemented to avoid duplicate pickup locations.
        return [
            'success' => true,
            'synced' => false,
            'message' => 'Shiprocket sync on update not implemented (saved locally only).',
        ];
    }

    // Required Shiprocket params mapping from existing pickupPointData.
    // Shiprocket required:
    // pickup_location, name, email, phone, address, city, state, country, pin_code
    $pickup_location = trim((string)($pickupPointData['name'] ?? ''));
    // Shiprocket "name" should come from Registered Name, but if it's empty,
    // fallback to Warehouse Name so we don't fail storage unnecessarily.
    $sr_name = trim((string)($pickupPointData['registered_name'] ?? ''));
    if ($sr_name === '') {
        $sr_name = trim((string)($pickupPointData['name'] ?? ''));
    }
    $email = trim((string)($pickupPointData['email'] ?? ''));
    $phone = (string)($pickupPointData['phone'] ?? '');
    $address = (string)($pickupPointData['address'] ?? '');
    $city = (string)($pickupPointData['city'] ?? '');
    // Pickup state: prefer new pickup_state column; fallback to return_state for backward compatibility.
    $state = trim((string)($pickupPointData['pickup_state'] ?? ($pickupPointData['return_state'] ?? '')));
    $country = (string)($pickupPointData['country'] ?? 'India');
    $pin_code = (string)($pickupPointData['pin'] ?? '');

    $missing = [];
    if ($pickup_location === '') $missing[] = 'pickup_location';
    if ($sr_name === '') $missing[] = 'name';
    if ($email === '') $missing[] = 'email';
    if ($phone === '') $missing[] = 'phone';
    if ($address === '') $missing[] = 'address';
    if ($city === '') $missing[] = 'city';
    if ($state === '') $missing[] = 'state';
    if ($country === '') $missing[] = 'country';
    if ($pin_code === '') $missing[] = 'pin_code';

    if (!empty($missing)) {
        return [
            'success' => false,
            'synced' => false,
            'message' => 'Shiprocket addpickup missing required fields: ' . implode(', ', $missing),
        ];
    }

    $apiUrlBase = rtrim((string)($courierData['api_url'] ?? ''), '/');
    if ($apiUrlBase === '') {
        $apiUrlBase = 'https://apiv2.shiprocket.in';
    }

    $url = $apiUrlBase . '/v1/external/settings/company/addpickup';

    $payload = [
        'pickup_location' => $pickup_location,
        'name' => $sr_name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'address_2' => '',
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'pin_code' => $pin_code,
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($curl);
    curl_close($curl);

    if ($curlErr !== '') {
        $saveSyncStatus(false, (string)$curlErr);
        return [
            'success' => false,
            'synced' => false,
            'message' => 'Shiprocket addpickup cURL error: ' . $curlErr,
        ];
    }

    $decoded = json_decode((string)$response, true);

    // If Shiprocket responded with HTTP 2xx, we typically want to save locally.
    // But requirement: if Shiprocket *explicitly fails* (e.g. success:false), do NOT store.
    if ($httpCode >= 200 && $httpCode < 300) {
        // Explicit failure checks when JSON decode works.
        if (is_array($decoded)) {
            if (array_key_exists('success', $decoded) && ($decoded['success'] === false)) {
                $detail = (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded));
                $saveSyncStatus(false, (string)$response);
                return [
                    'success' => false,
                    'synced' => false,
                    'message' => 'Shiprocket addpickup failed: ' . $detail,
                ];
            }

            $pickupId = (string)($decoded['pickup_id'] ?? $decoded['pickupId'] ?? '');
            if ($pickupId !== '') {
                $saveSyncStatus(true, (string)$response);
                return [
                    'success' => true,
                    'synced' => true,
                    'message' => 'Shiprocket pickup created successfully (pickup_id: ' . $pickupId . ')',
                ];
            }

            // Success HTTP but no pickup_id -> treat as failure for storage rule.
            $saveSyncStatus(false, (string)$response);
            return [
                'success' => false,
                'synced' => false,
                'message' => 'Shiprocket addpickup returned HTTP 2xx but no pickup_id found in response.',
            ];
        }

        // Fallback: if JSON parsing failed, do a lightweight raw check.
        $raw = (string)$response;
        if (preg_match('/"success"\s*:\s*(false|0)/i', $raw)) {
            $saveSyncStatus(false, (string)$response);
            return [
                'success' => false,
                'synced' => false,
                'message' => 'Shiprocket addpickup failed (response indicates success:false).',
            ];
        }
        $hasPickupId = (stripos($raw, 'pickup_id') !== false || stripos($raw, 'pickupId') !== false);
        if ($hasPickupId) {
            $saveSyncStatus(true, (string)$response);
            return [
                'success' => true,
                'synced' => true,
                'message' => 'Shiprocket pickup created successfully (HTTP 2xx; pickup_id found in raw response).',
            ];
        }

        $saveSyncStatus(false, (string)$response);
        return [
            'success' => false,
            'synced' => false,
            'message' => 'Shiprocket addpickup returned HTTP 2xx but pickup_id not found in response.',
        ];
    }

    $detail = '';
    if (is_array($decoded)) {
        $detail = (string)($decoded['message'] ?? $decoded['error'] ?? $decoded['detail'] ?? json_encode($decoded));
    } else {
        $detail = substr((string)$response, 0, 600);
    }

    return [
        'success' => false,
        'synced' => false,
        'message' => 'Shiprocket addpickup failed (HTTP ' . $httpCode . '): ' . $detail,
    ];
}

?>

