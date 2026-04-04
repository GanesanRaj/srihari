<?php
/**
 * Manifest API – Update Manifest Details
 * Location: /apps-api/manifest/update.php
 * Method: GET
 * Params:
 *   manifest_id (required)
 *   from_branch (opt)
 *   to_branch   (opt)
 *   coloader    (opt)
 *   vehicle_no  (opt)
 *   driver_name (opt)
 *   mobile_no   (opt)
 *   bag_count   (opt)
 *   weight      (opt)
 *   total_box   (opt)
 *   status      (opt) – draft | dispatched | received
 *   user_id     (opt) – who is updating
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

$manifest_id = (int)($req['manifest_id'] ?? 0);
$user_id     = (int)($req['user_id']     ?? 0);

if ($manifest_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'manifest_id is required']);
    exit;
}

try {
    // Verify manifest exists
    $chk = $pdo->prepare("SELECT id, status FROM tbl_manifest WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $manifest_id]);
    $manifest = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$manifest) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest not found']);
        exit;
    }

    $fields = [];
    $params = [':id' => $manifest_id];

    $allowed = ['from_branch', 'to_branch', 'coloader', 'vehicle_no', 'driver_name', 'mobile_no', 'bag_count', 'weight', 'total_box', 'status'];
    foreach ($allowed as $field) {
        if (isset($req[$field]) && $req[$field] !== '') {
            $fields[] = "$field = :$field";
            $params[":$field"] = $req[$field];
        }
    }

    // Allow clearing optional text fields when passed as empty
    $clearable = ['coloader', 'vehicle_no', 'driver_name', 'mobile_no'];
    foreach ($clearable as $field) {
        if (array_key_exists($field, $req) && $req[$field] === '' && !in_array("$field = :$field", $fields)) {
            $fields[] = "$field = :$field";
            $params[":$field"] = null;
        }
    }

    if (empty($fields)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        exit;
    }

    $fields[] = "updated_at = NOW()";
    if ($user_id > 0) {
        $fields[] = "updated_by = :user_id";
        $params[':user_id'] = $user_id;
    }

    $pdo->prepare("UPDATE tbl_manifest SET " . implode(', ', $fields) . " WHERE id = :id")
        ->execute($params);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Manifest updated successfully',
        'data'    => ['manifest_id' => $manifest_id]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
