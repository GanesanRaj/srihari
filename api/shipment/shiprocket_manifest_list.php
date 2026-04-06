<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if (!get_permission('shipment', 'is_view')) {
    require_api_permission('shipment', 'is_view');
}

try {
    if (!empty($_GET['filter_options']) && (string) $_GET['filter_options'] === '1') {
        $pickups = [];
        $ppStmt = $pdo->query(
            "SELECT DISTINCT TRIM(pickuppoint) AS p FROM shiprocket_manifest
             WHERE pickuppoint IS NOT NULL AND TRIM(pickuppoint) != ''
             ORDER BY p ASC LIMIT 500"
        );
        while ($r = $ppStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($r['p'])) {
                $pickups[] = $r['p'];
            }
        }

        $subCouriers = [];
        try {
            $scSql = "SELECT DISTINCT TRIM(b.shiprocket_courier_company_name) AS nm
                      FROM tbl_bookings b
                      INNER JOIN tbl_courier_partner cp ON cp.id = b.courier_id
                      WHERE b.shiprocket_courier_company_name IS NOT NULL
                        AND TRIM(b.shiprocket_courier_company_name) != ''
                        AND (
                            LOWER(TRIM(cp.partner_name)) LIKE '%shiprocket%'
                            OR UPPER(LEFT(TRIM(cp.partner_code), 2)) = 'SR'
                            OR cp.id = 4
                        )
                      ORDER BY nm ASC
                      LIMIT 500";
            $scStmt = $pdo->query($scSql);
            while ($r = $scStmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($r['nm'])) {
                    $subCouriers[] = $r['nm'];
                }
            }
        } catch (Throwable $ignore) {
        }

        echo json_encode([
            'status' => 'success',
            'pickups' => $pickups,
            'sub_couriers' => $subCouriers,
        ]);
        exit;
    }

    $fromDate = isset($_GET['from_date']) ? trim((string) $_GET['from_date']) : '';
    $toDate = isset($_GET['to_date']) ? trim((string) $_GET['to_date']) : '';
    if ($fromDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $fromDate = date('Y-m-01');
    }
    if ($toDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $toDate = date('Y-m-t');
    }
    if ($fromDate > $toDate) {
        $tmp = $fromDate;
        $fromDate = $toDate;
        $toDate = $tmp;
    }

    $pickupPoint = isset($_GET['pickup_point']) ? trim((string) $_GET['pickup_point']) : '';
    $subCourierFilter = isset($_GET['sub_courier']) ? trim((string) $_GET['sub_courier']) : '';

    $sql = "SELECT sm.id, sm.manifest_date, sm.manifested_id, sm.pickuppoint, sm.manifstered_awb, sm.created_at, sm.created_by, sm.response,
                   u.username AS created_by_name
            FROM shiprocket_manifest sm
            LEFT JOIN tbl_user u ON u.user_id = sm.created_by
            WHERE DATE(COALESCE(sm.manifest_date, sm.created_at)) BETWEEN :from_d AND :to_d";
    $params = [
        ':from_d' => $fromDate,
        ':to_d' => $toDate,
    ];

    if ($pickupPoint !== '') {
        $sql .= " AND sm.pickuppoint LIKE :pickup";
        $params[':pickup'] = '%' . $pickupPoint . '%';
    }

    $sql .= " ORDER BY sm.id DESC LIMIT 2000";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filteredRows = [];

    foreach ($rows as $row) {
        $awbs = [];
        $manifestUrl = '';
        $resp = $row['response'] ?? '';
        $respArr = is_string($resp) ? json_decode((string) $resp, true) : (is_array($resp) ? $resp : null);
        $awbsRaw = $row['manifstered_awb'] ?? '';
        $awbsArr = is_string($awbsRaw) ? json_decode((string) $awbsRaw, true) : (is_array($awbsRaw) ? $awbsRaw : null);
        if (is_array($awbsArr)) {
            $awbs = array_values(array_filter(array_map('strval', $awbsArr)));
        }
        if (is_array($respArr)) {
            $manifestUrl = trim((string) (
                $respArr['print_manifest_url']
                ?? $respArr['generate_manifest_url']
                ?? $respArr['print_manifest']['manifest_url']
                ?? $respArr['generate_manifest']['manifest_url']
                ?? ''
            ));
        }
        $row['awb_count'] = count($awbs);
        $row['awbs'] = $awbs;
        $row['manifest_url'] = $manifestUrl;

        $row['sub_couriers'] = [];
        if (!empty($awbs)) {
            $placeholders = implode(',', array_fill(0, count($awbs), '?'));
            $courierSql = "SELECT DISTINCT TRIM(COALESCE(NULLIF(b.shiprocket_courier_company_name, ''), cp.partner_name)) AS courier_name
                          FROM tbl_bookings b
                          LEFT JOIN tbl_courier_partner cp ON cp.id = b.courier_id
                          WHERE b.waybill_no IN ($placeholders) AND b.waybill_no IS NOT NULL AND b.waybill_no != ''";
            $courierStmt = $pdo->prepare($courierSql);
            $courierStmt->execute($awbs);
            $courierData = $courierStmt->fetchAll(PDO::FETCH_ASSOC);

            $courierNames = [];
            foreach ($courierData as $c) {
                $courierName = !empty($c['courier_name']) ? trim((string) $c['courier_name']) : '';
                if ($courierName !== '' && !in_array($courierName, $courierNames, true)) {
                    $courierNames[] = $courierName;
                }
            }
            $row['sub_couriers'] = $courierNames;
        }

        if ($subCourierFilter !== '') {
            $hit = false;
            $needle = mb_strtolower($subCourierFilter, 'UTF-8');
            foreach ($row['sub_couriers'] as $name) {
                if ($needle === '' || mb_strpos(mb_strtolower($name, 'UTF-8'), $needle) !== false) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit) {
                continue;
            }
        }

        $filteredRows[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $filteredRows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
