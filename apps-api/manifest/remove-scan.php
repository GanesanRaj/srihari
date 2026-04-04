<?php
/**
 * Manifest API – Remove AWB or TAG from Manifest
 * Location: /apps-api/manifest/remove-scan.php
 * Method: GET | POST
 * Params:
 *   manifest_id (required)
 *   awb_no      (opt) – remove single AWB
 *   tag_no      (opt) – remove all AWBs belonging to this tag
 *   (one of awb_no or tag_no is required)
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
$awb_no      = trim($req['awb_no'] ?? '');
$tag_no      = trim($req['tag_no'] ?? '');

if ($manifest_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'manifest_id is required']);
    exit;
}
if ($awb_no === '' && $tag_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'awb_no or tag_no is required']);
    exit;
}

try {
    $mStmt = $pdo->prepare("SELECT * FROM tbl_manifest WHERE id = :id LIMIT 1");
    $mStmt->execute([':id' => $manifest_id]);
    $manifest = $mStmt->fetch(PDO::FETCH_ASSOC);

    if (!$manifest) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest not found']);
        exit;
    }

    $entries = json_decode($manifest['json_data'] ?: '[]', true);
    $removed = 0;

    if ($tag_no !== '') {
        // Remove all AWBs belonging to this tag
        $filtered = array_filter($entries, function($e) use ($tag_no, &$removed) {
            if (($e['tag_no'] ?? '') === $tag_no) { $removed++; return false; }
            return true;
        });
    } else {
        // Remove single AWB
        $filtered = array_filter($entries, function($e) use ($awb_no, &$removed) {
            if ($e['awb_no'] === $awb_no) { $removed++; return false; }
            return true;
        });
    }

    if ($removed === 0) {
        $what = $tag_no !== '' ? "Tag $tag_no" : "AWB $awb_no";
        echo json_encode(['status' => 'error', 'message' => "$what not found in this manifest"]);
        exit;
    }

    $entries  = array_values($filtered);
    $total    = count($entries);
    $tagNos   = array_unique(array_filter(array_column($entries, 'tag_no')));
    $tagNoStr = implode(',', $tagNos);
    $bagKeys  = [];
    foreach ($entries as $e) {
        $key = !empty($e['tag_no']) ? trim($e['tag_no']) : ($e['awb_no'] ?? '');
        if ($key !== '') $bagKeys[$key] = true;
    }
    $bagCount   = count($bagKeys);
    $totalBox   = 0;
    $weightKg   = 0.0;
    $bookingIds = array_unique(array_filter(array_column($entries, 'booking_id')));
    if (!empty($bookingIds)) {
        $ph      = implode(',', array_fill(0, count($bookingIds), '?'));
        $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS tot_qty, COALESCE(SUM(weight),0)/1000 AS tot_kg FROM tbl_bookings WHERE id IN ($ph)");
        $sumStmt->execute(array_values($bookingIds));
        $row      = $sumStmt->fetch(PDO::FETCH_ASSOC);
        $totalBox = (int)($row['tot_qty'] ?? 0);
        $weightKg = round((float)($row['tot_kg'] ?? 0), 2);
    }

    $pdo->prepare("UPDATE tbl_manifest SET json_data = :json, total_count = :cnt, tag_no = :tag_no, bag_count = :bag_count, total_box = :total_box, weight = :weight WHERE id = :id")
        ->execute([
            ':json'      => json_encode($entries),
            ':cnt'       => $total,
            ':tag_no'    => $tagNoStr ?: null,
            ':bag_count' => $bagCount,
            ':total_box' => $totalBox,
            ':weight'    => $weightKg,
            ':id'        => $manifest_id,
        ]);

    echo json_encode([
        'status'      => 'success',
        'message'     => $removed . ' shipment(s) removed',
        'removed'     => $removed,
        'total_count' => $total,
        'bag_count'   => $bagCount,
        'total_box'   => $totalBox,
        'weight'      => $weightKg,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
