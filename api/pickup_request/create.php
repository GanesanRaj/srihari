<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('pickup_request', 'is_add');

$current_user = get_current_user_info();
$created_by   = $current_user['id'] ?? ($_SESSION['user_id'] ?? 1);

$pickup_point_id       = isset($_POST['pickup_point_id']) ? (int)$_POST['pickup_point_id'] : 0;
$pickup_date           = isset($_POST['pickup_date']) ? sanitizeText($_POST['pickup_date']) : '';
$pickup_time           = isset($_POST['pickup_time']) ? sanitizeText($_POST['pickup_time']) : '';
$expected_package_count = isset($_POST['expected_package_count']) ? max(1, (int)$_POST['expected_package_count']) : 1;

$errors = [];
if ($pickup_point_id <= 0) $errors[] = 'Pickup location is required';
if (empty($pickup_date))   $errors[] = 'Pickup date is required';
if (empty($pickup_time))   $errors[] = 'Pickup time is required';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickup_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid pickup date format (YYYY-MM-DD)']);
    exit;
}

// Normalize time to HH:MM:SS
if (preg_match('/^\d{2}:\d{2}$/', $pickup_time)) {
    $pickup_time .= ':00';
}

try {
    // Fetch pickup point + courier details
    $ppStmt = $pdo->prepare(
        "SELECT pp.id, pp.name, pp.courier_id,
                cp.partner_name, cp.partner_code, cp.api_key, cp.api_url
         FROM tbl_pickup_points pp
         JOIN tbl_courier_partner cp ON cp.id = pp.courier_id
         WHERE pp.id = :id AND pp.status = 'active'"
    );
    $ppStmt->execute([':id' => $pickup_point_id]);
    $pp = $ppStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pp) {
        echo json_encode(['status' => 'error', 'message' => 'Pickup point not found or inactive']);
        exit;
    }

    $courier_id           = (int)$pp['courier_id'];
    $pickup_location_name = $pp['name'];

    // Call Delhivery Pickup Request API
    $apiStatus   = 'Pending';
    $apiResponse = null;
    $requestId   = null;
    $syncMessage = '';

    $partnerCode = strtoupper(trim($pp['partner_code'] ?? ''));
    $partnerName = strtolower(trim($pp['partner_name'] ?? ''));
    $isDelhivery = strpos($partnerCode, 'DEL') === 0 || strpos($partnerName, 'delhivery') !== false;

    if ($isDelhivery) {
        if (empty($pp['api_key']) || empty($pp['api_url'])) {
            echo json_encode(['status' => 'error', 'message' => 'Delhivery API credentials missing']);
            exit;
        }

        $apiUrl = rtrim($pp['api_url'], '/') . '/fm/request/new/';

        $payload = [
            'pickup_time'            => $pickup_time,
            'pickup_date'            => $pickup_date,
            'pickup_location'        => $pickup_location_name,
            'expected_package_count' => $expected_package_count
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . $pp['api_key'],
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);
        curl_close($curl);

        $apiResponse = $response;

        if ($curlErr) {
            echo json_encode(['status' => 'error', 'message' => 'Delhivery connection error: ' . $curlErr]);
            exit;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $apiStatus   = 'Confirmed';
            $requestId   = $decoded['pickup_id'] ?? ($decoded['id'] ?? null);
            $syncMessage = ' and synced with Delhivery';
        } else {
            $apiStatus   = 'Failed';
            $errMsg      = $decoded['message'] ?? ($decoded['error'] ?? $response);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Delhivery API error (HTTP ' . $httpCode . '): ' . $errMsg
            ]);
            exit;
        }
    }

    // Insert into tbl_pickup_requests
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_pickup_requests
            (pickup_point_id, courier_id, pickup_location_name, pickup_date, pickup_time,
             expected_package_count, status, request_id, api_response, created_by, created_at)
         VALUES
            (:ppid, :cid, :loc, :date, :time, :count, :status, :rid, :resp, :uid, NOW())"
    );
    $stmt->execute([
        ':ppid'   => $pickup_point_id,
        ':cid'    => $courier_id,
        ':loc'    => $pickup_location_name,
        ':date'   => $pickup_date,
        ':time'   => $pickup_time,
        ':count'  => $expected_package_count,
        ':status' => $apiStatus,
        ':rid'    => $requestId,
        ':resp'   => $apiResponse,
        ':uid'    => $created_by
    ]);

    $insertId = $pdo->lastInsertId();

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Pickup request created' . $syncMessage,
        'id'         => $insertId,
        'request_id' => $requestId,
        'api_status' => $apiStatus
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
