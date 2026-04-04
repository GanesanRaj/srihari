<?php
/**
 * Dashboard API – Counts
 * Location: /apps-api/dashboard/counts.php
 * Method: GET
 * Params:
 *   user_id   (required) – logged in user
 *   branch_id (required) – user's branch
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

$user_id   = (int)($req['user_id']   ?? 0);
$branch_id = (int)($req['branch_id'] ?? 0);

if ($branch_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'branch_id is required']);
    exit;
}

try {
    $today = date('Y-m-d');

    // 1. Waiting Pickup – bookings with no status or Pending at this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings b
        LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
        WHERE COALESCE(b.branch_id, p.branch_id) = :bid
        AND (b.last_status IS NULL OR b.last_status = '' OR b.last_status = 'Pending')");
    $stmt->execute([':bid' => $branch_id]);
    $waiting_pickup = (int)$stmt->fetchColumn();

    // 2. Today's Bookings – created today at this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings b
        LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
        WHERE COALESCE(b.branch_id, p.branch_id) = :bid
        AND DATE(b.created_at) = :today");
    $stmt->execute([':bid' => $branch_id, ':today' => $today]);
    $today_bookings = (int)$stmt->fetchColumn();

    // 3. Upcoming Tags – tags incoming to this branch not yet fully verified
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_tags
        WHERE to_branch = :bid AND status != 'fully_verified'");
    $stmt->execute([':bid' => $branch_id]);
    $upcoming_tags = (int)$stmt->fetchColumn();

    // 4. Tags Created – tags dispatched from this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_tags WHERE from_branch = :bid");
    $stmt->execute([':bid' => $branch_id]);
    $tags_created = (int)$stmt->fetchColumn();

    // 5. Tags Verified – fully verified at this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_tags
        WHERE to_branch = :bid AND status = 'fully_verified'");
    $stmt->execute([':bid' => $branch_id]);
    $tags_verified = (int)$stmt->fetchColumn();

    // 6. Pending Delivery – Received at this branch, not yet delivered
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings b
        WHERE b.last_status = 'Received'
        AND b.id IN (
            SELECT DISTINCT booking_id FROM tbl_tracking
            WHERE scan_type = 'Received'
        )");
    $stmt->execute();
    $pending_delivery = (int)$stmt->fetchColumn();

    // 7. Today Delivered – delivered today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings
        WHERE last_status = 'Delivered' AND DATE(updated_at) = :today");
    $stmt->execute([':today' => $today]);
    $today_delivered = (int)$stmt->fetchColumn();

    // 8. Today Picked Up – scan type PICKUP today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT booking_id) FROM tbl_tracking
        WHERE scan_type = 'PICKUP' AND DATE(scan_datetime) = :today");
    $stmt->execute([':today' => $today]);
    $today_picked_up = (int)$stmt->fetchColumn();

    // 9. Active Manifests – draft/dispatched manifests from this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_manifest
        WHERE from_branch = :bid AND status IN ('draft', 'dispatched')");
    $stmt->execute([':bid' => $branch_id]);
    $active_manifests = (int)$stmt->fetchColumn();

    // 10. Incoming Manifests – dispatched manifests to this branch not yet received
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_manifest
        WHERE to_branch = :bid AND status = 'dispatched'");
    $stmt->execute([':bid' => $branch_id]);
    $incoming_manifests = (int)$stmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'waiting_pickup'    => $waiting_pickup,
            'today_bookings'    => $today_bookings,
            'upcoming_tags'     => $upcoming_tags,
            'tags_created'      => $tags_created,
            'tags_verified'     => $tags_verified,
            'pending_delivery'  => $pending_delivery,
            'today_delivered'   => $today_delivered,
            'today_picked_up'   => $today_picked_up,
            'active_manifests'  => $active_manifests,
            'incoming_manifests'=> $incoming_manifests,
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
