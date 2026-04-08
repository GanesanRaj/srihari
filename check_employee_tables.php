<?php
require_once 'config/config.php';

echo "=== Checking Employee Tables ===\n\n";

echo "Tables with 'employee' in name:\n";
$stmt = $pdo->query("SHOW TABLES LIKE '%employee%'");
while($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
    echo " - " . $row . "\n";
}

echo "\n=== Checking Employee ID 8 ===\n\n";

// Check tbl_employees
$stmt = $pdo->prepare("SELECT * FROM tbl_employees WHERE id = ?");
$stmt->execute([8]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($emp) {
    echo "Found in tbl_employees:\n";
    print_r($emp);
} else {
    echo "NOT found in tbl_employees\n";
}

// Check tbl_employee
$stmt = $pdo->query("SHOW TABLES LIKE 'tbl_employee'");
if ($stmt->fetch()) {
    echo "\ntbl_employee exists!\n";
    $stmt = $pdo->prepare("SELECT * FROM tbl_employee WHERE id = ?");
    $stmt->execute([8]);
    $emp2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($emp2) {
        echo "Found in tbl_employee:\n";
        print_r($emp2);
    } else {
        echo "NOT found in tbl_employee\n";
    }
}
?>
