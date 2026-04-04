<?php
/**
 * Client List API
 * Location: /apps-api/client/list.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$branch_id = $_REQUEST['branch_id'] ?? '';
$status = $_REQUEST['status'] ?? 'active';
$start = intval($_REQUEST['start'] ?? 0);
$limit = intval($_REQUEST['limit'] ?? 10);
$search = $_REQUEST['search'] ?? '';

$user_id = $_REQUEST['user_id'] ?? 1;

try {
    
$stmtUser = $pdo->prepare("SELECT branch_ids,client_ids FROM tbl_user WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$branch_ids = $user['branch_ids'] ?? '';
$client_ids = $user['client_ids'] ?? '';

error_log($branch_ids);
    
    
    $sql = "SELECT c.*, b.branch_name 
            FROM tbl_client c
            LEFT JOIN tbl_branch b ON c.branch_id = b.id
            WHERE 1=1";
    
    $countSql = "SELECT COUNT(*) as total FROM tbl_client c WHERE 1=1";
    $params = [];

    if (!empty($branch_id)) {
        $sql .= " AND c.branch_id = :branch_id";
        $countSql .= " AND c.branch_id = :branch_id";
        $params[':branch_id'] = $branch_id;
    }
    
    
    if (!empty($branch_ids)) {
        $ids = array_map('intval', explode(',', $branch_ids)); // sanitize
        $branch_ids_safe = implode(',', $ids);
    
        $sql .= " AND c.branch_id IN ($branch_ids_safe)";
        $countSql .= " AND c.branch_id IN ($branch_ids_safe)";
    }
    
    if (!empty($client_ids)) {
        $ids = array_map('intval', explode(',', $client_ids)); // sanitize
        $client_ids_safe = implode(',', $ids);
    
        $sql .= " AND c.id IN ($client_ids_safe)";
        $countSql .= " AND c.id IN ($client_ids_safe)";
    }
    
    

    if (!empty($status)) {
        $sql .= " AND c.status = :status";
        $countSql .= " AND c.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($search)) {
        $searchCondition = " AND (c.client_name LIKE :search OR c.contact_no LIKE :search OR c.email LIKE :search OR c.city LIKE :search)";
        $sql .= $searchCondition;
        $countSql .= $searchCondition;
        $params[':search'] = "%$search%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalFiltered = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql .= " ORDER BY c.client_name ASC LIMIT :start, :limit";

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
