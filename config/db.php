<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Database configuration from environment
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'srilive');
$username = env('DB_USER', 'root');
$password = env('DB_PASSWORD', '');
$charset = env('DB_CHARSET', 'utf8mb4');
$timezone = env('DB_TIMEZONE', '+05:30');

// Set up a PDO connection 

// Define global variable for database connection
global $pdo;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set MySQL timezone from environment
    $pdo->exec("SET time_zone = '$timezone'");
} catch (PDOException $e) {
    $error = env('APP_ENV') === 'development' 
        ? "Database connection failed: " . $e->getMessage()
        : "Database connection failed";
    die($error);
}
