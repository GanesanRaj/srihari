<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
    exit;
}

try {
    $sql = "SELECT
                esta.id,
                st.id as salary_template_id,
                st.template_name,
                esta.assigned_date,
                esta.effective_date,
                esta.status,
                u.name as assigned_by_name
            FROM tbl_employee_salary_templates esta
            LEFT JOIN tbl_salary_templates st ON esta.salary_template_id = st.id
            LEFT JOIN tbl_employees u ON esta.assigned_by = u.id
            WHERE esta.employee_id = :employee_id
            ORDER BY esta.assigned_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':employee_id', intval($_GET['employee_id']), PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
