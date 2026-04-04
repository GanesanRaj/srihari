<?php
/**
 * Delivery – Get shipment info with all child boxes
 * Location: /apps-api/delivery/get-booking-info.php
 * Method: GET | POST
 * Params:
 *   awb_no    (required) – child AWB (awb_no / child_ewaybill_no) OR parent waybill_no
 *   branch_id (opt)
 * Returns:
 *   booking details, sender, receiver, boxes[] (all child packages with status/images)
 *   summary: total_box, delivered_count, pending_count, attempt_count
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

$awb_no    = trim($req['awb_no']    ?? '');
$branch_id = (int)($req['branch_id'] ?? 0);

if ($awb_no === '') {
    echo json_encode(['status' => 'error', 'message' => 'awb_no is required']);
    exit;
}

try {
    // Resolve AWB → booking (child first, then parent)
    $pkgStmt = $pdo->prepare("SELECT bp.id AS package_id, bp.booking_id
                               FROM tbl_booking_packages bp
                               WHERE LOWER(TRIM(bp.child_ewaybill_no)) = LOWER(TRIM(:awb))
                                  OR LOWER(TRIM(bp.awb_no)) = LOWER(TRIM(:awb))
                               LIMIT 1");
    $pkgStmt->execute([':awb' => $awb_no]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $bookingId = null;
    if ($package) {
        $bookingId = (int)$package['booking_id'];
    } else {
        $parentStmt = $pdo->prepare("SELECT id FROM tbl_bookings WHERE LOWER(TRIM(waybill_no)) = LOWER(TRIM(:awb)) LIMIT 1");
        $parentStmt->execute([':awb' => $awb_no]);
        $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if ($parent) $bookingId = (int)$parent['id'];
    }

    if (!$bookingId) {
        echo json_encode(['status' => 'error', 'message' => 'AWB not found: ' . $awb_no]);
        exit;
    }

    // Get full booking details
    $bookStmt = $pdo->prepare("
        SELECT b.id, b.booking_ref_id, b.waybill_no, b.courier_id, b.last_status,
               b.consignee_name, b.consignee_phone, b.consignee_email, b.consignee_gst,
               b.consignee_address, b.consignee_pin, b.consignee_city, b.consignee_state, b.consignee_country,
               b.shipper_name, b.shipper_phone, b.shipper_address, b.shipper_pin, b.shipper_city, b.shipper_state,
               b.invoice_no, b.invoice_value, b.ewaybill_no,
               COALESCE(b.branch_id, p.branch_id) AS booking_branch_id,
               br.branch_name
        FROM tbl_bookings b
        LEFT JOIN tbl_pickup_points p  ON p.id  = b.pickup_point_id
        LEFT JOIN tbl_branch br        ON br.id = COALESCE(b.branch_id, p.branch_id)
        WHERE b.id = :bid
        LIMIT 1");
    $bookStmt->execute([':bid' => $bookingId]);
    $booking = $bookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    // All child boxes with delivery info
    $boxesStmt = $pdo->prepare("
        SELECT id AS package_id,
               row_no,
               awb_no,
               child_ewaybill_no,
               waybill_no,
               boxes,
               actual_weight,
               vol_weight,
               charged_weight,
               length,
               width,
               height,
               status            AS package_status,
               status_date       AS package_status_date,
               delivery_date     AS package_delivery_date,
               pod_images,
               delivery_pod_images,
               remarks
        FROM tbl_booking_packages
        WHERE booking_id = :bid
        ORDER BY row_no ASC");
    $boxesStmt->execute([':bid' => $bookingId]);
    $rawBoxes = $boxesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build boxes array — decode pod images, count statuses
    $totalBox      = count($rawBoxes);
    $deliveredCount = 0;
    $attemptCount   = 0;
    $pendingCount   = 0;

    $boxes = [];
    foreach ($rawBoxes as $row) {
        $pkgStatus = trim($row['package_status'] ?? $booking['last_status'] ?? '');

        if (strtolower($pkgStatus) === 'delivered') {
            $deliveredCount++;
        } elseif (strtolower($pkgStatus) === 'delivery attempt') {
            $attemptCount++;
        } else {
            $pendingCount++;
        }

        // Read delivery_pod_images first, fall back to pod_images
        $podImages = [];
        $imgRaw = !empty($row['delivery_pod_images']) ? $row['delivery_pod_images']
                : (!empty($row['pod_images'])          ? $row['pod_images'] : null);
        if ($imgRaw) {
            $decoded   = json_decode($imgRaw, true);
            $podImages = is_array($decoded) ? $decoded : [];
        }

        $boxes[] = [
            'package_id'          => (int)$row['package_id'],
            'row_no'              => (int)$row['row_no'],
            'awb_no'              => $row['awb_no'] ?? '',
            'child_ewaybill_no'   => $row['child_ewaybill_no'] ?? '',
            'waybill_no'          => $row['waybill_no'] ?? '',
            'boxes'               => (int)($row['boxes'] ?? 1),
            'actual_weight'       => (float)($row['actual_weight'] ?? 0),
            'vol_weight'          => (float)($row['vol_weight'] ?? 0),
            'charged_weight'      => (float)($row['charged_weight'] ?? 0),
            'length'              => (float)($row['length'] ?? 0),
            'width'               => (float)($row['width'] ?? 0),
            'height'              => (float)($row['height'] ?? 0),
            'status'              => $pkgStatus,
            'status_date'         => $row['package_status_date'] ?? null,
            'delivery_date'       => $row['package_delivery_date'] ?? null,
            'delivery_pod_images' => $podImages,
            'image_count'         => count($podImages),
            'remarks'             => $row['remarks'] ?? '',
        ];
    }

    $alreadyDelivered = ($totalBox > 0 && $deliveredCount >= $totalBox);

    echo json_encode([
        'status' => 'success',
        'data'   => [
            // Booking
            'booking_id'      => (int)$booking['id'],
            'booking_ref_id'  => $booking['booking_ref_id'] ?? '',
            'master_awb'      => $booking['waybill_no'] ?? '',
            'waybill_no'      => $booking['waybill_no'] ?? '',
            'last_status'     => trim($booking['last_status'] ?? ''),
            'already_delivered'=> $alreadyDelivered,
            'invoice_no'      => $booking['invoice_no'] ?? '',
            'invoice_value'   => $booking['invoice_value'] ?? '',
            'ewaybill_no'     => $booking['ewaybill_no'] ?? '',
            'branch_id'       => (int)($booking['booking_branch_id'] ?? 0),
            'branch_name'     => $booking['branch_name'] ?? '',

            // Sender
            'sender' => [
                'name'    => $booking['shipper_name']    ?? '',
                'phone'   => $booking['shipper_phone']   ?? '',
                'address' => $booking['shipper_address'] ?? '',
                'pin'     => $booking['shipper_pin']     ?? '',
                'city'    => $booking['shipper_city']    ?? '',
                'state'   => $booking['shipper_state']   ?? '',
            ],

            // Receiver
            'receiver' => [
                'name'    => $booking['consignee_name']    ?? '',
                'phone'   => $booking['consignee_phone']   ?? '',
                'email'   => $booking['consignee_email']   ?? '',
                'gst'     => $booking['consignee_gst']     ?? '',
                'address' => $booking['consignee_address'] ?? '',
                'pin'     => $booking['consignee_pin']     ?? '',
                'city'    => $booking['consignee_city']    ?? '',
                'state'   => $booking['consignee_state']   ?? '',
                'country' => $booking['consignee_country'] ?? 'India',
            ],

            // Summary
            'total_box'       => $totalBox,
            'delivered_count' => $deliveredCount,
            'attempt_count'   => $attemptCount,
            'pending_count'   => $pendingCount,

            // All child boxes
            'boxes'           => $boxes,
        ],
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
