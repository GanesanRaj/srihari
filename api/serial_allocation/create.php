<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('serial_allocation', 'is_add');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

// Declare variables
$branch_id = $service_type = $serial_from = $serial_to = $allocation_date = $remarks = '';
$errors = [];

// Validate POST data
$requiredFields = ['branch_id', 'service_type', 'serial_from', 'serial_to', 'allocation_date'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

// Validate service_type (express = Air, surface = Surface)
if (!in_array($service_type, ['express', 'surface'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid service type. Must be: express (Air) or surface']);
    exit;
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Validate serial range
    preg_match('/(\d+)/', $serial_from, $from_matches);
    preg_match('/(\d+)/', $serial_to, $to_matches);

    if (empty($from_matches) || empty($to_matches)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid serial number format']);
        exit;
    }

    $from_num = intval($from_matches[0]);
    $to_num = intval($to_matches[0]);

    if ($to_num <= $from_num) {
        echo json_encode(['status' => 'error', 'message' => 'Serial "To" must be greater than "From"']);
        exit;
    }

    $total_serials = ($to_num - $from_num) + 1;

    // Check for overlap with existing allocations (same branch + service type)
    $serviceTypes = ($service_type === 'express') ? ['express', 'air'] : [$service_type];
    $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
    $overlapSql = "SELECT serial_from, serial_to FROM tbl_serial_allocation
                   WHERE branch_id = ? AND service_type IN ($placeholders) AND status = 'active'";
    $overlapStmt = $pdo->prepare($overlapSql);
    $overlapStmt->execute(array_merge([$branch_id], $serviceTypes));
    $existingRanges = $overlapStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existingRanges as $row) {
        preg_match('/(\d+)/', $row['serial_from'], $ef);
        preg_match('/(\d+)/', $row['serial_to'], $et);
        if (!empty($ef) && !empty($et)) {
            $exist_from = (int) $ef[0];
            $exist_to = (int) $et[0];
            // Ranges overlap if: new_from <= existing_to AND new_to >= existing_from
            if ($from_num <= $exist_to && $to_num >= $exist_from) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Serial range overlaps with existing allocation: ' . $row['serial_from'] . ' to ' . $row['serial_to'] . '. Please use a non-overlapping range.'
                ]);
                exit;
            }
        }
    }

    // Generate allocation number
    $allocation_number = 'ALLOC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Check if allocation number already exists
    $checkSql = "SELECT id FROM tbl_serial_allocation WHERE serial_number = :serial_number";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':serial_number', $allocation_number);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        $allocation_number = 'ALLOC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert into tbl_serial_allocation
    $sql = "INSERT INTO tbl_serial_allocation
            (branch_id, service_type, serial_number, serial_from, serial_to, total_serials, used_serials, available_serials, allocation_date, status, remarks, created_by, created_at)
            VALUES
            (:branch_id, :service_type, :serial_number, :serial_from, :serial_to, :total_serials, 0, :available_serials, :allocation_date, 'active', :remarks, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':branch_id', $branch_id);
    $stmt->bindParam(':service_type', $service_type);
    $stmt->bindParam(':serial_number', $allocation_number);
    $stmt->bindParam(':serial_from', $serial_from);
    $stmt->bindParam(':serial_to', $serial_to);
    $stmt->bindParam(':total_serials', $total_serials);
    $stmt->bindParam(':available_serials', $total_serials);
    $stmt->bindParam(':allocation_date', $allocation_date);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':created_by', $current_user['id']);

    if (!$stmt->execute()) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to create allocation']);
        exit;
    }

    $allocation_id = $pdo->lastInsertId();

    // Generate individual serial numbers
    $prefix = preg_replace('/\d+/', '', $serial_from);
    $insertSerialSql = "INSERT INTO tbl_serial_numbers (allocation_id, branch_id, service_type, serial_number, is_used, status) VALUES (:allocation_id, :branch_id, :service_type, :serial_number, 0, 'available')";
    $serialStmt = $pdo->prepare($insertSerialSql);

    for ($i = $from_num; $i <= $to_num; $i++) {
        $serial_number = $prefix . str_pad($i, strlen($from_matches[0]), '0', STR_PAD_LEFT);

        $serialStmt->bindParam(':allocation_id', $allocation_id);
        $serialStmt->bindParam(':branch_id', $branch_id);
        $serialStmt->bindParam(':service_type', $service_type);
        $serialStmt->bindParam(':serial_number', $serial_number);
        $serialStmt->execute();

        // Log history
        $historySql = "INSERT INTO tbl_serial_history (serial_number_id, serial_number, branch_id, service_type, action, performed_by, action_date, remarks)
                       VALUES (LAST_INSERT_ID(), :serial_number, :branch_id, :service_type, 'allocated', :performed_by, NOW(), :remarks)";
        $historyStmt = $pdo->prepare($historySql);
        $historyStmt->bindParam(':serial_number', $serial_number);
        $historyStmt->bindParam(':branch_id', $branch_id);
        $historyStmt->bindParam(':service_type', $service_type);
        $historyStmt->bindParam(':performed_by', $current_user['id']);
        $historyStmt->bindParam(':remarks', $remarks);
        $historyStmt->execute();
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Serial allocation created successfully. Total ' . $total_serials . ' serials allocated.',
        'id' => $allocation_id,
        'allocation_number' => $allocation_number,
        'total_serials' => $total_serials
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
