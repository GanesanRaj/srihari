<?php
/**
 * Manifest API – Read Single Manifest
 * Location: /apps-api/manifest/readone.php
 * Method: GET
 * Params:
 *   id          (required if no manifest_no)
 *   manifest_no (required if no id)
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
    $id          = (int)($req['id'] ?? $req['manifest_id'] ?? 0);
    $manifest_no = trim($req['manifest_no'] ?? '');

    if ($id <= 0 && $manifest_no === '') {
        echo json_encode(['status' => 'error', 'message' => 'id or manifest_no is required']);
        exit;
    }

    $sql = "SELECT m.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name,
                br_from.branch_name AS from_branch_name,
                br_to.branch_name   AS to_branch_name
            FROM tbl_manifest m
            LEFT JOIN tbl_user u1      ON u1.user_id  = m.created_by
            LEFT JOIN tbl_user u2      ON u2.user_id  = m.updated_by
            LEFT JOIN tbl_branch br_from ON br_from.id = m.from_branch
            LEFT JOIN tbl_branch br_to   ON br_to.id   = m.to_branch
            WHERE " . ($id > 0 ? "m.id = :val" : "m.manifest_no = :val") . " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':val' => $id > 0 ? $id : $manifest_no]);
    $manifest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$manifest) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest not found']);
        exit;
    }

    $manifest['json_data'] = json_decode($manifest['json_data'] ?: '[]', true);

    $entries = is_array($manifest['json_data']) ? $manifest['json_data'] : [];

    // Bag count = unique tag_no or individual AWB
    $bagKeys = [];
    $tagNos  = [];
    foreach ($entries as $e) {
        $key = !empty($e['tag_no']) ? trim($e['tag_no']) : ($e['awb_no'] ?? '');
        if ($key !== '') $bagKeys[$key] = true;
        if (!empty($e['tag_no'])) $tagNos[trim($e['tag_no'])] = true;
    }
    $manifest['bag_count']      = count($bagKeys);
    $manifest['tags_count']     = count($tagNos);
    $manifest['shipment_count'] = count($entries);

    // Totals from tbl_bookings
    $manifest['total_box'] = 0;
    $manifest['weight']    = 0.0;
    $bookingIds = array_unique(array_filter(array_column($entries, 'booking_id')));
    if (!empty($bookingIds)) {
        $ph      = implode(',', array_fill(0, count($bookingIds), '?'));
        $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS tot_qty, COALESCE(SUM(weight),0)/1000 AS tot_kg FROM tbl_bookings WHERE id IN ($ph)");
        $sumStmt->execute(array_values($bookingIds));
        $row = $sumStmt->fetch(PDO::FETCH_ASSOC);
        $manifest['total_box'] = (int)($row['tot_qty'] ?? 0);
        $manifest['weight']    = round((float)($row['tot_kg'] ?? 0), 2);
    }

    echo json_encode(['status' => 'success', 'data' => $manifest]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
