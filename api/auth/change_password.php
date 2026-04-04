<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Please login again.'
    ]);
    exit;
}

include '../../config/config.php';

try {
    // Get POST data
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode([
            'status' => 'error',
            'message' => 'New password must be at least 6 characters long'
        ]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'New password and confirm password do not match'
        ]);
        exit;
    }

    if ($current_password === $new_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'New password must be different from current password'
        ]);
        exit;
    }

    // Get user from database
    $username = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT user_id, username, password FROM tbl_user WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found or account is inactive'
        ]);
        exit;
    }

    // Verify current password
    // Note: If passwords are hashed, use password_verify()
    // For plain text comparison (not recommended for production):
    if ($user['password'] !== $current_password) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }

    // Update password
    // Note: In production, use password_hash() for security
    $updateStmt = $pdo->prepare("
        UPDATE tbl_user 
        SET password = ?, 
            updated_at = NOW(),
            updated_by = ?
        WHERE username = ?
    ");
    
    $updated = $updateStmt->execute([$new_password, $_SESSION['user_id'], $username]);

    if ($updated) {
        // Log the password change (optional)
        error_log("Password changed for user: " . $username . " at " . date('Y-m-d H:i:s'));

        echo json_encode([
            'status' => 'success',
            'message' => 'Password changed successfully. Please login again with your new password.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update password. Please try again.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}
?>

