<?php
/**
 * In-scan – Bulk Update
 * Location: /apps-api/inscan/update.php
 * Params: tag_id, user_id, scans (JSON array of {awb_no, status, remarks})
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
$user_id = (int)($req['user_id'] ?? 1);
$scans   = $req['scans'] ?? []; // Expected format: [{"awb_no": "...", "status": "verified", "remarks": "..."}]

if (is_string($scans)) {
    $scans = json_decode($scans, true) ?? [];
}

if ($tag_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'tag_id is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch current tag data
    $tagStmt = $pdo->prepare("SELECT * FROM tbl_tags WHERE id = :id FOR UPDATE");
    $tagStmt->execute([':id' => $tag_id]);
    $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $entries = json_decode($tag['json_data'] ?: '[]', true);
    $awbToEntryIdx = [];
    foreach ($entries as $idx => $entry) {
        $awbToEntryIdx[$entry['awb_no']] = $idx;
    }

    $verifiedAtLeastOne = false;
    $updatedAwbs = [];

    foreach ($scans as $scan) {
        $awb = trim($scan['awb_no'] ?? '');
        $status = trim($scan['status'] ?? 'verified');
        $remarks = trim($scan['remarks'] ?? '');

        if ($awb === '' || !isset($awbToEntryIdx[$awb])) continue;

        $idx = $awbToEntryIdx[$awb];
        $entries[$idx]['status'] = $status;
        $entries[$idx]['remarks'] = $remarks;
        $entries[$idx]['updated_at'] = date('Y-m-d H:i:s');
        $entries[$idx]['updated_by'] = $user_id;

        $updatedAwbs[] = $awb;

        // Recording tracking for "verified" items (mirroring whms-tag-verify logic)
        if (strtolower($status) === 'verified') {
            $verifiedAtLeastOne = true;
            // Tracking logic (simplified based on update_scan.php)
            recordTrackingForVerification($pdo, $tag, $awb, $remarks, $user_id);
        }
    }

    // Recalculate tag status
    $totalCount = count($entries);
    $verifiedCount = 0;
    $holdCount = 0;
    
    foreach ($entries as $entry) {
        $st = strtolower($entry['status'] ?? '');
        if ($st === 'verified') $verifiedCount++;
        elseif ($st === 'hold') $holdCount++;
    }

    // PER USER REQUEST: "is total & scanned mismatch need status is hold"
    // Also handling partially_verified / fully_verified logic
    if ($verifiedCount === $totalCount && $totalCount > 0) {
        $tagStatus = 'fully_verified';
    } elseif ($verifiedCount > 0 || $holdCount > 0) {
        // If there's a mismatch or any hold, and we aren't fully verified
        // User said: "is total & scanned mismatch need status is hold"
        // I'll set to 'hold' if there's any pending or mismatch, but usually 'partially_verified' is used.
        // Let's follow the user's explicit rule: mismatch -> hold.
        $tagStatus = ($verifiedCount < $totalCount) ? 'hold' : 'partially_verified';
    } else {
        $tagStatus = $tag['status']; // Keep existing
    }

    // Update tag
    $updStmt = $pdo->prepare("UPDATE tbl_tags SET json_data = :json, status = :status, verified_by = :uid, verified_at = NOW() WHERE id = :id");
    $updStmt->execute([
        ':json' => json_encode($entries),
        ':status' => $tagStatus,
        ':uid' => $user_id,
        ':id' => $tag_id
    ]);

    // Manifest logic (if fully verified)
    if ($tagStatus === 'fully_verified') {
        updateManifestStatusIfAllTagsVerified($pdo, $tag['tag_no']);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Tag updated successfully',
        'data' => [
            'tag_id' => $tag_id,
            'tag_status' => $tagStatus,
            'total_count' => $totalCount,
            'verified_count' => $verifiedCount,
            'hold_count' => $holdCount,
            'updated_awbs' => $updatedAwbs
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Simplified tracking record based on whms-tag-verify/api/tag/update_scan.php
 */
