<?php
/**
 * Booking Edit/Update API (No Session)
 * Location: /apps-api/booking/edit.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

try {
    $id = $req['id'] ?? null;
    if (!$id) throw new Exception("Booking ID is required");

    $updatedBy = $req['user_id'] ?? 1;

    $stmtExist = $pdo->prepare("SELECT * FROM tbl_bookings WHERE id = :id");
    $stmtExist->execute([':id' => $id]);
    $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);
    if (!$existing) throw new Exception("Shipment not found");

    $courierId = isset($req['courier_id']) ? (int)$req['courier_id'] : $existing['courier_id'];
    $pickupPointId = isset($req['pickup_point_id']) ? (int)$req['pickup_point_id'] : $existing['pickup_point_id'];

    $lengths = (array)($req['length'] ?? []);
    if (!empty($lengths)) {
        $widths = (array)($req['width'] ?? []);
        $heights = (array)($req['height'] ?? []);
        $boxes = (array)($req['boxes'] ?? []);
        $actualWeights = (array)($req['actual_weight'] ?? []);
        $chargedWeights = (array)($req['charged_weight'] ?? []);

        $packageDetails = [];
        $totalActualWeight = 0; $totalBoxes = 0; $maxL = 0; $maxW = 0; $maxH = 0;

        foreach ($lengths as $i => $len) {
            $qty = max(1, (int)($boxes[$i] ?? 1));
            $actWt = floatval($actualWeights[$i] ?? 0);
            $totalBoxes += $qty;
            $totalActualWeight += ($actWt * $qty);
            $packageDetails[] = [
                'length' => (float)$len, 'width' => (float)($widths[$i] ?? 0), 'height' => (float)($heights[$i] ?? 0),
                'boxes' => $qty, 'actual_weight' => $actWt, 'charged_weight' => floatval($chargedWeights[$i] ?? 0)
            ];
            $maxL = max($maxL, (float)$len); $maxW = max($maxW, (float)($widths[$i] ?? 0)); $maxH = max($maxH, (float)($heights[$i] ?? 0));
        }
        $packageDetailsJson = json_encode($packageDetails);
        $weightGrams = $totalActualWeight * 1000;
    } else {
        $packageDetailsJson = $existing['package_details'];
        $weightGrams = $existing['weight'];
        $totalBoxes = $existing['quantity'];
        $maxL = $existing['length']; $maxW = $existing['width']; $maxH = $existing['height'];
    }

    $sql = "UPDATE tbl_bookings SET 
        courier_id = :c_id, pickup_point_id = :p_id,
        consignee_name = :c_name, consignee_phone = :c_phone, consignee_address = :c_add, consignee_pin = :c_pin, consignee_city = :c_city, consignee_state = :c_state,
        shipper_name = :s_name, shipper_phone = :s_phone, shipper_address = :s_add, shipper_pin = :s_pin, shipper_city = :s_city, shipper_state = :s_state,
        payment_mode = :pay_mode, cod_amount = :cod, weight = :w, quantity = :qty, length = :l, width = :wi, height = :h,
        product_desc = :prod_desc, package_details = :pkg_details, invoice_no = :inv_no, invoice_value = :inv_val, ewaybill_no = :eway,
        rto_name = :rto_name, rto_phone = :rto_phone, rto_address = :rto_address,
        updated_by = :user_id, updated_at = NOW()
    WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':c_id' => $courierId, ':p_id' => $pickupPointId,
        ':c_name' => $req['consignee_name'] ?? $existing['consignee_name'], ':c_phone' => $req['consignee_phone'] ?? $existing['consignee_phone'],
        ':c_add' => $req['consignee_address'] ?? $existing['consignee_address'], ':c_pin' => $req['consignee_pin'] ?? $existing['consignee_pin'],
        ':c_city' => $req['consignee_city'] ?? $existing['consignee_city'], ':c_state' => $req['consignee_state'] ?? $existing['consignee_state'],
        ':s_name' => $req['shipper_name'] ?? $existing['shipper_name'], ':s_phone' => $req['shipper_phone'] ?? $existing['shipper_phone'],
        ':s_add' => $req['shipper_address'] ?? $existing['shipper_address'], ':s_pin' => $req['shipper_pin'] ?? $existing['shipper_pin'],
        ':s_city' => $req['shipper_city'] ?? $existing['shipper_city'], ':s_state' => $req['shipper_state'] ?? $existing['shipper_state'],
        ':pay_mode' => $req['payment_mode'] ?? $existing['payment_mode'], ':cod' => $req['cod_amount'] ?? $existing['cod_amount'],
        ':w' => $weightGrams, ':qty' => $totalBoxes, ':l' => $maxL, ':wi' => $maxW, ':h' => $maxH,
        ':prod_desc' => $req['product_desc'] ?? $existing['product_desc'], ':pkg_details' => $packageDetailsJson,
        ':inv_no' => $req['invoice_no'] ?? $existing['invoice_no'], ':inv_val' => $req['invoice_value'] ?? $existing['invoice_value'],
        ':eway' => $req['ewaybill_no'] ?? $existing['ewaybill_no'],
        ':rto_name' => $req['rto_name'] ?? $existing['rto_name'], ':rto_phone' => $req['rto_phone'] ?? $existing['rto_phone'], ':rto_address' => $req['rto_address'] ?? $existing['rto_address'],
        ':user_id' => $updatedBy, ':id' => $id
    ]);

    if (!empty($lengths)) {
        $pdo->prepare("DELETE FROM tbl_booking_packages WHERE booking_id = ?")->execute([$id]);
        $pkgStmt = $pdo->prepare("INSERT INTO tbl_booking_packages (booking_id, waybill_no, row_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, charged_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($packageDetails as $idx => $pkg) {
            $rowAwb = ($existing['courier_id'] == 2 && !empty($existing['waybill_no'])) ? (($idx === 0) ? $existing['waybill_no'] : $existing['waybill_no'] . '-' . $idx) : ($pkg['awb_no'] ?? '');
            $childEway = !empty($pkg['child_ewaybill_no']) ? $pkg['child_ewaybill_no'] : null;
            $pkgStmt->execute([$id, $existing['waybill_no'], $idx+1, $rowAwb, $childEway, $pkg['length'], $pkg['width'], $pkg['height'], $pkg['boxes'], $pkg['actual_weight'], $pkg['charged_weight']]);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Shipment updated successfully!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
