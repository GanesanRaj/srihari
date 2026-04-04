<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $waybill = isset($_GET['waybill']) ? trim($_GET['waybill']) : '';

    if ($id <= 0 && $waybill === '') {
        echo json_encode(['status' => 'error', 'message' => 'Provide id or waybill']);
        exit;
    }

    // Resolve child AWB → parent booking_id if waybill not found directly in tbl_bookings
    $childAwb = null;
    if ($id <= 0 && $waybill !== '') {
        $chkPkg = $pdo->prepare("SELECT booking_id, awb_no FROM tbl_booking_packages WHERE awb_no = :awb LIMIT 1");
        $chkPkg->execute([':awb' => $waybill]);
        $pkgRow = $chkPkg->fetch(PDO::FETCH_ASSOC);
        if ($pkgRow) {
            $childAwb = $waybill;
            $id       = (int) $pkgRow['booking_id'];
            $waybill  = '';
        }
    }

    $sql = "SELECT b.*,
            c.partner_name as courier_name, c.partner_code as courier_code,
            p.name as pickup_point_name, p.pickup_point_code, p.address as pickup_address, p.phone as pickup_phone, p.city as pickup_city, p.pin as pickup_pin, p.branch_id as branch_id,
            br.branch_name, co.company_name,
            (SELECT GROUP_CONCAT(bp.awb_no ORDER BY bp.row_no ASC SEPARATOR ',')
             FROM tbl_booking_packages bp WHERE bp.booking_id = b.id) AS child_awbs,
            (SELECT GROUP_CONCAT(bp2.pod_images ORDER BY bp2.row_no ASC SEPARATOR '|||')
             FROM tbl_booking_packages bp2
             WHERE bp2.booking_id = b.id AND bp2.pod_images IS NOT NULL AND bp2.pod_images != '[]' AND bp2.pod_images != '') AS pod_images_raw,
            (SELECT GROUP_CONCAT(bp3.delivery_pod_images ORDER BY bp3.row_no ASC SEPARATOR '|||')
             FROM tbl_booking_packages bp3
             WHERE bp3.booking_id = b.id AND bp3.delivery_pod_images IS NOT NULL AND bp3.delivery_pod_images != '[]' AND bp3.delivery_pod_images != '') AS delivery_pod_images_raw,
            t.raw_response AS tracking_raw
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
            LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
            LEFT JOIN tbl_branch br ON p.branch_id = br.id
            LEFT JOIN tbl_company co ON br.company_id = co.id
            LEFT JOIN tbl_tracking t ON t.waybill_no = b.waybill_no
            WHERE 1=1";
    $params = [];

    if ($id > 0) {
        $sql .= " AND b.id = :id";
        $params[':id'] = $id;
    } else {
        $sql .= " AND b.waybill_no = :waybill";
        $params[':waybill'] = $waybill;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    // api_response is stored as JSON (full ShipmentData item: {"Shipment":{...}})
    if (!empty($row['api_response'])) {
        $row['ShipmentData'] = is_string($row['api_response']) ? json_decode($row['api_response'], true) : $row['api_response'];
    } else {
        $row['ShipmentData'] = null;
    }

    // Helper: decode GROUP_CONCAT of JSON arrays (separated by |||) into flat image list
    $decodePkgImages = function ($raw) {
        $urls = [];
        if (empty($raw)) return $urls;
        foreach (explode('|||', $raw) as $chunk) {
            $arr = json_decode($chunk, true);
            if (is_array($arr)) {
                foreach ($arr as $u) {
                    if (!empty($u)) $urls[] = ltrim((string)$u, '/');
                }
            }
        }
        return $urls;
    };

    // Build pickup_pod and delivery_pod from tracking history + package images
    $row['pickup_pod']   = [];
    $row['delivery_pod'] = [];
    if (!empty($row['tracking_raw'])) {
        $trackData = json_decode($row['tracking_raw'], true);
        $history   = $trackData['scan_details_history'] ?? [];
        foreach ($history as $scan) {
            $scanDate   = $scan['datetime'] ?? $scan['ScanDateTime'] ?? $scan['updated_at'] ?? '';
            $st         = strtolower($scan['status'] ?? '');
            $pickupImgs = $scan['pod_images'] ?? [];
            if (!empty($pickupImgs) && is_array($pickupImgs) && strpos($st, 'pick') !== false) {
                foreach ($pickupImgs as $url) {
                    if (!empty($url)) $row['pickup_pod'][] = ['url' => ltrim((string)$url, '/'), 'date' => $scanDate];
                }
            }
            $delivImgs = $scan['delivery_pod_images'] ?? [];
            if (!empty($delivImgs) && is_array($delivImgs)) {
                foreach ($delivImgs as $url) {
                    if (!empty($url)) $row['delivery_pod'][] = ['url' => ltrim((string)$url, '/'), 'date' => $scanDate];
                }
            }
        }
    }
    $pkgPickupUrls = $decodePkgImages($row['pod_images_raw'] ?? '');
    if (!empty($pkgPickupUrls)) {
        $row['pickup_pod'] = array_map(function($u){ return ['url'=>$u,'date'=>'']; }, $pkgPickupUrls);
    }
    $pkgDelivUrls = $decodePkgImages($row['delivery_pod_images_raw'] ?? '');
    if (!empty($pkgDelivUrls)) {
        $row['delivery_pod'] = array_map(function($u){ return ['url'=>$u,'date'=>'']; }, $pkgDelivUrls);
    }
    unset($row['pod_images_raw'], $row['delivery_pod_images_raw'], $row['tracking_raw']);

    // Package rows from tbl_booking_packages (for Parent AWB / Child AWB display; awb_no is ref, do not change)
    $pkgStmt = $pdo->prepare("SELECT row_no, waybill_no, awb_no, child_ewaybill_no, length, width, height, boxes, actual_weight, vol_weight, charged_weight FROM tbl_booking_packages WHERE booking_id = :bid ORDER BY row_no ASC");
    $pkgStmt->execute([':bid' => $row['id']]);
    $packages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Own Courier (courier_id = 2): collapse base + child (SUR-099, SUR-099-1, SUR-099-2) into one logical row so form shows "AWB SUR-099, boxes 2" and create receives one row → child gets SUR-099-1
    $courierId = (int) ($row['courier_id'] ?? 0);
    if ($courierId === 2 && count($packages) > 0) {
        $collapsed = [];
        $i = 0;
        while ($i < count($packages)) {
            $p = $packages[$i];
            $awb = trim((string) ($p['child_ewaybill_no'] ?? $p['awb_no'] ?? ''));
            $baseAwb = ($awb !== '' && preg_match('/^(.+)-\d+$/', $awb, $m)) ? $m[1] : $awb;
            $boxes = (int) ($p['boxes'] ?? 1);
            $volSum = (float) ($p['vol_weight'] ?? 0);
            $chgSum = (float) ($p['charged_weight'] ?? 0);
            $j = $i + 1;
            while ($j < count($packages)) {
                $nextAwb = trim((string) ($packages[$j]['child_ewaybill_no'] ?? $packages[$j]['awb_no'] ?? ''));
                if ($nextAwb !== '' && preg_match('/^' . preg_quote($baseAwb, '/') . '-(\d+)$/', $nextAwb)) {
                    $boxes += (int) ($packages[$j]['boxes'] ?? 1);
                    $volSum += (float) ($packages[$j]['vol_weight'] ?? 0);
                    $chgSum += (float) ($packages[$j]['charged_weight'] ?? 0);
                    $j++;
                } else {
                    break;
                }
            }
            $p['boxes'] = $boxes;
            $p['awb_no'] = $baseAwb;
            $p['child_ewaybill_no'] = $baseAwb;
            $p['vol_weight'] = $volSum;
            $p['charged_weight'] = $chgSum;
            $collapsed[] = $p;
            $i = $j;
        }
        $row['booking_packages'] = $collapsed;
    } else {
        $row['booking_packages'] = $packages;
    }

    echo json_encode(['status' => 'success', 'child_awb' => $childAwb, 'data' => $row]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
