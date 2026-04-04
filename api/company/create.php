<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('company', 'is_add');

// Declare variables
$company_name = $phone_number = $gst_no = $address = $city = $state = $pincode = $status = $remarks = $company_logo = '';
$errors = [];



// Validate POST data
$requiredFields = ['company_name', 'phone_number', 'address', 'city', 'state'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$gst_no = isset($_POST['gst_no']) ? sanitizeText($_POST['gst_no']) : '';
$pincode = isset($_POST['pincode']) ? sanitizeText($_POST['pincode']) : '';
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

// Handle File Upload (Logo) using Helper
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
    $uploaded_path = handle_image_upload($_FILES['logo'], 'company');
    if ($uploaded_path) {
        $company_logo = $uploaded_path;
    } else {
        $errors[] = "Failed to upload or compress logo.";
    }
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "INSERT INTO tbl_company (company_name, phone_number, gst_no, address, city, state, pincode, status, remarks, company_logo, created_at) 
            VALUES (:company_name, :phone_number, :gst_no, :address, :city, :state, :pincode, :status, :remarks, :company_logo, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':company_name', $company_name);
    $stmt->bindParam(':phone_number', $phone_number);
    $stmt->bindParam(':gst_no', $gst_no);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':company_logo', $company_logo);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Company created successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create company']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>