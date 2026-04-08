<?php
require_once 'config/config.php';

echo "=== Comparing Employee Tables ===\n\n";

// Count rows
$count1 = $pdo->query("SELECT COUNT(*) FROM tbl_employee")->fetchColumn();
$count2 = $pdo->query("SELECT COUNT(*) FROM tbl_employees")->fetchColumn();

echo "tbl_employee count: $count1\n";
echo "tbl_employees count: $count2\n\n";

// Get IDs from both tables
$stmt = $pdo->query("SELECT id, name FROM tbl_employee ORDER BY id");
echo "=== tbl_employee records ===\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}\n";
}

echo "\n=== tbl_employees records ===\n";
$stmt = $pdo->query("SELECT id, name FROM tbl_employees ORDER BY id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}\n";
}

echo "\n=== Problem Analysis ===\n";
echo "If you click edit on ID 8 in the list, but the form shows wrong data,\n";
echo "it means the list is fetching from a DIFFERENT table than the edit API.\n";
?>
