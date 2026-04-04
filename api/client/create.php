<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('client', 'is_add');

// Get current user info
$current_user = get_current_user_info();
if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

// Declare variables
$branch_id = $client_name = $contact_no = $email = $gst_number = $address = $location = $city = $state = $pincode = $client_logo = $commission_percentage = $cod_amount = $cod_percentage = $min_cod_amount = $status = $client_code = '';
$errors = [];



// Validate POST data
$requiredFields = ['branch_id', 'client_name', 'contact_no', 'address', 'city', 'state', 'pincode'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$email = isset($_POST['email']) ? sanitizeText($_POST['email']) : '';
$gst_number = isset($_POST['gst_number']) ? sanitizeText($_POST['gst_number']) : '';
$location = isset($_POST['location']) ? sanitizeText($_POST['location']) : '';
$commission_percentage = isset($_POST['commission_percentage']) ? sanitizeText($_POST['commission_percentage']) : 0.00;
$cod_amount = isset($_POST['cod_amount']) ? sanitizeText($_POST['cod_amount']) : 0.00;
$cod_percentage = isset($_POST['cod_percentage']) ? sanitizeText($_POST['cod_percentage']) : 0.00;
$min_cod_amount = isset($_POST['min_cod_amount']) ? sanitizeText($_POST['min_cod_amount']) : 0.00;
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$client_code = isset($_POST['client_code']) ? sanitizeText($_POST['client_code']) : '';

// Handle File Upload (Logo)
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
    // using helper function
    $uploaded_path = handle_image_upload($_FILES['logo'], 'client');
    if ($uploaded_path) {
        $client_logo = $uploaded_path;
    } else {
        $errors[] = "Failed to upload or compress logo.";
    }
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "INSERT INTO tbl_client (branch_id, client_name, client_code, contact_no, email, gst_number, address, location, city, state, pincode, client_logo, commission_percentage, cod_amount, cod_percentage, min_cod_amount, status, created_by, created_at)
            VALUES (:branch_id, :client_name, :client_code, :contact_no, :email, :gst_number, :address, :location, :city, :state, :pincode, :client_logo, :commission_percentage, :cod_amount, :cod_percentage, :min_cod_amount, :status, :created_by, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':branch_id', $branch_id);
    $stmt->bindParam(':client_name', $client_name);
    $stmt->bindParam(':client_code', $client_code);
    $stmt->bindParam(':contact_no', $contact_no);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':gst_number', $gst_number);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':client_logo', $client_logo);
    $stmt->bindParam(':commission_percentage', $commission_percentage);
    $stmt->bindParam(':cod_amount', $cod_amount);
    $stmt->bindParam(':cod_percentage', $cod_percentage);
    $stmt->bindParam(':min_cod_amount', $min_cod_amount);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':created_by', $current_user['id']);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Client created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create client']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>