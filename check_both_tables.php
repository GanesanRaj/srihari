<?php
require_once 'config/config.php';

echo "=== Comparing Employee Tables ===\n\n";

// Count rows
$count1 = $pdo->query("SELECT COUNT(*) FROM tbl_employee")->fetchColumn();
$count2 = $pdo->query("SELECT COUNT(*) FROM tbl_employees")->fetchColumn();

echo "tbl_employee count: $count1\n";
echo "tbl_employees count: $count2\n\n";

// Get IDs from both tables
$stmt = $pdo->query("SELECT id FROM tbl_employee ORDER BY id");
$ids1 = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "IDs in tbl_employee: " . implode(', ', $ids1) . "\n";

$stmt = $pdo->query("SELECT id FROM tbl_employees ORDER BY id");
$ids2 = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "IDs in tbl_employees: " . implode(', ', $ids2) . "\n\n";

// Check if the API read.php is reading from correct table
echo "=== API read.php check ===\n";
echo "Checking first few records from tbl_employees (via API logic):\n";

$stmt = $pdo->query("SELECT e.id, e.name, e.user_id FROM tbl_employees e LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, User: {$row['user_id']}\n";
}

echo "\n=== Checking tbl_employee (different table) ===\n";
$stmt = $pdo->query("SELECT id, name, user_id FROM tbl_employee LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, User: {$row['user_id']}\n";
}
?>
