<?php
/**
 * Manifest API – Submit / Change Status
 * Location: /apps-api/manifest/submit.php
 * Method: GET | POST
 * Params:
 *   manifest_id (required)
 *   status      (required) – dispatched | received
 *   user_id     (opt)
 *
 * Status flow:  draft → dispatched → received
 *
 * On status change, tracking records are inserted/updated for every
 * shipment inside the manifest's json_data.
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
$new_status  = trim($req['status']       ?? '');
$user_id     = (int)($req['user_id']     ?? 0);

if ($manifest_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'manifest_id is required']);
    exit;
}

$allowed_statuses = ['dispatched', 'received'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'status must be one of: ' . implode(', ', $allowed_statuses)]);
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

    $current_status = $manifest['status'] ?? 'draft';

    // Validate status flow: draft → dispatched → received
    $flow = ['draft' => 0, 'dispatched' => 1, 'received' => 2];
    $currentRank = $flow[$current_status] ?? 0;
    $newRank     = $flow[$new_status]     ?? 0;

    if ($newRank <= $currentRank) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Cannot change status from '$current_status' to '$new_status'",
        ]);
        exit;
    }

    $entries = json_decode($manifest['json_data'] ?: '[]', true);
    if (empty($entries)) {
        echo json_encode(['status' => 'error', 'message' => 'Manifest has no shipments']);
        exit;
    }

    $now = date('Y-m-d H:i:s');

    // Map status → tracking label
    $statusLabel = [
        'dispatched' => 'Dispatched',
        'received'   => 'Received',
    ];
    $trackStatus = $statusLabel[$new_status];

    $remarks = [
        'dispatched' => 'Manifest dispatched',
        'received'   => 'Manifest received at destination',
    ];
    $trackRemarks = $remarks[$new_status];

    // Resolve branch names for scan location
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

    // Update manifest status
    $updFields = ['status = :status', 'updated_at = NOW()'];
    $updParams = [':status' => $new_status, ':id' => $manifest_id];

    if ($new_status === 'dispatched') {
        $updFields[] = 'dispatched_at = :ts';
        $updParams[':ts'] = $now;
        if ($user_id > 0) { $updFields[] = 'dispatched_by = :uid'; $updParams[':uid'] = $user_id; }
    }
    if ($new_status === 'received') {
        $updFields[] = 'received_at = :ts';
        $updParams[':ts'] = $now;
        if ($user_id > 0) { $updFields[] = 'received_by = :uid'; $updParams[':uid'] = $user_id; }
    }
    if ($user_id > 0 && !isset($updParams[':uid'])) {
        $updFields[] = 'updated_by = :user_id';
        $updParams[':user_id'] = $user_id;
    }

    $pdo->prepare("UPDATE tbl_manifest SET " . implode(', ', $updFields) . " WHERE id = :id")
        ->execute($updParams);

    // Insert / update tracking for every shipment in the manifest
    $tracked = 0;
    $bookingIds = array_unique(array_filter(array_column($entries, 'booking_id')));

    foreach ($entries as $e) {
        $aeBookingId = $e['booking_id'] ?? null;
        $aeAwb       = $e['awb_no']     ?? '';
        if (!$aeBookingId) continue;

        // Get waybill_no
        $wbStmt = $pdo->prepare("SELECT waybill_no FROM tbl_bookings WHERE id = :id LIMIT 1");
        $wbStmt->execute([':id' => $aeBookingId]);
        $waybillNo = $wbStmt->fetchColumn() ?: $aeAwb;

        $newScan = [
            'status'   => $trackStatus,
            'datetime' => $now,
            'remarks'  => $trackRemarks,
            'location' => $scanLocation,
            'type'     => 'Manifest',
        ];

        // Fetch existing tracking to preserve history
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
            'current_status'       => $trackStatus,
            'scan_details'         => $newScan,
            'scan_details_history' => $history,
        ]);

        if ($exTrack) {
            $pdo->prepare("UPDATE tbl_tracking SET scan_type=:st, scan_location=:loc, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id")
                ->execute([':st'=>$trackStatus,':loc'=>$scanLocation,':dt'=>$now,':sc'=>$trackStatus,':rem'=>$trackRemarks,':raw'=>$rawData,':id'=>$exTrack['id']]);
        } else {
            $pdo->prepare("INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid,:wn,:st,:loc,:dt,:sc,:rem,:raw)")
                ->execute([':bid'=>$aeBookingId,':wn'=>$waybillNo,':st'=>$trackStatus,':loc'=>$scanLocation,':dt'=>$now,':sc'=>$trackStatus,':rem'=>$trackRemarks,':raw'=>$rawData]);
        }

        // Update booking last_status
        $pdo->prepare("UPDATE tbl_bookings SET last_status = :ls, updated_at = NOW() WHERE id = :id")
            ->execute([':ls' => $trackStatus, ':id' => $aeBookingId]);

        $tracked++;
    }

    echo json_encode([
        'status'          => 'success',
        'message'         => 'Manifest status changed to ' . $new_status,
        'manifest_id'     => $manifest_id,
        'manifest_no'     => $manifest['manifest_no'] ?? null,
        'previous_status' => $current_status,
        'new_status'      => $new_status,
        'tracked_count'   => $tracked,
        'scan_location'   => $scanLocation,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
