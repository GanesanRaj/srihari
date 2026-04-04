<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID is required']);
        exit;
    }

    $id = intval($_GET['id']);

    $sql = "SELECT * FROM tbl_salary_templates WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Salary template not found'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
