<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

try {
    // Check if template is in use
    $check_sql = "SELECT COUNT(*) as count FROM tbl_employee_salary_templates WHERE salary_template_id = :id AND status = 'active'";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->bindValue(':id', intval($_POST['id']), PDO::PARAM_INT);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete template. It is assigned to ' . $result['count'] . ' employee(s)']);
        exit;
    }

    // Delete template
    $sql = "DELETE FROM tbl_salary_templates WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', intval($_POST['id']), PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Salary template deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Salary template not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete salary template']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
