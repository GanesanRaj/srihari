<?php
// Comprehensive migration: srilive -> sripro

$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Data Migration: srilive -> sripro</h2>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Load table differences
$tables_to_migrate = json_decode(file_get_contents('tables_to_migrate.json'), true);

// Tables where srilive has more rows (priority for migration)
$priority_tables = [
    'permission',
    'shiprocket_manifest',
    'staff_privileges',
    'tbl_booking_auto_order_seq',
    'tbl_pickup_points',
    'tbl_pickup_requests'
];

$total_inserted = 0;
$total_updated = 0;
$total_errors = 0;

echo "<h3>Migrating Priority Tables (where srilive has more rows)</h3>";

foreach ($priority_tables as $table_name) {
    echo "<h4>Table: $table_name</h4>";
    
    // Get primary key
    $stmt = $pdo_live->query("SHOW KEYS FROM `$table_name` WHERE Key_name = 'PRIMARY'");
    $key_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $primary_key = $key_info ? $key_info['Column_name'] : 'id';
    
    // Get column names
    $stmt = $pdo_live->query("DESCRIBE `$table_name`");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $column_list = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $update_list = implode(', ', array_map(function($col) { return "$col = VALUES($col)"; }, $columns));
    
    // Get all rows from srilive
    $stmt = $pdo_live->query("SELECT * FROM `$table_name`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total rows in srilive: " . count($rows) . "</p>";
    
    $inserted = 0;
    $updated = 0;
    $errors = 0;
    
    foreach ($rows as $row) {
        try {
            $pk_value = $row[$primary_key];
            $values = array_values($row);
            
            // Check if row exists in sripro
            $check = $pdo_pro->prepare("SELECT COUNT(*) FROM `$table_name` WHERE `$primary_key` = ?");
            $check->execute([$pk_value]);
            $exists = $check->fetchColumn() > 0;
            
            if ($exists) {
                // Update existing row
                $set_clause = [];
                $update_values = [];
                foreach ($columns as $col) {
                    if ($col !== $primary_key) {
                        $set_clause[] = "`$col` = ?";
                        $update_values[] = $row[$col];
                    }
                }
                $update_values[] = $pk_value;
                
                $sql = "UPDATE `$table_name` SET " . implode(', ', $set_clause) . " WHERE `$primary_key` = ?";
                $stmt = $pdo_pro->prepare($sql);
                $stmt->execute($update_values);
                $updated++;
            } else {
                // Insert new row
                $sql = "INSERT INTO `$table_name` ($column_list) VALUES ($placeholders)";
                $stmt = $pdo_pro->prepare($sql);
                $stmt->execute($values);
                $inserted++;
            }
        } catch (Exception $e) {
            $errors++;
            echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>Inserted: $inserted, Updated: $updated, Errors: $errors</p>";
    $total_inserted += $inserted;
    $total_updated += $updated;
    $total_errors += $errors;
}

echo "<h2 style='color:green'>Migration Complete!</h2>";
echo "<p>Total rows inserted: $total_inserted</p>";
echo "<p>Total rows updated: $total_updated</p>";
echo "<p>Total errors: $total_errors</p>";
echo "<p><a href='analyze_db_differences.php'>Re-analyze differences</a></p>";
?>
