<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission('serial_allocation', 'is_view');

try {
    $serial_number = isset($_GET['serial_number']) ? sanitizeText($_GET['serial_number']) : '';

    if (empty($serial_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Serial number is required']);
        exit;
    }

    // Get serial history
    $sql = "SELECT sh.*, b.branch_name, b.branch_code, u.name as performed_by_name
            FROM tbl_serial_history sh
            LEFT JOIN tbl_branch b ON sh.branch_id = b.id
            LEFT JOIN tbl_employees u ON sh.performed_by = u.id
            WHERE sh.serial_number = :serial_number
            ORDER BY sh.action_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':serial_number', $serial_number);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($data)
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
