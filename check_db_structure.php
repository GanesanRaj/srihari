<?php
require_once 'config/config.php';

echo "<h2>Database Structure Analysis</h2>";

// Check if tables exist
$tables_to_check = ['roles', 'permission', 'permission_modules', 'staff_privileges'];

foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        
        echo "<h3>Table: $table - " . ($exists ? "EXISTS" : "MISSING") . "</h3>";
        
        if ($exists) {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
            }
            echo "</table>";
            
            // Show sample data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>Records: $count</p>";
            
            if ($count > 0 && $count <= 10) {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<table border='1'><tr>";
                foreach (array_keys($data[0]) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking $table: " . $e->getMessage() . "</p>";
    }
}

// Check if we need to create any tables
echo "<h2>Required SQL for Missing Tables</h2>";

$create_statements = [
    'roles' => "CREATE TABLE IF NOT EXISTS `roles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
    'permission_modules' => "CREATE TABLE IF NOT EXISTS `permission_modules` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `sorted` int(11) DEFAULT 0,
        `status` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
    'permission' => "CREATE TABLE IF NOT EXISTS `permission` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `module_id` int(11) NOT NULL,
        `name` varchar(100) NOT NULL,
        `prefix` varchar(50) NOT NULL,
        `show_view` tinyint(1) DEFAULT 1,
        `show_add` tinyint(1) DEFAULT 1,
        `show_edit` tinyint(1) DEFAULT 1,
        `show_delete` tinyint(1) DEFAULT 1,
        `status` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `prefix` (`prefix`),
        KEY `module_id` (`module_id`),
        FOREIGN KEY (`module_id`) REFERENCES `permission_modules` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
    'staff_privileges' => "CREATE TABLE IF NOT EXISTS `staff_privileges` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `role_id` int(11) NOT NULL,
        `permission_id` int(11) NOT NULL,
        `is_view` tinyint(1) DEFAULT 0,
        `is_add` tinyint(1) DEFAULT 0,
        `is_edit` tinyint(1) DEFAULT 0,
        `is_delete` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_permission` (`role_id`, `permission_id`),
        KEY `role_id` (`role_id`),
        KEY `permission_id` (`permission_id`),
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($create_statements as $table => $sql) {
    echo "<h4>$table:</h4>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
}

// Insert basic data if tables are empty
echo "<h2>Sample Data Insertion</h2>";

$insert_statements = [
    'roles' => [
        "INSERT INTO `roles` (`id`, `name`, `description`) VALUES (1, 'Administrator', 'Full system access with all permissions')",
        "INSERT INTO `roles` (`id`, `name`, `description`) VALUES (2, 'Manager', 'Can manage most functions but limited system settings')",
        "INSERT INTO `roles` (`id`, `name`, `description`) VALUES (3, 'Employee', 'Basic access to daily operations')"
    ],
    'permission_modules' => [
        "INSERT INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (1, 'Dashboard', 1)",
        "INSERT INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (2, 'Employee Management', 2)",
        "INSERT INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (3, 'Settings', 3)",
        "INSERT INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (4, 'Reports', 4)"
    ],
    'permission' => [
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (1, 1, 'Dashboard', 'dashboard')",
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (2, 2, 'Employee List', 'employee-list')",
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (3, 2, 'Department List', 'department-list')",
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (4, 2, 'Designation List', 'designation-list')",
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (5, 3, 'Role Settings', 'setting-role')",
        "INSERT INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (6, 3, 'Permission Settings', 'setting-permission')"
    ]
];

foreach ($insert_statements as $table => $statements) {
    echo "<h4>$table:</h4>";
    foreach ($statements as $sql) {
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    }
}
?>
