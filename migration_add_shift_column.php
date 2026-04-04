<?php
require_once 'config/db.php';

try {
    // check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tbl_employees LIKE 'shift_id'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE tbl_employees ADD COLUMN shift_id INT(11) NULL AFTER designation_id";
        $pdo->exec($sql);
        echo "Column shift_id added successfully.";
    } else {
        echo "Column shift_id already exists.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
