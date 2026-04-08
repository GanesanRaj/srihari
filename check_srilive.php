<?php
// Check srilive database
$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SRILIVE Database ===\n\n";
    
    echo "Employee tables:\n";
    $stmt = $pdo_live->query("SHOW TABLES LIKE '%employee%'");
    while($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
        echo " - " . $row . "\n";
    }
    
    echo "\ntbl_employees in srilive:\n";
    $stmt = $pdo_live->query("SELECT id, name FROM tbl_employees ORDER BY id");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Name: {$row['name']}\n";
    }
    
    echo "\ntbl_employee in srilive:\n";
    $stmt = $pdo_live->query("SELECT id, name FROM tbl_employee ORDER BY id");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Name: {$row['name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
