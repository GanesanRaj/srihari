<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate ID
    if (empty($input['id']) || (int)$input['id'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
        exit;
    }
    
    $id = (int)$input['id'];
    
    // Check if role exists
    $checkStmt = $pdo->prepare("SELECT id, is_system FROM roles WHERE id = ?");
    $checkStmt->execute([$id]);
    $existingRole = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingRole) {
        echo json_encode(['success' => false, 'message' => 'Role not found']);
        exit;
    }
    
    // Validate required fields
    if (empty($input['prefix'])) {
        echo json_encode(['success' => false, 'message' => 'Role prefix is required']);
        exit;
    }
    
    if (empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'Role name is required']);
        exit;
    }
    
    // Check if prefix already exists for another role
    $checkPrefixStmt = $pdo->prepare("SELECT id FROM roles WHERE prefix = ? AND id != ?");
    $checkPrefixStmt->execute([$input['prefix'], $id]);
    if ($checkPrefixStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Role prefix already exists']);
        exit;
    }
    
    // Prepare data
    $prefix = trim($input['prefix']);
    $name = trim($input['name']);
    $is_system = isset($input['is_system']) ? (int)$input['is_system'] : 0;
    
    // Update role
    $stmt = $pdo->prepare("
        UPDATE roles 
        SET prefix = ?, name = ?, is_system = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$prefix, $name, $is_system, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Role updated successfully',
        'data' => [
            'id' => $id,
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
    error_log("Role update error: " . $e->getMessage());
}
?>

