<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $employee_id = $_POST['employee_id'] ?? 0;
    $salary_month = $_POST['salary_month'] ?? '';
    $salary_template_id = $_POST['salary_template_id'] ?? null;
    $working_days = $_POST['working_days'] ?? 0;
    $attendance_days = $_POST['attendance_days'] ?? 0;
    $leave_days = $_POST['leave_days'] ?? 0;
    $half_days = $_POST['half_days'] ?? 0;
    $absence_days = $_POST['absence_days'] ?? 0;
    $per_day_salary = $_POST['per_day_salary'] ?? 0;
    $basic_salary = $_POST['basic_salary'] ?? 0;
    $hra = $_POST['hra'] ?? 0;
    $da = $_POST['da'] ?? 0;
    $allowances = $_POST['allowances'] ?? 0;
    $shift_allowance = $_POST['shift_allowance'] ?? 0;
    $gross_salary = $_POST['gross_salary'] ?? 0;
    $pf_deduction = $_POST['pf_deduction'] ?? 0;
    $insurance_deduction = $_POST['insurance_deduction'] ?? 0;
    $tax_deduction = $_POST['tax_deduction'] ?? 0;
    $other_deductions = $_POST['other_deductions'] ?? 0;
    $total_deductions = $_POST['total_deductions'] ?? 0;
    $net_salary = $_POST['net_salary'] ?? 0;
    $notes = $_POST['notes'] ?? '';

    if (empty($employee_id) || empty($salary_month)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    // Convert month input (YYYY-MM) to first day of month
    $salary_month_date = date('Y-m-01', strtotime($salary_month));

    // Check if payroll already exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_payroll WHERE employee_id = ? AND salary_month = ?");
    $checkStmt->execute([$employee_id, $salary_month_date]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payroll already generated for this employee and month']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_payroll
        (employee_id, salary_month, salary_template_id, working_days, attendance_days,
         leave_days, half_days, absence_days, per_day_salary, basic_salary, hra, da,
         allowances, shift_allowance, gross_salary, pf_deduction, insurance_deduction,
         tax_deduction, other_deductions, total_deductions, net_salary, status, notes,
         created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW())
    ");

    $created_by = $_SESSION['user_id'] ?? 1;

    $result = $stmt->execute([
        $employee_id,
        $salary_month_date,
        $salary_template_id,
        $working_days,
        $attendance_days,
        $leave_days,
        $half_days,
        $absence_days,
        $per_day_salary,
        $basic_salary,
        $hra,
        $da,
        $allowances,
        $shift_allowance,
        $gross_salary,
        $pf_deduction,
        $insurance_deduction,
        $tax_deduction,
        $other_deductions,
        $total_deductions,
        $net_salary,
        $notes,
        $created_by
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payroll generated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate payroll']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
