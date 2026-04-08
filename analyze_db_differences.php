<?php
// Analyze differences between srilive and sripro databases

$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Comparison: srilive vs sripro</h2>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get all tables from both databases
$tables_live = $pdo_live->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tables_pro = $pdo_pro->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Tables to skip (system tables, logs, etc.)
$skip_tables = ['migration_logs', 'activity_logs', 'error_logs'];

echo "<h3>Tables Analysis</h3>";
echo "<p>Total tables in srilive: " . count($tables_live) . "</p>";
echo "<p>Total tables in sripro: " . count($tables_pro) . "</p>";

// Find tables only in srilive
$only_in_live = array_diff($tables_live, $tables_pro);
echo "<p style='color:orange'>Tables only in srilive: " . count($only_in_live) . "</p>";
if (!empty($only_in_live)) {
    echo "<ul>";
    foreach ($only_in_live as $tbl) {
        echo "<li>$tbl</li>";
    }
    echo "</ul>";
}

// Find tables only in sripro
$only_in_pro = array_diff($tables_pro, $tables_live);
echo "<p style='color:orange'>Tables only in sripro: " . count($only_in_pro) . "</p>";
if (!empty($only_in_pro)) {
    echo "<ul>";
    foreach ($only_in_pro as $tbl) {
        echo "<li>$tbl</li>";
    }
    echo "</ul>";
}

// Get common tables
$common_tables = array_intersect($tables_live, $tables_pro);
echo "<p style='color:green'>Common tables: " . count($common_tables) . "</p>";

// Analyze row counts for common tables
echo "<h3>Row Count Comparison</h3>";
echo "<table border='1'><tr><th>Table</th><th>srilive</th><th>sripro</th><th>Difference</th><th>Action</th></tr>";

$tables_with_differences = [];

foreach ($common_tables as $table) {
    if (in_array($table, $skip_tables)) continue;
    
    $count_live = $pdo_live->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    $count_pro = $pdo_pro->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    $diff = $count_live - $count_pro;
    
    $action = $diff > 0 ? "<span style='color:red'>Migrate $diff rows</span>" : ($diff < 0 ? "<span style='color:orange'>sripro has more</span>" : "<span style='color:green'>Synced</span>");
    
    if ($diff != 0) {
        $tables_with_differences[] = ['table' => $table, 'diff' => $diff, 'live_count' => $count_live, 'pro_count' => $count_pro];
    }
    
    echo "<tr><td>$table</td><td>$count_live</td><td>$count_pro</td><td>$diff</td><td>$action</td></tr>";
}
echo "</table>";

echo "<h3>Tables needing migration: " . count($tables_with_differences) . "</h3>";

// Save table list for migration
file_put_contents('tables_to_migrate.json', json_encode($tables_with_differences));

echo "<p>Table list saved to tables_to_migrate.json</p>";
echo "<p><a href='migrate_all_data.php'>Proceed to Migration</a></p>";
?>
