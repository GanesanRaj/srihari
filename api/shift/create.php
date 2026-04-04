<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

// require_api_permission('shift', 'is_add');

try {
    $shift_name = $_POST['shift_name'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $duration_hours = $_POST['duration_hours'] ?? 8.0;
    $break_minutes = $_POST['break_minutes'] ?? 30;
    $shift_allowance = $_POST['shift_allowance'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_shifts
        (shift_name, start_time, end_time, duration_hours, break_minutes, shift_allowance, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $created_by = $_SESSION['user_id'] ?? 1;

    $result = $stmt->execute([
        $shift_name,
        $start_time,
        $end_time,
        $duration_hours,
        $break_minutes,
        $shift_allowance,
        $status,
        $created_by
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Shift created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create shift']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
