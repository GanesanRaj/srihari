<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check Add Permission
require_api_permission('pickuppoint', 'is_add');

// Get current user info
$current_user = get_current_user_info();
$created_by = $current_user['id'] ?? ($_SESSION['user_id'] ?? 1);

// Declare variables
$company_id = $branch_id = $courier_id = $pickup_point_code = $name = $registered_name = $phone = $email = '';
$address = $city = $pin = $country = '';
$pickup_state = '';
$return_address = $return_city = $return_pin = $return_state = $return_country = '';
$status = '';
$errors = [];

// Validate POST data - Required fields
$requiredFields = ['company_id', 'branch_id', 'courier_id', 'name', 'phone', 'pin', 'return_address'];

foreach ($requiredFields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        $$field = sanitizeText($_POST[$field]);
    } else {
        $errors[] = "Field '$field' is required";
    }
}

// Optional fields
$pickup_point_code = isset($_POST['pickup_point_code']) ? sanitizeText($_POST['pickup_point_code']) : '';
$registered_name = isset($_POST['registered_name']) ? sanitizeText($_POST['registered_name']) : '';
$email = isset($_POST['email']) ? sanitizeText($_POST['email']) : '';
$address = isset($_POST['address']) ? sanitizeText($_POST['address']) : '';
$city = isset($_POST['city']) ? sanitizeText($_POST['city']) : '';
$pickup_state = isset($_POST['pickup_state']) ? sanitizeText($_POST['pickup_state']) : '';
$country = isset($_POST['country']) ? sanitizeText($_POST['country']) : 'India';
$return_city = isset($_POST['return_city']) ? sanitizeText($_POST['return_city']) : '';
$return_pin = isset($_POST['return_pin']) ? sanitizeText($_POST['return_pin']) : '';
$return_state = isset($_POST['return_state']) ? sanitizeText($_POST['return_state']) : '';
$return_country = isset($_POST['return_country']) ? sanitizeText($_POST['return_country']) : 'India';
$status = isset($_POST['status']) ? sanitizeText($_POST['status']) : 'active';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $sql = "INSERT INTO tbl_pickup_points (
                company_id, branch_id, courier_id, pickup_point_code, name, registered_name, 
                phone, email, address, city, pin, pickup_state, country,
                return_address, return_city, return_pin, return_state, return_country,
                status, created_by, created_at
            ) VALUES (
                :company_id, :branch_id, :courier_id, :pickup_point_code, :name, :registered_name,
                :phone, :email, :address, :city, :pin, :pickup_state, :country,
                :return_address, :return_city, :return_pin, :return_state, :return_country,
                :status, :created_by, NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->bindParam(':branch_id', $branch_id);
    $stmt->bindParam(':courier_id', $courier_id);
    $stmt->bindParam(':pickup_point_code', $pickup_point_code);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':registered_name', $registered_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':pin', $pin);
    $stmt->bindParam(':pickup_state', $pickup_state);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':return_address', $return_address);
    $stmt->bindParam(':return_city', $return_city);
    $stmt->bindParam(':return_pin', $return_pin);
    $stmt->bindParam(':return_state', $return_state);
    $stmt->bindParam(':return_country', $return_country);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':created_by', $created_by);

    if ($stmt->execute()) {
        $newPickupPointId = $pdo->lastInsertId();

        // Fetch courier partner details
        $courierCheckSql = "SELECT id, partner_name, partner_code, api_key, api_url, token FROM tbl_courier_partner WHERE id = :courier_id";
        $courierCheckStmt = $pdo->prepare($courierCheckSql);
        $courierCheckStmt->bindParam(':courier_id', $courier_id);
        $courierCheckStmt->execute();
        $courierData = $courierCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$courierData) {
            // Delete the created pickup point
            $deleteSql = "DELETE FROM tbl_pickup_points WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->bindValue(':id', $newPickupPointId, PDO::PARAM_INT);
            $deleteStmt->execute();

            echo json_encode(['status' => 'error', 'message' => 'Courier partner not found']);
            exit;
        }

        // Prepare pickup point data for courier service
        $pickupPointData = [
            'phone' => $phone,
            'city' => $city,
            'name' => $name,
            'pin' => $pin,
            'address' => $address,
            'pickup_state' => $pickup_state,
            'country' => $country,
            'email' => $email,
            'registered_name' => $registered_name,
            'return_address' => $return_address,
            'return_pin' => $return_pin,
            'return_city' => $return_city,
            'return_state' => $return_state,
            'return_country' => $return_country
        ];

        // Call courier service to sync
        require_once __DIR__ . '/services/courier_service.php';
        $syncResult = syncPickupPointWithCourier($pdo, $courierData, $pickupPointData, $newPickupPointId, 'create');

        if ($syncResult['success']) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Pickup point created successfully' . ($syncResult['synced'] ? ' and ' . $syncResult['message'] : ''),
                'id' => $newPickupPointId,
                'synced' => $syncResult['synced']
            ]);
        } else {
            // Sync failed - delete the pickup point row
            $deleteSql = "DELETE FROM tbl_pickup_points WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->bindValue(':id', $newPickupPointId, PDO::PARAM_INT);
            $deleteStmt->execute();

            echo json_encode(['status' => 'error', 'message' => 'Pickup point not created: ' . $syncResult['message']]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create pickup point']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
