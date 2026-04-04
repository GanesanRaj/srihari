<?php
// cron-shiprocket.php
// Run via cron every X minutes, or include with $_GET['waybill'] for single sync.

require_once __DIR__ . '/config/config.php';

set_time_limit(0);

if (php_sapi_name() !== 'cli' && !defined('IN_CREATION')) {
    header('Content-Type: text/plain');
}

// echo "--- Starting Shiprocket Tracking Update: " . date('Y-m-d H:i:s') . " ---\n";

try {
    $shipmentWaybill = isset($_GET['waybill']) ? trim((string) $_GET['waybill']) : '';

    if ($shipmentWaybill !== '') {
        $sql = "SELECT
                    b.id, b.waybill_no, b.updated_at,
                    c.partner_code, c.partner_name, c.token
                FROM tbl_bookings b
                JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.waybill_no = :waybill
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':waybill' => $shipmentWaybill]);
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT
                    b.id, b.waybill_no, b.updated_at,
                    c.partner_code, c.partner_name, c.token
                FROM tbl_bookings b
                JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.last_status NOT IN ('Delivered', 'Cancelled', 'RTO Delivered', 'Lost')
                  AND b.waybill_no IS NOT NULL
                  AND b.waybill_no != ''
                  AND (
                      UPPER(COALESCE(c.partner_code, '')) LIKE 'SR%'
                      OR LOWER(COALESCE(c.partner_name, '')) LIKE '%shiprocket%'
                  )
                ORDER BY b.updated_at DESC
                LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($shipments)) {
        echo $shipmentWaybill !== '' ? "Shipment with Waybill {$shipmentWaybill} not found.\n" : "No active Shiprocket shipments found to track.\n";
        return;
    }

    echo "Found " . count($shipments) . " Shiprocket shipments to track.\n";

    foreach ($shipments as $shipment) {
        processShiprocketTracking($pdo, $shipment);
    }

    echo "--- Finished Shiprocket Tracking Update: " . date('Y-m-d H:i:s') . " ---\n";
} catch (Exception $e) {
    echo "Top Level Error: " . $e->getMessage() . "\n";
}

if (!function_exists('processShiprocketTracking')) {
function processShiprocketTracking($pdo, $shipment)
{
    $bookingId = (int) ($shipment['id'] ?? 0);
    $waybillNo = trim((string) ($shipment['waybill_no'] ?? ''));
    $token = trim((string) ($shipment['token'] ?? ''));

    if ($bookingId <= 0 || $waybillNo === '') {
        return;
    }
    if ($token === '') {
        echo "Skipping WB {$waybillNo}: Shiprocket token missing.\n";
        return;
    }
    if (stripos($token, 'bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $url = 'https://apiv2.shiprocket.in/v1/external/courier/track/awb/' . rawurlencode($waybillNo);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== '') {
        echo "cURL Error for WB {$waybillNo}: {$err}\n";
        return;
    }

    $data = json_decode((string) $response, true);
    if (!is_array($data)) {
        echo "Invalid JSON for WB {$waybillNo}\n";
        return;
    }

    if ($httpCode === 404) {
        echo "Shiprocket tracking 404 for WB {$waybillNo}\n";
        return;
    }

    $trackingData = $data['tracking_data'] ?? null;
    if (!is_array($trackingData)) {
        echo "Missing tracking_data for WB {$waybillNo}\n";
        return;
    }

    $activities = $trackingData['shipment_track_activities'] ?? [];
    $shipmentTrack = $trackingData['shipment_track'][0] ?? [];

    $latestStatus = trim((string) ($shipmentTrack['current_status'] ?? ''));
    $latestLocation = trim((string) ($shipmentTrack['destination'] ?? $shipmentTrack['delivered_to'] ?? ''));
    $latestDate = trim((string) ($shipmentTrack['delivered_date'] ?? $shipmentTrack['pickup_date'] ?? ''));
    $latestCode = trim((string) ($trackingData['shipment_status'] ?? ''));
    $latestRemarks = trim((string) ($trackingData['error'] ?? ''));

    $history = [];
    if (is_array($activities)) {
        foreach ($activities as $event) {
            $evDateRaw = trim((string) ($event['date'] ?? ''));
            $evDate = normalizeShiprocketDate($evDateRaw);
            $evStatus = trim((string) ($event['sr-status-label'] ?? $event['activity'] ?? $event['status'] ?? ''));
            $evLocation = trim((string) ($event['location'] ?? ''));
            $evCode = trim((string) ($event['sr-status'] ?? $event['status'] ?? ''));
            $evRemarks = trim((string) ($event['activity'] ?? ''));

            $history[] = [
                'status' => $evStatus,
                'location' => $evLocation,
                'datetime' => $evDate,
                'status_code' => $evCode,
                'remarks' => $evRemarks,
            ];
        }
    }

    if (!empty($history)) {
        $latest = $history[0];
        $latestStatus = $latest['status'] !== '' ? $latest['status'] : $latestStatus;
        $latestLocation = $latest['location'] !== '' ? $latest['location'] : $latestLocation;
        $latestDate = $latest['datetime'] !== '' ? $latest['datetime'] : normalizeShiprocketDate($latestDate);
        $latestCode = $latest['status_code'] !== '' ? $latest['status_code'] : $latestCode;
        $latestRemarks = $latest['remarks'] !== '' ? $latest['remarks'] : $latestRemarks;
    } else {
        $latestDate = normalizeShiprocketDate($latestDate);
    }

    if ($latestDate === '') {
        $latestDate = date('Y-m-d H:i:s');
    }
    if ($latestStatus === '') {
        $latestStatus = 'Tracking Pending';
    }

    $rawPayload = [
        'awb_no' => $waybillNo,
        'provider' => 'shiprocket',
        'current_status' => $latestStatus,
        'scan_details' => [
            'status' => $latestStatus,
            'location' => $latestLocation,
            'datetime' => $latestDate,
            'remarks' => $latestRemarks,
            'status_code' => $latestCode,
        ],
        'scan_details_history' => $history,
        'tracking_data' => $trackingData,
    ];

    $upsert = $pdo->prepare("
        INSERT INTO tbl_tracking
            (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response, created_at, updated_at)
        VALUES
            (:bid, :wb, :stype, :sloc, :sdt, :scod, :rem, :raw, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            booking_id = VALUES(booking_id),
            scan_type = VALUES(scan_type),
            scan_location = VALUES(scan_location),
            scan_datetime = VALUES(scan_datetime),
            status_code = VALUES(status_code),
            remarks = VALUES(remarks),
            raw_response = VALUES(raw_response),
            updated_at = NOW()
    ");
    $upsert->execute([
        ':bid' => $bookingId,
        ':wb' => $waybillNo,
        ':stype' => $latestStatus,
        ':sloc' => $latestLocation,
        ':sdt' => $latestDate,
        ':scod' => $latestCode,
        ':rem' => $latestRemarks,
        ':raw' => json_encode($rawPayload),
    ]);

    $bookingUpd = $pdo->prepare("UPDATE tbl_bookings SET last_status = :st, updated_at = NOW() WHERE id = :id");
    $bookingUpd->execute([
        ':st' => $latestStatus,
        ':id' => $bookingId,
    ]);

    echo "Updated tracking for WB {$waybillNo}: {$latestStatus}\n";
}
}

if (!function_exists('normalizeShiprocketDate')) {
function normalizeShiprocketDate($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d H:i:s', $ts);
}
}
?>
