<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

$errors = [];

// Required fields
$required_fields = ['branch_id', 'role_id', 'designation_id', 'name', 'user_id', 'password'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $errors[] = "Field '$field' is required";
    }
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "INSERT INTO tbl_employees (
                branch_id, role_id, designation_id, name, age, email, 
                father_name, mother_name, education, salary, experience, 
                phone, address, city, pincode, state, 
                country, status, user_id, password, created_by, created_at
            ) VALUES (
                :branch_id, :role_id, :designation_id, :name, :age, :email, 
                :father_name, :mother_name, :education, :salary, :experience, 
                :phone, :address, :city, :pincode, :state, 
                :country, :status, :user_id, :password, :created_by, NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':branch_id', $_POST['branch_id']);
    $stmt->bindValue(':role_id', $_POST['role_id']);
    $stmt->bindValue(':designation_id', $_POST['designation_id']);
    $stmt->bindValue(':name', sanitizeText($_POST['name']));
    $stmt->bindValue(':age', isset($_POST['age']) ? intval($_POST['age']) : null);
    $stmt->bindValue(':email', isset($_POST['email']) ? sanitizeText($_POST['email']) : null);
    $stmt->bindValue(':father_name', isset($_POST['father_name']) ? sanitizeText($_POST['father_name']) : null);
    $stmt->bindValue(':mother_name', isset($_POST['mother_name']) ? sanitizeText($_POST['mother_name']) : null);
    $stmt->bindValue(':education', isset($_POST['education']) ? sanitizeText($_POST['education']) : null);
    $stmt->bindValue(':salary', isset($_POST['salary']) ? $_POST['salary'] : 0.00);
    $stmt->bindValue(':experience', isset($_POST['experience']) ? sanitizeText($_POST['experience']) : null);
    $stmt->bindValue(':phone', isset($_POST['phone']) ? sanitizeText($_POST['phone']) : null);
    $stmt->bindValue(':address', isset($_POST['address']) ? sanitizeText($_POST['address']) : null);
    $stmt->bindValue(':city', isset($_POST['city']) ? sanitizeText($_POST['city']) : null);
    $stmt->bindValue(':pincode', isset($_POST['pincode']) ? sanitizeText($_POST['pincode']) : null);
    $stmt->bindValue(':state', isset($_POST['state']) ? sanitizeText($_POST['state']) : null);
    $stmt->bindValue(':country', isset($_POST['country']) ? sanitizeText($_POST['country']) : 'INDIA');
    $stmt->bindValue(':status', isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active');
    $stmt->bindValue(':user_id', sanitizeText($_POST['user_id']));
    $stmt->bindValue(':password', $_POST['password']); // Not encrypted as per requirement
    $stmt->bindValue(':created_by', $userId);

    if ($stmt->execute()) {
        $employeeId = (int) $pdo->lastInsertId();
        // Insert into tbl_user so employee can log in (apps-api / web)
        $username = sanitizeText($_POST['user_id']);
        $password = $_POST['password'];
        $branchId = (int) $_POST['branch_id'];
        $roleId   = (int) $_POST['role_id'];
        $status   = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
        try {
            $userSql = "INSERT INTO tbl_user (username, password, branch_id, role_id, status, user_type, user_id) 
                        VALUES (:username, :password, :branch_id, :role_id, :status, 'employee', :user_id)";
            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute([
                ':username'  => $username,
                ':password'  => $password,
                ':branch_id' => $branchId,
                ':role_id'   => $roleId,
                ':status'    => $status,
                ':user_id'   => $employeeId
            ]);
        } catch (PDOException $ue) {
            // tbl_user may not exist or columns differ; employee is still created
            error_log('Employee create: tbl_user insert failed: ' . $ue->getMessage());
        }
        echo json_encode(['status' => 'success', 'message' => 'Employee created successfully', 'id' => $employeeId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create employee']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
