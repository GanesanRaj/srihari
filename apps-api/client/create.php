<?php
/**
 * Client Create API
 * Location: /apps-api/client/create.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

if (empty($req['branch_id']) || empty($req['client_name']) || empty($req['contact_no']) || empty($req['address'])) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
    exit();
}

try {
    $sql = "INSERT INTO tbl_client (branch_id, client_name, contact_no, email, gst_number, address, location, city, state, pincode, status, created_by) 
            VALUES (:branch_id, :client_name, :contact_no, :email, :gst_number, :address, :location, :city, :state, :pincode, :status, :created_by)";

    $stmt = $pdo->prepare($sql);
    
    $user_id = $req['user_id'] ?? 1;

    $stmt->bindValue(':branch_id', $req['branch_id']);
    $stmt->bindValue(':client_name', $req['client_name']);
    $stmt->bindValue(':contact_no', $req['contact_no']);
    $stmt->bindValue(':email', $req['email'] ?? null);
    $stmt->bindValue(':gst_number', $req['gst_number'] ?? null);
    $stmt->bindValue(':address', $req['address']);
    $stmt->bindValue(':location', $req['location'] ?? null);
    $stmt->bindValue(':city', $req['city'] ?? null);
    $stmt->bindValue(':state', $req['state'] ?? null);
    $stmt->bindValue(':pincode', $req['pincode'] ?? null);
    $stmt->bindValue(':status', $req['status'] ?? 'active');
    $stmt->bindValue(':created_by', $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Client created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create client']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
