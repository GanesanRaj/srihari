<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add/Edit Permission
if (isset($_POST['id']) && !empty($_POST['id'])) {
    require_api_permission('consignee', 'is_edit');
} else {
    require_api_permission('consignee', 'is_add');
}

$data = $_POST;

// Validate required fields
if (empty($data['branch_id']) || empty($data['client_id']) || empty($data['name']) || empty($data['contact_no']) || empty($data['address']) || empty($data['city']) || empty($data['state']) || empty($data['pincode'])) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
    exit();
}

try {
    // Sanitization
    $branch_id = sanitizeText($data['branch_id']);
    $client_id = sanitizeText($data['client_id']);
    $name = sanitizeText($data['name']);
    $contact_no = sanitizeText($data['contact_no']);
    $alt_contact_no = isset($data['alt_contact_no']) ? sanitizeText($data['alt_contact_no']) : null;
    $email = isset($data['email']) ? sanitizeText($data['email']) : null;
    $gst_number = isset($data['gst_number']) ? sanitizeText($data['gst_number']) : null;
    $address = sanitizeText($data['address']);
    $location = isset($data['location']) ? sanitizeText($data['location']) : null;
    $city = sanitizeText($data['city']);
    $state = sanitizeText($data['state']);
    $pincode = sanitizeText($data['pincode']);
    $status = isset($data['status']) ? sanitizeText($data['status']) : 'active';

    $user_id = 1; // Default to admin
    if (function_exists('get_current_user_id')) {
        $user_id = get_current_user_id();
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    if (isset($data['id']) && !empty($data['id'])) {
        // Update logic
        $id = sanitizeText($data['id']);

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
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':updated_by', $user_id);
    } else {
        // Insert logic
        $sql = "INSERT INTO tbl_consignee (branch_id, client_id, name, contact_no, alt_contact_no, email, gst_number, address, location, city, state, pincode, status, created_by) 
                VALUES (:branch_id, :client_id, :name, :contact_no, :alt_contact_no, :email, :gst_number, :address, :location, :city, :state, :pincode, :status, :created_by)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':created_by', $user_id);
    }

    // Bind common parameters
    $stmt->bindParam(':branch_id', $branch_id);
    $stmt->bindParam(':client_id', $client_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':contact_no', $contact_no);
    $stmt->bindParam(':alt_contact_no', $alt_contact_no);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':gst_number', $gst_number);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => isset($data['id']) ? 'Consignee updated successfully' : 'Consignee created successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save consignee']);
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>