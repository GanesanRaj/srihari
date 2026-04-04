<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }

    $fields = [];
    $params = [':id' => $id];

    if (array_key_exists('scan_type', $_POST)) {
        $fields[] = 'scan_type = :scan_type';
        $params[':scan_type'] = trim($_POST['scan_type']);
    }
    if (array_key_exists('scan_location', $_POST)) {
        $fields[] = 'scan_location = :scan_location';
        $params[':scan_location'] = trim($_POST['scan_location']);
    }
    if (array_key_exists('scan_datetime', $_POST)) {
        $fields[] = 'scan_datetime = :scan_datetime';
        $params[':scan_datetime'] = trim($_POST['scan_datetime']);
    }
    if (array_key_exists('status_code', $_POST)) {
        $fields[] = 'status_code = :status_code';
        $params[':status_code'] = trim($_POST['status_code']);
    }
    if (array_key_exists('remarks', $_POST)) {
        $fields[] = 'remarks = :remarks';
        $params[':remarks'] = trim($_POST['remarks']);
    }

    if (empty($fields)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        exit;
    }

    $fields[] = 'updated_at = NOW()';
    $sql = "UPDATE tbl_tracking SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Tracking record updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tracking record not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
