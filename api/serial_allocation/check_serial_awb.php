<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Also allow WHMS booking users
if ( ! get_permission('serial_allocation', 'is_view') && ! get_permission('whms_booking', 'is_view') ) {
    require_api_permission('serial_allocation', 'is_view');
}

$serial_number = isset($_GET['serial_number']) ? trim($_GET['serial_number']) : '';
$branch_id = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
$service_type = isset($_GET['service_type']) ? strtolower(trim($_GET['service_type'])) : '';
// Shipping Mode: Air and Express are same; Surface is separate. Branch-based allocation.
if ($service_type === 'air' || $service_type === 'express') {
    $service_type = 'express';
}

if ($serial_number === '') {
    echo json_encode(['status' => 'error', 'valid' => false, 'message' => 'Serial/AWB is required']);
    exit;
}

try {
    // Match by serial_number (trimmed, case-insensitive) so "SN-101" or " sn-101 " work
    $sql = "SELECT sn.id, sn.serial_number, sn.branch_id, sn.service_type, sn.is_used, sn.status
            FROM tbl_serial_numbers sn
            WHERE LOWER(TRIM(sn.serial_number)) = LOWER(TRIM(:serial_number))";
    $params = [':serial_number' => $serial_number];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $msg = "Serial \"{$serial_number}\" not found in allocation. Check Serial Allocation list: the serial must exist for the selected branch (e.g. Branch Office - Mumbai) and be available. Or leave AWB empty to assign from allocation.";
        echo json_encode(['status' => 'success', 'valid' => false, 'message' => $msg]);
        exit;
    }

    $allowedStatuses = ['available', 'reserved', 'cancelled'];
    if ($row['is_used'] != 0 || !in_array($row['status'], $allowedStatuses, true)) {
        echo json_encode(['status' => 'success', 'valid' => false, 'message' => 'Serial already used or not available']);
        exit;
    }

    if ($branch_id > 0 && (int) $row['branch_id'] !== $branch_id) {
        echo json_encode(['status' => 'success', 'valid' => false, 'message' => 'Serial does not belong to selected branch. Allocation is branch-based.']);
        exit;
    }

    if ($service_type !== '') {
        $serialServiceType = $row['service_type'] ?? 'air';
        $isSerialAir = in_array($serialServiceType, ['express', 'air'], true);
        $isSerialSurface = ($serialServiceType === 'surface');
        $isRequestAir = in_array($service_type, ['express', 'air'], true);
        $isRequestSurface = ($service_type === 'surface');

        $ok = ($isRequestAir && $isSerialAir) || ($isRequestSurface && $isSerialSurface);
        if (!$ok) {
            $serialFor = $isSerialAir ? 'Air/Express' : 'Surface';
            $youSelected = $isRequestSurface ? 'Surface' : 'Air/Express';
            $msg = "Shipping mode mismatch. Serial is from {$serialFor} allocation (branch-based); you selected {$youSelected}. Use the correct mode for this serial or leave AWB empty to assign from allocation.";
            echo json_encode(['status' => 'success', 'valid' => false, 'message' => $msg]);
            exit;
        }
    }

    echo json_encode([
        'status' => 'success',
        'valid' => true,
        'message' => 'Valid available serial',
        'serial_number' => $row['serial_number']
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'valid' => false, 'message' => 'Database error']);
}
?>
