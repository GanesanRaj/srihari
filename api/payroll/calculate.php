<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $employee_id = $_POST['employee_id'] ?? 0;
    $salary_month = $_POST['salary_month'] ?? '';

    if (empty($employee_id) || empty($salary_month)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    // Get employee salary template
    $stmt = $pdo->prepare("
        SELECT est.*, st.*
        FROM tbl_employee_salary_templates est
        JOIN tbl_salary_templates st ON est.salary_template_id = st.id
        WHERE est.employee_id = ? AND est.status = 'active'
        ORDER BY est.assigned_date DESC
        LIMIT 1
    ");
    $stmt->execute([$employee_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'No salary template assigned to this employee']);
        exit;
    }

    // Calculate working days in month
    $month_start = date('Y-m-01', strtotime($salary_month));
    $month_end = date('Y-m-t', strtotime($salary_month));
    $working_days = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($salary_month)), date('Y', strtotime($salary_month)));

    // Get attendance summary for the month
    $attendanceStmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days,
            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_days
        FROM tbl_attendance
        WHERE employee_id = ?
        AND attendance_date BETWEEN ? AND ?
    ");
    $attendanceStmt->execute([$employee_id, $month_start, $month_end]);
    $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

    $attendance_days = ($attendance['present_days'] ?? 0) + ($attendance['leave_days'] ?? 0) + (($attendance['half_days'] ?? 0) * 0.5);
    $absence_days = $attendance['absent_days'] ?? 0;
    $leave_days = $attendance['leave_days'] ?? 0;
    $half_days = $attendance['half_days'] ?? 0;

    // Calculate shift allowances
    $shiftStmt = $pdo->prepare("
        SELECT SUM(s.shift_allowance) as total_shift_allowance
        FROM tbl_attendance a
        JOIN tbl_shifts s ON a.shift_id = s.id
        WHERE a.employee_id = ?
        AND a.attendance_date BETWEEN ? AND ?
        AND a.status = 'present'
    ");
    $shiftStmt->execute([$employee_id, $month_start, $month_end]);
    $shift_data = $shiftStmt->fetch(PDO::FETCH_ASSOC);
    $shift_allowance = $shift_data['total_shift_allowance'] ?? 0;

    // Calculate salary components
    $basic_salary = $template['basic_salary'];
    $hra = $template['hra'];
    $da = $template['da'];
    $allowances = $template['medical_allowance'] + $template['conveyance'] + $template['other_allowances'];

    $gross_salary = $basic_salary + $hra + $da + $allowances;
    $per_day_salary = $gross_salary / $working_days;

    // Proportionate salary based on attendance
    $earned_salary = $per_day_salary * $attendance_days;

    // Deductions
    $pf_deduction = $template['pf_deduction'];
    $insurance_deduction = $template['insurance_deduction'];
    $tax_deduction = $template['tax_deduction'];
    $other_deductions = $template['other_deductions'];
    $total_deductions = $pf_deduction + $insurance_deduction + $tax_deduction + $other_deductions;

    // Net salary
    $net_salary = $earned_salary + $shift_allowance - $total_deductions;

    $result = [
        'success' => true,
        'data' => [
            'salary_template_id' => $template['id'],
            'working_days' => $working_days,
            'attendance_days' => $attendance_days,
            'leave_days' => $leave_days,
            'half_days' => $half_days,
            'absence_days' => $absence_days,
            'per_day_salary' => round($per_day_salary, 2),
            'basic_salary' => round($basic_salary, 2),
            'hra' => round($hra, 2),
            'da' => round($da, 2),
            'allowances' => round($allowances, 2),
            'shift_allowance' => round($shift_allowance, 2),
            'gross_salary' => round($gross_salary, 2),
            'pf_deduction' => round($pf_deduction, 2),
            'insurance_deduction' => round($insurance_deduction, 2),
            'tax_deduction' => round($tax_deduction, 2),
            'other_deductions' => round($other_deductions, 2),
            'total_deductions' => round($total_deductions, 2),
            'net_salary' => round($net_salary, 2)
        ]
    ];

    echo json_encode($result);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
