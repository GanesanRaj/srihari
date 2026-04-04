<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $id = $_POST['id'] ?? 0;
    $employee_id = $_POST['employee_id'] ?? 0;
    $attendance_date = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? 'present';
    $shift_id = $_POST['shift_id'] ?? null;
    $check_in_time = $_POST['check_in_time'] ?? null;
    $check_out_time = $_POST['check_out_time'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (empty($id) || empty($employee_id) || empty($attendance_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE tbl_attendance
        SET employee_id = ?, attendance_date = ?, status = ?, shift_id = ?,
            check_in_time = ?, check_out_time = ?, notes = ?, updated_by = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $updated_by = $_SESSION['user_id'] ?? 1;

    $result = $stmt->execute([
        $employee_id,
        $attendance_date,
        $status,
        $shift_id ?: null,
        $check_in_time ?: null,
        $check_out_time ?: null,
        $notes,
        $updated_by,
        $id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
