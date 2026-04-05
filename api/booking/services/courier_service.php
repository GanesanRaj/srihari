<?php
/**
 * Courier router for booking flow.
 * Mirrors api/pickuppoint/services/courier_service.php style.
 */

$BOOKING_COURIER_REGISTRY = [
    [
        'code_prefix' => 'DEL',
        'name_contains' => 'delhivery',
        'file' => 'delhivery.php',
        'create_handler' => 'syncBookingWithDelhivery',
        'track_handler' => 'trackBookingWithDelhivery',
        'cancel_handler' => 'cancelBookingWithDelhivery'
    ],
    [
        'code_prefix' => 'SR',
        'name_contains' => 'shiprocket',
        'file' => 'shiprocket.php',
        'create_handler' => 'syncBookingWithShiprocket',
        'track_handler' => 'trackBookingWithShiprocket',
        'cancel_handler' => 'cancelBookingWithShiprocket'
    ],
    [
        'id' => 2,
        'file' => 'owncourrier.php',
        'create_handler' => 'syncBookingWithOwnCourier',
        'track_handler' => 'trackBookingWithOwnCourier',
        'cancel_handler' => 'cancelBookingWithOwnCourier'
    ],
];

function findBookingCourierHandler($courierData)
{
    global $BOOKING_COURIER_REGISTRY;

    $courierId = intval($courierData['id'] ?? 0);
    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($BOOKING_COURIER_REGISTRY as $courier) {
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

function syncBookingWithCourier($pdo, $courierData, $shipmentData)
{
    $matched = findBookingCourierHandler($courierData);

    // If courier service is not implemented, keep local booking flow successful.
    if (!$matched) {
        return [
            'success' => true,
            'synced' => false,
            'waybill' => null,
            'api_response' => ['status' => 'local_only', 'message' => 'Saved locally (no API sync for this courier)'],
            'message' => 'Saved locally (no API sync for this courier)'
        ];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['create_handler'])) {
        return [
            'success' => false,
            'synced' => false,
            'message' => 'Create handler not found: ' . $matched['create_handler']
        ];
    }

    return $matched['create_handler']($pdo, $courierData, $shipmentData);
}

function trackBookingWithCourier($pdo, $courierData, $waybillNo)
{
    $matched = findBookingCourierHandler($courierData);

    if (!$matched) {
        return [
            'success' => false,
            'message' => 'Tracking is not configured for this courier'
        ];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['track_handler'])) {
        return [
            'success' => false,
            'message' => 'Track handler not found: ' . $matched['track_handler']
        ];
    }

    return $matched['track_handler']($pdo, $courierData, $waybillNo);
}

function cancelBookingWithCourier($pdo, $courierData, $bookingData)
{
    $matched = findBookingCourierHandler($courierData);

    if (!$matched) {
        return [
            'success' => true,
            'message' => 'Local status updated only (no cancel API for this courier)'
        ];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['cancel_handler'])) {
        return [
            'success' => false,
            'message' => 'Cancel handler not found: ' . $matched['cancel_handler']
        ];
    }

    return $matched['cancel_handler']($pdo, $courierData, $bookingData);
}
?>