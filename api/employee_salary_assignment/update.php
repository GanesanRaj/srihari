<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$errors = [];

// Required fields
$required_fields = ['salary_template_id', 'assigned_date'];
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
    // Verify assignment exists
    $check = $pdo->prepare("SELECT employee_id FROM tbl_employee_salary_templates WHERE id = :id");
    $check->bindValue(':id', intval($_GET['id']), PDO::PARAM_INT);
    $check->execute();

    if ($check->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
        exit;
    }

    $assignment = $check->fetch(PDO::FETCH_ASSOC);

    // Verify salary template exists
    $template_check = $pdo->prepare("SELECT id FROM tbl_salary_templates WHERE id = :id");
    $template_check->bindValue(':id', intval($_POST['salary_template_id']), PDO::PARAM_INT);
    $template_check->execute();

    if ($template_check->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Salary template not found']);
        exit;
    }

    // Update assignment
    $sql = "UPDATE tbl_employee_salary_templates SET
                salary_template_id = :salary_template_id,
                assigned_date = :assigned_date,
                effective_date = :effective_date,
                status = :status
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':id', intval($_GET['id']), PDO::PARAM_INT);
    $stmt->bindValue(':salary_template_id', intval($_POST['salary_template_id']), PDO::PARAM_INT);
    $stmt->bindValue(':assigned_date', $_POST['assigned_date']);
    $stmt->bindValue(':effective_date', isset($_POST['effective_date']) ? $_POST['effective_date'] : $_POST['assigned_date']);
    $stmt->bindValue(':status', isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active');

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Assignment updated successfully'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update assignment']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
