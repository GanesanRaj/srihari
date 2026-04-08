<?php
// Generate CREATE TABLE statements for missing tables

$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Generating CREATE TABLE statements for missing tables</h2>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get tables from both databases
$tables_live = $pdo_live->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tables_pro = $pdo_pro->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Find missing tables
$missing_tables = array_diff($tables_live, $tables_pro);

if (empty($missing_tables)) {
    echo "<p>No missing tables found!</p>";
    exit;
}

echo "<p>Missing tables: " . implode(', ', $missing_tables) . "</p>";

// Generate CREATE TABLE statements
$output = "-- CREATE TABLE STATEMENTS FOR MISSING TABLES\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "USE sripro;\n\n";

foreach ($missing_tables as $table) {
    $stmt = $pdo_live->query("SHOW CREATE TABLE `$table`");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        $create_sql = $result['Create Table'];
        
        // Remove AUTO_INCREMENT values
        $create_sql = preg_replace('/AUTO_INCREMENT=\d+\s*/', '', $create_sql);
        
        $output .= "-- Table: $table\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create_sql . ";\n\n";
        
        echo "<p style='color:green'>Generated CREATE TABLE for: $table</p>";
    }
}

$file = 'create_missing_tables.sql';
file_put_contents($file, $output);

echo "<h2 style='color:green'>Done! Generated $file</h2>";
echo "<p>Run this SQL file in sripro database to create missing tables.</p>";
echo "<hr><pre>" . htmlspecialchars($output) . "</pre>";
?>
