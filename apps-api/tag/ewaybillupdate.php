<?php
/**
 * Tag API – Update E-Way Bill Number on a Booking
 * Location: /apps-api/tag/ewaybillupdate.php
 * Method: GET
 * Params:
 *   awb_no      (required) – master or child AWB number
 *   ewaybill_no (required) – e-way bill number to set
 *   user_id     (optional) – who is updating
 *
 * Behaviour:
 *   - If awb_no matches a child package (tbl_booking_packages.awb_no):
 *       → updates tbl_booking_packages.child_ewaybill_no for that package
 *       → also updates tbl_bookings.ewaybill_no on the master booking
 *   - If awb_no matches a master waybill (tbl_bookings.waybill_no):
 *       → updates tbl_bookings.ewaybill_no only
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

$awb_no      = trim($req['awb_no']      ?? '');
$ewaybill_no = trim($req['ewaybill_no'] ?? '');
$user_id     = (int)($req['user_id']    ?? 0);

if ($awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'awb_no is required']);
    exit;
}
if ($ewaybill_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'ewaybill_no is required']);
    exit;
}

try {
    $updated_package = false;

    // 1. Check if awb_no is a child package AWB in tbl_booking_packages
    $pkgStmt = $pdo->prepare("SELECT bp.id, bp.booking_id, bp.awb_no, b.waybill_no
                               FROM tbl_booking_packages bp
                               JOIN tbl_bookings b ON b.id = bp.booking_id
                               WHERE bp.awb_no = :awb_no
                               LIMIT 1");
    $pkgStmt->execute([':awb_no' => $awb_no]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    if ($package) {
        // Update child_ewaybill_no in tbl_booking_packages
        $pkgUpdate = $pdo->prepare("UPDATE tbl_booking_packages
                                    SET child_ewaybill_no = :ewaybill_no,
                                        updated_by = :user_id,
                                        updated_at = NOW()
                                    WHERE id = :id");
        $pkgUpdate->execute([
            ':ewaybill_no' => $ewaybill_no,
            ':user_id'     => $user_id ?: null,
            ':id'          => $package['id']
        ]);
        
        $updated_package = true;
        $booking_id      = $package['booking_id'];
        $waybill_no      = $package['waybill_no'];
    } else {
        // 2. Fallback: find master booking by waybill_no
        $bStmt = $pdo->prepare("SELECT id, waybill_no FROM tbl_bookings WHERE waybill_no = :awb_no LIMIT 1");
        $bStmt->execute([':awb_no' => $awb_no]);
        $master = $bStmt->fetch(PDO::FETCH_ASSOC);

        if (!$master) {
            echo json_encode(['status' => 'error', 'message' => 'Booking not found for AWB: ' . $awb_no]);
            exit;
        }
        $booking_id = $master['id'];
        $waybill_no = $master['waybill_no'];
    }

    // 3. Always update ewaybill_no on the master booking
    $bUpdateParams = [':ewaybill_no' => $ewaybill_no, ':id' => $booking_id];
    $bUpdateSql    = "UPDATE tbl_bookings SET ewaybill_no = :ewaybill_no";
    if ($user_id > 0) {
        $bUpdateSql .= ", updated_by = :user_id";
        $bUpdateParams[':user_id'] = $user_id;
    }
    $bUpdateSql .= ", updated_at = NOW() WHERE id = :id";
    $pdo->prepare($bUpdateSql)->execute($bUpdateParams);

    echo json_encode([
        'status'  => 'success',
        'message' => 'E-Way Bill updated successfully',
        'data'    => [
            'booking_id'      => (int)$booking_id,
            'waybill_no'      => $waybill_no,
            'child_awb'       => $updated_package ? $awb_no : null,
            'ewaybill_no'     => $ewaybill_no,
            'package_updated' => $updated_package
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
