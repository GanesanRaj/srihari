<?php
/**
 * Own Courier Booking Handler
 * 
 * Used for "Own Booking" (Courier Partner ID 2).
 * Records the shipment locally without any external API sync.
 */

function syncBookingWithOwnCourier($pdo, $courierData, $shipmentData)
{
    try {
        // Use preferred AWB from serial allocation if provided; otherwise SHA series
        $waybillNo = isset($shipmentData['preferred_waybill']) && trim($shipmentData['preferred_waybill']) !== ''
            ? trim($shipmentData['preferred_waybill'])
            : generateOwnWaybill($pdo);

        return [
            'success' => true,
            'synced' => true,
            'waybill' => $waybillNo,
            'api_response' => [
                'status' => 'success',
                'message' => 'Own Booking: Waybill Generated',
                'waybill' => $waybillNo
            ],
            'message' => 'Shipment booked locally with AWB: ' . $waybillNo
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'AWB Generation Failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Generates the next waybill in the SHA series (SHA001, SHA002, ...)
 */
function generateOwnWaybill($pdo)
{
    // Find the latest waybill starting with SHA
    $stmt = $pdo->prepare("SELECT waybill_no FROM tbl_bookings WHERE waybill_no LIKE 'SHA%' ORDER BY waybill_no DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return 'SHA001';
    }

    $lastWaybill = $row['waybill_no'];
    // Extract numeric part from SHAxxx
    if (preg_match('/SHA(\d+)/i', $lastWaybill, $matches)) {
        $number = intval($matches[1]);
        $nextNumber = $number + 1;
        // Keep at least 3 digits as per SHA001
        return 'SHA' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    return 'SHA001';
}

function trackBookingWithOwnCourier($pdo, $courierData, $waybillNo)
{
    return [
        'success' => true,
        'current_status' => 'Created',
        'data' => [
            'Scans' => [
                [
                    'ScanDetail' => [
                        'Scan' => 'Booking Created',
                        'ScanDateTime' => date('c'),
                        'Instructions' => 'Shipment created locally via Own Booking'
                    ]
                ]
            ]
        ],
        'message' => 'Local tracking data'
    ];
}
?>