<?php
/**
 * In-scan – Get Tag Info
 * Location: /apps-api/inscan/get-tag-info.php
 * Params: tag_no, branch_id
 * Returns: tag details and shipment list with counts.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$tag_no    = trim($_REQUEST['tag_no'] ?? '');
$branch_id = (int)($_REQUEST['branch_id'] ?? 0);

if ($tag_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'tag_no is required']);
    exit;
}

try {
    // Fetch tag details
    $sql = "SELECT t.*, 
                   u1.username AS created_by_name,
                   u2.username AS verified_by_name
            FROM tbl_tags t
            LEFT JOIN tbl_user u1 ON u1.user_id = t.created_by
            LEFT JOIN tbl_user u2 ON u2.user_id = t.verified_by
            WHERE t.tag_no = :tag_no LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tag_no' => $tag_no]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $entries = json_decode($tag['json_data'] ?: '[]', true);
    $totalCount = count($entries);
    $scannedCount = 0;

    foreach ($entries as $entry) {
        if (isset($entry['status']) && strtolower($entry['status']) === 'verified') {
            $scannedCount++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => (int)$tag['id'],
            'tag_no' => $tag['tag_no'],
            'status' => $tag['status'],
            'from_branch' => (int)$tag['from_branch'],
            'to_branch' => (int)$tag['to_branch'],
            'total_count' => $totalCount,
            'scanned_count' => $scannedCount,
            'shipments' => $entries,
            'created_at' => $tag['created_at'],
            'created_by_name' => $tag['created_by_name']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
