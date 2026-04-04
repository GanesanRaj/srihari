<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_POST['id']);
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
    $sql = "UPDATE tbl_employees SET 
                branch_id = :branch_id, 
                role_id = :role_id, 
                designation_id = :designation_id, 
                name = :name, 
                user_id = :user_id, 
                password = :password, 
                age = :age, 
                email = :email, 
                father_name = :father_name, 
                mother_name = :mother_name, 
                education = :education, 
                salary = :salary, 
                experience = :experience, 
                phone = :phone, 
                address = :address, 
                city = :city, 
                pincode = :pincode, 
                state = :state, 
                country = :country, 
                status = :status, 
                updated_by = :updated_by, 
                updated_at = NOW() 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':id', $id);
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
    $stmt->bindValue(':updated_by', $userId);

    if ($stmt->execute()) {
        // Update tbl_user so login stays in sync (username, password, branch_id, role_id, status)
        $username = sanitizeText($_POST['user_id']);
        $password = $_POST['password'];
        $branchId = (int) $_POST['branch_id'];
        $roleId   = (int) $_POST['role_id'];
        $status   = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
        try {
            $userUpd = $pdo->prepare("UPDATE tbl_user SET username = :username, password = :password, branch_id = :branch_id, role_id = :role_id, status = :status WHERE user_id = :emp_id");
            $userUpd->execute([
                ':username'  => $username,
                ':password'  => $password,
                ':branch_id' => $branchId,
                ':role_id'   => $roleId,
                ':status'    => $status,
                ':emp_id'    => $id
            ]);
            if ($userUpd->rowCount() === 0) {
                // No user row linked to this employee (e.g. created before user sync); insert one
                $userIns = $pdo->prepare("INSERT INTO tbl_user (username, password, branch_id, role_id, status, user_type, user_id) VALUES (:username, :password, :branch_id, :role_id, :status, 'employee', :user_id)");
                $userIns->execute([
                    ':username'  => $username,
                    ':password'  => $password,
                    ':branch_id' => $branchId,
                    ':role_id'   => $roleId,
                    ':status'    => $status,
                    ':user_id'   => $id
                ]);
            }
        } catch (PDOException $ue) {
            error_log('Employee update: tbl_user sync failed: ' . $ue->getMessage());
        }
        echo json_encode(['status' => 'success', 'message' => 'Employee updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update employee']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
