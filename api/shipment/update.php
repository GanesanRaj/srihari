<?php
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';

// Check permissions if needed
// require_permission('shipment', 'is_edit'); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // 1. Validate Input
    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception("Booking ID is required for update");
    }

    $courierId = $_POST['courier_id'] ?? null;
    $pickupPointId = $_POST['pickup_point_id'] ?? null;
    $bookingRefId = $_POST['booking_ref_id'] ?? '';

    // Consignee
    $consigneeName = $_POST['consignee_name'] ?? '';
    $consigneePhone = $_POST['consignee_phone'] ?? '';
    $consigneeAddress = $_POST['consignee_address'] ?? '';
    $consigneePin = $_POST['consignee_pin'] ?? '';
    $consigneeCity = $_POST['consignee_city'] ?? '';
    $consigneeState = $_POST['consignee_state'] ?? '';
    $consigneeCountry = $_POST['consignee_country'] ?? 'India';
    $consigneeEmail = $_POST['consignee_email'] ?? '';
    $consigneeGst = $_POST['consignee_gst'] ?? '';

    // Consignor (Manual)
    $shipperName = $_POST['shipper_name'] ?? '';
    $shipperPhone = $_POST['shipper_phone'] ?? '';
    $shipperAddress = $_POST['shipper_address'] ?? '';
    $shipperPin = $_POST['shipper_pin'] ?? '';
    $shipperCity = $_POST['shipper_city'] ?? '';
    $shipperState = $_POST['shipper_state'] ?? '';

    // Invoice & RTO
    $invoiceNo = $_POST['invoice_no'] ?? '';
    $invoiceValue = $_POST['invoice_value'] ?? 0;
    $ewaybillNo = $_POST['ewaybill_no'] ?? '';

    $rtoName = $_POST['rto_name'] ?? '';
    $rtoPhone = $_POST['rto_phone'] ?? '';
    $rtoAddress = $_POST['rto_address'] ?? '';

    if (empty($rtoName) && empty($rtoPhone) && empty($rtoAddress)) {
        $rtoName = $shipperName;
        $rtoPhone = $shipperPhone;
        $rtoAddress = $shipperAddress;
    }

    // Shipment Details
    $paymentMode = $_POST['payment_mode'] ?? 'Prepaid';
    $codAmount = $_POST['cod_amount'] ?? 0;
    $shippingMode = $_POST['shipping_mode'] ?? 'Surface';
    $productDesc = $_POST['product_desc'] ?? '';

    // Package Arrays
    $lengths = $_POST['length'] ?? [];
    $widths = $_POST['width'] ?? [];
    $heights = $_POST['height'] ?? [];
    $boxes = $_POST['boxes'] ?? [];
    $actualWeights = $_POST['actual_weight'] ?? [];
    $chargedWeights = $_POST['charged_weight'] ?? [];

    if (!$courierId || !$pickupPointId || !$consigneeName || !$consigneePhone || !$consigneeAddress || !$consigneePin) {
        throw new Exception("Missing required fields");
    }

    // Process Package Details
    $packageDetails = [];
    $totalActualWeight = 0;
    $totalChargedWeight = 0;
    $totalBoxes = 0;
    $maxL = 0;
    $maxW = 0;
    $maxH = 0;

    for ($i = 0; $i < count($lengths); $i++) {
        $qty = intval($boxes[$i] ?? 1);
        $actWt = floatval($actualWeights[$i] ?? 0);
        $chgWt = floatval($chargedWeights[$i] ?? 0);

        $totalBoxes += $qty;
        $totalActualWeight += ($actWt * $qty);
        $totalChargedWeight += ($chgWt);

        $packageDetails[] = [
            'length' => $lengths[$i],
            'width' => $widths[$i],
            'height' => $heights[$i],
            'boxes' => $qty,
            'actual_weight' => $actWt,
            'charged_weight' => $chgWt
        ];

        if (floatval($lengths[$i]) > $maxL)
            $maxL = floatval($lengths[$i]);
        if (floatval($widths[$i]) > $maxW)
            $maxW = floatval($widths[$i]);
        if (floatval($heights[$i]) > $maxH)
            $maxH = floatval($heights[$i]);
    }

    $packageDetailsJson = json_encode($packageDetails);
    $weightGrams = $totalActualWeight * 1000;

    // Check if booking exists
    $stmt = $pdo->prepare("SELECT * FROM tbl_bookings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $existingBooking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingBooking) {
        throw new Exception("Shipment not found");
    }

    // Fetch Courier Partner
    $stmt = $pdo->prepare("SELECT * FROM tbl_courier_partner WHERE id = :id");
    $stmt->execute([':id' => $courierId]);
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courier) {
        throw new Exception("Invalid Courier Partner");
    }

    // 4. Prepare Data for Service
    $shipmentData = [
        'id' => $id,
        'waybill_no' => $existingBooking['waybill_no'],
        'booking_ref_id' => $bookingRefId,
        'consignee_name' => $consigneeName,
        'consignee_phone' => $consigneePhone,
        'consignee_address' => $consigneeAddress,
        'consignee_pin' => $consigneePin,
        'consignee_city' => $consigneeCity,
        'consignee_state' => $consigneeState,
        'consignee_country' => $consigneeCountry,
        'payment_mode' => $paymentMode,
        'cod_amount' => $codAmount,
        'product_desc' => $productDesc,
        'quantity' => $totalBoxes,
        'weight' => $weightGrams,
        'length' => $maxL,
        'width' => $maxW,
        'height' => $maxH,
        'shipping_mode' => $shippingMode,
        'invoice_no' => $invoiceNo,
        'invoice_value' => $invoiceValue,
        'ewaybill_no' => $ewaybillNo,
        'last_status' => $existingBooking['last_status'],
        'existing_payment_mode' => $existingBooking['payment_mode']
    ];

    // 5. Call Courier Service Router for Update
    require_once __DIR__ . '/services/courier_service.php';
    $serviceResponse = updateBookingWithCourier($pdo, $courier, $shipmentData);

    $apiResponseJson = null;
    if ($serviceResponse['success']) {
        $apiResponseJson = json_encode($serviceResponse['api_response'] ?? $serviceResponse);
    } else {
        throw new Exception("Courier API Update Failed: " . $serviceResponse['message']);
    }

    // 6. Update Database
    $sql = "UPDATE tbl_bookings SET 
        booking_ref_id = :ref_id,
        courier_id = :c_id,
        pickup_point_id = :p_id,
        consignee_name = :c_name,
        consignee_phone = :c_phone,
        consignee_email = :c_email,
        consignee_gst = :c_gst,
        consignee_address = :c_add,
        consignee_pin = :c_pin,
        consignee_city = :c_city,
        consignee_state = :c_state,
        consignee_country = :c_country,
        shipper_name = :s_name,
        shipper_phone = :s_phone,
        shipper_address = :s_add,
        shipper_pin = :s_pin,
        shipper_city = :s_city,
        shipper_state = :s_state,
        payment_mode = :pay_mode,
        cod_amount = :cod,
        weight = :w,
        quantity = :qty,
        length = :l,
        width = :wi,
        height = :h,
        shipping_mode = :ship_mode,
        product_desc = :prod_desc,
        package_details = :pkg_details,
        invoice_no = :inv_no,
        invoice_value = :inv_val,
        ewaybill_no = :eway,
        rto_name = :rto_name,
        rto_phone = :rto_phone,
        rto_address = :rto_add,
        api_response = :api_resp,
        updated_by = :user_id,
        updated_at = NOW()
    WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ref_id' => $bookingRefId,
        ':c_id' => $courierId,
        ':p_id' => $pickupPointId,
        ':c_name' => $consigneeName,
        ':c_phone' => $consigneePhone,
        ':c_email' => $consigneeEmail,
        ':c_gst' => $consigneeGst,
        ':c_add' => $consigneeAddress,
        ':c_pin' => $consigneePin,
        ':c_city' => $consigneeCity,
        ':c_state' => $consigneeState,
        ':c_country' => $consigneeCountry,
        ':s_name' => $shipperName,
        ':s_phone' => $shipperPhone,
        ':s_add' => $shipperAddress,
        ':s_pin' => $shipperPin,
        ':s_city' => $shipperCity,
        ':s_state' => $shipperState,
        ':pay_mode' => $paymentMode,
        ':cod' => $codAmount,
        ':w' => $weightGrams,
        ':qty' => $totalBoxes,
        ':l' => $maxL,
        ':wi' => $maxW,
        ':h' => $maxH,
        ':ship_mode' => $shippingMode,
        ':prod_desc' => $productDesc,
        ':pkg_details' => $packageDetailsJson,
        ':inv_no' => $invoiceNo,
        ':inv_val' => $invoiceValue,
        ':eway' => $ewaybillNo,
        ':rto_name' => $rtoName,
        ':rto_phone' => $rtoPhone,
        ':rto_add' => $rtoAddress,
        ':api_resp' => $apiResponseJson,
        ':user_id' => $_SESSION['user_id'] ?? 1,
        ':id' => $id
    ]);

    if ($serviceResponse['success'] && !empty($existingBooking['waybill_no']) && $courierId == 1) {
        try {
            if (!defined('IN_CREATION'))
                define('IN_CREATION', true);
            $_GET['waybill'] = $existingBooking['waybill_no'];
            ob_start();
            include __DIR__ . '/../../cron-delhivery.php';
            ob_end_clean();
        } catch (Exception $e) {
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Shipment updated successfully!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>