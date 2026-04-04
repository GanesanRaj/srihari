<?php
/**
 * Branch List API
 * Location: /apps-api/branch/list.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$company_id = $_REQUEST['company_id'] ?? '';
$status = $_REQUEST['status'] ?? 'active';
$start = intval($_REQUEST['start'] ?? 0);
$limit = intval($_REQUEST['limit'] ?? 10);
$search = $_REQUEST['search'] ?? '';

$user_id = $_REQUEST['user_id'] ?? 1;

try {
    // Get branch_ids from tbl_user
        $stmtUser = $pdo->prepare("SELECT branch_ids FROM tbl_user WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        $branch_ids = $user['branch_ids'] ?? '';
    
    
    $sql = "SELECT b.*, c.company_name 
            FROM tbl_branch b
            LEFT JOIN tbl_company c ON b.company_id = c.id
            WHERE 1=1";
    
    $countSql = "SELECT COUNT(*) as total FROM tbl_branch b LEFT JOIN tbl_company c ON b.company_id = c.id WHERE 1=1";
    $params = [];

    if (!empty($company_id)) {
        $sql .= " AND b.company_id = :company_id";
        $countSql .= " AND b.company_id = :company_id";
        $params[':company_id'] = $company_id;
    }
    
    if (!empty($branch_ids)) {
        $ids = array_map('intval', explode(',', $branch_ids)); // sanitize
        $branch_ids_safe = implode(',', $ids);
    
        $sql .= " AND b.id IN ($branch_ids_safe)";
        $countSql .= " AND b.id IN ($branch_ids_safe)";
    }

    if (!empty($status)) {
        $sql .= " AND b.status = :status";
        $countSql .= " AND b.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($search)) {
        $searchCondition = " AND (b.branch_name LIKE :search OR b.branch_code LIKE :search OR b.contact_no LIKE :search OR b.state LIKE :search)";
        $sql .= $searchCondition;
        $countSql .= $searchCondition;
        $params[':search'] = "%$search%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalFiltered = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY b.branch_name ASC LIMIT :start, :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
