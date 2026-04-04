<?php
/**
 * Settings API – Status Dropdowns
 * Location: /apps-api/settings/statuses.php
 * Method: GET
 * Params:
 *   type (opt) – booking | tag | manifest | all (default: all)
 *
 * Returns status lists for use in mobile app dropdowns
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

$type = trim($req['type'] ?? 'all');

// Booking / Shipment statuses
$booking_statuses = [
    ['value' => '',                 'label' => 'All'],
    ['value' => 'Pending',          'label' => 'Pending'],
    ['value' => 'Booked',           'label' => 'Booked'],
    ['value' => 'Picked Up',        'label' => 'Picked Up'],
    ['value' => 'In Transit',       'label' => 'In Transit'],
    ['value' => 'Manifested',       'label' => 'Manifested'],
    ['value' => 'Received',         'label' => 'Received'],
    ['value' => 'Out for Delivery', 'label' => 'Out for Delivery'],
    ['value' => 'Delivered',        'label' => 'Delivered'],
    ['value' => 'RTO',              'label' => 'RTO'],
    ['value' => 'Cancelled',        'label' => 'Cancelled'],
    ['value' => 'Hold',             'label' => 'Hold'],
    ['value' => 'Lost',             'label' => 'Lost'],
    ['value' => 'Delivery Failed',  'label' => 'Delivery Failed'],
    ['value' => 'Returned',         'label' => 'Returned'],
];

// Tag statuses
$tag_statuses = [
    ['value' => '',                  'label' => 'All'],
    ['value' => 'packed',            'label' => 'Packed'],
    ['value' => 'hold',              'label' => 'Hold'],
    ['value' => 'partially_verified','label' => 'Partially Verified'],
    ['value' => 'fully_verified',    'label' => 'Fully Verified'],
];

// Manifest statuses
$manifest_statuses = [
    ['value' => '',           'label' => 'All'],
    ['value' => 'draft',      'label' => 'Draft'],
    ['value' => 'dispatched', 'label' => 'Dispatched'],
    ['value' => 'received',   'label' => 'Received'],
];

$result = [];

if ($type === 'booking') {
    $result = ['booking' => $booking_statuses];
} elseif ($type === 'tag') {
    $result = ['tag' => $tag_statuses];
} elseif ($type === 'manifest') {
    $result = ['manifest' => $manifest_statuses];
} else {
    $result = [
        'booking'  => $booking_statuses,
        'tag'      => $tag_statuses,
        'manifest' => $manifest_statuses,
    ];
}

echo json_encode(['status' => 'success', 'data' => $result]);
