<?php
/**
 * Courier Service - Routes pickup point sync to the correct courier file
 * 
 * To add a new courier:
 *   1. Create a new file in services/ (e.g., bluedart.php)
 *   2. Add an entry to $COURIER_REGISTRY below
 */

// ============================================================================
// COURIER REGISTRY
// ============================================================================
// 'code_prefix'   → matches if partner_code starts with this
// 'name_contains' → matches if partner_name contains this (case-insensitive)
// 'file'          → file to include from services/ folder
// 'handler'       → function name inside that file

$COURIER_REGISTRY = [
    ['code_prefix' => 'DEL', 'name_contains' => 'delhivery', 'file' => 'delhivery.php', 'handler' => 'syncWithDelhivery'],
    ['id' => 2, 'file' => 'owncourrier.php', 'handler' => 'syncWithOwnCourier'],
    ['code_prefix' => 'SR', 'name_contains' => 'shiprocket', 'file' => 'shiprocket.php', 'handler' => 'syncWithShiprocket'],
];

// ============================================================================
// ROUTER
// ============================================================================

function syncPickupPointWithCourier($pdo, $courierData, $pickupPointData, $pickupPointId, $action = 'create')
{
    global $COURIER_REGISTRY;

    $courierId = intval($courierData['id'] ?? 0);
    $partnerCode = strtoupper(trim($courierData['partner_code'] ?? ''));
    $partnerName = strtolower(trim($courierData['partner_name'] ?? ''));

    foreach ($COURIER_REGISTRY as $courier) {
        $matched = false;

        // Check if matched by ID specifically (for Own Courier ID 2)
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
            // Load courier file
            require_once __DIR__ . '/' . $courier['file'];

            if (function_exists($courier['handler'])) {
                return $courier['handler']($pdo, $courierData, $pickupPointData, $pickupPointId, $action);
            }

            return ['success' => false, 'message' => 'Handler not found: ' . $courier['handler'], 'synced' => false];
        }
    }

    // No matching courier - save locally
    return ['success' => true, 'message' => 'Saved locally (no API sync for this courier)', 'synced' => false];
}
?>