<?php
// Fix permissions in sripro to match srilive structure

$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Fixing Permissions: Sync from srilive to sripro</h2>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $pdo_pro->beginTransaction();
    
    // Step 1: Get permissions from srilive
    $stmt = $pdo_live->query("SELECT * FROM permission ORDER BY id");
    $live_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Permissions in srilive: " . count($live_permissions) . "</p>";
    
    // Step 2: Clear sripro permission table (keep modules)
    $pdo_pro->exec("DELETE FROM staff_privileges");
    $pdo_pro->exec("DELETE FROM permission");
    
    echo "<p style='color:orange'>Cleared sripro permission and staff_privileges tables</p>";
    
    // Step 3: Insert permissions from srilive
    $inserted = 0;
    foreach ($live_permissions as $perm) {
        $stmt = $pdo_pro->prepare("INSERT INTO permission (id, module_id, name, prefix, show_view, show_add, show_edit, show_delete) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $perm['id'],
            $perm['module_id'],
            $perm['name'],
            $perm['prefix'],
            $perm['show_view'],
            $perm['show_add'],
            $perm['show_edit'],
            $perm['show_delete']
        ]);
        $inserted++;
    }
    
    echo "<p style='color:green'>Inserted $inserted permissions</p>";
    
    // Step 4: Copy staff_privileges from srilive
    $stmt = $pdo_live->query("SELECT * FROM staff_privileges");
    $live_privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $priv_inserted = 0;
    foreach ($live_privileges as $priv) {
        $stmt = $pdo_pro->prepare("INSERT INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $priv['role_id'],
            $priv['permission_id'],
            $priv['is_view'],
            $priv['is_add'],
            $priv['is_edit'],
            $priv['is_delete']
        ]);
        $priv_inserted++;
    }
    
    echo "<p style='color:green'>Inserted $priv_inserted staff privileges</p>";
    
    $pdo_pro->commit();
    
    echo "<h2 style='color:green'>Permissions Fixed Successfully!</h2>";
    echo "<p>Permission table now matches srilive structure</p>";
    
} catch (Exception $e) {
    $pdo_pro->rollBack();
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}
?>
