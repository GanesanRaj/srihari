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
    $bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $waybill = trim($_GET['waybill'] ?? '');
    $pdfParam = strtolower(trim($_GET['pdf'] ?? 'true'));
    $pdf = !in_array($pdfParam, ['false', '0', 'no'], true);
    $pdfSize = strtoupper(trim($_GET['pdf_size'] ?? 'A4'));
    if (!in_array($pdfSize, ['A4', '4R'], true)) {
        $pdfSize = 'A4';
    }

    if ($bookingId <= 0 && $waybill === '') {
        throw new Exception('Booking ID or Waybill is required');
    }

    $sql = "SELECT 
                b.id AS booking_id,
                b.waybill_no,
                b.courier_id AS booking_courier_id,
                b.pickup_point_id,
                p.courier_id AS pickup_courier_id,
                COALESCE(b.courier_id, p.courier_id) AS resolved_courier_id
            FROM tbl_bookings b
            LEFT JOIN tbl_pickup_points p ON p.id = b.pickup_point_id
            WHERE " . ($bookingId > 0 ? "b.id = :id" : "b.waybill_no = :waybill") . "
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    if ($bookingId > 0) {
        $stmt->execute([':id' => $bookingId]);
    } else {
        $stmt->execute([':waybill' => $waybill]);
    }
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    $waybillNo = $booking['waybill_no'] ?: $waybill;
    if ($waybillNo === '') {
        throw new Exception('Waybill not generated for this booking yet');
    }

    $courierId = (int) ($booking['resolved_courier_id'] ?? 0);
    if ($courierId <= 0) {
        throw new Exception('Courier not found for this booking/pickup point');
    }

    $courierStmt = $pdo->prepare("SELECT id, partner_name, partner_code, api_key, api_url FROM tbl_courier_partner WHERE id = :id LIMIT 1");
    $courierStmt->execute([':id' => $courierId]);
    $courierData = $courierStmt->fetch(PDO::FETCH_ASSOC);
    if (!$courierData) {
        throw new Exception('Courier credentials not found');
    }

    require_once __DIR__ . '/services/courier_service.php';
    $result = generateLabelFromCourier($courierData, [
        'waybill' => $waybillNo,
        'pdf' => $pdf,
        'pdf_size' => $pdfSize
    ]);

    if (empty($result['success'])) {
        throw new Exception($result['message'] ?? 'Label generation failed');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Label generated successfully',
        'booking_id' => (int) $booking['booking_id'],
        'waybill' => $waybillNo,
        'courier' => $courierData['partner_name'] ?? null,
        'pdf' => $pdf,
        'pdf_size' => $pdfSize,
        'label_url' => $result['label_url'] ?? null,
        'data' => $result['response'] ?? null
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
