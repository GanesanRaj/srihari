<?php
require_once 'config/config.php';

echo "=== Verifying Fixed Permissions in sripro ===\n\n";

$stmt = $pdo->query('SELECT m.name as module, p.name as permission, p.prefix 
                      FROM permission p 
                      JOIN permission_modules m ON p.module_id = m.id 
                      ORDER BY m.sorted, p.id');

echo str_pad('Module', 20) . ' ' . str_pad('Permission', 30) . ' Prefix' . "\n";
echo str_repeat('-', 70) . "\n";

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo str_pad($row['module'], 20) . ' ' . str_pad($row['permission'], 30) . ' ' . $row['prefix'] . "\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Total permissions: " . $pdo->query('SELECT COUNT(*) FROM permission')->fetchColumn() . "\n";
echo "Staff privileges: " . $pdo->query('SELECT COUNT(*) FROM staff_privileges')->fetchColumn() . "\n";
?>
