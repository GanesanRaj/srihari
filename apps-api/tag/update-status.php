<?php
/**
 * Tag API – Update Tag Status
 * Location: /apps-api/tag/update-status.php
 * Params (POST): tag_id, status (packed, hold, verified), user_id (opt)
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

$tag_id  = (int)($req['tag_id'] ?? 0);
$status  = trim($req['status'] ?? '');
$user_id = (int)($req['user_id'] ?? 1);

if ($tag_id <= 0 || $status === '') {
    echo json_encode(['status' => 'error', 'message' => 'tag_id and status are required']);
    exit;
}

// Map 'verified' to 'fully_verified' internal status if needed
$status_map = [
    'packed' => 'packed',
    'hold' => 'hold',
    'verified' => 'fully_verified'
];

$internal_status = $status_map[$status] ?? $status;

try {
    $stmt = $pdo->prepare("UPDATE tbl_tags SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $internal_status, ':id' => $tag_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Tag status updated to ' . $status,
        'data' => [
            'tag_id' => $tag_id,
            'status' => $internal_status
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
