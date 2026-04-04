<?php
/**
 * Courier Service - Routes tracking to the correct courier implementation.
 *
 * To add a new courier:
 *   1. Create a new file in services/ (e.g., bluedart.php)
 *   2. Add an entry to $COURIER_REGISTRY below
 */

$COURIER_REGISTRY = [
    ['code_prefix' => 'DEL', 'name_contains' => 'delhivery', 'file' => 'delhivery.php', 'handler' => 'trackWithDelhivery'],
    // ['code_prefix' => 'BLUEDART', 'name_contains' => 'bluedart',  'file' => 'bluedart.php',  'handler' => 'trackWithBluedart'],
];

function findTrackingCourierHandler($courierData)
{
    global $COURIER_REGISTRY;

    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($COURIER_REGISTRY as $courier) {
        $matched = false;
        if (!empty($courier['code_prefix']) && strpos($partnerCode, $courier['code_prefix']) === 0) {
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

function trackWithCourier($pdo, $courierData, $waybillNo)
{
    $courier = findTrackingCourierHandler($courierData);

    if (!$courier) {
        return ['success' => false, 'message' => 'Tracking is not configured for this courier'];
    }

    require_once __DIR__ . '/' . $courier['file'];

    if (!function_exists($courier['handler'])) {
        return ['success' => false, 'message' => 'Handler not found: ' . $courier['handler']];
    }

    return $courier['handler']($pdo, $courierData, $waybillNo);
}
