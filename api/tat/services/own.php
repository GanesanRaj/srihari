<?php
/**
 * Own Courier TAT Handler
 */

function getTatWithOwn($courierData, $tatInput)
{
    return [
        'success' => true,
        'tat_days' => 3,
        'expected_delivery_date' => date('Y-m-d', strtotime('+3 days')),
        'message' => 'Standard local delivery TAT (3 days)',
        'response' => ['status' => 'success']
    ];
}
