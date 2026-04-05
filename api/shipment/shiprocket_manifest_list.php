<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!get_permission('shipment', 'is_view')) {
    require_api_permission('shipment', 'is_view');
}

try {
    $sql = "SELECT sm.id, sm.manifest_date, sm.manifested_id, sm.pickuppoint, sm.manifstered_awb, sm.created_at, sm.created_by, sm.response,
                   u.username AS created_by_name
            FROM shiprocket_manifest sm
            LEFT JOIN tbl_user u ON u.user_id = sm.created_by
            ORDER BY sm.id DESC
            LIMIT 500";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $awbs = [];
        $manifestUrl = '';
        $resp = $row['response'] ?? '';
        $respArr = is_string($resp) ? json_decode((string)$resp, true) : (is_array($resp) ? $resp : null);
        $awbsRaw = $row['manifstered_awb'] ?? '';
        $awbsArr = is_string($awbsRaw) ? json_decode((string)$awbsRaw, true) : (is_array($awbsRaw) ? $awbsRaw : null);
        if (is_array($awbsArr)) {
            $awbs = array_values(array_filter(array_map('strval', $awbsArr)));
        }
        if (is_array($respArr)) {
            $manifestUrl = trim((string)(
                $respArr['print_manifest_url']
                ?? $respArr['generate_manifest_url']
                ?? $respArr['print_manifest']['manifest_url']
                ?? $respArr['generate_manifest']['manifest_url']
                ?? ''
            ));
        }
        $row['awb_count'] = count($awbs);
        $row['awbs'] = $awbs;
        $row['manifest_url'] = $manifestUrl;
        
        // Fetch sub-courier information - show courier partner name from bookings
        $row['sub_couriers'] = [];
        if (!empty($awbs)) {
            // Get courier names from bookings using waybill numbers
            $placeholders = implode(',', array_fill(0, count($awbs), '?'));
            $courierSql = "SELECT DISTINCT cp.partner_name as courier_name 
                          FROM tbl_bookings b 
                          LEFT JOIN tbl_courier_partner cp ON cp.id = b.courier_id 
                          WHERE b.waybill_no IN ($placeholders) AND b.waybill_no IS NOT NULL AND b.waybill_no != ''";
            $courierStmt = $pdo->prepare($courierSql);
            $courierStmt->execute($awbs);
            $courierData = $courierStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $courierNames = [];
            foreach ($courierData as $c) {
                $courierName = !empty($c['courier_name']) ? trim((string)$c['courier_name']) : 'Unknown';
                if ($courierName !== '' && !in_array($courierName, $courierNames)) {
                    $courierNames[] = $courierName;
                }
            }
            $row['sub_couriers'] = $courierNames;
        }
    }
    unset($row);

    echo json_encode([
        'status' => 'success',
        'data' => $rows
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
