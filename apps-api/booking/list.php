<?php
/**
 * Booking List API (No Session)
 * Location: /apps-api/booking/list.php
 * Filters: user_id, branch_id, courier_id, status, search. No pickup_point (app list is not pickup-point-based).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

// Accept request parameters (POST or GET)
$user_id = $_REQUEST['user_id'] ?? '';
$branch_id = $_REQUEST['branch_id'] ?? '';
$courier_id = $_REQUEST['courier_id'] ?? '';
$status = $_REQUEST['status'] ?? '';
$search = $_REQUEST['search'] ?? '';
$awb_no = trim($_REQUEST['awb_no'] ?? '');
$start = intval($_REQUEST['start'] ?? 0);
$limit = intval($_REQUEST['limit'] ?? 10);

try {
    // Branch from tbl_bookings.branch_id; creator from tbl_user. Weight from tbl_booking_packages when present.
    $sql = "SELECT b.*, cp.partner_name as courier_name, u.username as creator_name, br.branch_name,
            (SELECT COALESCE(SUM(p.actual_weight), 0) FROM tbl_booking_packages p WHERE p.booking_id = b.id) AS package_actual_weight,
            (SELECT COALESCE(SUM(p.charged_weight), 0) FROM tbl_booking_packages p WHERE p.booking_id = b.id) AS package_charged_weight
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner cp ON b.courier_id = cp.id
            LEFT JOIN tbl_user u ON b.created_by = u.id
            LEFT JOIN tbl_branch br ON b.branch_id = br.id
            WHERE 1=1";
    
    $countSql = "SELECT COUNT(*) as total FROM tbl_bookings b WHERE 1=1";
    $params = [];

    // Filter by User/Creator (created_by is user id from tbl_user)
    if (!empty($user_id)) {
        $sql .= " AND b.created_by = :user_id";
        $countSql .= " AND b.created_by = :user_id";
        $params[':user_id'] = $user_id;
    }

    // Filter by Branch (from tbl_bookings.branch_id)
    if (!empty($branch_id)) {
        $sql .= " AND b.branch_id = :branch_id";
        $countSql .= " AND b.branch_id = :branch_id";
        $params[':branch_id'] = $branch_id;
    }

    if (!empty($courier_id)) {
        $sql .= " AND b.courier_id = :courier_id";
        $countSql .= " AND b.courier_id = :courier_id";
        $params[':courier_id'] = $courier_id;
    }

    if (!empty($status)) {
        $sql .= " AND b.last_status = :status";
        $countSql .= " AND b.last_status = :status";
        $params[':status'] = $status;
    }

    if (!empty($awb_no)) {
        $awbCondition = " AND (b.waybill_no = :awb_no OR b.package_details LIKE :awb_no_like)";
        $sql .= $awbCondition;
        $countSql .= $awbCondition;
        $params[':awb_no'] = $awb_no;
        $params[':awb_no_like'] = '%"' . $awb_no . '"%';
    }

    if (!empty($search)) {
        $searchCondition = " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR b.consignee_phone LIKE :search OR b.consignee_city LIKE :search)";
        $sql .= $searchCondition;
        $countSql .= $searchCondition;
        $params[':search'] = "%$search%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalFiltered = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY b.created_at DESC LIMIT :start, :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        if (!empty($row['package_details'])) {
            $row['package_details_array'] = json_decode($row['package_details'], true);
        }
        // Weight from tbl_booking_packages (kg); fallback to tbl_bookings.weight (stored in grams → kg)
        $pkgActual = (float)($row['package_actual_weight'] ?? 0);
        $pkgCharged = (float)($row['package_charged_weight'] ?? 0);
        if ($pkgActual > 0 || $pkgCharged > 0) {
            $row['weight'] = $pkgActual > 0 ? $pkgActual : $pkgCharged;
        } else {
            $row['weight'] = isset($row['weight']) && $row['weight'] !== '' ? (float)$row['weight'] / 1000 : 0;
        }
        unset($row['package_actual_weight'], $row['package_charged_weight']);
    }

    echo json_encode([
        'status' => 'success',
        'total_records' => $totalFiltered,
        'start' => $start,
        'limit' => $limit,
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
