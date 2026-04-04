<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    // Build query with filters
    $sql = "SELECT
                esta.id,
                esta.employee_id,
                e.name as employee_name,
                e.user_id as employee_code,
                st.template_name,
                esta.assigned_date,
                esta.effective_date,
                esta.status,
                u.name as assigned_by_name
            FROM tbl_employee_salary_templates esta
            LEFT JOIN tbl_employees e ON esta.employee_id = e.id
            LEFT JOIN tbl_salary_templates st ON esta.salary_template_id = st.id
            LEFT JOIN tbl_employees u ON esta.assigned_by = u.id
            WHERE 1=1";

    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND esta.status = :status";
    }

    // Filter by employee
    if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
        $sql .= " AND esta.employee_id = :employee_id";
    }

    // Search
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (e.name LIKE :search OR e.user_id LIKE :search OR st.template_name LIKE :search)";
    }

    $sql .= " ORDER BY esta.assigned_date DESC";

    // Apply pagination for DataTable
    if (isset($_GET['start']) && isset($_GET['length'])) {
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $sql .= " LIMIT :start, :length";
    }

    $stmt = $pdo->prepare($sql);

    // Bind values
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $stmt->bindValue(':status', sanitizeText($_GET['status']));
    }

    if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
        $stmt->bindValue(':employee_id', intval($_GET['employee_id']), PDO::PARAM_INT);
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $stmt->bindValue(':search', $search);
    }

    if (isset($_GET['start']) && isset($_GET['length'])) {
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total records
    $count_sql = "SELECT COUNT(*) as total FROM tbl_employee_salary_templates WHERE 1=1";

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $count_sql .= " AND status = :status";
    }

    if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
        $count_sql .= " AND employee_id = :employee_id";
    }

    $count_stmt = $pdo->prepare($count_sql);

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $count_stmt->bindValue(':status', sanitizeText($_GET['status']));
    }

    if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
        $count_stmt->bindValue(':employee_id', intval($_GET['employee_id']), PDO::PARAM_INT);
    }

    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $response = [
        'status' => 'success',
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => $total,
        'recordsFiltered' => count($data),
        'data' => $data
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
