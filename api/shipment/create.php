<?php
header('Content-Type: application/json');
require '../../config/db.php';
require '../../config/middleware.php';

// Check permissions
// require_permission('shipment', 'is_add'); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    // 1. Validate Input
    $courierId = $_POST['courier_id'] ?? null;
    $pickupPointId = $_POST['pickup_point_id'] ?? null;
    $bookingRefId = $_POST['booking_ref_id'] ?? 'ORD-' . time();

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

    // If RTO details are empty, assume Same as Consignor (Shipper)
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
        throw new Exception("Missing required fields (Courier, Pickup Point, Consignee Details)");
    }

    // Process Package Details
    $packageDetails = [];
    $totalActualWeight = 0;
    $totalChargedWeight = 0;
    $totalBoxes = 0;
    $maxL = 0;
    $maxW = 0;
    $maxH = 0; // For API reference

    for ($i = 0; $i < count($lengths); $i++) {
        $qty = intval($boxes[$i] ?? 1);
        $actWt = floatval($actualWeights[$i] ?? 0);
        $chgWt = floatval($chargedWeights[$i] ?? 0);

        $totalBoxes += $qty;
        // Total weight is usually ActWt per box * qty? Or is input Total?
        // In form JS: actWtPerBox. So total = actWt * qty
        $totalActualWeight += ($actWt * $qty);
        $totalChargedWeight += ($chgWt); // Charged weight in form is already (rate * box * qty) ? No, form JS: (chgWtPerBox * boxes)

        $packageDetails[] = [
            'length' => $lengths[$i],
            'width' => $widths[$i],
            'height' => $heights[$i],
            'boxes' => $qty,
            'actual_weight' => $actWt,
            'charged_weight' => $chgWt
        ];

        // Find max dimensions for single entry reference
        if (floatval($lengths[$i]) > $maxL)
            $maxL = floatval($lengths[$i]);
        if (floatval($widths[$i]) > $maxW)
            $maxW = floatval($widths[$i]);
        if (floatval($heights[$i]) > $maxH)
            $maxH = floatval($heights[$i]);
    }

    $packageDetailsJson = json_encode($packageDetails);

    // Convert weights to grams if needed by API? Delhivery uses Grams usually.
    // Assuming UI handles Kg. 1 Kg = 1000g.
    $weightGrams = $totalActualWeight * 1000;

    // 2. Fetch API Credentials
    $stmt = $pdo->prepare("SELECT * FROM tbl_courier_partner WHERE id = :id");
    $stmt->execute([':id' => $courierId]);
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courier) {
        throw new Exception("Invalid Courier Partner ID");
    }

    // 3. Fetch Pickup Point Details (Warehouse Name)
    $stmt = $pdo->prepare("SELECT * FROM tbl_pickup_points WHERE id = :id");
    $stmt->execute([':id' => $pickupPointId]);
    $pickupPoint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pickupPoint) {
        throw new Exception("Invalid Pickup Point ID");
    }

    $warehouseName = $pickupPoint['name'];

    // 4. Prepare Data for Service
    $shipmentData = [
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
        'quantity' => $totalBoxes, // Total pieces
        'weight' => $weightGrams, // Total Actual Weight in Grams
        'length' => $maxL,
        'width' => $maxW,
        'height' => $maxH,
        'shipping_mode' => $shippingMode,
        'pickup_location_name' => $warehouseName,
        'invoice_no' => $invoiceNo,
        'invoice_date' => date('Y-m-d'), // Assuming today
        'invoice_value' => $invoiceValue,
        'ewaybill_no' => $ewaybillNo
    ];

    // 5. Call Courier Service Router
    require_once __DIR__ . '/services/courier_service.php';
    $serviceResponse = syncBookingWithCourier($pdo, $courier, $shipmentData);

    $waybillNo = null;
    $apiResponseJson = null;

    if ($serviceResponse['success']) {
        $waybillNo = $serviceResponse['waybill'] ?? null;

        // If it's Own Courier (ID 2) and no waybill returned, generate a local one
        if (empty($waybillNo) && $courierId == 2) {
            $waybillNo = 'LOCAL' . str_pad(time(), 10, '0', STR_PAD_LEFT);
        }

        $apiResponseJson = json_encode($serviceResponse['api_response'] ?? $serviceResponse);
        if ($apiResponseJson === false)
            $apiResponseJson = json_encode(['status' => 'error', 'message' => 'JSON encoding failed']);
    } else {
        $apiResponseJson = json_encode($serviceResponse['api_response'] ?? $serviceResponse);
        if ($apiResponseJson === false)
            $apiResponseJson = json_encode(['status' => 'error', 'api_failed' => true]);

        throw new Exception("Courier API Failed: " . $serviceResponse['message']);
    }

    $autoOrderNoIns = isset($serviceResponse['auto_order_no']) ? (int)$serviceResponse['auto_order_no'] : null;
    if ($autoOrderNoIns <= 0) {
        $autoOrderNoIns = null;
    }

    // 6. Insert into Database
    $sql = "INSERT INTO tbl_bookings (
        booking_ref_id, auto_order_no, waybill_no, courier_id, pickup_point_id,
        consignee_name, consignee_phone, consignee_email, consignee_gst, consignee_address, consignee_pin,
        consignee_city, consignee_state, consignee_country,
        shipper_name, shipper_phone, shipper_address, shipper_pin, shipper_city, shipper_state,
        payment_mode, cod_amount, weight, length, width, height,
        shipping_mode, product_desc, package_details,
        invoice_no, invoice_value, ewaybill_no,
        rto_name, rto_phone, rto_address,
        api_response, last_status, created_by, created_at
    ) VALUES (
        :ref_id, :auto_order_no, :waybill, :c_id, :p_id,
        :c_name, :c_phone, :c_email, :c_gst, :c_add, :c_pin,
        :c_city, :c_state, :c_country,
        :s_name, :s_phone, :s_add, :s_pin, :s_city, :s_state,
        :pay_mode, :cod, :w, :l, :wi, :h,
        :ship_mode, :prod_desc, :pkg_details,
        :inv_no, :inv_val, :eway,
        :rto_name, :rto_phone, :rto_add,
        :api_resp, 'Created', :user_id, NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ref_id' => $bookingRefId,
        ':auto_order_no' => $autoOrderNoIns,
        ':waybill' => $waybillNo,
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
        ':w' => $weightGrams, // storing gms
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
        ':user_id' => $_SESSION['user_id'] ?? 1
    ]);

    $bookingId = $pdo->lastInsertId();

    // 7. Initial Tracking Entry
    if ($waybillNo) {
        $trackSql = "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, remarks) VALUES (:bid, :wn, 'Booking Created', 'Shipment created locally')";
        $pdo->prepare($trackSql)->execute([':bid' => $bookingId, ':wn' => $waybillNo]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Shipment Booked!', 'waybill' => $waybillNo, 'booking_id' => $bookingId]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>