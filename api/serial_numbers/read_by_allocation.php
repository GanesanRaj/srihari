<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission ( 'serial_allocation', 'is_view' );

try {
    $allocation_id = isset ($_GET[ 'allocation_id' ]) ? intval ( $_GET[ 'allocation_id' ] ) : 0;

    if ($allocation_id === 0) {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Allocation ID is required' ] );
        exit;
        }

    // Get all serial numbers for the allocation, left join with bookings to find usage
    $sql = "SELECT sn.*, 
                   CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END AS is_used,
                   b.created_at AS used_date
            FROM tbl_serial_numbers sn
            LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci
            WHERE sn.allocation_id = :allocation_id
            ORDER BY sn.serial_number ASC";

    $stmt = $pdo->prepare ( $sql );
    $stmt->bindParam ( ':allocation_id', $allocation_id );
    $stmt->execute ();

    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    echo json_encode ( [
        'status' => 'success',
        'data' => $data,
        'total' => count ( $data )
    ] );

    }
catch ( PDOException $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage () ] );
    }
?>
