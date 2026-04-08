<?php
require_once 'config/config.php';

echo "=== PERMISSION MODULES ===\n";
$stmt = $pdo->query('SELECT id, name, prefix, sorted FROM permission_modules ORDER BY sorted');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . '. ' . $row['name'] . ' (' . $row['prefix'] . ') - Sort: ' . $row['sorted'] . "\n";
}

echo "\n=== PERMISSIONS COUNT ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as count FROM permission');
echo 'Total permissions: ' . $stmt->fetch()['count'] . "\n";

echo "\n=== PERMISSIONS BY MODULE ===\n";
$stmt = $pdo->query('
    SELECT m.name as module_name, p.name as permission_name, p.prefix 
    FROM permission p 
    JOIN permission_modules m ON p.module_id = m.id 
    ORDER BY m.sorted, p.id
');
$current_module = '';
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($current_module != $row['module_name']) {
        $current_module = $row['module_name'];
        echo "\n[$current_module]\n";
    }
    echo '  - ' . $row['permission_name'] . ' (' . $row['prefix'] . ')' . "\n";
}

echo "\n=== ADMIN PRIVILEGES ===\n";
$stmt = $pdo->query('SELECT COUNT(*) as count FROM staff_privileges WHERE role_id = 1');
echo 'Administrator has access to ' . $stmt->fetch()['count'] . ' permissions' . "\n";
?>
