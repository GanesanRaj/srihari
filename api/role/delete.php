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
    
    // Check if role exists and if it's a system role
    $checkStmt = $pdo->prepare("SELECT id, is_system, name FROM roles WHERE id = ?");
    $checkStmt->execute([$id]);
    $role = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        echo json_encode(['success' => false, 'message' => 'Role not found']);
        exit;
    }
    
    // Prevent deletion of system roles
    if ($role['is_system'] == 1 || $role['is_system'] == '1') {
        echo json_encode(['success' => false, 'message' => 'System roles cannot be deleted']);
        exit;
    }
    
    // Check if role is assigned to any users (if user table exists)
    try {
        $userCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_employee WHERE role_id = ?");
        $userCheckStmt->execute([$id]);
        $userCount = $userCheckStmt->fetchColumn();
        
        if ($userCount > 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete role. It is assigned to {$userCount} user(s)"
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Table might not exist, continue with deletion
        error_log("User check error (non-critical): " . $e->getMessage());
    }
    
    // Delete role
    $deleteStmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Role deleted successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("Role delete error: " . $e->getMessage());
}
?>

