<?php
/**
 * Booking Delete/Cancel API (No Session)
 * Location: /apps-api/booking/delete.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

try {
    $id = $req['id'] ?? null;
    if (!$id) throw new Exception("Booking ID is required");

    $updatedBy = $req['user_id'] ?? 1;

    $stmtExist = $pdo->prepare("SELECT id, waybill_no FROM tbl_bookings WHERE id = :id");
    $stmtExist->execute([':id' => $id]);
    $booking = $stmtExist->fetch(PDO::FETCH_ASSOC);

    if (!$booking) throw new Exception("Shipment not found");

    $sql = "UPDATE tbl_bookings SET last_status = 'Cancelled', updated_by = :user_id, updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([':user_id' => $updatedBy, ':id' => $id])) {
        $trackSql = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, status_code, scan_datetime, remarks) VALUES (:bid, :wn, 'Cancelled', 'Cancelled', NOW(), 'Shipment cancelled via Mobile API')";
        $pdo->prepare($trackSql)->execute([':bid' => $id, ':wn' => $booking['waybill_no']]);
        echo json_encode(['status' => 'success', 'message' => 'Shipment cancelled successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to cancel shipment']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
