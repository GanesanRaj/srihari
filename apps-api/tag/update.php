<?php
/**
 * Tag API – Update Tag
 * Location: /apps-api/tag/update.php
 * Method: GET
 * Params:
 *   tag_id      (required)
 *   from_branch (opt)
 *   to_branch   (opt)
 *   remarks     (opt)
 *   status      (opt) – packed | hold | partially_verified | fully_verified
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

$tag_id  = (int)($req['tag_id'] ?? 0);
$user_id = (int)($req['user_id'] ?? 0);

if ($tag_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'tag_id is required']);
    exit;
}

try {
    // Verify tag exists
    $chk = $pdo->prepare("SELECT id, status FROM tbl_tags WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $tag_id]);
    $tag = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $fields = [];
    $params = [':id' => $tag_id];

    // Updatable fields
    if (isset($req['from_branch']) && $req['from_branch'] !== '') {
        $fields[] = 'from_branch = :from_branch';
        $params[':from_branch'] = (int)$req['from_branch'];
    }
    if (isset($req['to_branch']) && $req['to_branch'] !== '') {
        $fields[] = 'to_branch = :to_branch';
        $params[':to_branch'] = (int)$req['to_branch'];
    }
    if (isset($req['remarks'])) {
        $fields[] = 'remarks = :remarks';
        $params[':remarks'] = trim($req['remarks']) ?: null;
    }
    if (isset($req['status']) && $req['status'] !== '') {
        $allowed = ['packed', 'hold', 'partially_verified', 'fully_verified'];
        $st = trim($req['status']);
        if (!in_array($st, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
            exit;
        }
        $fields[] = 'status = :status';
        $params[':status'] = $st;

        // Set verified_at / verified_by when marking fully_verified
        if ($st === 'fully_verified') {
            $fields[] = 'verified_at = NOW()';
            if ($user_id > 0) {
                $fields[] = 'verified_by = :verified_by';
                $params[':verified_by'] = $user_id;
            }
        }
    }

    if (empty($fields)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        exit;
    }

    $pdo->prepare("UPDATE tbl_tags SET " . implode(', ', $fields) . " WHERE id = :id")
        ->execute($params);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Tag updated successfully',
        'data'    => ['tag_id' => $tag_id]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
