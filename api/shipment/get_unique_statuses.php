<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $stmt = $pdo->query("SELECT DISTINCT last_status FROM tbl_bookings WHERE last_status IS NOT NULL AND last_status != '' ORDER BY last_status ASC");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['status' => 'success', 'data' => $statuses]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>