<?php
// Set headers first before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start session after headers
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from POST data
     
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if this is webapp login (mobile app) or regular login
    $isWebappLogin = isset($_POST['device_token']);

    if ($isWebappLogin) {
        // Webapp login - authenticate against tbl_employee
        $stmt = $pdo->prepare("SELECT e.*, d.name as designation_name, dept.name as department_name, b.branch_name as branch_name, r.name as role_name
                              FROM `tbl_employee` e
                              LEFT JOIN tbl_designation d ON e.designation_id = d.id
                              LEFT JOIN tbl_department dept ON e.department_id = dept.id
                              LEFT JOIN tbl_branch b ON e.branch_id = b.id
                              LEFT JOIN roles r ON e.role_id = r.id
                              WHERE e.email = :email AND e.status = 'active'");
        $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    } else {
        // Regular login - authenticate against tbl_user
        $stmt = $pdo->prepare("SELECT * FROM `tbl_user` WHERE username = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    }

    try {
        // Execute the query
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($user) {
            if ($password == $user['password']) {
                if ($isWebappLogin) {
                    // Webapp login - set mobile session variables
                    $_SESSION['mobile_username'] = $user['email'];
                    $_SESSION['mobile_user_id'] = $user['id'];
                    $_SESSION['mobile_employee_id'] = $user['id'];
                    $_SESSION['mobile_employee_name'] = $user['name'];
                    $_SESSION['mobile_user_type'] = 'employee'; // Webapp is for employees
                    $_SESSION['mobile_designation'] = $user['designation_name'];
                    $_SESSION['mobile_department'] = $user['department_name'];
                    $_SESSION['mobile_branch'] = $user['branch_name'];
                    $_SESSION['mobile_role'] = $user['role_name'];
                } else {
                    // Regular login - set regular session variables
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['user_type'] = $user['user_type'] ?? 'both';
                }

                // Store device/browser information for notifications
                $deviceInfo = [
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'login_time' => date('Y-m-d H:i:s'),
                    'session_id' => session_id()
                ];

                // Update or insert device info (you can expand this later)
                $_SESSION['device_info'] = $deviceInfo;

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Logged in successfully.',
                    'device_registered' => true
                ]);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid password.']);
                exit();
            }
        } else {
            $errorMessage = $isWebappLogin ? 'Employee credentials not found.' : 'User credentials not found.';
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}
?>