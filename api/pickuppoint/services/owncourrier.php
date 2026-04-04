<?php
/**
 * Own Courier Service Handler
 * 
 * This handler is used when the courier partner is "Own Courier" (ID 2).
 * It simply saves the data locally and doesn't attempt any API calls.
 */

function syncWithOwnCourier($pdo, $courierData, $pickupPointData, $pickupPointId, $action = 'create')
{
    // No external API sync needed for Own Courier
    $message = ($action === 'create') ? 'Pickup point created locally' : 'Pickup point updated locally';

    return [
        'success' => true,
        'message' => $message,
        'synced' => false
    ];
}
?>