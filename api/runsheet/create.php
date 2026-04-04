<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

try {
    $currentUser = get_current_user_info();
    $createdBy   = $currentUser['id'] ?? ($_SESSION['user_id'] ?? 1);

    $driverName   = sanitizeText($_POST['driver_name'] ?? '');
    $mobileNumber = sanitizeText($_POST['mobile_number'] ?? '');
    $runsheetDate = trim($_POST['runsheet_date'] ?? date('Y-m-d'));
    $manualNo     = trim($_POST['runsheet_no'] ?? '');

    /*// Use manual RS No if provided, otherwise auto-generate RS-YYYYMMDD-NNN
    if ($manualNo !== '') {
        // Check uniqueness
        $chk = $pdo->prepare("SELECT id FROM tbl_runsheet WHERE runsheet_no = :no LIMIT 1");
        $chk->execute([':no' => $manualNo]);
        if ($chk->fetch()) throw new Exception("Run Sheet No '{$manualNo}' already exists");
        $runsheetNo = $manualNo;
    } else {
        $date      = date('Ymd');
        $countStmt = $pdo->query("SELECT COUNT(*) FROM tbl_runsheet WHERE DATE(created_at) = CURDATE()");
        $seq       = str_pad((int)$countStmt->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
        $runsheetNo = "RS-{$date}-{$seq}";
    }*/
    // Use manual RS No if provided, otherwise auto-generate RS-YYYYMMDD-NNN
if ($manualNo !== '') {

    // Check uniqueness
    $chk = $pdo->prepare("SELECT id FROM tbl_runsheet WHERE runsheet_no = :no LIMIT 1");
    $chk->execute([':no' => $manualNo]);

    if ($chk->fetch()) {
        throw new Exception("Run Sheet No '{$manualNo}' already exists");
    }

    $runsheetNo = $manualNo;

} else {

    $date = date('Ymd');

    // Get last runsheet for today
    $stmt = $pdo->prepare("
        SELECT runsheet_no 
        FROM tbl_runsheet 
        WHERE runsheet_no LIKE :prefix 
        ORDER BY runsheet_no DESC 
        LIMIT 1
    ");

    $prefix = "RS-{$date}-%";
    $stmt->execute([':prefix' => $prefix]);

    $last = $stmt->fetchColumn();

    if ($last) {
        $lastSeq = (int) substr($last, -3);
        $nextSeq = $lastSeq + 1;
    } else {
        $nextSeq = 1;
    }

    $seq = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

    $runsheetNo = "RS-{$date}-{$seq}";
}

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_runsheet (runsheet_no, driver_name, mobile_number, runsheet_date, shipment_count, status, created_by)
         VALUES (:runsheet_no, :driver_name, :mobile_number, :runsheet_date, 0, 'draft', :created_by)"
    );
    $stmt->execute([
        ':runsheet_no'   => $runsheetNo,
        ':driver_name'   => $driverName,
        ':mobile_number' => $mobileNumber,
        ':runsheet_date' => $runsheetDate,
        ':created_by'    => $createdBy,
    ]);
    $runsheetId = $pdo->lastInsertId();

    echo json_encode([
        'status'        => 'success',
        'runsheet_id'   => $runsheetId,
        'runsheet_no'   => $runsheetNo,
        'runsheet_date' => $runsheetDate,
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
