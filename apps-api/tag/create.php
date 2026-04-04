<?php
/**
 * Tag API – Create Tag
 * Location: /apps-api/tag/create.php
 * Method: GET
 * Params:
 *   user_id     (opt) – who is creating (default 1)
 *   from_branch (opt) – branch id
 *   to_branch   (opt) – branch id
 *   tag_no      (opt) – custom tag number; auto-generated if empty
 *   remarks     (opt)
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

$user_id     = !empty($req['user_id'])    ? (int)$req['user_id']    : 1;
$from_branch = !empty($req['from_branch']) ? (int)$req['from_branch'] : null;
$to_branch   = !empty($req['to_branch'])   ? (int)$req['to_branch']   : null;
$tag_no      = trim($req['tag_no'] ?? '');
$remarks     = trim($req['remarks'] ?? '');

try {
    if ($tag_no === '') {
        // Auto-generate tag_no: TAG-YYYYMMDD-NNN
        $date      = date('Ymd');
        $countStmt = $pdo->query("SELECT COUNT(*) FROM tbl_tags WHERE DATE(created_at) = CURDATE()");
        $seq       = str_pad((int)$countStmt->fetchColumn() + 1, 3, '0', STR_PAD_LEFT);
        $tag_no    = "TAG-{$date}-{$seq}";
    } else {
        // Check duplicate
        $chk = $pdo->prepare("SELECT id FROM tbl_tags WHERE tag_no = :tn LIMIT 1");
        $chk->execute([':tn' => $tag_no]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Tag number already exists']);
            exit;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO tbl_tags
        (tag_no, from_branch, to_branch, total_count, status, created_by, json_data, remarks, created_at)
        VALUES
        (:tag_no, :from_branch, :to_branch, 0, 'packed', :created_by, '[]', :remarks, NOW())");

    $stmt->execute([
        ':tag_no'      => $tag_no,
        ':from_branch' => $from_branch,
        ':to_branch'   => $to_branch,
        ':created_by'  => $user_id,
        ':remarks'     => $remarks ?: null,
    ]);

    $tag_id = (int)$pdo->lastInsertId();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Tag created successfully',
        'data'    => [
            'tag_id' => $tag_id,
            'tag_no' => $tag_no,
            'status' => 'packed'
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
