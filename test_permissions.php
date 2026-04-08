<?php
require_once 'config/config.php';
require_once 'config/middleware.php';

echo "<h2>Permission System Test</h2>";

// Test different role scenarios
$test_roles = [1, 2, 3]; // Superadmin, Admin, DataEntry

foreach ($test_roles as $role_id) {
    echo "<h3>Testing Role ID: $role_id</h3>";
    
    // Get role name
    try {
        $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $role = $stmt->fetch();
        echo "<p><strong>Role:</strong> " . htmlspecialchars($role['name'] ?? 'Unknown') . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error getting role name: " . $e->getMessage() . "</p>";
        continue;
    }
    
    // Test permission checking functions
    $test_permissions = [
        'dashboard',
        'employee-list',
        'department-list',
        'company',
        'branch',
        'pickuppoint',
        'coloader',
        'setting-role',
        'setting-permission'
    ];
    
    echo "<table border='1'><tr><th>Permission</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th><th>Can Access Any</th></tr>";
    
    foreach ($test_permissions as $permission) {
        $view = can_view($permission) ? 'Yes' : 'No';
        $add = can_add($permission) ? 'Yes' : 'No';
        $edit = can_edit($permission) ? 'Yes' : 'No';
        $delete = can_delete($permission) ? 'Yes' : 'No';
        $any = can_access_any($permission) ? 'Yes' : 'No';
        
        echo "<tr>";
        echo "<td>$permission</td>";
        echo "<td>$view</td>";
        echo "<td>$add</td>";
        echo "<td>$edit</td>";
        echo "<td>$delete</td>";
        echo "<td>$any</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test nav_can function (similar to what's used in navbar)
    echo "<h4>Nav Can Tests:</h4>";
    $nav_tests = [
        ['dashboard'],
        ['employee-list', 'department-list'],
        ['company', 'branch'],
        ['setting-role', 'setting-permission'],
        ['pickuppoint', 'coloader']
    ];
    
    // Define nav_can function for testing
    $nav_can = function( $prefixes ) use ($role_id) {
        if ((int) $role_id === 1) return true;   // role id 1 = admin, show all menu
        if ( ! is_array( $prefixes ) ) $prefixes = [ $prefixes ];
        foreach ( $prefixes as $p ) {
            if (can_access_any( $p )) return true;
        }
        return false;
    };
    
    foreach ($nav_tests as $prefixes) {
        $prefix_str = is_array($prefixes) ? implode(', ', $prefixes) : $prefixes;
        $result = $nav_can($prefixes) ? 'Yes' : 'No';
        echo "<p><strong>Nav Can [$prefix_str]:</strong> $result</p>";
    }
    
    echo "<hr>";
}

// Test middleware functions
echo "<h3>Middleware Function Tests</h3>";

// Test require_permission (this will redirect if not allowed)
echo "<p><strong>Testing require_permission function:</strong></p>";
echo "<p>This function would normally redirect if permission is denied.</p>";

// Test check_page_access
echo "<p><strong>Testing check_page_access function:</strong></p>";
$access_test = check_page_access('dashboard', 'is_view', false);
echo "<p>Dashboard view access: " . ($access_test ? 'Granted' : 'Denied') . "</p>";

$access_test = check_page_access('setting-role', 'is_edit', false);
echo "<p>Setting role edit access: " . ($access_test ? 'Granted' : 'Denied') . "</p>";

// Show current user info
echo "<h3>Current Session Info</h3>";
echo "<p><strong>Session Role ID:</strong> " . ($_SESSION['role_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Session Username:</strong> " . ($_SESSION['username'] ?? 'Not set') . "</p>";
echo "<p><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";

// Show database permissions for current role
if (isset($_SESSION['role_id'])) {
    echo "<h3>Database Permissions for Current Role</h3>";
    try {
        $stmt = $pdo->prepare("
            SELECT sp.*, p.name as permission_name, p.prefix 
            FROM staff_privileges sp 
            JOIN permission p ON sp.permission_id = p.id 
            WHERE sp.role_id = ?
            ORDER BY p.prefix
        ");
        $stmt->execute([$_SESSION['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($permissions) {
            echo "<table border='1'><tr><th>Permission</th><th>Prefix</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr>";
            foreach ($permissions as $perm) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($perm['permission_name']) . "</td>";
                echo "<td>" . htmlspecialchars($perm['prefix']) . "</td>";
                echo "<td>" . ($perm['is_view'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($perm['is_add'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($perm['is_edit'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($perm['is_delete'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No permissions found for current role.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error fetching permissions: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Complete!</h2>";
echo "<p><a href='setting-role.php'>Manage Roles</a></p>";
echo "<p><a href='setting-permission.php?id=1'>Manage Permissions</a></p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>
