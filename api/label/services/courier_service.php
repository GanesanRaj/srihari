<?php
/**
 * Label Service Router
 * Pattern aligned with api/pickuppoint/services/courier_service.php
 */

$LABEL_COURIER_REGISTRY = [
    ['code_prefix' => 'DEL', 'name_contains' => 'delhivery', 'file' => 'delhivery.php', 'handler' => 'generateLabelWithDelhivery'],
    ['code_prefix' => 'OWN', 'name_contains' => 'own', 'file' => 'owncourier.php', 'handler' => 'generateLabelWithOwnCourier'],
];

function generateLabelFromCourier($courierData, $labelInput)
{
    global $LABEL_COURIER_REGISTRY;

    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($LABEL_COURIER_REGISTRY as $courier) {
        $matched = false;

        if (!empty($courier['code_prefix']) && strpos($partnerCode, $courier['code_prefix']) === 0) {
            $matched = true;
        }
        if (!$matched && !empty($courier['name_contains']) && strpos($partnerName, $courier['name_contains']) !== false) {
            $matched = true;
        }

        if ($matched) {
            require_once __DIR__ . '/' . $courier['file'];

            if (function_exists($courier['handler'])) {
                return $courier['handler']($courierData, $labelInput);
            }

            return ['success' => false, 'message' => 'Handler not found: ' . $courier['handler']];
        }
    }

    // Fallback: Use own courier handler for unmatched couriers
    require_once __DIR__ . '/owncourier.php';
    if (function_exists('generateLabelWithOwnCourier')) {
        return generateLabelWithOwnCourier($courierData, $labelInput);
    }

    return ['success' => false, 'message' => 'Label service not configured for this courier'];
}
?>
