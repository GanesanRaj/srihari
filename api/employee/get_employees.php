<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';

    $sql = "SELECT
                e.id,
                e.user_id as code,
                e.name,
                e.status,
                e.shift_id,
                b.branch_name
            FROM tbl_employees e
            LEFT JOIN tbl_branch b ON e.branch_id = b.id
            WHERE e.status = 'active'";

    if (!empty($branch_id)) {
        $sql .= " AND e.branch_id = :branch_id";
    }

    $sql .= " ORDER BY e.name ASC";

    $stmt = $pdo->prepare($sql);

    if (!empty($branch_id)) {
        $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
    }

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