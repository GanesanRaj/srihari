<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

$errors = [];

// Required fields
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $errors[] = "ID is required";
}
if (!isset($_POST['template_name']) || empty($_POST['template_name'])) {
    $errors[] = "Template name is required";
}
if (!isset($_POST['basic_salary']) || empty($_POST['basic_salary'])) {
    $errors[] = "Basic salary is required";
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "UPDATE tbl_salary_templates SET
                template_name = :template_name,
                description = :description,
                basic_salary = :basic_salary,
                hra = :hra,
                da = :da,
                medical_allowance = :medical_allowance,
                conveyance = :conveyance,
                other_allowances = :other_allowances,
                pf_deduction = :pf_deduction,
                insurance_deduction = :insurance_deduction,
                tax_deduction = :tax_deduction,
                other_deductions = :other_deductions,
                status = :status,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':id', intval($_POST['id']), PDO::PARAM_INT);
    $stmt->bindValue(':template_name', sanitizeText($_POST['template_name']));
    $stmt->bindValue(':description', isset($_POST['description']) ? sanitizeText($_POST['description']) : null);
    $stmt->bindValue(':basic_salary', floatval($_POST['basic_salary']));
    $stmt->bindValue(':hra', isset($_POST['hra']) ? floatval($_POST['hra']) : 0);
    $stmt->bindValue(':da', isset($_POST['da']) ? floatval($_POST['da']) : 0);
    $stmt->bindValue(':medical_allowance', isset($_POST['medical_allowance']) ? floatval($_POST['medical_allowance']) : 0);
    $stmt->bindValue(':conveyance', isset($_POST['conveyance']) ? floatval($_POST['conveyance']) : 0);
    $stmt->bindValue(':other_allowances', isset($_POST['other_allowances']) ? floatval($_POST['other_allowances']) : 0);
    $stmt->bindValue(':pf_deduction', isset($_POST['pf_deduction']) ? floatval($_POST['pf_deduction']) : 0);
    $stmt->bindValue(':insurance_deduction', isset($_POST['insurance_deduction']) ? floatval($_POST['insurance_deduction']) : 0);
    $stmt->bindValue(':tax_deduction', isset($_POST['tax_deduction']) ? floatval($_POST['tax_deduction']) : 0);
    $stmt->bindValue(':other_deductions', isset($_POST['other_deductions']) ? floatval($_POST['other_deductions']) : 0);
    $stmt->bindValue(':status', isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active');
    $stmt->bindValue(':updated_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Salary template updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update salary template']);
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Template name already exists']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
