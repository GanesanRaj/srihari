<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

require_api_permission('pickup_request', 'is_view');

try {
    $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 1;
    $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    $where  = "WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (pr.pickup_location_name LIKE :s OR pr.pickup_date LIKE :s2
                        OR pr.status LIKE :s3 OR pr.request_id LIKE :s4
                        OR cp.partner_name LIKE :s5)";
        $params[':s']  = "%$search%";
        $params[':s2'] = "%$search%";
        $params[':s3'] = "%$search%";
        $params[':s4'] = "%$search%";
        $params[':s5'] = "%$search%";
    }

    if (!empty($_GET['status'])) {
        $where .= " AND pr.status = :filter_status";
        $params[':filter_status'] = $_GET['status'];
    }

    if (!empty($_GET['courier_id'])) {
        $where .= " AND pr.courier_id = :filter_courier";
        $params[':filter_courier'] = (int)$_GET['courier_id'];
    }

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM tbl_pickup_requests pr
         LEFT JOIN tbl_courier_partner cp ON cp.id = pr.courier_id
         $where"
    );
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT pr.*, cp.partner_name,
                pp.address AS pickup_address, pp.city AS pickup_city, pp.pin AS pickup_pin
         FROM tbl_pickup_requests pr
         LEFT JOIN tbl_courier_partner cp ON cp.id = pr.courier_id
         LEFT JOIN tbl_pickup_points pp ON pp.id = pr.pickup_point_id
         $where
         ORDER BY pr.id DESC
         LIMIT :start, :length"
    );
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':start',  $start,  PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $total,
        'data'            => $data
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