function recordTrackingForVerification($pdo, $tag, $awb, $remarks, $user_id) {
    // This function mimics the tracking insertion/update from api/tag/update_scan.php
    // It's abbreviated here for the API but covers the core logic.
    
    $now = date('Y-m-d H:i:s');
    
    // Resolve location
    $scanLocation = "";
    if ($tag['to_branch']) {
        $bStmt = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id = ?");
        $bStmt->execute([$tag['to_branch']]);
        $scanLocation = $bStmt->fetchColumn() ?: "Destination";
    }

    // Resolve booking
    $bkStmt = $pdo->prepare("SELECT id, waybill_no, last_status FROM tbl_bookings WHERE waybill_no = :awb LIMIT 1");
    $bkStmt->execute([':awb' => $awb]);
    $bkRow = $bkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$bkRow) {
        $pkgStmt = $pdo->prepare("SELECT bp.booking_id, b.waybill_no, b.last_status FROM tbl_booking_packages bp JOIN tbl_bookings b ON b.id = bp.booking_id WHERE bp.awb_no = :awb LIMIT 1");
        $pkgStmt->execute([':awb' => $awb]);
        $bkRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($bkRow) {
        $bookingId = $bkRow['id'] ?? $bkRow['booking_id'];
        $waybillNo = $bkRow['waybill_no'];
        
        $exStmt = $pdo->prepare("SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1");
        $exStmt->execute([':wn' => $waybillNo]);
        $exTrack = $exStmt->fetch(PDO::FETCH_ASSOC);

        $history = [];
        if ($exTrack && !empty($exTrack['raw_response'])) {
            $decoded = json_decode($exTrack['raw_response'], true);
            $history = $decoded['scan_details_history'] ?? (isset($decoded['scan_details']) ? [$decoded['scan_details']] : []);
        }

        $newScan = [
            'status' => 'Received',
            'datetime' => $now,
            'remarks' => $remarks ?: 'Verified at destination',
            'location' => $scanLocation,
            'type' => 'Tag Verify'
        ];
        $history[] = $newScan;

        $rawData = json_encode([
            'awb_no' => $waybillNo,
            'tag_id' => $tag['id'],
            'tag_no' => $tag['tag_no'],
            'current_status' => 'Received',
            'scan_details' => $newScan,
            'scan_details_history' => $history
        ]);

        if ($exTrack) {
            $pdo->prepare("UPDATE tbl_tracking SET scan_type='Received', scan_location=:loc, scan_datetime=:dt, status_code='Received', remarks=:rem, raw_response=:raw WHERE id=:id")
                ->execute([':loc' => $scanLocation, ':dt' => $now, ':rem' => $newScan['remarks'], ':raw' => $rawData, ':id' => $exTrack['id']]);
        } else {
            $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, 'Received', :loc, :dt, 'Received', :rem, :raw)")
                ->execute([':bid' => $bookingId, ':wn' => $waybillNo, ':loc' => $scanLocation, ':dt' => $now, ':rem' => $newScan['remarks'], ':raw' => $rawData]);
        }

        // Update booking status
        $pdo->prepare("UPDATE tbl_bookings SET last_status = 'Received', updated_at = NOW() WHERE id = :id AND last_status NOT IN ('Out for Delivery', 'Delivered')")
            ->execute([':id' => $bookingId]);
    }
}

/**
 * Manifest logic from update_scan.php
 */
function updateManifestStatusIfAllTagsVerified($pdo, $curTagNo) {
    $mfStmt = $pdo->prepare("SELECT id, tag_no FROM tbl_manifest WHERE FIND_IN_SET(:tn, tag_no) > 0");
    $mfStmt->execute([':tn' => $curTagNo]);
    $manifests = $mfStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($manifests as $mf) {
        $mfTagNos = array_filter(array_map('trim', explode(',', $mf['tag_no'])));
        if (empty($mfTagNos)) continue;

        $ph = implode(',', array_fill(0, count($mfTagNos), '?'));
        $tsStmt = $pdo->prepare("SELECT status FROM tbl_tags WHERE tag_no IN ($ph)");
        $tsStmt->execute(array_values($mfTagNos));
        $tagStatuses = $tsStmt->fetchAll(PDO::FETCH_COLUMN);

        $notDone = array_filter($tagStatuses, fn($s) => $s !== 'fully_verified');
        if (empty($notDone) && !empty($tagStatuses)) {
            $pdo->prepare("UPDATE tbl_manifest SET status = 'Received', updated_at = NOW() WHERE id = :id")
                ->execute([':id' => $mf['id']]);
        }
    }
}
