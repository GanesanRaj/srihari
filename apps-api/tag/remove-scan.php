<?php
/**
 * Tag API – Remove AWB from Tag
 * Location: /apps-api/tag/remove-scan.php
 * Method: GET | POST
 * Params:
 *   tag_id (required)
 *   awb_no (required) – AWB to remove from json_data
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
$awb_no = trim($req['awb_no'] ?? '');

if ($tag_id <= 0 || $awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'tag_id and awb_no are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    $tagStmt = $pdo->prepare("SELECT id, json_data, status FROM tbl_tags WHERE id = :id FOR UPDATE");
    $tagStmt->execute([':id' => $tag_id]);
    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $entries = json_decode($tag['json_data'] ?: '[]', true);

    // Find and remove the AWB
    $found   = false;
    $updated = [];
    foreach ($entries as $entry) {
        if ($entry['awb_no'] === $awb_no) {
            $found = true;
        } else {
            $updated[] = $entry;
        }
    }

    if (!$found) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'AWB not found in this tag']);
        exit;
    }

    // Recalculate tag status
    $hasHold   = in_array('hold', array_column($updated, 'status'));
    $tagStatus = empty($updated) ? 'packed' : ($hasHold ? 'hold' : 'packed');

    $pdo->prepare("UPDATE tbl_tags SET json_data = :json, total_count = :cnt, status = :status WHERE id = :id")
        ->execute([
            ':json'   => json_encode(array_values($updated)),
            ':cnt'    => count($updated),
            ':status' => $tagStatus,
            ':id'     => $tag_id
        ]);

    $pdo->commit();

    echo json_encode([
        'status'      => 'success',
        'message'     => 'AWB removed from tag',
        'awb_no'      => $awb_no,
        'tag_status'  => $tagStatus,
        'total_count' => count($updated)
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
