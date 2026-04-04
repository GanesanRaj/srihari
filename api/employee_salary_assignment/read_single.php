<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

try {
    $sql = "SELECT
                esta.id,
                esta.employee_id,
                e.name as employee_name,
                e.user_id as employee_code,
                esta.salary_template_id,
                st.template_name,
                st.basic_salary,
                st.hra,
                st.da,
                st.medical_allowance,
                st.conveyance,
                st.other_allowances,
                st.pf_deduction,
                st.insurance_deduction,
                st.tax_deduction,
                st.other_deductions,
                esta.assigned_date,
                esta.effective_date,
                esta.status,
                u.name as assigned_by_name
            FROM tbl_employee_salary_templates esta
            LEFT JOIN tbl_employees e ON esta.employee_id = e.id
            LEFT JOIN tbl_salary_templates st ON esta.salary_template_id = st.id
            LEFT JOIN tbl_employees u ON esta.assigned_by = u.id
            WHERE esta.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', intval($_GET['id']), PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
        exit;
    }

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
