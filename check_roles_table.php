<?php
require_once 'config/config.php';

echo "<h2>Checking roles table structure in sripro</h2>";
try {
    $stmt = $pdo->query("DESCRIBE roles");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>Existing roles in sripro</h2>";
    $stmt = $pdo->query("SELECT * FROM roles");
    echo "<table border='1'><tr><th>id</th><th>name</th><th>description</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>" . ($row['description'] ?? '') . "</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
