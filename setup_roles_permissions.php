<?php
require_once 'config/config.php';

echo "<h2>Setting Up Roles and Permissions System</h2>";

// Create tables if they don't exist
$tables = [
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

$created_tables = [];
foreach ($tables as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        $created_tables[] = $table_name;
        echo "<p style='color: green;'>Table '$table_name' created/verified successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating table '$table_name': " . $e->getMessage() . "</p>";
    }
}

// Insert basic data
echo "<h3>Inserting Basic Data</h3>";

// Insert roles
$roles_data = [
    [1, 'Administrator', 'Full system access with all permissions'],
    [2, 'Manager', 'Can manage most functions but limited system settings'],
    [3, 'Employee', 'Basic access to daily operations']
];

foreach ($roles_data as $role) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `roles` (`id`, `name`, `description`) VALUES (?, ?, ?)");
        $stmt->execute($role);
        echo "<p style='color: green;'>Role '{$role[1]}' inserted/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error inserting role '{$role[1]}': " . $e->getMessage() . "</p>";
    }
}

// Insert permission modules
$modules_data = [
    [1, 'Dashboard', 1],
    [2, 'Employee Management', 2],
    [3, 'Settings', 3],
    [4, 'Reports', 4],
    [5, 'Master Data', 5]
];

foreach ($modules_data as $module) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (?, ?, ?)");
        $stmt->execute($module);
        echo "<p style='color: green;'>Module '{$module[1]}' inserted/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error inserting module '{$module[1]}': " . $e->getMessage() . "</p>";
    }
}

// Insert permissions
$permissions_data = [
    [1, 1, 'Dashboard', 'dashboard'],
    [2, 2, 'Employee List', 'employee-list'],
    [3, 2, 'Department List', 'department-list'],
    [4, 2, 'Designation List', 'designation-list'],
    [5, 3, 'Role Settings', 'setting-role'],
    [6, 3, 'Permission Settings', 'setting-permission'],
    [7, 4, 'Reports', 'reports'],
    [8, 5, 'Lead Management', 'master-lead'],
    [9, 5, 'Customer Management', 'master-customer'],
    [10, 5, 'Pickup Points', 'pickuppoint'],
    [11, 5, 'Coloader', 'coloader']
];

foreach ($permissions_data as $permission) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (?, ?, ?, ?)");
        $stmt->execute($permission);
        echo "<p style='color: green;'>Permission '{$permission[2]}' inserted/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error inserting permission '{$permission[2]}': " . $e->getMessage() . "</p>";
    }
}

// Set up default permissions for Administrator (full access)
echo "<h3>Setting Up Administrator Permissions</h3>";
try {
    $stmt = $pdo->query("SELECT id FROM permission");
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    foreach ($permissions as $permission_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (?, ?, 1, 1, 1, 1)");
        $stmt->execute([1, $permission_id]);
    }
    echo "<p style='color: green;'>Administrator permissions set up successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error setting up administrator permissions: " . $e->getMessage() . "</p>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p><a href='setting-role.php'>Go to Role Management</a></p>";
echo "<p><a href='setting-permission.php?id=1'>Manage Administrator Permissions</a></p>";
?>
