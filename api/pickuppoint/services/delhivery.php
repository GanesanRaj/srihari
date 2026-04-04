<?php
/**
 * Delhivery Courier Service
 * 
 * API: POST /api/backend/clientwarehouse/create/
 * Auth: Token-based (Authorization: Token xxx)
 * Credential field: api_key
 */

function syncWithDelhivery($pdo, $courierData, $pickupPointData, $pickupPointId, $action = 'create')
{

    // Validate credentials
    if (empty($courierData['api_key'])) {
        return ['success' => false, 'message' => 'Delhivery API Key is missing', 'synced' => false];
    }
    if (empty($courierData['api_url'])) {
        return ['success' => false, 'message' => 'Delhivery API URL is missing', 'synced' => false];
    }

    $apiToken = $courierData['api_key'];
    $postData = [];
    $apiUrl = '';

    if ($action === 'create') {
        // Build API URL for Create
        $apiUrl = rtrim($courierData['api_url'], '/') . '/api/backend/clientwarehouse/create/';

        // Build payload for Create — required fields always sent as strings
        $postData = [
            'name'             => (string)($pickupPointData['name'] ?? ''),
            'phone'            => (string)($pickupPointData['phone'] ?? ''),
            'pin'              => (string)($pickupPointData['pin'] ?? ''),
            'address'          => (string)($pickupPointData['address'] ?? ''),
            'city'             => (string)($pickupPointData['city'] ?? ''),
            'country'          => (string)((!empty($pickupPointData['country'])) ? $pickupPointData['country'] : 'India'),
            'return_address'   => (string)($pickupPointData['return_address'] ?? ''),
            'return_country'   => (string)((!empty($pickupPointData['return_country'])) ? $pickupPointData['return_country'] : 'India'),
        ];

        // Optional fields — only include if non-empty to avoid Delhivery type errors
        $optionalFields = ['email', 'registered_name', 'return_pin', 'return_city', 'return_state'];
        foreach ($optionalFields as $field) {
            $val = (string)($pickupPointData[$field] ?? '');
            if ($val !== '') {
                $postData[$field] = $val;
            }
        }

    } elseif ($action === 'update') {
        // Build API URL for Edit
        $apiUrl = rtrim($courierData['api_url'], '/') . '/api/backend/clientwarehouse/edit/';

        // Build payload for Edit — cast all as strings
        $postData = [
            'name'    => (string)($pickupPointData['name'] ?? ''),
            'pin'     => (string)($pickupPointData['pin'] ?? ''),
            'phone'   => (string)($pickupPointData['phone'] ?? ''),
            'address' => (string)($pickupPointData['address'] ?? ''),
        ];
    } else {
        return ['success' => false, 'message' => 'Invalid action for Delhivery sync', 'synced' => false];
    }

    // cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Token " . $apiToken,
            "Content-Type: application/json",
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'message' => 'Delhivery connection error: ' . $err, 'synced' => false];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Success - update sync status
        $updateSql = "UPDATE tbl_pickup_points 
                      SET delhivery_synced = 1, 
                          delhivery_response = :response,
                          updated_at = NOW()
                      WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(':response', $response);
        $updateStmt->bindValue(':id', $pickupPointId, PDO::PARAM_INT);
        $updateStmt->execute();

        $actionMsg = ($action === 'create') ? 'Created and synced' : 'Updated and synced';
        return ['success' => true, 'message' => "$actionMsg with Delhivery successfully", 'synced' => true];
    } else {
        return ['success' => false, 'message' => "Delhivery sync failed ($action) (HTTP $httpCode): " . $response, 'synced' => false];
    }
}
?>