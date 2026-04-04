<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission (also allow WHMS booking users)
if ( ! get_permission('serial_allocation', 'is_view') && ! get_permission('whms_booking', 'is_view') ) {
    require_api_permission('serial_allocation', 'is_view');
}

try {
    $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
    $service_type = isset($_GET['service_type']) ? sanitizeText($_GET['service_type']) : '';

    if ($branch_id === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Branch ID is required']);
        exit;
    }

    // Get available serial numbers for the branch
    $sql = "SELECT sn.*, sa.allocation_date, sa.serial_number as allocation_number
            FROM tbl_serial_numbers sn
            LEFT JOIN tbl_serial_allocation sa ON sn.allocation_id = sa.id
            WHERE sn.branch_id = :branch_id
            AND sn.is_used = 0
            AND sn.status = 'available'
            AND sa.status = 'active'";

    // Add service type filter: Air and Express are same, Surface is separate (branch-based allocation)
    if (!empty($service_type)) {
        $st = strtolower(trim($service_type));
        if ($st === 'air' || $st === 'express') {
            $sql .= " AND sn.service_type IN ('express', 'air')";
        } else {
            $sql .= " AND sn.service_type = :service_type";
        }
    }

    $sql .= " ORDER BY sn.serial_number ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':branch_id', $branch_id);
    if (!empty($service_type)) {
        $st = strtolower(trim($service_type));
        if ($st !== 'air' && $st !== 'express') {
            $stmt->bindValue(':service_type', $st);
        }
    }
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($data)
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
