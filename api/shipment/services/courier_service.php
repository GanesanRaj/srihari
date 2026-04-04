<?php
/**
 * Shipment Courier Service Router
 */

$SHIPMENT_COURIER_REGISTRY = [
    [
        'code_prefix' => 'DEL',
        'name_contains' => 'delhivery',
        'file' => 'delhivery.php',
        'create_handler' => 'createShipmentDelhivery',
        'update_handler' => 'updateShipmentDelhivery',
        'track_handler' => 'trackShipmentDelhivery'
    ],
    [
        'id' => 2,
        'file' => 'owncourrier.php',
        'create_handler' => 'syncBookingWithOwnCourier',
        'update_handler' => 'updateBookingWithOwnCourier',
        'track_handler' => 'trackBookingWithOwnCourier'
    ],
];

function findShipmentCourierHandler($courierData)
{
    global $SHIPMENT_COURIER_REGISTRY;

    $courierId = intval($courierData['id'] ?? 0);
    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($SHIPMENT_COURIER_REGISTRY as $courier) {
        $matched = false;

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
    $matched = findShipmentCourierHandler($courierData);

    if (!$matched) {
        return [
            'success' => true,
            'synced' => false,
            'waybill' => null,
            'api_response' => ['status' => 'local_only', 'message' => 'Saved locally (no API sync)'],
            'message' => 'Saved locally'
        ];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['create_handler'])) {
        return ['success' => false, 'message' => 'Handler not found: ' . $matched['create_handler']];
    }

    return $matched['create_handler']($pdo, $courierData, $shipmentData);
}

function updateBookingWithCourier($pdo, $courierData, $shipmentData)
{
    $matched = findShipmentCourierHandler($courierData);

    if (!$matched) {
        return [
            'success' => true,
            'synced' => false,
            'api_response' => ['status' => 'local_only', 'message' => 'Updated locally (no API sync)'],
            'message' => 'Updated locally'
        ];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['update_handler'])) {
        // If update handler is not mandatory, return locally success
        return [
            'success' => true,
            'synced' => false,
            'message' => 'Update handler not implemented, updating locally only'
        ];
    }

    return $matched['update_handler']($pdo, $courierData, $shipmentData);
}

function trackBookingWithCourier($pdo, $courierData, $waybillNo)
{
    $matched = findShipmentCourierHandler($courierData);

    if (!$matched) {
        return ['success' => false, 'message' => 'Tracking not configured'];
    }

    require_once __DIR__ . '/' . $matched['file'];

    if (!function_exists($matched['track_handler'])) {
        return ['success' => false, 'message' => 'Track handler not found'];
    }

    return $matched['track_handler']($pdo, $courierData, $waybillNo);
}
