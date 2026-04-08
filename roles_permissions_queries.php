<?php
require_once 'config/config.php';

echo "<h2>Roles and Permissions Management Queries</h2>";

// 1. INSERT queries for new roles
echo "<h3>1. INSERT New Roles</h3>";
$insert_roles_queries = [
    "INSERT INTO `roles` (`name`, `description`) VALUES ('Supervisor', 'Can manage team members and daily operations')",
    "INSERT INTO `roles` (`name`, `description`) VALUES ('Accountant', 'Handles financial transactions and reports')",
    "INSERT INTO `roles` (`name`, `description`) VALUES 'Sales Executive', 'Manages customer relationships and sales')",
    "INSERT INTO `roles` (`name`, `description`) VALUES ('Warehouse Staff', 'Handles inventory and warehouse operations')"
];

foreach ($insert_roles_queries as $i => $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 2. INSERT queries for new permission modules
echo "<h3>2. INSERT New Permission Modules</h3>";
$insert_modules_queries = [
    "INSERT INTO `permission_modules` (`name`, `sorted`) VALUES ('Finance', 6)",
    "INSERT INTO `permission_modules` (`name`, `sorted`) VALUES ('Sales', 7)",
    "INSERT INTO `permission_modules` (`name`, `sorted`) VALUES ('Warehouse', 8)",
    "INSERT INTO `permission_modules` (`name`, `sorted`) VALUES ('Operations', 9)"
];

foreach ($insert_modules_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 3. INSERT queries for new permissions based on existing files
echo "<h3>3. INSERT New Permissions (Based on Existing Files)</h3>";
$insert_permissions_queries = [
    // Dashboard
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (1, 'Dashboard', 'dashboard')",
    
    // Employee Management
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (2, 'Employee List', 'employee-list')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (2, 'Department List', 'department-list')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (2, 'Designation List', 'designation-list')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (2, 'Salary Template', 'salary-template')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (2, 'Employee Salary Assign', 'employee-salary-assign')",
    
    // Settings
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (3, 'Role Settings', 'setting-role')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (3, 'Permission Settings', 'setting-permission')",
    
    // Reports
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (4, 'Reports', 'reports')",
    
    // Master Data
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Branch', 'branch')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Company', 'company')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Company List', 'company-list')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Client', 'client')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Consignor', 'consignor')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Consignee', 'consignee')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Pickup Points', 'pickuppoint')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Status', 'status')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (5, 'Coloader', 'coloader')",
    
    // Booking/Shipment
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Shipment Create', 'shipment-create')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Shipment Bulk', 'shipment-bulk')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Shipment List', 'shipment-list')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Shipment Status Update', 'shipment-status-update')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Tracking', 'tracking')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Rate Calculator', 'rate-calculator')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'NDR Shipments', 'ndr-shipments')",
    "INSERT INTO `permission` (`module_id`, `name`, `prefix`) VALUES (6, 'Pickup Request', 'pickup-request')"
];

