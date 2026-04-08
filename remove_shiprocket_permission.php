<?php
require_once 'config/config.php';

echo "<h2>Removing Enable Shiprocket Permission</h2>";

try {
    // First, check if the permission exists
    $stmt = $pdo->prepare("SELECT id, name, prefix FROM permission WHERE prefix = ?");
    $stmt->execute(['enable-shiprocket']);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($perm) {
        echo "<p>Found permission: {$perm['name']} (ID: {$perm['id']})</p>";
        
        // Delete from staff_privileges first (due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM staff_privileges WHERE permission_id = ?");
        $stmt->execute([$perm['id']]);
        echo "<p style='color: orange;'>Removed {$stmt->rowCount()} privilege records</p>";
        
        // Delete from permission table
        $stmt = $pdo->prepare("DELETE FROM permission WHERE id = ?");
        $stmt->execute([$perm['id']]);
        echo "<p style='color: green;'>Permission 'Enable Shiprocket' deleted successfully</p>";
    } else {
        echo "<p style='color: orange;'>Permission 'enable-shiprocket' not found in database</p>";
    }
    
    echo "<h2 style='color: green;'>Done!</h2>";
    echo "<p><a href='setting-role.php'>Go to Role Management</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
}
?>
