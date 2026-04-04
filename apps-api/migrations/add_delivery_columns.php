<?php
require 'g:/xampp/htdocs/SRIHARIAGENCIES/config/db.php';

try {
    echo "Adding delivery columns to tbl_booking_packages...\n";
    
    // Add delivery_pod_images TEXT
    $pdo->exec("ALTER TABLE tbl_booking_packages ADD COLUMN IF NOT EXISTS delivery_pod_images TEXT AFTER pod_images");
    echo "Added delivery_pod_images column.\n";
    
    // Add delivery_date DATETIME
    $pdo->exec("ALTER TABLE tbl_booking_packages ADD COLUMN IF NOT EXISTS delivery_date DATETIME AFTER status_date");
    echo "Added delivery_date column.\n";
    
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
