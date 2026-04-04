<?php
/**
 * Delhivery tracking – delegates to booking Delhivery service.
 */

require_once __DIR__ . '/../../booking/services/delhivery.php';

function trackWithDelhivery($pdo, $courierData, $waybillNo)
{
    return trackBookingWithDelhivery($pdo, $courierData, $waybillNo);
}
