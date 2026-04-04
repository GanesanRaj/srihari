<?php
/**
 * Manifest API – Delete Manifest
 * Location: /apps-api/manifest/delete.php
 * Method: GET
 * Params:
 *   manifest_id (required)
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

if ($manifest_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'manifest_id is required']);
    exit;
}

try {
    $chk = $pdo->prepare("SELECT id, manifest_no FROM tbl_manifest WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $manifest_id]);
    $manifest = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$manifest) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest not found']);
        exit;
    }

    $pdo->prepare("DELETE FROM tbl_manifest WHERE id = :id")->execute([':id' => $manifest_id]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Manifest deleted successfully',
        'data'    => [
            'manifest_id' => $manifest_id,
            'manifest_no' => $manifest['manifest_no']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
