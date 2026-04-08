<?php
// Migrate roles from srilive to sripro database

$live_db = ['host' => 'localhost', 'dbname' => 'srilive', 'user' => 'root', 'password' => ''];
$pro_db = ['host' => 'localhost', 'dbname' => 'sripro', 'user' => 'root', 'password' => ''];

try {
    $pdo_live = new PDO("mysql:host={$live_db['host']};dbname={$live_db['dbname']};charset=utf8mb4", $live_db['user'], $live_db['password']);
    $pdo_live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_pro = new PDO("mysql:host={$pro_db['host']};dbname={$pro_db['dbname']};charset=utf8mb4", $pro_db['user'], $pro_db['password']);
    $pdo_pro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Role Migration: srilive -> sripro</h2>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get roles from srilive
$stmt = $pdo_live->query("SELECT * FROM roles");
$live_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Roles in srilive: " . count($live_roles) . "</p>";

// Get existing roles in sripro
$stmt = $pdo_pro->query("SELECT id FROM roles");
$pro_roles = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
$pro_role_map = array_flip($pro_roles);

echo "<p>Existing roles in sripro: " . count($pro_roles) . "</p>";

$migrated = 0;
$updated = 0;

foreach ($live_roles as $role) {
    $role_id = $role['id'];
    
    if (isset($pro_role_map[$role_id])) {
        // Update existing role
        $stmt = $pdo_pro->prepare("UPDATE roles SET name = ?, description = ?, prefix = ?, is_system = ? WHERE id = ?");
        $stmt->execute([
            $role['name'],
            $role['description'],
            $role['prefix'] ?? null,
            $role['is_system'] ?? 'no',
            $role_id
        ]);
        $updated++;
        echo "<p style='color:orange'>Updated role: {$role['name']} (ID: $role_id)</p>";
    } else {
        // Insert new role
        $stmt = $pdo_pro->prepare("INSERT INTO roles (id, name, description, prefix, is_system) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $role['id'],
            $role['name'],
            $role['description'],
            $role['prefix'] ?? null,
            $role['is_system'] ?? 'no'
        ]);
        $migrated++;
        echo "<p style='color:green'>Migrated role: {$role['name']} (ID: $role_id)</p>";
    }
}

echo "<h2 style='color:green'>Migration Complete!</h2>";
echo "<p>New roles migrated: $migrated</p>";
echo "<p>Existing roles updated: $updated</p>";
echo "<p>Total roles in sripro now: " . ($migrated + $updated + count($pro_roles)) . "</p>";
?>
