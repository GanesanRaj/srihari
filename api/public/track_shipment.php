<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$waybill = $_GET['waybill'] ?? '';

if (empty($waybill)) {
    echo json_encode(['status' => 'error', 'message' => 'Waybill number is required']);
    exit;
}

try {
    // 1. Fetch Booking Info
    $sql = "SELECT b.waybill_no, b.consignee_name, b.consignee_city, b.last_status, b.created_at, b.shipping_mode,
                   p.name as pickup_name, p.city as pickup_city
            FROM tbl_bookings b
            LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
            WHERE b.waybill_no = :waybill";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':waybill' => $waybill]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid waybill number']);
        exit;
    }

    // 2. Fetch Tracking History
    $histSql = "SELECT scan_type as status, scan_location as location, scan_datetime as time, remarks
                FROM tbl_tracking
                WHERE waybill_no = :waybill
                ORDER BY scan_datetime DESC, id DESC";

    $histStmt = $pdo->prepare($histSql);
    $histStmt->execute([':waybill' => $waybill]);
    $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'booking' => $booking,
            'history' => $history
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>