<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission ( 'serial_allocation', 'is_view' );

if ( ! isset ($_GET[ 'id' ])) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Allocation ID is required' ] );
    exit;
    }

$id = sanitizeText ( $_GET[ 'id' ] );

try {
    $sql = "SELECT sa.*, b.branch_name, b.branch_code
            FROM tbl_serial_allocation sa
            LEFT JOIN tbl_branch b ON sa.branch_id = b.id
            WHERE sa.id = :id";

    $stmt = $pdo->prepare ( $sql );
    $stmt->bindParam ( ':id', $id );
    $stmt->execute ();

    $data = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ($data) {
        // Get per-status counts from tbl_serial_numbers
        $countSql  = "SELECT
                        SUM(CASE WHEN b.id IS NULL THEN 1 ELSE 0 END) AS available_count,
                        SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END) AS used_count,
                        COUNT(*) AS total_count
                     FROM tbl_serial_numbers sn
                     LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci
                     WHERE sn.allocation_id = :id";
        $countStmt = $pdo->prepare ( $countSql );
        $countStmt->bindParam ( ':id', $id );
        $countStmt->execute ();
        $counts                    = $countStmt->fetch ( PDO::FETCH_ASSOC );
        $data[ 'available_count' ] = (int) ($counts[ 'available_count' ] ?? 0);
        $data[ 'used_count' ]      = (int) ($counts[ 'used_count' ] ?? 0);
        $data[ 'total_serials' ]   = (int) ($counts[ 'total_count' ] ?? 0);
        $data[ 'reserved_count' ]  = 0;
        $data[ 'cancelled_count' ] = 0;

        echo json_encode ( [ 'status' => 'success', 'data' => $data ] );
        } else {
        echo json_encode ( [ 'status' => 'error', 'message' => 'Allocation not found' ] );
        }

    }
catch ( PDOException $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage () ] );
    }
?>
