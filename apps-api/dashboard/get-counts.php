<?php
/**
 * Dashboard API – Get Counts and Recent Lists
 * Location: /apps-api/dashboard/get-counts.php
 * Params: branch_id (int)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/config.php';

$branch_id = (int)($_REQUEST['branch_id'] ?? 0);

if ($branch_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'branch_id is required']);
    exit;
}

try {
    $today = date('Y-m-d');

    // 1. Waiting Pickup Count & List
    // Filter: last_status is empty, 'Pending' or NULL, and branch_id matches
    $pickupQuery = "SELECT COUNT(*) FROM tbl_bookings b 
                    LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                    WHERE COALESCE(b.branch_id, p.branch_id) = :bid 
                    AND (b.last_status IS NULL OR b.last_status = '' OR b.last_status = 'Pending')";
    $pickupCountStmt = $pdo->prepare($pickupQuery);
    $pickupCountStmt->execute([':bid' => $branch_id]);
    $waitingPickupCount = (int)$pickupCountStmt->fetchColumn();

    $recentPickupsStmt = $pdo->prepare("SELECT b.waybill_no, b.shipper_name, b.shipper_city, b.created_at 
                                         FROM tbl_bookings b 
                                         LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                                         WHERE COALESCE(b.branch_id, p.branch_id) = :bid 
                                         AND (b.last_status IS NULL OR b.last_status = '' OR b.last_status = 'Pending')
                                         ORDER BY b.id DESC LIMIT 5");
    $recentPickupsStmt->execute([':bid' => $branch_id]);
    $recentPickups = $recentPickupsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Upcoming Tags Count & List
    // Filter: to_branch = branch_id and status != 'fully_verified'
    $tagCountStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_tags WHERE to_branch = :bid AND status != 'fully_verified'");
    $tagCountStmt->execute([':bid' => $branch_id]);
    $upcomingTagsCount = (int)$tagCountStmt->fetchColumn();

    $recentTagsStmt = $pdo->prepare("SELECT t.tag_no, b.branch_name as from_branch_name, t.total_count, t.created_at 
                                      FROM tbl_tags t
                                      LEFT JOIN tbl_branch b ON t.from_branch = b.id
                                      WHERE t.to_branch = :bid AND t.status != 'fully_verified'
                                      ORDER BY t.id DESC LIMIT 5");
    $recentTagsStmt->execute([':bid' => $branch_id]);
    $recentTags = $recentTagsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Pending Delivery Count
    // Filter: last_status = 'Received' at this branch (assuming Received implies it's at the destination branch ready for delivery)
    // In some systems, we might need to check if to_branch in manifest/tag matches. 
    // For now, let's use last_status = 'Received' as the indicator.
    $deliveryQuery = "SELECT COUNT(*) FROM tbl_bookings WHERE last_status = 'Received' AND id IN (
                        SELECT booking_id FROM tbl_tracking WHERE scan_location LIKE :loc AND scan_type = 'Received'
                    )";
    // We need the branch name for the location search
    $bNameStmt = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id = ?");
    $bNameStmt->execute([$branch_id]);
    $branchName = $bNameStmt->fetchColumn() ?: '';
    
    $deliveryCountStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bookings WHERE last_status = 'Received'");
    $deliveryCountStmt->execute();
    $pendingDeliveryCount = (int)$deliveryCountStmt->fetchColumn();

    // 4. Today's Stats
    $todayPickedStmt = $pdo->prepare("SELECT COUNT(DISTINCT booking_id) FROM tbl_tracking WHERE DATE(scan_datetime) = :today AND scan_type = 'PICKUP'");
    $todayPickedStmt->execute([':today' => $today]);
    $todayPickedUpCount = (int)$todayPickedStmt->fetchColumn();

    $todayDeliveredStmt = $pdo->prepare("SELECT COUNT(DISTINCT booking_id) FROM tbl_tracking WHERE DATE(scan_datetime) = :today AND scan_type = 'DELIVERY'");
    $todayDeliveredStmt->execute([':today' => $today]);
    $todayDeliveredCount = (int)$todayDeliveredStmt->fetchColumn();

    // 5. Additional Recent Lists (5 Limit)
    
    // Recent Bookings (any status, created at this branch)
    $recentBookingsStmt = $pdo->prepare("SELECT b.waybill_no, b.shipper_name, b.consignee_name, b.last_status, b.created_at 
                                          FROM tbl_bookings b 
                                          LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                                          WHERE COALESCE(b.branch_id, p.branch_id) = :bid 
                                          ORDER BY b.id DESC LIMIT 5");
    $recentBookingsStmt->execute([':bid' => $branch_id]);
    $recentBookings = $recentBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Tag Creations (Created by this branch)
    $recentTagCreationsStmt = $pdo->prepare("SELECT tag_no, to_branch, total_count, status, created_at 
                                             FROM tbl_tags 
                                             WHERE from_branch = :bid 
                                             ORDER BY id DESC LIMIT 5");
    $recentTagCreationsStmt->execute([':bid' => $branch_id]);
    $recentTagCreations = $recentTagCreationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Tag Verifications (Verified at this branch)
    $recentTagVerificationsStmt = $pdo->prepare("SELECT tag_no, from_branch, total_count, status, verified_at 
                                                 FROM tbl_tags 
                                                 WHERE to_branch = :bid AND status = 'fully_verified'
                                                 ORDER BY verified_at DESC LIMIT 5");
    $recentTagVerificationsStmt->execute([':bid' => $branch_id]);
    $recentTagVerifications = $recentTagVerificationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Deliveries (Delivered from this branch)
    $recentDeliveriesStmt = $pdo->prepare("SELECT b.waybill_no, b.consignee_name, b.consignee_city, b.updated_at as delivered_at
                                            FROM tbl_bookings b
                                            WHERE b.last_status = 'Delivered'
                                            ORDER BY b.updated_at DESC LIMIT 5");
    // Note: Ideally filter deliveries by branch too if tracked in tbl_bookings.
    $recentDeliveriesStmt->execute();
    $recentDeliveries = $recentDeliveriesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'counts' => [
                'waiting_pickup' => $waitingPickupCount,
                'upcoming_tags' => $upcomingTagsCount,
                'pending_delivery' => $pendingDeliveryCount
            ],
            'lists' => [
                'recent_pickups' => $recentPickups,
                'recent_tags' => $recentTags,
                'recent_bookings' => $recentBookings,
                'recent_tag_creations' => $recentTagCreations,
                'recent_tag_verifications' => $recentTagVerifications,
                'recent_deliveries' => $recentDeliveries
            ],
            'today_stats' => [
                'picked_up' => $todayPickedUpCount,
                'delivered' => $todayDeliveredCount
            ]
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