foreach ($insert_permissions_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 4. ALTER queries to modify existing tables
echo "<h3>4. ALTER TABLE Queries</h3>";
$alter_queries = [
    "ALTER TABLE `roles` ADD COLUMN `description` text DEFAULT NULL AFTER `name`",
    "ALTER TABLE `roles` ADD COLUMN `status` tinyint(1) DEFAULT 1 AFTER `description`",
    "ALTER TABLE `roles` ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `status`",
    "ALTER TABLE `roles` ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    
    "ALTER TABLE `permission_modules` ADD COLUMN `status` tinyint(1) DEFAULT 1 AFTER `sorted`",
    
    "ALTER TABLE `permission` ADD COLUMN `show_view` tinyint(1) DEFAULT 1 AFTER `prefix`",
    "ALTER TABLE `permission` ADD COLUMN `show_add` tinyint(1) DEFAULT 1 AFTER `show_view`",
    "ALTER TABLE `permission` ADD COLUMN `show_edit` tinyint(1) DEFAULT 1 AFTER `show_add`",
    "ALTER TABLE `permission` ADD COLUMN `show_delete` tinyint(1) DEFAULT 1 AFTER `show_edit`",
    "ALTER TABLE `permission` ADD COLUMN `status` tinyint(1) DEFAULT 1 AFTER `show_delete`"
];

foreach ($alter_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 5. UPDATE queries to modify existing data
echo "<h3>5. UPDATE Existing Data</h3>";
$update_queries = [
    "UPDATE `roles` SET `description` = 'Full system access with all permissions' WHERE `id` = 1",
    "UPDATE `roles` SET `description` = 'Can manage most functions but limited system settings' WHERE `id` = 2",
    "UPDATE `roles` SET `description` = 'Basic access to daily operations' WHERE `id` = 3",
    
    "UPDATE `permission_modules` SET `sorted` = 1 WHERE `name` = 'Dashboard'",
    "UPDATE `permission_modules` SET `sorted` = 2 WHERE `name` = 'Employee Management'",
    "UPDATE `permission_modules` SET `sorted` = 3 WHERE `name` = 'Settings'",
    "UPDATE `permission_modules` SET `sorted` = 4 WHERE `name` = 'Reports'",
    "UPDATE `permission_modules` SET `sorted` = 5 WHERE `name` = 'Master Data'"
];

foreach ($update_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 6. INSERT queries for staff privileges (role permissions)
echo "<h3>6. INSERT Staff Privileges (Role Permissions)</h3>";
$privilege_queries = [
    // Manager permissions (role_id = 2)
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (2, 1, 1, 0, 0, 0)", // Dashboard view
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (2, 2, 1, 1, 1, 0)", // Employee list
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (2, 3, 1, 1, 1, 0)", // Department list
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (2, 4, 1, 0, 0, 0)", // Designation list
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (2, 7, 1, 0, 0, 0)", // Reports view
    
    // Employee permissions (role_id = 3)
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (3, 1, 1, 0, 0, 0)", // Dashboard view
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (3, 2, 1, 0, 0, 0)", // Employee list view only
    "INSERT INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (3, 7, 1, 0, 0, 0)"  // Reports view only
];

foreach ($privilege_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 7. SELECT queries for verification
echo "<h3>7. SELECT Queries for Verification</h3>";
$select_queries = [
    "SELECT * FROM `roles` ORDER BY id",
    "SELECT * FROM `permission_modules` ORDER BY sorted",
    "SELECT p.*, pm.name as module_name FROM `permission` p JOIN `permission_modules` pm ON p.module_id = pm.id ORDER BY pm.sorted, p.id",
    "SELECT sp.*, r.name as role_name, p.name as permission_name, p.prefix FROM `staff_privileges` sp JOIN `roles` r ON sp.role_id = r.id JOIN `permission` p ON sp.permission_id = p.id ORDER BY r.id, p.id"
];

foreach ($select_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// 8. DELETE queries for cleanup
echo "<h3>8. DELETE Queries for Cleanup</h3>";
$delete_queries = [
    "DELETE FROM `staff_privileges` WHERE role_id = ? AND permission_id = ?",
    "DELETE FROM `permission` WHERE id = ?",
    "DELETE FROM `permission_modules` WHERE id = ?",
    "DELETE FROM `roles` WHERE id = ?"
];

foreach ($delete_queries as $query) {
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
}

// Execute some basic queries to show current state
echo "<h3>9. Current Database State</h3>";
try {
    echo "<h4>Current Roles:</h4>";
    $stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th></tr>";
    foreach ($roles as $role) {
        echo "<tr><td>{$role['id']}</td><td>" . htmlspecialchars($role['name']) . "</td><td>" . htmlspecialchars($role['description'] ?? 'N/A') . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h4>Current Permission Modules:</h4>";
    $stmt = $pdo->query("SELECT id, name, sorted FROM permission_modules ORDER BY sorted");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Sort Order</th></tr>";
    foreach ($modules as $module) {
        echo "<tr><td>{$module['id']}</td><td>" . htmlspecialchars($module['name']) . "</td><td>{$module['sorted']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h4>Current Permissions (First 10):</h4>";
    $stmt = $pdo->query("SELECT p.id, p.name, p.prefix, pm.name as module FROM permission p JOIN permission_modules pm ON p.module_id = pm.id ORDER BY pm.sorted, p.id LIMIT 10");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Prefix</th><th>Module</th></tr>";
    foreach ($permissions as $permission) {
        echo "<tr><td>{$permission['id']}</td><td>" . htmlspecialchars($permission['name']) . "</td><td>" . htmlspecialchars($permission['prefix']) . "</td><td>" . htmlspecialchars($permission['module']) . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching current data: " . $e->getMessage() . "</p>";
}

echo "<h2>Complete!</h2>";
echo "<p><a href='setting-role.php'>Manage Roles</a></p>";
echo "<p><a href='setting-permission.php?id=1'>Manage Permissions</a></p>";
?>
