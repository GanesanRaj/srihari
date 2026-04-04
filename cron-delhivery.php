<?php
// cron-delhivery.php
// Run this file via cron job every X minutes (e.g. 15 mins)

require_once __DIR__ . '/config/config.php';

// Set infinite time limit for cron
set_time_limit(0);

if (php_sapi_name() !== 'cli' && !defined('IN_CREATION')) {
    header('Content-Type: text/plain');
}

echo "--- Starting Tracking Update: " . date('Y-m-d H:i:s') . " ---\n";

try {
    // 1. Fetch shipments to track
    $shipmentWaybill = $_GET['waybill'] ?? null;

    if ($shipmentWaybill) {
        // Single Shipment Mode
        $sql = "SELECT 
                    b.id, b.waybill_no, b.courier_id, b.updated_at,
                    c.partner_code, c.api_url, c.api_key
                FROM tbl_bookings b
                JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.waybill_no = :waybill
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':waybill' => $shipmentWaybill]);
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Cron Batch Mode
        // Limit 50, Order by updated_at DESC (as requested)
        // Filter out completed shipments
        $sql = "SELECT 
                    b.id, b.waybill_no, b.courier_id, b.updated_at,
                    c.partner_code, c.api_url, c.api_key
                FROM tbl_bookings b
                JOIN tbl_courier_partner c ON b.courier_id = c.id
                WHERE b.last_status NOT IN ('Delivered', 'Cancelled', 'RTO Delivered', 'Lost')
                  AND b.waybill_no IS NOT NULL 
                  AND b.waybill_no != ''
                  AND b.courier_id = 1
                ORDER BY b.updated_at DESC
                LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($shipments)) {
        echo $shipmentWaybill ? "Shipment with Waybill $shipmentWaybill not found.\n" : "No active shipments found to track.\n";
        return;
    }

    echo "Found " . count($shipments) . " shipments to track.\n";

    // Group by courier to batch requests
    $batches = [];
    foreach ($shipments as $s) {
        $cid = $s['courier_id'];
        if (!isset($batches[$cid])) {
            $batches[$cid] = [
                'api_url' => $s['api_url'],
                'api_key' => $s['api_key'],
                'partner_code' => $s['partner_code'], // e.g. 'DELHIVERY'
                'items' => []
            ];
        }
        $batches[$cid]['items'][] = $s;
    }

    foreach ($batches as $cid => $batch) {
        $partnerCode = strtoupper($batch['partner_code']);

        // Check if Delhivery
        if (strpos($partnerCode, 'DEL') !== false || strpos(strtolower($batch['api_url']), 'delhivery') !== false) {
            processDelhiveryBatch($pdo, $batch);
        } else {
            echo "Skipping courier ID $cid (Not Delhivery/Not implemented).\n";
        }
    }

    echo "--- Finished Tracking Update: " . date('Y-m-d H:i:s') . " ---\n";

} catch (Exception $e) {
    echo "Top Level Error: " . $e->getMessage() . "\n";
}

// --- Helper Functions ---

function processDelhiveryBatch($pdo, $batch)
{
    // Delhivery allows 50 waybills per request. We already limited total to 50, so one batch is usually enough.
    // But safely chunk anyway.
    $chunks = array_chunk($batch['items'], 50);

    foreach ($chunks as $chunk) {
        $waybills = array_column($chunk, 'waybill_no');
        $waybillMap = []; // map waybill -> booking_id
        foreach ($chunk as $item) {
            $waybillMap[trim($item['waybill_no'])] = $item['id'];
        }

        $wbString = implode(',', $waybills);
        $url = rtrim($batch['api_url'], '/') . '/api/v1/packages/json/?waybill=' . urlencode($wbString) . '&verbose=1';
        $tmout = 30;

        echo "Fetching Delhivery: " . count($waybills) . " items...\n";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $tmout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $batch['api_key'],
                'Accept: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        echo "Raw Response: " . $response . "\n";

        if ($err) {
            echo "cURL Error for batch: $err\n";
            continue;
        }

        $data = json_decode($response, true);

        if (!isset($data['ShipmentData']) || !is_array($data['ShipmentData'])) {
            echo "Invalid API response format.\n";
            continue;
        }

        foreach ($data['ShipmentData'] as $shipmentRes) {
            $shipment = $shipmentRes['Shipment'] ?? null;
            if (!$shipment)
                continue;

            $wb = (string) ($shipment['AWB'] ?? '');

            // Allow for case-insensitive or trim matching
            $matchedId = null;
            foreach ($waybillMap as $mapWb => $mapId) {
                if (strcasecmp(trim($wb), trim($mapWb)) === 0) {
                    $matchedId = $mapId;
                    break;
                }
            }

            if (!$matchedId) {
                echo "Warning: API returned WB '$wb' which was not in our request map.\n";
                continue;
            }

            $bookingId = $matchedId;

            // 1. Update tbl_bookings: store full API response for this AWB (ShipmentData item with "Shipment" wrapper)
            $currentStatus = $shipment['Status']['Status'] ?? '';
            $apiRespForAwb = json_encode($shipmentRes); // full response: {"Shipment":{"AWB":"...","Scans":[...],"Status":{...},...}}
            $updStmt = $pdo->prepare("UPDATE tbl_bookings SET last_status = COALESCE(NULLIF(:st, ''), last_status), api_response = :api_resp, updated_at = NOW() WHERE id = :id");
            $updStmt->execute([
                ':st' => $currentStatus,
                ':api_resp' => $apiRespForAwb,
                ':id' => $bookingId
            ]);

            // 2. Process Scans
            $scans = $shipment['Scans'] ?? [];
            if (empty($scans)) {
                echo "No scans found for WB $wb\n";
            } else {
                echo "Processing " . count($scans) . " scans for WB $wb\n";
            }

            foreach ($scans as $scanObj) {
                $scan = $scanObj['ScanDetail'] ?? null;
                if (!$scan)
                    continue;

                $scanTime = $scan['ScanDateTime'] ?? null;
                $scanLoc = $scan['ScanLocation'] ?? $scan['ScannedLocation'] ?? '';
                $scanType = $scan['Scan'] ?? '';
                $statusCode = $scan['StatusCode'] ?? '';
                $instructions = $scan['Instructions'] ?? '';

                if (!$scanTime)
                    continue;

                $sdt = date('Y-m-d H:i:s', strtotime($scanTime));
                // Store full Shipment (PickUpDate, Destination, Scans[], Status, AWB, ...) per tracking row
                $rawJson = json_encode([$shipmentRes]);

                // Insert or update: avoids duplicate key error (e.g. waybill_no_2 unique)
                $upsertStmt = $pdo->prepare("
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
                $upsertStmt->execute([
                    ':bid' => $bookingId,
                    ':wb' => $wb,
                    ':stype' => $scanType,
                    ':sloc' => $scanLoc,
                    ':sdt' => $sdt,
                    ':scod' => $statusCode,
                    ':rem' => $instructions,
                    ':raw' => $rawJson
                ]);
            }
        }
    }
}
?>