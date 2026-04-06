<?php
require_once 'config/db.php';

try {
    // Check if result_file is already longtext
    $pdo->exec("ALTER TABLE tbl_bulkupload_jobs MODIFY COLUMN result_file LONGTEXT DEFAULT NULL");
    echo "Altered tbl_bulkupload_jobs: result_file is now LONGTEXT\n";

    // Also ensureConsignee Email and GST exist in bookings if not already
    // (Assuming they were added in previous steps, but good to check if needed)
    $checkStatus = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_bookings' AND COLUMN_NAME = 'ewb_update_status'" )->fetchColumn ();
    if ((int) $checkStatus === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN ewb_update_status VARCHAR(20) NOT NULL DEFAULT 'not_required' AFTER ewaybill_no" );
        echo "Added tbl_bookings.ewb_update_status\n";
        }

    $checkResp = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_bookings' AND COLUMN_NAME = 'ewb_update_response'" )->fetchColumn ();
    if ((int) $checkResp === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN ewb_update_response LONGTEXT NULL AFTER ewb_update_status" );
        echo "Added tbl_bookings.ewb_update_response\n";
        }

    $checkAt = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_bookings' AND COLUMN_NAME = 'ewb_update_at'" )->fetchColumn ();
    if ((int) $checkAt === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN ewb_update_at DATETIME NULL AFTER ewb_update_response" );
        echo "Added tbl_bookings.ewb_update_at\n";
        }

    // Add pickup_state column in tbl_pickup_points for Shiprocket pickup sync
    $checkPickupState = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tbl_pickup_points'
          AND COLUMN_NAME = 'pickup_state'" )->fetchColumn ();
    if ((int) $checkPickupState === 0) {
        $pdo->exec ( "ALTER TABLE tbl_pickup_points ADD COLUMN pickup_state VARCHAR(100) NULL AFTER pin" );
        echo "Added tbl_pickup_points.pickup_state\n";
    }

    // Store selected Shiprocket courier service for Delhivery/Shiprocket flows
    $checkShiprocketCourierName = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tbl_bookings'
          AND COLUMN_NAME = 'shiprocket_courier_company_name'" )->fetchColumn ();
    if ((int) $checkShiprocketCourierName === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN shiprocket_courier_company_name VARCHAR(255) NULL AFTER courier_id" );
        echo "Added tbl_bookings.shiprocket_courier_company_name\n";
    }

    $checkShiprocketCourierId = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tbl_bookings'
          AND COLUMN_NAME = 'shiprocket_courier_company_id'" )->fetchColumn ();
    if ((int) $checkShiprocketCourierId === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN shiprocket_courier_company_id INT NULL AFTER shiprocket_courier_company_name" );
        echo "Added tbl_bookings.shiprocket_courier_company_id\n";
    }

    // Add is_manifest flag for manifest status tracking
    $checkIsManifest = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tbl_bookings'
          AND COLUMN_NAME = 'is_manifest'" )->fetchColumn ();
    if ((int) $checkIsManifest === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN is_manifest TINYINT(1) NOT NULL DEFAULT 0 AFTER shiprocket_courier_company_id" );
        echo "Added tbl_bookings.is_manifest\n";
    }

    // Create shiprocket_manifest table
    $checkManifestTable = $pdo->query ( "SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'shiprocket_manifest'" )->fetchColumn ();
    if ((int) $checkManifestTable === 0) {
        $pdo->exec ( "CREATE TABLE shiprocket_manifest (
            id INT AUTO_INCREMENT PRIMARY KEY,
            manifest_date DATE NOT NULL,
            manifested_id VARCHAR(100) NOT NULL,
            pickuppoint VARCHAR(255) NULL,
            manifstered_awb LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INT NULL,
            response LONGTEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci" );
        echo "Created table shiprocket_manifest\n";
    }

    // Monotonic sequence for Shiprocket order_id (auto_order_no on tbl_bookings)
    $checkSeqTable = $pdo->query ( "SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_booking_auto_order_seq'" )->fetchColumn ();
    if ((int) $checkSeqTable === 0) {
        $pdo->exec ( "CREATE TABLE tbl_booking_auto_order_seq (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci" );
        echo "Created table tbl_booking_auto_order_seq\n";
    }

    $checkAutoOrderNo = $pdo->query ( "SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tbl_bookings'
          AND COLUMN_NAME = 'auto_order_no'" )->fetchColumn ();
    if ((int) $checkAutoOrderNo === 0) {
        $pdo->exec ( "ALTER TABLE tbl_bookings ADD COLUMN auto_order_no INT UNSIGNED NULL UNIQUE AFTER booking_ref_id" );
        echo "Added tbl_bookings.auto_order_no (UNIQUE)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>