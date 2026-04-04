<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// require_api_permission('shipment', 'is_view');

try {
    $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $sql = "SELECT b.id, b.booking_ref_id, b.waybill_no, b.consignee_name, b.consignee_phone, b.payment_mode, b.cod_amount, b.last_status, b.created_at, 
            c.partner_name as courier_name, p.name as pickup_point_name,
            br.branch_name, co.company_name
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
            LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
            LEFT JOIN tbl_branch br ON p.branch_id = br.id
            LEFT JOIN tbl_company co ON br.company_id = co.id
            WHERE 1=1";

    if ($searchValue !== '') {
        $sql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search)";
    }
    if (!empty($_GET['company_id'])) {
        $sql .= " AND br.company_id = :company_id";
    }
    if (!empty($_GET['branch_id'])) {
        $sql .= " AND p.branch_id = :branch_id";
    }
    if (!empty($_GET['status'])) {
        $sql .= " AND b.last_status = :filter_status";
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);

    if ($searchValue !== '') {
        $stmt->bindValue(':search', '%' . $searchValue . '%', PDO::PARAM_STR);
    }
    if (!empty($_GET['company_id'])) {
        $stmt->bindValue(':company_id', (int) $_GET['company_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['branch_id'])) {
        $stmt->bindValue(':branch_id', (int) $_GET['branch_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['status'])) {
        $stmt->bindValue(':filter_status', $_GET['status'], PDO::PARAM_STR);
    }

    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "SELECT COUNT(*) FROM tbl_bookings b
                 LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                 LEFT JOIN tbl_branch br ON p.branch_id = br.id
                 WHERE 1=1";

    if ($searchValue !== '') {
        $countSql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search)";
    }
    if (!empty($_GET['company_id'])) {
        $countSql .= " AND br.company_id = :company_id";
    }
    if (!empty($_GET['branch_id'])) {
        $countSql .= " AND p.branch_id = :branch_id";
    }
    if (!empty($_GET['status'])) {
        $countSql .= " AND b.last_status = :filter_status";
    }

    $countStmt = $pdo->prepare($countSql);
    if ($searchValue !== '') {
        $countStmt->bindValue(':search', '%' . $searchValue . '%', PDO::PARAM_STR);
    }
    if (!empty($_GET['company_id'])) {
        $countStmt->bindValue(':company_id', (int) $_GET['company_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['branch_id'])) {
        $countStmt->bindValue(':branch_id', (int) $_GET['branch_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['status'])) {
        $countStmt->bindValue(':filter_status', $_GET['status'], PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetchColumn();

    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int) $_GET['draw'] : 1,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
        'status' => 'success'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
