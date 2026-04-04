<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['prefix'])) {
        echo json_encode(['success' => false, 'message' => 'Role prefix is required']);
        exit;
    }
    
    if (empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Role name is required']);
        exit;
    }
    
    // Check if prefix already exists
    $checkStmt = $pdo->prepare("SELECT id FROM roles WHERE prefix = ?");
    $checkStmt->execute([$input['prefix']]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Role prefix already exists']);
        exit;
    }
    
    // Prepare data
    $prefix = trim($input['prefix']);
    $name = trim($input['name']);
    $is_system = isset($input['is_system']) ? (int)$input['is_system'] : 0;
    
    // Insert role
    $stmt = $pdo->prepare("
        INSERT INTO roles (prefix, name, is_system) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$prefix, $name, $is_system]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Role created successfully',
        'data' => [
            'id' => $pdo->lastInsertId(),
            'prefix' => $prefix,
            'name' => $name
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("Role create error: " . $e->getMessage());
}
?>

