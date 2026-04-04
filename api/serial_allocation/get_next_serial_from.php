<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('serial_allocation', 'is_add');

$branch_id = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
$service_type = isset($_GET['service_type']) ? trim($_GET['service_type']) : '';

if (!$branch_id || !$service_type) {
    echo json_encode(['status' => 'error', 'message' => 'branch_id and service_type are required', 'next_serial_from' => '']);
    exit;
}

if (!in_array($service_type, ['express', 'surface', 'air'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid service type', 'next_serial_from' => '']);
    exit;
}

try {
    // For Air/Express, consider both 'express' and 'air' allocations to get the true next number
    $service_types = in_array($service_type, ['express', 'air']) ? ['express', 'air'] : ['surface'];
    $placeholders = implode(',', array_fill(0, count($service_types), '?'));
    $sql = "SELECT serial_to FROM tbl_serial_allocation
            WHERE branch_id = ? AND service_type IN ($placeholders) AND status = 'active'
            ORDER BY id DESC";
    $params = array_merge([$branch_id], $service_types);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $next_serial_from = '';
    $max_num = 0;
    $used_prefix = '';
    $pad_len = 3;

    foreach ($rows as $serial_to) {
        if (preg_match('/^(.+?)(\d+)$/', $serial_to, $m)) {
            $prefix = $m[1];
            $numStr = $m[2];
            $num = (int) $numStr;
            if ($num > $max_num) {
                $max_num = $num;
                $used_prefix = $prefix;
                $pad_len = strlen($numStr);
            }
        }
    }

    if ($max_num > 0 && $used_prefix !== '') {
        $next_num = $max_num + 1;
        $next_serial_from = $used_prefix . str_pad($next_num, $pad_len, '0', STR_PAD_LEFT);
    } else {
        // No previous allocation: suggest default start for this service type
        if (in_array($service_type, ['express', 'air'])) {
            $next_serial_from = 'AIR-001';
        } else {
            $next_serial_from = 'SUR-001';
        }
    }

    echo json_encode([
        'status' => 'success',
        'next_serial_from' => $next_serial_from
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error', 'next_serial_from' => '']);
}
?>
