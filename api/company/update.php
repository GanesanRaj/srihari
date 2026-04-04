<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Edit Permission
require_api_permission('company', 'is_edit');

$company_name = $phone_number = $gst_no = $address = $city = $state = $pincode = $status = $remarks = $company_logo = '';
$errors = [];



if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

$id = intval($_POST['id']);

// Validate POST data
$requiredFields = ['company_name', 'phone_number', 'address', 'city', 'state'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

$gst_no = isset($_POST['gst_no']) ? sanitizeText($_POST['gst_no']) : '';
$pincode = isset($_POST['pincode']) ? sanitizeText($_POST['pincode']) : '';
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';
$remarks = isset($_POST['remarks']) ? sanitizeText($_POST['remarks']) : '';

// Fetch current logo path for deletion later if new logo is uploaded
$old_logo = null;
try {
    $stmt = $pdo->prepare("SELECT company_logo FROM tbl_company WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $old_logo = $row['company_logo'];
    }
} catch (PDOException $e) {
    // Ignore error here, proceed with update
}

// Handle File Upload (Logo) using Helper
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
    // Helper handles compression and deletion of old logo
    $uploaded_path = handle_image_upload($_FILES['logo'], 'company', $old_logo);
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
    // Construct SQL dynamically based on whether logo is updated
    if ($company_logo != '') {
        $sql = "UPDATE tbl_company SET company_name=:company_name, phone_number=:phone_number, gst_no=:gst_no, address=:address, city=:city, state=:state, pincode=:pincode, status=:status, remarks=:remarks, company_logo=:company_logo, updated_at=NOW() WHERE id=:id";
    } else {
        $sql = "UPDATE tbl_company SET company_name=:company_name, phone_number=:phone_number, gst_no=:gst_no, address=:address, city=:city, state=:state, pincode=:pincode, status=:status, remarks=:remarks, updated_at=NOW() WHERE id=:id";
    }

    $stmt = $pdo->prepare($sql);

    $params = [
        ':company_name' => $company_name,
        ':phone_number' => $phone_number,
        ':gst_no' => $gst_no,
        ':address' => $address,
        ':city' => $city,
        ':state' => $state,
        ':pincode' => $pincode,
        ':status' => $status,
        ':remarks' => $remarks,
        ':id' => $id
    ];

    if ($company_logo != '') {
        $params[':company_logo'] = $company_logo;
    }

    if ($stmt->execute($params)) {
        echo json_encode(['status' => 'success', 'message' => 'Company updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update company']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>