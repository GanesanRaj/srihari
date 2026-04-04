<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$current_user = get_current_user_info();
$userId = $current_user ? $current_user['id'] : 1;

$errors = [];

// Required fields
$required_fields = ['template_name', 'basic_salary'];
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
    $sql = "INSERT INTO tbl_salary_templates (
                template_name, description, basic_salary, hra, da,
                medical_allowance, conveyance, other_allowances,
                pf_deduction, insurance_deduction, tax_deduction, other_deductions,
                status, created_by, created_at
            ) VALUES (
                :template_name, :description, :basic_salary, :hra, :da,
                :medical_allowance, :conveyance, :other_allowances,
                :pf_deduction, :insurance_deduction, :tax_deduction, :other_deductions,
                :status, :created_by, NOW()
            )";

    $stmt = $pdo->prepare($sql);

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
    $stmt->bindValue(':created_by', $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Salary template created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create salary template']);
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Template name already exists']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
