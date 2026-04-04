<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    // DataTable parameters
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $search_filter = isset($_GET['search']) ? $_GET['search'] : '';

    // Build WHERE clause
    $where_conditions = [];
    $params = [];

    if (!empty($search) || !empty($search_filter)) {
        $search_term = !empty($search) ? $search : $search_filter;
        $where_conditions[] = "template_name LIKE :search";
        $params[':search'] = '%' . $search_term . '%';
    }

    if (!empty($status_filter)) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $status_filter;
    }

    $where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM tbl_salary_templates" . $where_clause;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get filtered data
    $sql = "SELECT * FROM tbl_salary_templates" . $where_clause . " ORDER BY id DESC LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data,
        'status' => 'success'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
