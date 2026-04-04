<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

$phone = $_GET['phone'] ?? '';
$type = $_GET['type'] ?? 'consignee'; // 'shipper' or 'consignee'

if (empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required']);
    exit;
}

try {
    if ($type === 'shipper') {
        $sql = "SELECT shipper_name as name, shipper_phone as phone, shipper_address as address, 
                       shipper_pin as pin, shipper_city as city, shipper_state as state
                FROM tbl_bookings 
                WHERE shipper_phone = :phone 
                ORDER BY id DESC LIMIT 1";
    } else {
        $sql = "SELECT consignee_name as name, consignee_phone as phone, consignee_address as address, 
                       consignee_pin as pin, consignee_city as city, consignee_state as state,
                       consignee_email as email, consignee_gst as gst
                FROM tbl_bookings 
                WHERE consignee_phone = :phone 
                ORDER BY id DESC LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phone' => $phone]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No record found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>