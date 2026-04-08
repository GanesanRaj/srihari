<?php
require_once 'config/config.php';

$tables = ['permission_modules', 'permission', 'staff_privileges'];

foreach ($tables as $t) {
    echo "TABLE: $t\n";
    echo str_repeat("-", 40) . "\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
