<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// require_api_permission('shipment', 'is_view');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $originPin = preg_replace('/\D/', '', $_GET['origin_pin'] ?? '');
    $destinationPin = preg_replace('/\D/', '', $_GET['destination_pin'] ?? '');
    $mot = strtoupper(trim($_GET['mot'] ?? 'S'));
    $pdt = strtoupper(trim($_GET['pdt'] ?? 'B2C'));
    $expectedPickupDate = trim($_GET['expected_pickup_date'] ?? '');
    $courierId = isset($_GET['courier_id']) ? (int) $_GET['courier_id'] : 0;

    if (strlen($originPin) !== 6 || strlen($destinationPin) !== 6) {
        throw new Exception('Origin and destination PIN must be 6 digits');
    }

    if (!in_array($mot, ['S', 'E', 'N'], true)) {
        $mot = 'S';
    }

    if (!in_array($pdt, ['B2B', 'B2C', ''], true)) {
        $pdt = 'B2C';
    }

    // Resolve courier credentials
    if ($courierId > 0) {
        $stmt = $pdo->prepare("SELECT id, partner_name, partner_code, api_key, api_url FROM tbl_courier_partner WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $courierId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, partner_name, partner_code, api_key, api_url FROM tbl_courier_partner WHERE LOWER(partner_name) LIKE '%delhivery%' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
    }

    $courierData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$courierData) {
        throw new Exception('Delhivery courier credentials not found');
    }

    $tatInput = [
        'origin_pin' => $originPin,
        'destination_pin' => $destinationPin,
        'mot' => $mot,
        'pdt' => $pdt,
        'expected_pickup_date' => $expectedPickupDate
    ];

    require_once __DIR__ . '/services/courier_service.php';
    $result = getTatFromCourier($courierData, $tatInput);

    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'TAT request failed');
    }

    echo json_encode([
        'status' => 'success',
        'message' => $result['message'] ?? 'TAT fetched successfully',
        'tat_days' => $result['tat_days'] ?? null,
        'expected_delivery_date' => $result['expected_delivery_date'] ?? null,
        'data' => $result['response'] ?? null
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
