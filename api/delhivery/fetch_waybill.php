<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';
require_once __DIR__ . '/../../api/booking/services/delhivery.php';

// GET ?courier_id=X&count=N
$courierId = isset($_GET['courier_id']) ? (int)$_GET['courier_id'] : 0;
$count     = max(1, min(50, (int)($_GET['count'] ?? 1)));

if ($courierId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'courier_id is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, api_key, api_url, partner_name FROM tbl_courier_partner WHERE id = :id");
    $stmt->execute([':id' => $courierId]);
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courier) {
        echo json_encode(['status' => 'error', 'message' => 'Courier not found']);
        exit;
    }

    if (empty($courier['api_key'])) {
        echo json_encode(['status' => 'error', 'message' => 'Delhivery API Key is missing for this courier']);
        exit;
    }
    if (empty($courier['api_url'])) {
        echo json_encode(['status' => 'error', 'message' => 'Delhivery API URL is missing for this courier']);
        exit;
    }

    $baseUrl  = rtrim($courier['api_url'], '/');
    $apiToken = $courier['api_key'];

    $waybills = fetchDelhiveryWaybills($baseUrl, $apiToken, $count);

    if (empty($waybills)) {
        echo json_encode(['status' => 'error', 'message' => 'No waybills returned from Delhivery. Check API key and URL.']);
        exit;
    }

    echo json_encode([
        'status'   => 'success',
        'waybills' => $waybills,
        'count'    => count($waybills)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
