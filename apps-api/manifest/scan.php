<?php
/**
 * Manifest API – Scan TAG into Manifest
 * Location: /apps-api/manifest/scan.php
 * Method: GET | POST
 * Params:
 *   manifest_id (required)
 *   scan_value  (required) – TAG number only (TAG-YYYYMMDD-NNN)
 *   user_id     (opt)
 *   clear       (opt) – "1" to clear all entries before scanning
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
$scan_value  = trim($req['scan_value']   ?? '');
$user_id     = (int)($req['user_id']     ?? 0);
$clear       = ($req['clear']            ?? '0') === '1';

if ($manifest_id <= 0 || $scan_value === '') {
    echo json_encode(['status' => 'error', 'message' => 'manifest_id and scan_value are required']);
    exit;
}

try {
    // Fetch manifest
    $mStmt = $pdo->prepare("SELECT * FROM tbl_manifest WHERE id = :id LIMIT 1");
    $mStmt->execute([':id' => $manifest_id]);
    $manifest = $mStmt->fetch(PDO::FETCH_ASSOC);

    if (!$manifest) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest not found']);
        exit;
    }

    // Enforce TAG-only scan
    if (!preg_match('/^TAG-/i', $scan_value)) {
        echo json_encode(['status' => 'error', 'message' => 'Only TAG numbers are allowed. scan_value must start with TAG-']);
        exit;
    }

    $existing     = $clear ? [] : json_decode($manifest['json_data'] ?: '[]', true);
    $existingAwbs = array_column($existing, 'awb_no');
    $addedEntries = [];
    $now          = date('Y-m-d H:i:s');
    $scannedBy    = $user_id > 0 ? (string)$user_id : 'system';

    // TAG scan: expand all shipments from the tag
    $tagStmt = $pdo->prepare("SELECT * FROM tbl_tags WHERE tag_no = :tag_no LIMIT 1");
    $tagStmt->execute([':tag_no' => $scan_value]);
    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        echo json_encode(['status' => 'error', 'message' => 'Tag not found: ' . $scan_value]);
        exit;
    }

    $tagEntries = json_decode($tag['json_data'] ?: '[]', true);
    if (empty($tagEntries)) {
        echo json_encode(['status' => 'error', 'message' => 'Tag has no shipments: ' . $scan_value]);
        exit;
    }

    foreach ($tagEntries as $te) {
        $awb = $te['awb_no'];
        if (in_array($awb, $existingAwbs)) continue;

        $entry = [
            'awb_no'         => $awb,
            'booking_id'     => $te['booking_id']     ?? null,
            'consignee_name' => $te['consignee_name'] ?? '',
            'consignee_city' => $te['consignee_city'] ?? '',
            'tag_no'         => $tag['tag_no'],
            'scanned_at'     => $now,
            'scanned_by'     => $scannedBy,
        ];
        $existing[]     = $entry;
        $existingAwbs[] = $awb;
        $addedEntries[] = $entry;
    }

    if (empty($addedEntries)) {
        echo json_encode(['status' => 'error', 'message' => 'All shipments from ' . $scan_value . ' are already in this manifest']);
        exit;
    }

    // Recalculate totals
    $total      = count($existing);
    $tagNos     = array_unique(array_filter(array_column($existing, 'tag_no')));
    $tagNoStr   = implode(',', $tagNos);
    $bagKeys    = [];
    foreach ($existing as $e) {
        $key = !empty($e['tag_no']) ? trim($e['tag_no']) : ($e['awb_no'] ?? '');
        if ($key !== '') $bagKeys[$key] = true;
    }
    $bagCount   = count($bagKeys);
    $totalBox   = 0;
    $weightKg   = 0.0;
    $bookingIds = array_unique(array_filter(array_column($existing, 'booking_id')));
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
            ':json'      => json_encode($existing),
            ':cnt'       => $total,
            ':tag_no'    => $tagNoStr ?: null,
            ':bag_count' => $bagCount,
            ':total_box' => $totalBox,
            ':weight'    => $weightKg,
            ':id'        => $manifest_id,
        ]);

    // Resolve branch names for tracking location
    $scanLocation = null;
    $branchIds = array_filter([(int)$manifest['from_branch'], (int)$manifest['to_branch']]);
    if (!empty($branchIds)) {
        $bph   = implode(',', array_fill(0, count($branchIds), '?'));
        $bStmt = $pdo->prepare("SELECT id, branch_name FROM tbl_branch WHERE id IN ($bph)");
        $bStmt->execute(array_values($branchIds));
        $branchMap = [];
        foreach ($bStmt->fetchAll(PDO::FETCH_ASSOC) as $br) {
            $branchMap[$br['id']] = $br['branch_name'];
        }
        $parts = array_filter([
            $branchMap[(int)$manifest['from_branch']] ?? null,
            $branchMap[(int)$manifest['to_branch']]   ?? null,
        ]);
        if (!empty($parts)) $scanLocation = implode(' → ', $parts);
    }

    // Update tracking & booking status for newly added entries
    foreach ($addedEntries as $ae) {
        $aeBookingId = $ae['booking_id'] ?? null;
        $aeAwb       = $ae['awb_no']     ?? '';
        if (!$aeBookingId) continue;

        $wbStmt = $pdo->prepare("SELECT waybill_no FROM tbl_bookings WHERE id = :id LIMIT 1");
        $wbStmt->execute([':id' => $aeBookingId]);
        $waybillNo = $wbStmt->fetchColumn() ?: $aeAwb;

        $newScan = [
            'status'   => 'Manifested',
            'datetime' => $now,
            'remarks'  => 'Added to manifest',
            'location' => $scanLocation,
            'type'     => 'Manifest'
        ];

        $exStmt = $pdo->prepare("SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1");
        $exStmt->execute([':wn' => $waybillNo]);
        $exTrack = $exStmt->fetch(PDO::FETCH_ASSOC);

        $history = [];
        if ($exTrack && !empty($exTrack['raw_response'])) {
            $decoded = json_decode($exTrack['raw_response'], true);
            if (isset($decoded['scan_details_history'])) $history = $decoded['scan_details_history'];
            elseif (isset($decoded['scan_details']))     $history = [$decoded['scan_details']];
        }
        $history[] = $newScan;

        $rawData = json_encode([
            'awb_no'               => $waybillNo,
            'manifest_id'          => $manifest_id,
            'manifest_ref'         => $manifest['manifest_no'] ?? null,
            'scan_location'        => $scanLocation,
            'current_status'       => 'Manifested',
            'scan_details'         => $newScan,
            'scan_details_history' => $history,
        ]);

        if ($exTrack) {
            $pdo->prepare("UPDATE tbl_tracking SET scan_type=:st, scan_location=:loc, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id")
                ->execute([':st'=>'Manifested',':loc'=>$scanLocation,':dt'=>$now,':sc'=>'Manifested',':rem'=>'Added to manifest',':raw'=>$rawData,':id'=>$exTrack['id']]);
        } else {
            $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid,:wn,:st,:loc,:dt,:sc,:rem,:raw)")
                ->execute([':bid'=>$aeBookingId,':wn'=>$waybillNo,':st'=>'Manifested',':loc'=>$scanLocation,':dt'=>$now,':sc'=>'Manifested',':rem'=>'Added to manifest',':raw'=>$rawData]);
        }

        $pdo->prepare("UPDATE tbl_bookings SET last_status = 'Manifested', updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $aeBookingId]);
    }

    echo json_encode([
        'status'          => 'success',
        'message'         => count($addedEntries) . ' shipment(s) added',
        'entries'         => $addedEntries,
        'total_count'     => $total,
        'bag_count'       => $bagCount,
        'total_box'       => $totalBox,
        'weight'          => $weightKg,
        'manifest_status' => $manifest['status'] ?: 'draft',
        'cleared'         => $clear,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
