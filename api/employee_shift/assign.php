<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    $employee_id = $_POST['employee_id'] ?? 0;
    $shift_id = $_POST['shift_id'] ?? 0;
    $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';

    if (empty($employee_id) || empty($shift_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee and shift are required']);
        exit;
    }

    // First, deactivate all previous shift assignments for this employee
    $deactivateStmt = $pdo->prepare("
        UPDATE tbl_employee_shifts
        SET status = 'inactive'
        WHERE employee_id = ? AND status = 'active'
    ");
    $deactivateStmt->execute([$employee_id]);

    // Check if this combination already exists
    $checkStmt = $pdo->prepare("
        SELECT id FROM tbl_employee_shifts
        WHERE employee_id = ? AND shift_id = ?
    ");
    $checkStmt->execute([$employee_id, $shift_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE tbl_employee_shifts
            SET assigned_date = ?, status = ?, created_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$assigned_date, $status, $existing['id']]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO tbl_employee_shifts
            (employee_id, shift_id, assigned_date, status, assigned_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $assigned_by = $_SESSION['user_id'] ?? 1;
        $result = $stmt->execute([$employee_id, $shift_id, $assigned_date, $status, $assigned_by]);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Shift assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign shift']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
