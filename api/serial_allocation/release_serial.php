<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check permission
require_api_permission('serial_allocation', 'is_edit');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

if (!isset($_POST['serial_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Serial number is required']);
    exit;
}

$serial_number = sanitizeText($_POST['serial_number']);
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : 'Released from booking deletion';

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if serial exists and is used
    $checkSql = "SELECT * FROM tbl_serial_numbers WHERE serial_number = :serial_number AND is_used = 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':serial_number', $serial_number);
    $checkStmt->execute();
    $serial = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$serial) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Serial number not found or not in use']);
        exit;
    }

    // Release serial number (make it available again)
    $updateSql = "UPDATE tbl_serial_numbers
                  SET is_used = 0,
                      status = 'available',
                      booking_id = NULL,
                      used_date = NULL,
                      updated_at = NOW()
                  WHERE id = :id";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':id', $serial['id']);
    $updateStmt->execute();

    // Update allocation counters
    $updateAllocationSql = "UPDATE tbl_serial_allocation
                            SET used_serials = used_serials - 1,
                                available_serials = available_serials + 1
                            WHERE id = :allocation_id";

    $allocationStmt = $pdo->prepare($updateAllocationSql);
    $allocationStmt->bindParam(':allocation_id', $serial['allocation_id']);
    $allocationStmt->execute();

    // Log history
    $historySql = "INSERT INTO tbl_serial_history (serial_number_id, serial_number, branch_id, action, booking_id, performed_by, action_date, remarks)
                   VALUES (:serial_number_id, :serial_number, :branch_id, 'released', :booking_id, :performed_by, NOW(), :remarks)";

    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->bindParam(':serial_number_id', $serial['id']);
    $historyStmt->bindParam(':serial_number', $serial_number);
    $historyStmt->bindParam(':branch_id', $serial['branch_id']);
    $historyStmt->bindParam(':booking_id', $booking_id);
    $historyStmt->bindParam(':performed_by', $current_user['id']);
    $historyStmt->bindParam(':remarks', $remarks);
    $historyStmt->execute();

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Serial number released successfully and is now available',
        'serial_number' => $serial_number
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
