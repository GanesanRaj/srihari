<?php
/**
 * Manifest API – Create Manifest
 * Location: /apps-api/manifest/create.php
 * Method: GET
 * Params:
 *   user_id      (optional) – who is creating
 *   from_branch  (optional) – branch id
 *   to_branch    (optional) – branch id
 *   coloader     (optional)
 *   vehicle_no   (optional)
 *   driver_name  (optional)
 *   mobile_no    (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

try {
    $user_id     = !empty($req['user_id'])    ? (int)$req['user_id']    : 1;
    $from_branch = !empty($req['from_branch']) ? (int)$req['from_branch'] : null;
    $to_branch   = !empty($req['to_branch'])   ? (int)$req['to_branch']   : null;
    $coloader    = trim($req['coloader']    ?? '');
    $vehicle_no  = trim($req['vehicle_no']  ?? '');
    $driver_name = trim($req['driver_name'] ?? '');
    $mobile_no   = trim($req['mobile_no']   ?? '');

    // Auto-generate manifest_no: MAN-YYYYMMDD-NNN
    $date      = date('Ymd');
    $countStmt = $pdo->query("SELECT COUNT(*) FROM tbl_manifest WHERE DATE(created_at) = CURDATE()");
    $seq       = str_pad((int)$countStmt->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
    $manifestNo = "MAN-{$date}-{$seq}";

    $stmt = $pdo->prepare("INSERT INTO tbl_manifest
        (manifest_no, from_branch, to_branch, coloader, vehicle_no, driver_name, mobile_no,
         total_count, status, created_by, json_data, created_at)
        VALUES
        (:manifest_no, :from_branch, :to_branch, :coloader, :vehicle_no, :driver_name, :mobile_no,
         0, 'draft', :created_by, '[]', NOW())");

    $stmt->execute([
        ':manifest_no' => $manifestNo,
        ':from_branch' => $from_branch,
        ':to_branch'   => $to_branch,
        ':coloader'    => $coloader ?: null,
        ':vehicle_no'  => $vehicle_no ?: null,
        ':driver_name' => $driver_name ?: null,
        ':mobile_no'   => $mobile_no ?: null,
        ':created_by'  => $user_id
    ]);

    $manifestId = (int)$pdo->lastInsertId();

    echo json_encode([
        'status'      => 'success',
        'message'     => 'Manifest created successfully',
        'data'        => [
            'manifest_id' => $manifestId,
            'manifest_no' => $manifestNo,
            'status'      => 'draft'
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
