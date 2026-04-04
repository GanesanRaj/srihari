<?php
/**
 * Dashboard API – Get Detailed Lists
 * Location: /apps-api/dashboard/details.php
 * Params: branch_id (int), type (waiting_pickup, upcoming_tags, pending_delivery)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$branch_id = (int)($_REQUEST['branch_id'] ?? 0);
$type      = trim($_REQUEST['type'] ?? '');

if ($branch_id <= 0 || $type === '') {
    echo json_encode(['status' => 'error', 'message' => 'branch_id and type are required']);
    exit;
}

try {
    $results = [];

    switch ($type) {
        case 'waiting_pickup':
            $stmt = $pdo->prepare("SELECT b.id, b.waybill_no, b.shipper_name, b.shipper_city, b.shipper_phone, b.created_at 
                                   FROM tbl_bookings b 
                                   LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                                   WHERE COALESCE(b.branch_id, p.branch_id) = :bid 
                                   AND (b.last_status IS NULL OR b.last_status = '' OR b.last_status = 'Pending')
                                   ORDER BY b.id DESC");
            $stmt->execute([':bid' => $branch_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'upcoming_tags':
            $stmt = $pdo->prepare("SELECT t.id, t.tag_no, b.branch_name as from_branch_name, t.total_count, t.status, t.created_at 
                                   FROM tbl_tags t
                                   LEFT JOIN tbl_branch b ON t.from_branch = b.id
                                   WHERE t.to_branch = :bid AND t.status != 'fully_verified'
                                   ORDER BY t.id DESC");
            $stmt->execute([':bid' => $branch_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'pending_delivery':
            // Logic matches get-counts.php
            $stmt = $pdo->prepare("SELECT b.id, b.waybill_no, b.consignee_name, b.consignee_city, b.consignee_phone, b.last_status, b.created_at 
                                   FROM tbl_bookings b
                                   WHERE b.last_status = 'Received'
                                   ORDER BY b.id DESC");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
            exit;
    }

    echo json_encode([
        'status' => 'success',
        'type' => $type,
        'count' => count($results),
        'data' => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
