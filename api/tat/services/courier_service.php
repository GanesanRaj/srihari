<?php
/**
 * TAT Courier Service Router
 * Pattern aligned with api/pickuppoint/services/courier_service.php
 */

$TAT_COURIER_REGISTRY = [
    ['code_prefix' => 'DEL', 'name_contains' => 'delhivery', 'file' => 'delhivery.php', 'handler' => 'getTatWithDelhivery'],
    ['id' => 2, 'name_contains' => 'own', 'file' => 'own.php', 'handler' => 'getTatWithOwn'],
];

function getTatFromCourier($courierData, $tatInput)
{
    global $TAT_COURIER_REGISTRY;

    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($TAT_COURIER_REGISTRY as $courier) {
        $matched = false;

        // Match by ID if set
        if (isset($courier['id']) && $courier['id'] == ($courierData['id'] ?? 0)) {
            $matched = true;
        }

        if (!$matched && !empty($courier['code_prefix']) && strpos($partnerCode, $courier['code_prefix']) === 0) {
            $matched = true;
        }
        if (!$matched && !empty($courier['name_contains']) && strpos($partnerName, $courier['name_contains']) !== false) {
            $matched = true;
        }

        if ($matched) {
            require_once __DIR__ . '/' . $courier['file'];

            if (function_exists($courier['handler'])) {
                return $courier['handler']($courierData, $tatInput);
            }

            return ['success' => false, 'message' => 'Handler not found: ' . $courier['handler']];
        }
    }

    return ['success' => false, 'message' => 'TAT service not configured for this courier'];
}
?>