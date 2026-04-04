<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    // Get role ID from query parameter
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
        exit;
    }
    
    // Fetch role data
    $stmt = $pdo->prepare("
        SELECT id, prefix, name, is_system 
        FROM roles 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        echo json_encode(['success' => false, 'message' => 'Role not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $role
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("Role read single error: " . $e->getMessage());
}
?>

