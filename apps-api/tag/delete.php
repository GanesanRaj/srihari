<?php
/**
 * Tag API – Delete Tag
 * Location: /apps-api/tag/delete.php
 * Method: GET
 * Params:
 *   tag_id (required)
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

$tag_id = (int)($req['tag_id'] ?? 0);

if ($tag_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'tag_id is required']);
    exit;
}

try {
    $chk = $pdo->prepare("SELECT id, tag_no FROM tbl_tags WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $tag_id]);
    $tag = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $pdo->prepare("DELETE FROM tbl_tags WHERE id = :id")->execute([':id' => $tag_id]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Tag deleted successfully',
        'data'    => [
            'tag_id' => $tag_id,
            'tag_no' => $tag['tag_no']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
