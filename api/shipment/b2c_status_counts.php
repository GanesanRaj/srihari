<?php
/**
 * api/shipment/b2c_status_counts.php
 * Returns counts for each status tab for the B2C shipment list.
 * GET params: courier_id, company_id, branch_id, from_date, to_date
 */
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';

$NDR_CODES = "'EOD-74','EOD-15','EOD-104','EOD-43','EOD-86','EOD-11','EOD-69','EOD-6'";
$RESCHEDULE_CODES = "'EOD-777','EOD-21'";

// Base WHERE (joins tbl_branch for company filter)
$baseWhere = " FROM tbl_bookings b LEFT JOIN tbl_branch br ON b.branch_id = br.id WHERE 1=1";
$params = [];

if (!empty($_GET['courier_id'])) {
    $baseWhere .= " AND b.courier_id = :courier_id";
    $params[':courier_id'] = (int)$_GET['courier_id'];
}
if (!empty($_GET['company_id'])) {
    $baseWhere .= " AND br.company_id = :company_id";
    $params[':company_id'] = (int)$_GET['company_id'];
}
if (!empty($_GET['branch_id'])) {
    $baseWhere .= " AND b.branch_id = :branch_id";
    $params[':branch_id'] = (int)$_GET['branch_id'];
}
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $baseWhere .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $_GET['from_date'];
    $params[':to_date']   = $_GET['to_date'];
}

$notCancelled = " AND LOWER(IFNULL(b.last_status,'')) != 'cancelled'";

$tabs = [
    'all'                 => "",
    'soft_date_uploaded'  => $notCancelled,
    'synced_ready_to_ship'=> $notCancelled . " AND b.status_type = 'UD' AND b.add_to_pickup = 1 AND (b.pickup_date IS NULL OR b.pickup_date = '')",
    'ready_for_pickup'    => $notCancelled . " AND b.status_type = 'UD' AND (b.add_to_pickup = 0 OR b.add_to_pickup IS NULL) AND (b.pickup_date IS NULL OR b.pickup_date = '')",
    'in_transit'          => $notCancelled . " AND b.status_type = 'UD' AND b.last_status IN ('In Transit','Pending','Dispatched') AND (b.nsl_code IS NULL OR b.nsl_code NOT IN ($NDR_CODES))",
    'return_to_origin'    => $notCancelled . " AND b.status_type = 'DL' AND LOWER(IFNULL(b.last_status,'')) IN ('rto','return to origin')",
    'delivered'           => $notCancelled . " AND b.status_type = 'DL' AND LOWER(IFNULL(b.last_status,'')) = 'delivered'",
    'ndr_shipment'        => $notCancelled . " AND b.nsl_code IN ($NDR_CODES)",
    'cancelled'           => " AND LOWER(IFNULL(b.last_status,'')) = 'cancelled'",
];

$counts = [];
try {
    foreach ($tabs as $tab => $extra) {
        $sql  = "SELECT COUNT(*)" . $baseWhere . $extra;
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $counts[$tab] = (int)$stmt->fetchColumn();
    }
    echo json_encode(['status' => 'success', 'counts' => $counts]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
