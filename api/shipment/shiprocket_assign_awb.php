<?php
/**
 * shiprocket_assign_awb.php
 *
 * Manually assigns AWB for an already-created Shiprocket shipment and updates local DB:
 * - tbl_bookings.waybill_no
 * - tbl_booking_packages.waybill_no, awb_no, child_ewaybill_no
 * - tbl_tracking.waybill_no
 *
 * POST JSON:
 * {
 *   "booking_id": 123,
 *   "courier_company_id": 142   // optional (Shiprocket courier service id)
 * }
 */
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Superadmin only (same style as delete/cancel)
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
$courierCompanyId = isset($body['courier_company_id']) && $body['courier_company_id'] !== ''
    ? (int)$body['courier_company_id']
    : null;

if ($bookingId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing booking_id.']);
    exit;
}

try {
    // Fetch booking data including api_response where Shiprocket shipment_id is stored.
    $bkStmt = $pdo->prepare("SELECT id, courier_id, waybill_no, api_response FROM tbl_bookings WHERE id = :id LIMIT 1");
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

    // Courier partner token (Shiprocket)
    $cpStmt = $pdo->prepare("SELECT id, partner_name, partner_code, token FROM tbl_courier_partner WHERE id = :id LIMIT 1");
    $cpStmt->execute([':id' => $courierId]);
    $courier = $cpStmt->fetch(PDO::FETCH_ASSOC);
    if (!$courier) {
        echo json_encode(['status' => 'error', 'message' => 'Courier partner not found.']);
        exit;
    }

    $token = trim((string)($courier['token'] ?? ''));
    if ($token === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket token missing for this courier partner.']);
        exit;
    }

    // Extract Shiprocket shipment_id from stored api_response
    $apiRespRaw = $booking['api_response'] ?? '';
    $apiResp = is_string($apiRespRaw) ? json_decode((string)$apiRespRaw, true) : (is_array($apiRespRaw) ? $apiRespRaw : null);
    if (!is_array($apiResp)) {
        echo json_encode(['status' => 'error', 'message' => 'Booking api_response is missing/invalid JSON. Cannot find shiprocket shipment_id.']);
        exit;
    }

    $shiprocketShipmentId = trim((string)($apiResp['shipment_id'] ?? $apiResp['response']['data']['shipment_id'] ?? ($apiResp['awb_assign']['response']['data']['shipment_id'] ?? '')));
    if ($shiprocketShipmentId === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket shipment_id not found in api_response.']);
        exit;
    }

    // Call Shiprocket assign/awb
    require_once __DIR__ . '/../booking/services/shiprocket.php';
    $awbResult = assignAwbWithShiprocket($courier, $shiprocketShipmentId, $courierCompanyId);
    if (empty($awbResult['success'])) {
        echo json_encode(['status' => 'error', 'message' => $awbResult['message'] ?? 'Shiprocket AWB assignment failed', 'api_response' => $awbResult['api_response'] ?? null]);
        exit;
    }

    $awbCode = trim((string)($awbResult['awb_code'] ?? ''));
    if ($awbCode === '') {
        echo json_encode(['status' => 'error', 'message' => 'Shiprocket assign/awb returned empty awb_code.']);
        exit;
    }

    // Update local DB to use AWB as waybill everywhere.
    $pdo->beginTransaction();

    // Update booking waybill
    $pdo->prepare("UPDATE tbl_bookings SET waybill_no = :awb WHERE id = :id")
        ->execute([':awb' => $awbCode, ':id' => $bookingId]);

    // Update packages: keep same AWB for all rows
    $pdo->prepare("UPDATE tbl_booking_packages
                   SET waybill_no = :awb, awb_no = :awb, child_ewaybill_no = :awb
                   WHERE booking_id = :id")
        ->execute([':awb' => $awbCode, ':id' => $bookingId]);

    // Update tracking rows
    $pdo->prepare("UPDATE tbl_tracking SET waybill_no = :awb WHERE booking_id = :id")
        ->execute([':awb' => $awbCode, ':id' => $bookingId]);

    // Append awb assign payload into api_response for audit/debug
    $apiResp['awb_assign'] = $awbResult['api_response'] ?? $awbResult;
    $pdo->prepare("UPDATE tbl_bookings SET api_response = :resp WHERE id = :id")
        ->execute([':resp' => json_encode($apiResp), ':id' => $bookingId]);

    $pdo->commit();

    // Trigger immediate Shiprocket tracking sync for new AWB.
    try {
        if (!defined('IN_CREATION')) {
            define('IN_CREATION', true);
        }
        $_GET['waybill'] = $awbCode;
        ob_start();
        include __DIR__ . '/../../cron-shiprocket.php';
        ob_end_clean();
    } catch (Throwable $ignore) {
        // Non-blocking
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'AWB assigned and updated in DB: ' . $awbCode,
        'awb_code' => $awbCode,
        'api_response' => $awbResult['api_response'] ?? null,
    ]);
} catch (Exception $e) {
    try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ignored) {}
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>

