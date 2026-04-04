<?php
require_once 'config/db.php';

// Set content type to HTML
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .info { color: blue; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Employee Database Migration</h1>
    <hr>
    <pre>
<?php
try {
    echo "Starting database migration...\n\n";

    // Check if role_id column exists
    $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = 'tbl_employees' AND COLUMN_NAME = 'role_id'";
    $stmt = $pdo->query($checkSql);
    $roleIdExists = $stmt->rowCount() > 0;

    // Check if designation_id column exists
    $checkSql2 = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'tbl_employees' AND COLUMN_NAME = 'designation_id'";
    $stmt2 = $pdo->query($checkSql2);
    $designationIdExists = $stmt2->rowCount() > 0;

    // Check if user_id column exists
    $checkSql3 = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'tbl_employees' AND COLUMN_NAME = 'user_id'";
    $stmt3 = $pdo->query($checkSql3);
    $userIdExists = $stmt3->rowCount() > 0;

    // Check if password column exists
    $checkSql4 = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'tbl_employees' AND COLUMN_NAME = 'password'";
    $stmt4 = $pdo->query($checkSql4);
    $passwordExists = $stmt4->rowCount() > 0;

    if (!$roleIdExists) {
        echo "Adding role_id column...\n";
        $pdo->exec("ALTER TABLE `tbl_employees` 
                    ADD COLUMN `role_id` INT(11) DEFAULT NULL COMMENT 'Link to roles table' AFTER `branch_id`");
        echo "✓ role_id column added successfully\n";
    } else {
        echo "✓ role_id column already exists\n";
    }

    if (!$designationIdExists) {
        echo "Adding designation_id column...\n";
        $pdo->exec("ALTER TABLE `tbl_employees` 
                    ADD COLUMN `designation_id` INT(11) DEFAULT NULL COMMENT 'Link to tbl_designations' AFTER `role_id`");
        echo "✓ designation_id column added successfully\n";
    } else {
        echo "✓ designation_id column already exists\n";
    }

    if (!$userIdExists) {
        echo "Adding user_id column...\n";
        $pdo->exec("ALTER TABLE `tbl_employees` 
                    ADD COLUMN `user_id` VARCHAR(100) DEFAULT NULL COMMENT 'Login username' AFTER `status`");
        echo "✓ user_id column added successfully\n";
    } else {
        echo "✓ user_id column already exists\n";
    }

    if (!$passwordExists) {
        echo "Adding password column...\n";
        $pdo->exec("ALTER TABLE `tbl_employees` 
                    ADD COLUMN `password` VARCHAR(255) DEFAULT NULL COMMENT 'Login password (plain text)' AFTER `user_id`");
        echo "✓ password column added successfully\n";
    } else {
        echo "✓ password column already exists\n";
    }

    // Add indexes if they don't exist
    echo "\nAdding indexes...\n";
    $checkIndexSql = "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                      WHERE TABLE_NAME = 'tbl_employees' AND INDEX_NAME = 'idx_role_id'";
    $indexStmt = $pdo->query($checkIndexSql);
    
    if ($indexStmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_employees` ADD KEY `idx_role_id` (`role_id`)");
        echo "✓ idx_role_id index added\n";
    } else {
        echo "✓ idx_role_id index already exists\n";
    }

    $checkIndexSql2 = "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                       WHERE TABLE_NAME = 'tbl_employees' AND INDEX_NAME = 'idx_designation_id'";
    $indexStmt2 = $pdo->query($checkIndexSql2);
    
    if ($indexStmt2->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_employees` ADD KEY `idx_designation_id` (`designation_id`)");
        echo "✓ idx_designation_id index added\n";
    } else {
        echo "✓ idx_designation_id index already exists\n";
    }

    echo "\n================================";
    echo "\n✅ MIGRATION COMPLETED SUCCESSFULLY!";
    echo "\n================================\n";
    echo "Table structure updated:\n";
    echo "- role_id column: ✓\n";
    echo "- designation_id column: ✓\n";
    echo "- user_id column: ✓\n";
    echo "- password column: ✓\n\n";
    echo "You can now:\n";
    echo "1. Go to employee-list.php\n";
    echo "2. Add new employees with role and designation\n";
    echo "3. Edit existing employees\n";

} catch (PDOException $e) {
    echo "❌ MIGRATION FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>
    </pre>
    <hr>
    <p><a href="employee-list.php">Go to Employee List</a> | <a href="employee-add.php">Add New Employee</a></p>
</body>
</html>
