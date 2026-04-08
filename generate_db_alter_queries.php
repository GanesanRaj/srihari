<?php
// Script to compare srilive and sripro databases and generate ALTER queries

// Database configurations
$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

// Connect to databases
try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Schema Comparison: srilive vs sripro</h2>";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get all tables from live database
$tables_live = $pdo_live->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tables_pro = $pdo_pro->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$alter_queries = [];
$missing_tables = [];

echo "<h3>Comparing " . count($tables_live) . " tables from srilive...</h3>";

foreach ($tables_live as $table) {
    // Check if table exists in pro
    if (!in_array($table, $tables_pro)) {
        $missing_tables[] = $table;
        echo "<p style='color:red'>Table '$table' missing in sripro</p>";
        continue;
    }
    
    // Get columns from both databases
    $cols_live = $pdo_live->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $cols_pro = $pdo_pro->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    // Create column map for pro database
    $pro_col_map = [];
    foreach ($cols_pro as $col) {
        $pro_col_map[$col['Field']] = $col;
    }
    
    // Find missing columns in pro
    $prev_col = null;
    foreach ($cols_live as $col) {
        $col_name = $col['Field'];
        
        if (!isset($pro_col_map[$col_name])) {
            // Column missing in pro - generate ALTER statement
            $alter_sql = generateAlterStatement($table, $col, $prev_col);
            $alter_queries[] = $alter_sql;
            echo "<p style='color:orange'>Missing column: $table.$col_name</p>";
        }
        $prev_col = $col_name;
    }
}

// Generate output SQL file
$output_file = 'alter_queries_sripro.sql';
$content = "-- ALTER QUERIES TO SYNC sripro WITH srilive\n";
$content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$content .= "USE sripro;\n\n";

if (!empty($missing_tables)) {
    $content .= "-- MISSING TABLES (need to be created manually):\n";
    foreach ($missing_tables as $tbl) {
        $content .= "-- Table: $tbl\n";
    }
    $content .= "\n";
}

if (!empty($alter_queries)) {
    $content .= "-- ALTER TABLE QUERIES TO ADD MISSING COLUMNS:\n\n";
    foreach ($alter_queries as $query) {
        $content .= $query . "\n\n";
    }
} else {
    $content .= "-- No missing columns found!\n";
}

file_put_contents($output_file, $content);

echo "<h2 style='color:green'>Done! Generated $output_file</h2>";
echo "<p>Total ALTER queries: " . count($alter_queries) . "</p>";
echo "<p>Missing tables: " . count($missing_tables) . "</p>";
echo "<p><a href='$output_file' download>Download SQL File</a></p>";
echo "<hr><pre>" . htmlspecialchars($content) . "</pre>";

function generateAlterStatement($table, $column, $after_col) {
    $col_name = $column['Field'];
    $col_type = $column['Type'];
    $col_null = ($column['Null'] === 'NO') ? 'NOT NULL' : 'NULL';
    $col_default = '';
    $col_extra = $column['Extra'] ? ' ' . strtoupper($column['Extra']) : '';
    
    // Handle default value
    if ($column['Default'] !== null) {
        if (is_string($column['Default'])) {
            $col_default = " DEFAULT '" . $column['Default'] . "'";
        } else {
            $col_default = " DEFAULT " . $column['Default'];
        }
    } elseif ($column['Null'] === 'YES' && $column['Default'] === null) {
        $col_default = " DEFAULT NULL";
    }
    
    $after_clause = $after_col ? " AFTER `$after_col`" : " FIRST";
    
    return "ALTER TABLE `$table` ADD COLUMN `$col_name` $col_type $col_null$col_default$col_extra$after_clause;";
}
?>
