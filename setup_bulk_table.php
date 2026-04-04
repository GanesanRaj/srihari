<?php
require_once 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `tbl_bulkupload_jobs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `filename` varchar(255) NOT NULL,
      `status` enum('Pending','Processing','Completed','Failed') DEFAULT 'Pending',
      `total_records` int(11) DEFAULT 0,
      `success_count` int(11) DEFAULT 0,
      `failure_count` int(11) DEFAULT 0,
      `result_file` varchar(255) DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime DEFAULT current_timestamp(),
      `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'tbl_bulkupload_jobs' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>