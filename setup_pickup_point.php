<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

try {
    $sql = file_get_contents('database/pickup_point_setup.sql');
    $pdo->exec($sql);
    echo "Pickup Points setup completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>