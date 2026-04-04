<?php
/**
 * Own Courier Shipment Handler
 */

function syncBookingWithOwnCourier($pdo, $courierData, $shipmentData)
{
    try {
        $waybillNo = generateOwnWaybill($pdo);

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

function updateBookingWithOwnCourier($pdo, $courierData, $shipmentData)
{
    // For Own Courier, updating doesn't require any external API call
    return [
        'success' => true,
        'synced' => true,
        'message' => 'Own Booking: Local data updated',
        'api_response' => ['status' => 'success', 'message' => 'Local update successful']
    ];
}

function generateOwnWaybill($pdo)
{
    $stmt = $pdo->prepare("SELECT waybill_no FROM tbl_bookings WHERE waybill_no LIKE 'SHA%' ORDER BY waybill_no DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return 'SHA001';
    }

    $lastWaybill = $row['waybill_no'];
    if (preg_match('/SHA(\d+)/i', $lastWaybill, $matches)) {
        $number = intval($matches[1]);
        $nextNumber = $number + 1;
        return 'SHA' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    return 'SHA001';
}

function trackBookingWithOwnCourier($pdo, $courierData, $waybillNo)
{
    return [
        'success' => true,
        'current_status' => 'Update',
        'data' => [
            'Scans' => [
                [
                    'ScanDetail' => [
                        'Scan' => 'Update',
                        'ScanDateTime' => date('c'),
                        'Instructions' => 'Local record updated'
                    ]
                ]
            ]
        ],
        'message' => 'Local tracking data'
    ];
}
