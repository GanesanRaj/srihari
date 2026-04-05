<?php
/**
 * shiprocket_cancel_awb.php
 *
 * Cancels Shiprocket shipments using AWB numbers:
 * POST /v1/external/orders/cancel/shipment/awbs
 *
 * Features:
 * - Calls Shiprocket cancel API with AWB numbers (up to 2000 AWBs)
 * - Updates local booking status to 'Cancelled' (soft cancel)
 * - Adds tracking record for the cancellation
 *
 * POST JSON:
 * {
 *   "booking_ids": [123, 456],
 *   "awbs": ["19041211125783", "19041211125784"]
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

$bookingIds = $body['booking_ids'] ?? [];
$awbs = $body['awbs'] ?? [];

if (!is_array($bookingIds)) {
    $bookingIds = array_filter(array_map('trim', explode(',', (string)$bookingIds)));
}
if (!is_array($awbs)) {
    $awbs = array_filter(array_map('trim', explode(',', (string)$awbs)));
}

$bookingIds = array_values(array_unique(array_filter(array_map('intval', $bookingIds))));
$awbs = array_values(array_unique(array_filter($awbs)));

if (empty($bookingIds)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing booking_ids.']);
    exit;
}
if (empty($awbs)) {
    echo json_encode(['status' => 'error', 'message' => 'No AWBs provided.']);
    exit;
}

try {
    // Get courier token from first booking
    $bkStmt = $pdo->prepare("SELECT courier_id, waybill_no FROM tbl_bookings WHERE id = :id LIMIT 1");
    $bkStmt->execute([':id' => $bookingIds[0]]);
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

    // Call Shiprocket cancel API with AWBs
    $url = 'https://apiv2.shiprocket.in/v1/external/orders/cancel/shipment/awbs';
    $payload = ['awbs' => $awbs];

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

    // Shiprocket returns 200 with message for async cancellation
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

    // Update local bookings to Cancelled status (soft cancel)
    $updatedBy = (int)($_SESSION['user_id'] ?? 1);
    $successCount = 0;
    $failedBookings = [];

    foreach ($bookingIds as $bid) {
        try {
            // Get waybill for tracking record
            $wbStmt = $pdo->prepare("SELECT waybill_no FROM tbl_bookings WHERE id = :id");
            $wbStmt->execute([':id' => $bid]);
            $wbData = $wbStmt->fetch(PDO::FETCH_ASSOC);
            $waybill = $wbData['waybill_no'] ?? '';

            // Update booking status
            $updStmt = $pdo->prepare("UPDATE tbl_bookings SET last_status = 'Cancelled', updated_by = :user_id, updated_at = NOW() WHERE id = :id");
            $updStmt->execute([':user_id' => $updatedBy, ':id' => $bid]);

            // Add tracking record
            if ($waybill) {
                $trackSql = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_datetime, remarks) VALUES (:bid, :wn, 'Cancelled', 'Cancelled', NOW(), 'Shipment cancelled via Shiprocket API')";
                $pdo->prepare($trackSql)->execute([':bid' => $bid, ':wn' => $waybill]);
            }
            
            $successCount++;
        } catch (Exception $ex) {
            $failedBookings[] = ['id' => $bid, 'error' => $ex->getMessage()];
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Shiprocket cancellation initiated. ' . $successCount . ' local booking(s) marked as Cancelled.',
        'api_response' => $decoded,
        'local_updated' => $successCount,
        'failed_bookings' => $failedBookings
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
