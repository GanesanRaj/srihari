<?php
/**
 * shiprocket_request_pickup.php
 *
 * Manually request pickup for Shiprocket shipments.
 * Shiprocket requires: Create Order → Assign AWB → Generate Pickup → Generate Manifest
 *
 * POST JSON:
 * {
 *   "booking_ids": [123, 456]
 * }
 *
 * Extracts shipment_id(s) from tbl_bookings.api_response and calls
 * POST /v1/external/courier/generate/pickup
 */
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/middleware.php';
require_once '../booking/services/shiprocket.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!get_permission('shipment', 'is_add')) {
    require_api_permission('shipment', 'is_add');
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

$bookingIds = $body['booking_ids'] ?? [];
if (!is_array($bookingIds) || empty($bookingIds)) {
    echo json_encode(['status' => 'error', 'message' => 'Select at least one booking for pickup request.']);
    exit;
}

$bookingIds = array_values(array_unique(array_filter(array_map('intval', $bookingIds), function ($v) {
    return $v > 0;
})));
if (empty($bookingIds)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid booking ids.']);
    exit;
}

try {
    $ph = implode(',', array_fill(0, count($bookingIds), '?'));
    $sql = "SELECT b.id, b.waybill_no, b.api_response, b.courier_id,
                   c.partner_name AS courier_name, c.token AS courier_token
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON c.id = b.courier_id
            WHERE b.id IN ($ph)";
    $stmt = $pdo->prepare($sql);
    foreach ($bookingIds as $i => $bid) {
        $stmt->bindValue($i + 1, $bid, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode(['status' => 'error', 'message' => 'No bookings found for selected ids.']);
        exit;
    }

    $shipmentIds = [];
    $courierData = null;
    $awbMap = []; // shipment_id => waybill_no

    foreach ($rows as $r) {
        $courierName = strtolower((string)($r['courier_name'] ?? ''));
        if ($courierName !== '' && strpos($courierName, 'shiprocket') === false && (int)($r['courier_id'] ?? 0) !== 4) {
            continue;
        }
        if ($courierData === null) {
            $courierData = ['token' => (string)($r['courier_token'] ?? '')];
        }

        $apiRespRaw = $r['api_response'] ?? '';
        $apiResp = is_string($apiRespRaw) ? json_decode((string)$apiRespRaw, true) : (is_array($apiRespRaw) ? $apiRespRaw : null);
        if (is_array($apiResp)) {
            $sid = (int)trim((string)(
                $apiResp['shipment_id']
                ?? $apiResp['response']['data']['shipment_id']
                ?? ($apiResp['awb_assign']['response']['data']['shipment_id'] ?? '0')
            ));
            if ($sid > 0) {
                $shipmentIds[] = $sid;
                $wb = trim((string)($r['waybill_no'] ?? ''));
                if ($wb !== '') {
                    $awbMap[(string)$sid] = $wb;
                }
            }
        }
    }

    if ($courierData === null || trim((string)($courierData['token'] ?? '')) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket token missing for selected bookings.']);
        exit;
    }
    if (empty($shipmentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No shipment_id found in selected bookings. Assign AWB first.']);
        exit;
    }

    $result = requestPickupWithShiprocket($courierData, $shipmentIds);

    if (!empty($result['success'])) {
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'] ?? 'Pickup requested successfully',
            'shipment_ids' => $shipmentIds,
            'api_response' => $result['api_response'] ?? null,
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'] ?? 'Pickup request failed',
            'shipment_ids' => $shipmentIds,
            'api_response' => $result['api_response'] ?? null,
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
