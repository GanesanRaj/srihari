<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $attendanceData = json_decode($json, true);

    if (!is_array($attendanceData) || empty($attendanceData)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        exit;
    }

    $created = 0;
    $skipped = 0;
    $errors = [];

    $created_by = $_SESSION['user_id'] ?? 1;

    foreach ($attendanceData as $data) {
        $employee_id = $data['employee_id'] ?? 0;
        $attendance_date = $data['attendance_date'] ?? '';
        $status = $data['status'] ?? 'present';
        $shift_id = $data['shift_id'] ?? null;
        $check_in_time = !empty($data['check_in_time']) ? $data['check_in_time'] : null;
        $check_out_time = !empty($data['check_out_time']) ? $data['check_out_time'] : null;
        $notes = $data['notes'] ?? '';

        if (empty($employee_id) || empty($attendance_date)) {
            $skipped++;
            continue;
        }

        // Check if attendance already exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM tbl_attendance
            WHERE employee_id = ? AND attendance_date = ?
        ");
        $checkStmt->execute([$employee_id, $attendance_date]);

        if ($checkStmt->fetch()) {
            $skipped++;
            continue;
        }

        // Insert attendance
        $stmt = $pdo->prepare("
            INSERT INTO tbl_attendance
            (employee_id, attendance_date, status, shift_id, check_in_time, check_out_time, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $employee_id,
            $attendance_date,
            $status,
            $shift_id,
            $check_in_time,
            $check_out_time,
            $notes,
            $created_by
        ]);

        if ($result) {
            $created++;
        } else {
            $skipped++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Bulk attendance processed',
        'created' => $created,
        'skipped' => $skipped,
        'total' => count($attendanceData)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
