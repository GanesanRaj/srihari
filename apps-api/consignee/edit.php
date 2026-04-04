<?php
/**
 * Consignee Edit/Update API
 * Location: /apps-api/consignee/edit.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

if (empty($req['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required for editing']);
    exit();
}

try {
    $sql = "UPDATE tbl_consignee SET 
            branch_id = :branch_id, 
            client_id = :client_id, 
            name = :name, 
            contact_no = :contact_no, 
            alt_contact_no = :alt_contact_no, 
            email = :email, 
            gst_number = :gst_number, 
            address = :address, 
            location = :location, 
            city = :city, 
            state = :state, 
            pincode = :pincode, 
            status = :status,
            updated_by = :updated_by,
            updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    
    $user_id = $req['user_id'] ?? 1;

    $stmt->bindValue(':id', $req['id']);
    $stmt->bindValue(':branch_id', $req['branch_id']);
    $stmt->bindValue(':client_id', $req['client_id']);
    $stmt->bindValue(':name', $req['name']);
    $stmt->bindValue(':contact_no', $req['contact_no']);
    $stmt->bindValue(':alt_contact_no', $req['alt_contact_no'] ?? null);
    $stmt->bindValue(':email', $req['email'] ?? null);
    $stmt->bindValue(':gst_number', $req['gst_number'] ?? null);
    $stmt->bindValue(':address', $req['address']);
    $stmt->bindValue(':location', $req['location'] ?? null);
    $stmt->bindValue(':city', $req['city']);
    $stmt->bindValue(':state', $req['state']);
    $stmt->bindValue(':pincode', $req['pincode']);
    $stmt->bindValue(':status', $req['status'] ?? 'active');
    $stmt->bindValue(':updated_by', $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Consignee updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update consignee']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
