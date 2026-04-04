<?php
require_once 'config/db.php';
try {
    $sql = "UPDATE tbl_employees SET shift_id = 4 WHERE name LIKE '%steve%'";
    $pdo->exec($sql);
    echo "Updated Steve's shift to 4 (Flexible Shift).";
} catch (Exception $e) {
    echo $e->getMessage();
}
