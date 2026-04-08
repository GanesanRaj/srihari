<?php
require_once 'config/config.php';

echo "<h2>Fixing Roles Table Structure</h2>";

// Add description column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM roles LIKE 'description'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN `description` text DEFAULT NULL AFTER `name`");
        echo "<p style='color: green;'>Added 'description' column to roles table</p>";
    } else {
        echo "<p style='color: blue;'>Description column already exists in roles table</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error adding description column: " . $e->getMessage() . "</p>";
}

// Insert roles with descriptions
$roles_data = [
    [1, 'Administrator', 'Full system access with all permissions'],
    [2, 'Manager', 'Can manage most functions but limited system settings'],
    [3, 'Employee', 'Basic access to daily operations']
];

foreach ($roles_data as $role) {
    try {
        $stmt = $pdo->prepare("INSERT INTO `roles` (`id`, `name`, `description`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)");
        $stmt->execute($role);
        echo "<p style='color: green;'>Role '{$role[1]}' updated/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error updating role '{$role[1]}': " . $e->getMessage() . "</p>";
    }
}

// Show current roles
echo "<h3>Current Roles in Database</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th><th>Status</th></tr>";
    foreach ($roles as $role) {
        echo "<tr>";
        echo "<td>{$role['id']}</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['description'] ?? 'N/A') . "</td>";
        echo "<td>{$role['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching roles: " . $e->getMessage() . "</p>";
}

echo "<h2>Roles Table Fixed!</h2>";
echo "<p><a href='setting-role.php'>Go to Role Management</a></p>";
?>
