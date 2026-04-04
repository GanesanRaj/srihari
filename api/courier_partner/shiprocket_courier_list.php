<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking/shipment users)
if (!get_permission('courier_partner', 'is_view') && !get_permission('whms_booking', 'is_view') && !get_permission('whms_shipment', 'is_view')) {
    require_api_permission('courier_partner', 'is_view');
}

try {
    $courierId = isset($_GET['courier_id']) ? (int) $_GET['courier_id'] : 0;
    $type = sanitizeText($_GET['type'] ?? 'active');
    if ($courierId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing courier_id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, partner_name, partner_code, token FROM tbl_courier_partner WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $courierId]);
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$courier) {
        echo json_encode(['status' => 'error', 'message' => 'Courier partner not found']);
        exit;
    }

    $token = trim((string)($courier['token'] ?? ''));
    if ($token === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket token missing for selected courier partner']);
        exit;
    }
    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $url = 'https://apiv2.shiprocket.in/v1/external/courier/courierListWithCounts?type=' . urlencode($type);

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
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket courierListWithCounts cURL error: ' . $curlErr]);
        exit;
    }

    $decoded = json_decode((string)$response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $detail = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded)) : substr((string)$response, 0, 800);
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket courierList failed (HTTP ' . $httpCode . '): ' . $detail]);
        exit;
    }

    $courierData = $decoded['courier_data'] ?? [];
    if (!is_array($courierData)) {
        $courierData = [];
    }

    echo json_encode(['status' => 'success', 'data' => $courierData]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>

