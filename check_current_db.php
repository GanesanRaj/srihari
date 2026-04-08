<?php
require_once 'config/config.php';

echo "Current Database: " . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n";

echo "Employee ID 8 in tbl_employees: ";
$r = $pdo->query('SELECT name FROM tbl_employees WHERE id=8')->fetchColumn();
echo ($r ?: 'NOT FOUND') . "\n";

echo "\nAll employees in tbl_employees:\n";
$stmt = $pdo->query("SELECT id, name, user_id FROM tbl_employees ORDER BY id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, User: {$row['user_id']}\n";
}
?>
