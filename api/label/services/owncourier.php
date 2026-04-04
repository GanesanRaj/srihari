<?php
/**
 * Own Courier Label Generator
 * Generates label data directly from booking database without external API
 */

function generateLabelWithOwnCourier($courierData, $labelInput)
{
    global $pdo;

    try {
        $waybill = trim($labelInput['waybill'] ?? '');

        if ($waybill === '') {
            return [
                'success' => false,
                'message' => 'Waybill number is required'
            ];
        }

        // Query booking data.
        // Support both:
        // - current master waybill in tbl_bookings.waybill_no
        // - per-package AWB in tbl_booking_packages.awb_no (after AWB reassignment flows)
        $stmt = $pdo->prepare("
            SELECT
                b.*,
                p.name AS pickup_name,
                p.address AS pickup_address,
                p.city AS pickup_city,
                p.pin AS pickup_pin,
                co.company_name,
                co.company_logo
            FROM tbl_bookings b
            LEFT JOIN tbl_pickup_points p ON p.id = b.pickup_point_id
            LEFT JOIN tbl_branch br ON br.id = p.branch_id
            LEFT JOIN tbl_company co ON co.id = COALESCE(p.company_id, br.company_id)
            LEFT JOIN tbl_booking_packages bp ON bp.booking_id = b.id
            WHERE (b.waybill_no = :waybill OR bp.awb_no = :waybill)
            LIMIT 1
        ");

        $stmt->execute([':waybill' => $waybill]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found for waybill: ' . $waybill
            ];
        }

        // Parse package details from JSON if available
        $packageDetails = [];
        if (!empty($booking['package_details'])) {
            $decoded = json_decode($booking['package_details'], true);
            if (is_array($decoded)) {
                $packageDetails = $decoded;
            }
        }

        // If no package details, create a default single package
        if (empty($packageDetails)) {
            $packageDetails = [[
                'length' => $booking['length'] ?? 0,
                'width' => $booking['width'] ?? 0,
                'height' => $booking['height'] ?? 0,
                'boxes' => 1,
                'actual_weight' => $booking['actual_weight'] ?? 0,
                'charged_weight' => $booking['charged_weight'] ?? 0
            ]];
        }

        // Get courier name from passed data
        $courierName = trim($courierData['partner_name'] ?? '');

        // Build response in Delhivery-compatible format
        $packages = [];

        foreach ($packageDetails as $idx => $pkg) {
            $packages[] = [
                'wbn' => $booking['waybill_no'],
                'mwn' => $booking['waybill_no'], // Same for own courier (no MPS)
                'pin' => $booking['consignee_pin'] ?? '',
                'name' => $booking['consignee_name'] ?? '',
                'address' => $booking['consignee_address'] ?? '',
                'destination' => ($booking['consignee_city'] ?? '') . ', ' . ($booking['consignee_state'] ?? ''),
                'pt' => $booking['payment_mode'] ?? 'Prepaid',
                'mot' => $booking['shipping_mode'] === 'Surface' ? 'S' : 'E',
                'rs' => floatval($booking['invoice_value'] ?? 0),
                'cod' => floatval($booking['cod_amount'] ?? 0),
                'prd' => $booking['product_desc'] ?? 'Item',
                'snm' => $booking['pickup_name'] ?? $booking['company_name'] ?? '',
                'sadd' => ($booking['pickup_address'] ?? '') . ', ' .
                         ($booking['pickup_city'] ?? '') . ' - ' .
                         ($booking['pickup_pin'] ?? ''),
                'oid' => $booking['booking_ref_id'] ?? '',
                'radd' => $booking['rto_address'] ?? $booking['pickup_address'] ?? '',
                'rpin' => $booking['rto_pin'] ?? $booking['pickup_pin'] ?? '',
                'cd' => $booking['created_at'] ?? date('Y-m-d H:i:s'),
                'sort_code' => '', // Own courier doesn't have sort code
                'delhivery_logo' => '', // No courier logo for own courier
                'courier_name' => $courierName, // Add courier name for display
            ];
        }

        return [
            'success' => true,
            'response' => [
                'packages' => $packages
            ]
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error generating label: ' . $e->getMessage()
        ];
    }
}
