<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

$errors = [];

// Required fields
$required_fields = ['employee_id', 'salary_template_id', 'assigned_date'];
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
    // Verify employee exists
    $employee_check = $pdo->prepare("SELECT id FROM tbl_employees WHERE id = :id");
    $employee_check->bindValue(':id', intval($_POST['employee_id']), PDO::PARAM_INT);
    $employee_check->execute();

    if ($employee_check->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit;
    }

    // Verify salary template exists
    $template_check = $pdo->prepare("SELECT id FROM tbl_salary_templates WHERE id = :id");
    $template_check->bindValue(':id', intval($_POST['salary_template_id']), PDO::PARAM_INT);
    $template_check->execute();

    if ($template_check->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Salary template not found']);
        exit;
    }

    // Check if employee already has an active assignment
    $existing_check = $pdo->prepare("
        SELECT id FROM tbl_employee_salary_templates
        WHERE employee_id = :employee_id AND status = 'active'
    ");
    $existing_check->bindValue(':employee_id', intval($_POST['employee_id']), PDO::PARAM_INT);
    $existing_check->execute();

    if ($existing_check->rowCount() > 0) {
        // Deactivate previous assignment
        $deactivate = $pdo->prepare("
            UPDATE tbl_employee_salary_templates
            SET status = 'inactive'
            WHERE employee_id = :employee_id AND status = 'active'
        ");
        $deactivate->bindValue(':employee_id', intval($_POST['employee_id']), PDO::PARAM_INT);
        $deactivate->execute();
    }

    // Create new assignment
    $sql = "INSERT INTO tbl_employee_salary_templates (
                employee_id, salary_template_id, assigned_date, effective_date,
                status, assigned_by, created_at
            ) VALUES (
                :employee_id, :salary_template_id, :assigned_date, :effective_date,
                :status, :assigned_by, NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':employee_id', intval($_POST['employee_id']), PDO::PARAM_INT);
    $stmt->bindValue(':salary_template_id', intval($_POST['salary_template_id']), PDO::PARAM_INT);
    $stmt->bindValue(':assigned_date', $_POST['assigned_date']);
    $stmt->bindValue(':effective_date', isset($_POST['effective_date']) ? $_POST['effective_date'] : $_POST['assigned_date']);
    $stmt->bindValue(':status', isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active');
    $stmt->bindValue(':assigned_by', $userId);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Salary template assigned successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign salary template']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
