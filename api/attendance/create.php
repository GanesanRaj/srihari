<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $employee_id = $_POST['employee_id'] ?? 0;
    $attendance_date = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? 'present';
    $shift_id = $_POST['shift_id'] ?? null;
    $check_in_time = $_POST['check_in_time'] ?? null;
    $check_out_time = $_POST['check_out_time'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (empty($employee_id) || empty($attendance_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    // Check if attendance already exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_attendance WHERE employee_id = ? AND attendance_date = ?");
    $checkStmt->execute([$employee_id, $attendance_date]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Attendance already marked for this date']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO tbl_attendance
        (employee_id, attendance_date, status, shift_id, check_in_time, check_out_time, notes, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $created_by = $_SESSION['user_id'] ?? 1;

    $result = $stmt->execute([
        $employee_id,
        $attendance_date,
        $status,
        $shift_id ?: null,
        $check_in_time ?: null,
        $check_out_time ?: null,
        $notes,
        $created_by
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
