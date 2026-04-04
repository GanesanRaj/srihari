<?php
/**
 * shiprocket_cancel_order.php
 *
 * Cancels Shiprocket orders using:
 * POST /v1/external/orders/cancel
 *
 * IMPORTANT:
 * - Does NOT delete local shipments.
 * - Only calls Shiprocket cancel API and returns response.
 *
 * POST JSON:
 * {
 *   "booking_id": 123,
 *   "ids": [16168898, 16167171]
 * }
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Superadmin only (same pattern as shipment_delete)
$roleId = (int)($_SESSION['role_id'] ?? 0);
if ($roleId !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Superadmin only.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: $_POST;

$bookingId = isset($body['booking_id']) ? (int)$body['booking_id'] : 0;
$ids = $body['ids'] ?? [];

if (!is_array($ids)) {
    $ids = array_filter(array_map('trim', explode(',', (string)$ids)));
}
$ids = array_values(array_unique(array_filter(array_map(function ($x) {
    $x = trim((string)$x);
    return $x !== '' ? (int)$x : null;
}, $ids))));

if ($bookingId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing booking_id.']);
    exit;
}
if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No order ids provided in ids[].']);
    exit;
}

try {
    // Resolve courier partner token from the booking's courier_id
    $bkStmt = $pdo->prepare("SELECT courier_id FROM tbl_bookings WHERE id = :id LIMIT 1");
    $bkStmt->execute([':id' => $bookingId]);
    $booking = $bkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
        exit;
    }

    $courierId = (int)($booking['courier_id'] ?? 0);
    if ($courierId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid courier_id in booking.']);
        exit;
    }

    $cpStmt = $pdo->prepare("SELECT token FROM tbl_courier_partner WHERE id = :id LIMIT 1");
    $cpStmt->execute([':id' => $courierId]);
    $courier = $cpStmt->fetch(PDO::FETCH_ASSOC);
    $token = trim((string)($courier['token'] ?? ''));
    if ($token === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket token missing for this courier partner.']);
        exit;
    }
    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $url = 'https://apiv2.shiprocket.in/v1/external/orders/cancel';
    $payload = ['ids' => $ids];

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
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket cancel cURL error: ' . $curlErr]);
        exit;
    }

    $decoded = json_decode((string)$response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $detail = is_array($decoded)
            ? (string)($decoded['message'] ?? $decoded['error'] ?? json_encode($decoded))
            : substr((string)$response, 0, 800);

        echo json_encode([
            'status' => 'error',
            'message' => 'Shiprocket cancel failed (HTTP ' . $httpCode . '): ' . $detail,
            'api_response' => $decoded,
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Shiprocket orders cancelled successfully.',
        'api_response' => $decoded,
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>

