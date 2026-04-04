<?php
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';

try {
    // Pagination parameters
    $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    // Handle -1 (get all records)
    if ($length <= 0) {
        $length = 999999;
    }

    $sql = "SELECT b.*,
            c.partner_name as courier_name, p.name as pickup_point_name,
            br.branch_name, co.company_name,
            u.username as creator_name
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
            LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
            LEFT JOIN tbl_branch br ON p.branch_id = br.id
            LEFT JOIN tbl_company co ON br.company_id = co.id
            LEFT JOIN tbl_user u ON b.created_by = u.id
            WHERE 1=1";

    // Search
    if (!empty($searchValue)) {
        $sql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR b.consignee_phone LIKE :search)";
    }

    // Filter by Company
    if (!empty($_GET['company_id'])) {
        $sql .= " AND br.company_id = :company_id";
    }

    // Filter by Branch
    if (!empty($_GET['branch_id'])) {
        $sql .= " AND p.branch_id = :branch_id";
    }

    // Filter by Status
    if (!empty($_GET['status'])) {
        $sql .= " AND b.last_status = :filter_status";
    }

    // Filter by Courier
    if (!empty($_GET['courier_id'])) {
        $sql .= " AND b.courier_id = :courier_id";
    }

    // Filter by Date Range
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $sql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT :start, :length";

    $stmt = $pdo->prepare($sql);

    // Bind values
    if (!empty($searchValue)) {
        $stmt->bindValue(':search', "%$searchValue%", PDO::PARAM_STR);
    }
    if (!empty($_GET['company_id'])) {
        $stmt->bindValue(':company_id', $_GET['company_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['branch_id'])) {
        $stmt->bindValue(':branch_id', $_GET['branch_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['status'])) {
        $stmt->bindValue(':filter_status', $_GET['status'], PDO::PARAM_STR);
    }
    if (!empty($_GET['courier_id'])) {
        $stmt->bindValue(':courier_id', $_GET['courier_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $stmt->bindValue(':from_date', $_GET['from_date'], PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $_GET['to_date'], PDO::PARAM_STR);
    }

    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count filter records
    $countSql = "SELECT COUNT(*) FROM tbl_bookings b
                 LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
                 LEFT JOIN tbl_branch br ON p.branch_id = br.id
                 WHERE 1=1";

    if (!empty($searchValue)) {
        $countSql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR b.consignee_phone LIKE :search)";
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
    if (!empty($_GET['courier_id'])) {
        $countSql .= " AND b.courier_id = :courier_id";
    }

    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $countSql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
    }

    $countStmt = $pdo->prepare($countSql);
    if (!empty($searchValue)) {
        $countStmt->bindValue(':search', "%$searchValue%", PDO::PARAM_STR);
    }
    if (!empty($_GET['company_id'])) {
        $countStmt->bindValue(':company_id', $_GET['company_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['branch_id'])) {
        $countStmt->bindValue(':branch_id', $_GET['branch_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['status'])) {
        $countStmt->bindValue(':filter_status', $_GET['status'], PDO::PARAM_STR);
    }
    if (!empty($_GET['courier_id'])) {
        $countStmt->bindValue(':courier_id', $_GET['courier_id'], PDO::PARAM_INT);
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $countStmt->bindValue(':from_date', $_GET['from_date'], PDO::PARAM_STR);
        $countStmt->bindValue(':to_date', $_GET['to_date'], PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
        'status' => 'success'
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>