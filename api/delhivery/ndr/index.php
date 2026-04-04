<?php
header('Content-Type: application/json');
require '../../../config/db.php';
require '../../../config/middleware.php';

function getDelhiveryCourier($pdo)
{
    $stmt = $pdo->prepare("SELECT id, partner_name, partner_code, api_key, api_url
                           FROM tbl_courier_partner
                           WHERE LOWER(partner_name) LIKE '%delhivery%' OR UPPER(partner_code) LIKE 'DEL%'
                           LIMIT 1");
    $stmt->execute();
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$courier) {
        throw new Exception('Delhivery courier config not found');
    }
    if (empty($courier['api_key']) || empty($courier['api_url'])) {
        throw new Exception('Delhivery API configuration missing');
    }
    return $courier;
}

function callDelhiveryJson($url, $token, $method = 'GET', $payload = null)
{
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Token ' . $token,
            'Content-Type: application/json'
        ],
    ];
    if ($payload !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new Exception('cURL Error: ' . $err);
    }
    $decoded = json_decode((string)$resp, true);
    return [$httpCode, $decoded, $resp];
}

try {
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    if ($requestMethod === 'POST') {
        $body = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($body)) $body = [];
        $action = strtoupper(trim((string)($body['act'] ?? '')));
        $waybill = trim((string)($body['waybill'] ?? ''));

        if ($waybill === '' || $action === '') {
            throw new Exception('waybill and act are required');
        }
        if (!in_array($action, ['RE-ATTEMPT', 'PICKUP_RESCHEDULE'], true)) {
            throw new Exception('Invalid act. Allowed: RE-ATTEMPT, PICKUP_RESCHEDULE');
        }

        $courier = getDelhiveryCourier($pdo);
        $url = rtrim((string)$courier['api_url'], '/') . '/api/p/update';
        [$httpCode, $decoded, $raw] = callDelhiveryJson($url, $courier['api_key'], 'POST', [
            'data' => [[
                'waybill' => $waybill,
                'act' => $action
            ]]
        ]);

        $ok = ($httpCode >= 200 && $httpCode < 300);
        $uplId = '';
        if (is_array($decoded)) {
            $uplId = trim((string)($decoded['upl'] ?? $decoded['upl_id'] ?? $decoded['request_id'] ?? ''));
        }

        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'NDR action submitted' : ('Delhivery NDR update failed (HTTP ' . $httpCode . ')'),
            'upl_id' => $uplId,
            'http_code' => $httpCode,
            'response' => is_array($decoded) ? $decoded : ['raw' => $raw]
        ]);
        exit;
    }

    if ($requestMethod !== 'GET') {
        throw new Exception('Invalid request method');
    }

    $courier = getDelhiveryCourier($pdo);
    $delhiveryCourierId = (int)($courier['id'] ?? 0);
    if ($delhiveryCourierId <= 0) {
        throw new Exception('Invalid Delhivery courier configuration');
    }

    $ndrCodes = ['EOD-74', 'EOD-15', 'EOD-104', 'EOD-43', 'EOD-86', 'EOD-11', 'EOD-69', 'EOD-6', 'EOD-777', 'EOD-21', 'EOD-148', 'L-RPIE'];
    $codePlaceholders = implode(',', array_fill(0, count($ndrCodes), '?'));
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
    $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 500;
    if ($length <= 0) {
        $length = 50;
    }
    $length = min($length, 500);
    $search = trim((string)($_GET['search']['value'] ?? $_GET['search'] ?? ''));

    $baseFrom = " FROM tbl_bookings b
                  LEFT JOIN tbl_tracking t ON t.booking_id = b.id
                  WHERE b.courier_id = ?
                    AND (
                        t.status_code IN ($codePlaceholders)
                        OR t.status_code LIKE 'L-%'
                    )";

    $whereExtra = '';
    $params = array_merge([$delhiveryCourierId], $ndrCodes);
    if ($search !== '') {
        $whereExtra = " AND (b.waybill_no LIKE ? OR b.booking_ref_id LIKE ? OR b.consignee_name LIKE ? OR t.status_code LIKE ? OR t.remarks LIKE ?)";
        $searchLike = '%' . $search . '%';
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
    }

    $recordsTotalStmt = $pdo->prepare("SELECT COUNT(*)" . $baseFrom);
    $recordsTotalStmt->execute(array_merge([$delhiveryCourierId], $ndrCodes));
    $recordsTotal = (int)$recordsTotalStmt->fetchColumn();

    $recordsFilteredStmt = $pdo->prepare("SELECT COUNT(*)" . $baseFrom . $whereExtra);
    $recordsFilteredStmt->execute($params);
    $recordsFiltered = (int)$recordsFilteredStmt->fetchColumn();

    $listSql = "SELECT b.id, b.waybill_no AS awb, b.booking_ref_id, b.last_status,
                       b.consignee_name, b.consignee_phone,
                       t.status_code AS nsl_code, t.remarks AS ndr_reason,
                       t.scan_datetime AS last_scan_datetime, t.scan_location AS last_scan_location"
             . $baseFrom . $whereExtra . " ORDER BY t.scan_datetime DESC LIMIT $length OFFSET $start";

    $stmt = $pdo->prepare($listSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($draw > 0) {
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($rows),
        'total' => $recordsTotal,
        'filtered' => $recordsFiltered,
        'data' => $rows
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
