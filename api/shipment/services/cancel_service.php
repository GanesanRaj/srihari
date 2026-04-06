<?php
/**
 * Cancel Service Router
 * Mirrors shipment service pattern for cancellation
 */

$CANCEL_COURIER_REGISTRY = [
    [
        'code_prefix' => 'DEL',
        'name_contains' => 'delhivery',
        'file' => 'delhivery.php',
        'cancel_handler' => 'cancelBookingWithDelhivery'
    ],
    [
        'code_prefix' => 'SR',
        'name_contains' => 'shiprocket',
        'file' => 'shiprocket.php',
        'cancel_handler' => 'cancelBookingWithShiprocket'
    ],
    [
        'id' => 2,
        'file' => 'owncourrier.php',
        'cancel_handler' => 'cancelBookingWithOwnCourier'
    ],
];

function findCancelCourierHandler($courierData)
{
    global $CANCEL_COURIER_REGISTRY;

    $courierId = intval($courierData['id'] ?? 0);
    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($CANCEL_COURIER_REGISTRY as $courier) {
        $matched = false;

        // Check by ID first (for Own Courier ID 2)
        if (isset($courier['id']) && $courier['id'] == $courierId) {
            $matched = true;
        }

        if (!$matched && !empty($courier['code_prefix']) && strpos($partnerCode, $courier['code_prefix']) === 0) {
            $matched = true;
        }
        if (!$matched && !empty($courier['name_contains']) && strpos($partnerName, $courier['name_contains']) !== false) {
            $matched = true;
        }

        if ($matched) {
            return $courier;
        }
    }

    return null;
}

function cancelBookingWithCourierService($pdo, $courierData, $bookingData)
{
    $matched = findCancelCourierHandler($courierData);

    if (!$matched) {
        return [
            'success' => true,
            'message' => 'Local status updated only (no cancel API for this courier)'
        ];
    }

    require_once __DIR__ . '/../../booking/services/' . $matched['file'];

    if (!function_exists($matched['cancel_handler'])) {
        return [
            'success' => false,
            'message' => 'Cancel handler not found: ' . $matched['cancel_handler']
        ];
    }

    return $matched['cancel_handler']($pdo, $courierData, $bookingData);
}
?>
