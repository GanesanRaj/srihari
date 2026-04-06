<?php
/**
 * Unified printable Shiprocket-style manifest for one or more shiprocket_manifest rows.
 * Query: ?ids=1,2,3 (max 50)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Match extraction logic from api/shipment/read.php for Shiprocket order id.
 */
function extract_shiprocket_order_id($apiRespRaw)
{
    $shiprocketOrderId = '';
    $apiRespArr = null;
    if ($apiRespRaw === null || $apiRespRaw === '') {
        return '';
    }
    if (is_string($apiRespRaw)) {
        $apiRespArr = json_decode($apiRespRaw, true);
    } elseif (is_array($apiRespRaw)) {
        $apiRespArr = $apiRespRaw;
    }
    if (!is_array($apiRespArr)) {
        return '';
    }

    $shiprocketOrderId = trim((string) ($apiRespArr['order_id'] ?? $apiRespArr['response']['data']['order_id'] ?? ''));
    if (!empty($apiRespArr['awb_assign']) && is_array($apiRespArr['awb_assign'])) {
        $assign = $apiRespArr['awb_assign'];
        $shiprocketOrderId = trim((string) ($assign['response']['data']['order_id'] ?? $shiprocketOrderId));
    }
    return $shiprocketOrderId;
}

/**
 * Printable lines for manifest footer from tbl_pickup_points row (address / city–pin / contact).
 */
function sr_pickup_point_footer_lines(array $pp)
{
    $lines = [];
    $addr = trim((string) ($pp['address'] ?? ''));
    if ($addr !== '') {
        $lines[] = $addr;
    }
    $city = trim((string) ($pp['city'] ?? ''));
    $st = trim((string) ($pp['pickup_state'] ?? ''));
    $pin = trim((string) ($pp['pin'] ?? ''));
    if ($city !== '' || $st !== '' || $pin !== '') {
        $right = $st;
        if ($pin !== '') {
            $right = ($right !== '' ? $right . '-' : '') . $pin;
        }
        $line = $city . ($city !== '' && $right !== '' ? ',' : '') . $right;
        if ($line !== '') {
            $lines[] = $line . '.';
        }
    }
    $phone = trim((string) ($pp['phone'] ?? ''));
    if ($phone !== '') {
        $lines[] = 'Contact : ' . $phone;
    }
    return $lines;
}

$rawIds = isset($_GET['ids']) ? (string) $_GET['ids'] : '';
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawIds)), function ($v) {
    return $v > 0;
})));
if (count($ids) > 50) {
    $ids = array_slice($ids, 0, 50);
}

$error = '';
$lines = [];
$headerSeller = '';
$headerCourier = '';
$headerPickup = '';
$pickupFooterSections = [];
$totalShipments = 0;
$generatedAt = date('F j, Y, g:i a');

if (empty($ids)) {
    $error = 'Select at least one manifest (use ids=1,2,3).';
} else {
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $fieldList = implode(',', array_map('intval', $ids));
        $sql = "SELECT id, manifest_date, manifested_id, pickuppoint, manifstered_awb
                FROM shiprocket_manifest
                WHERE id IN ($ph)
                ORDER BY FIELD(id, $fieldList)";
        $stmt = $pdo->prepare($sql);
        foreach ($ids as $k => $id) {
            $stmt->bindValue($k + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $manifests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($manifests)) {
            throw new Exception('No manifest records found for the selected IDs.');
        }

        $awbOrder = [];
        $pickupNames = [];
        foreach ($manifests as $m) {
            $pp = trim((string) ($m['pickuppoint'] ?? ''));
            if ($pp !== '') {
                $pickupNames[$pp] = true;
            }
            $raw = $m['manifstered_awb'] ?? '';
            $arr = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
            if (!is_array($arr)) {
                continue;
            }
            foreach ($arr as $wb) {
                $wb = trim((string) $wb);
                if ($wb !== '') {
                    $awbOrder[] = $wb;
                }
            }
        }

        if (empty($awbOrder)) {
            throw new Exception('No AWBs stored on the selected manifest(s).');
        }

        $uniqueAwbs = array_values(array_unique($awbOrder));
        $placeholders = implode(',', array_fill(0, count($uniqueAwbs), '?'));
        $bSql = "SELECT booking_ref_id, auto_order_no, waybill_no, product_desc, shipper_name,
                        shiprocket_courier_company_name, api_response, pickup_point_id
                 FROM tbl_bookings
                 WHERE waybill_no IN ($placeholders)";
        $bStmt = $pdo->prepare($bSql);
        foreach ($uniqueAwbs as $i => $wb) {
            $bStmt->bindValue($i + 1, $wb, PDO::PARAM_STR);
        }
        $bStmt->execute();
        $bookingRows = $bStmt->fetchAll(PDO::FETCH_ASSOC);

        $byWaybill = [];
        foreach ($bookingRows as $br) {
            $w = trim((string) ($br['waybill_no'] ?? ''));
            if ($w !== '') {
                $byWaybill[$w] = $br;
            }
        }

        $sellers = [];
        $couriers = [];
        $idx = 0;
        foreach ($awbOrder as $awb) {
            $idx++;
            $b = $byWaybill[$awb] ?? null;
            $orderNo = '';
            $contents = '-';
            $seller = '';
            $courierDisp = '';

            if ($b) {
                $autoNo = (int) ($b['auto_order_no'] ?? 0);
                if ($autoNo > 0) {
                    $orderNo = (string) $autoNo;
                } else {
                    $orderNo = extract_shiprocket_order_id($b['api_response'] ?? '');
                    if ($orderNo === '') {
                        $orderNo = trim((string) ($b['booking_ref_id'] ?? ''));
                    }
                }
                $contents = trim((string) ($b['product_desc'] ?? ''));
                if ($contents === '') {
                    $contents = '-';
                }
                $seller = trim((string) ($b['shipper_name'] ?? ''));
                $courierDisp = trim((string) ($b['shiprocket_courier_company_name'] ?? ''));
                if ($seller !== '') {
                    $sellers[$seller] = true;
                }
                if ($courierDisp !== '') {
                    $couriers[$courierDisp] = true;
                }
            }

            $lines[] = [
                'sno' => $idx,
                'order_no' => $orderNo !== '' ? $orderNo : '-',
                'awb' => $awb,
                'contents' => $contents,
            ];
        }

        $totalShipments = count($lines);
        $sellerList = array_keys($sellers);
        if (count($sellerList) === 1) {
            $headerSeller = $sellerList[0];
        } elseif (count($sellerList) === 0) {
            $headerSeller = '—';
        } else {
            $headerSeller = 'Various';
        }

        $courierList = array_keys($couriers);
        if (count($courierList) === 1) {
            $headerCourier = $courierList[0];
        } elseif (count($courierList) === 0) {
            $headerCourier = '—';
        } else {
            $headerCourier = $courierList[0] . ' +' . (count($courierList) - 1);
        }

        $pickupList = array_keys($pickupNames);
        if (count($pickupList) === 1) {
            $headerPickup = $pickupList[0];
        } elseif (count($pickupList) === 0) {
            $headerPickup = '—';
        } else {
            $headerPickup = implode(', ', $pickupList);
        }

        $pickupPointIds = [];
        foreach ($bookingRows as $br) {
            $pid = (int) ($br['pickup_point_id'] ?? 0);
            if ($pid > 0) {
                $pickupPointIds[$pid] = true;
            }
        }
        if (!empty($pickupPointIds)) {
            $idList = array_keys($pickupPointIds);
            $ppPh = implode(',', array_fill(0, count($idList), '?'));
            $ppSql = "SELECT id, name, address, city, pin, pickup_state, phone
                      FROM tbl_pickup_points WHERE id IN ($ppPh)";
            $ppStmt = $pdo->prepare($ppSql);
            foreach ($idList as $i => $pid) {
                $ppStmt->bindValue($i + 1, $pid, PDO::PARAM_INT);
            }
            $ppStmt->execute();
            foreach ($ppStmt->fetchAll(PDO::FETCH_ASSOC) as $pp) {
                $fl = sr_pickup_point_footer_lines($pp);
                if (!empty($fl)) {
                    $pickupFooterSections[] = [
                        'name' => trim((string) ($pp['name'] ?? '')),
                        'lines' => $fl,
                    ];
                }
            }
        }
        if (empty($pickupFooterSections)) {
            $tried = [];
            foreach ($pickupList as $pname) {
                $pname = trim($pname);
                if ($pname === '' || $pname === '—') {
                    continue;
                }
                $key = strtolower($pname);
                if (isset($tried[$key])) {
                    continue;
                }
                $tried[$key] = true;
                $pnStmt = $pdo->prepare(
                    "SELECT id, name, address, city, pin, pickup_state, phone
                     FROM tbl_pickup_points
                     WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
                     LIMIT 1"
                );
                $pnStmt->execute([$pname]);
                $pp = $pnStmt->fetch(PDO::FETCH_ASSOC);
                if ($pp) {
                    $fl = sr_pickup_point_footer_lines($pp);
                    if (!empty($fl)) {
                        $pickupFooterSections[] = [
                            'name' => trim((string) ($pp['name'] ?? '')),
                            'lines' => $fl,
                        ];
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shiprocket Manifest<?php echo $error === '' ? '' : ' — Error'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4 portrait;
            margin: 10mm 11mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #111;
            background: #e8eaef;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .document-sheets { background: #fff !important; }
            .print-flow-header {
                display: none !important;
            }
            .print-fixed-header {
                display: block;
                position: fixed;
                top: 10mm;
                left: 11mm;
                right: 11mm;
                z-index: 50;
                background: #fff;
                padding-bottom: 8px;
                border-bottom: 2px solid #5b4fcf;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-fixed-header .sheet-head {
                margin-bottom: 0;
                padding-bottom: 6px;
                border-bottom: none;
            }
            .print-fixed-head-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
            }
            .print-head-page {
                flex: 0 0 auto;
                text-align: right;
                font-size: 9pt;
                font-weight: 700;
                color: #333;
                padding-top: 2px;
                white-space: nowrap;
            }
            .print-head-page::after {
                content: "Page " counter(page) " of " counter(pages);
            }
            .print-document {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                border: none !important;
                padding: 34mm 0 10mm 0 !important;
            }
        }

        .toolbar {
            padding: 12px 16px;
            background: #1a1a2e;
            color: #fff;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .toolbar button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            background: #4361ee;
            color: #fff;
        }

        .toolbar a {
            color: #aeb8ff;
            margin-left: auto;
            font-size: 13px;
        }

        .document-sheets {
            padding-bottom: 24px;
        }

        .print-fixed-header {
            display: none;
        }

        .print-document {
            width: 210mm;
            max-width: 100%;
            margin: 16px auto;
            padding: 14mm 13mm 16mm;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,.12);
            border: 1px solid #ddd;
        }

        .sheet-head {
            border-bottom: 2px solid #5b4fcf;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .sr-brand {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #5b4fcf;
            text-transform: uppercase;
        }

        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            margin-top: 6px;
            letter-spacing: 0.02em;
        }

        .generated {
            text-align: center;
            font-size: 9pt;
            color: #444;
            margin-top: 4px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 16px;
            margin-top: 12px;
            font-size: 9.5pt;
        }

        .meta-grid .meta-pickup-row {
            grid-column: 1 / -1;
        }

        .meta-grid .total-line {
            grid-column: 1 / -1;
            text-align: right;
            font-weight: 600;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #e0e0e0;
        }

        .manifest-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }

        .manifest-table thead {
            display: table-header-group;
        }

        .manifest-table th,
        .manifest-table td {
            border: 1px solid #222;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .manifest-table th {
            background: #eef0f4;
            font-weight: 600;
            text-align: left;
        }

        .manifest-table td.contents-cell {
            max-height: 48px;
            overflow: hidden;
            line-height: 1.35;
        }

        .manifest-table .col-sno { width: 36px; text-align: center; }
        .manifest-table .col-cb { width: 28px; text-align: center; }
        .manifest-table .col-order { width: 100px; word-break: break-all; }
        .manifest-table .col-awb { width: 112px; word-break: break-all; }
        .manifest-table .col-barcode { text-align: center; width: 120px; }

        .print-cb {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #000;
            margin: 0 auto;
        }

        .barcode-svg {
            max-width: 100%;
            height: 40px;
        }

        .signoff {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 2px dashed #333;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signoff h3 {
            font-size: 10.5pt;
            margin-bottom: 10px;
            text-align: center;
        }

        .signoff-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
            font-size: 9pt;
        }

        .signoff-grid .field {
            border-bottom: 1px solid #000;
            min-height: 26px;
            padding-top: 2px;
        }

        .signoff-grid .label {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .pickup-address-block {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid #bbb;
            max-width: 100%;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .pickup-address-inner {
            max-width: min(100%, 38rem);
            margin-left: auto;
            margin-right: auto;
            text-align: center;
            font-size: 9pt;
            line-height: 1.52;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .pickup-address-title {
            color: #333;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-size: 8.5pt;
            margin-bottom: 10px;
        }

        .pickup-address-title strong {
            font-weight: 700;
        }

        .pickup-address-block .pickup-address-name {
            font-size: 8.5pt;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .pickup-address-block .pickup-address-line {
            margin: 0 0 5px 0;
        }

        .pickup-address-block .pickup-address-line:last-child {
            margin-bottom: 0;
        }

        @media screen {
            .pickup-address-inner {
                padding: 12px 16px;
                background: #f4f5f9;
                border-radius: 6px;
            }
        }

        @media print {
            .pickup-address-inner {
                padding: 0;
                background: transparent;
                border-radius: 0;
            }
        }

        .footer-note {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 16px;
        }

        .err-box {
            max-width: 520px;
            margin: 40px auto;
            padding: 24px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<?php if ($error !== '') : ?>
    <div class="toolbar no-print">
        <button type="button" onclick="history.back()">Back</button>
        <a href="shiprocket-manifest-list.php">Manifest list</a>
    </div>
    <div class="err-box">
        <strong>Cannot print</strong>
        <p style="margin-top:8px;"><?php echo h($error); ?></p>
    </div>
<?php else : ?>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <a href="shiprocket-manifest-list.php">Back to list</a>
    </div>

    <div class="document-sheets" id="printArea">
        <div class="print-fixed-header" aria-hidden="true">
            <header class="sheet-head">
                <div class="print-fixed-head-top">
                    <div class="sr-brand">Shiprocket</div>
                    <div class="print-head-page"></div>
                </div>
                <h1 class="doc-title">Shiprocket Manifest</h1>
                <p class="generated">Generated on: <?php echo h($generatedAt); ?></p>
            </header>
        </div>

        <div class="print-document">
            <header class="sheet-head print-flow-header">
                <div class="sr-brand">Shiprocket</div>
                <h1 class="doc-title">Shiprocket Manifest</h1>
                <p class="generated">Generated on: <?php echo h($generatedAt); ?></p>
            </header>

            <div class="meta-grid">
                <div><strong>Seller:</strong> <?php echo h($headerSeller); ?></div>
                <div><strong>Courier:</strong> <?php echo h($headerCourier); ?></div>
                <div class="meta-pickup-row"><strong>Pickup point:</strong> <?php echo h($headerPickup); ?></div>
                <div class="total-line">Total shipments to dispatch: <?php echo (int) $totalShipments; ?></div>
            </div>

            <table class="manifest-table">
                <thead>
                    <tr>
                        <th class="col-sno">S.no</th>
                        <th class="col-cb"></th>
                        <th class="col-order">Order no</th>
                        <th class="col-awb">Awb no</th>
                        <th>Contents</th>
                        <th class="col-barcode">Barcode</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $row) : ?>
                    <tr>
                        <td class="col-sno"><?php echo (int) $row['sno']; ?></td>
                        <td class="col-cb"><span class="print-cb" aria-hidden="true"></span></td>
                        <td class="col-order"><?php echo h($row['order_no']); ?></td>
                        <td class="col-awb"><?php echo h($row['awb']); ?></td>
                        <td class="contents-cell"><?php echo h($row['contents']); ?></td>
                        <td class="col-barcode">
                            <svg class="barcode-svg" data-awb="<?php echo h($row['awb']); ?>"></svg>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="signoff">
                <h3>To Be Filled By <?php echo h($headerCourier !== '—' ? $headerCourier : 'Courier'); ?> Logistics Executive</h3>
                <div class="signoff-grid">
                    <div>
                        <div class="label">Pick up time</div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">Total items picked</div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">FE Name</div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">Seller Name: <?php echo h($headerSeller); ?></div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">FE Signature</div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">Seller Signature</div>
                        <div class="field"></div>
                    </div>
                    <div>
                        <div class="label">FE Phone</div>
                        <div class="field"></div>
                    </div>
                    <div></div>
                </div>
            </div>

            <?php if (!empty($pickupFooterSections) || ($headerPickup !== '' && $headerPickup !== '—')) : ?>
            <div class="pickup-address-block">
                <div class="pickup-address-inner">
                    <div class="pickup-address-title"><strong>Pickup point address</strong></div>
                    <?php foreach ($pickupFooterSections as $sec) : ?>
                        <?php if (count($pickupFooterSections) > 1 && ($sec['name'] ?? '') !== '') : ?>
                        <div class="pickup-address-name"><?php echo h($sec['name']); ?></div>
                        <?php endif; ?>
                        <?php foreach ($sec['lines'] as $ln) : ?>
                        <div class="pickup-address-line"><?php echo h($ln); ?></div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if (empty($pickupFooterSections) && $headerPickup !== '' && $headerPickup !== '—') : ?>
                    <div class="pickup-address-line"><?php echo h($headerPickup); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <p class="footer-note">This is a system generated document</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.barcode-svg[data-awb]').forEach(function (svg) {
                var code = svg.getAttribute('data-awb') || '';
                if (!code || typeof JsBarcode === 'undefined') return;
                try {
                    JsBarcode(svg, code, {
                        format: 'CODE128',
                        width: 1.1,
                        height: 36,
                        margin: 0,
                        displayValue: false
                    });
                } catch (e) {
                    svg.outerHTML = '<span style="font-size:9px;color:#666;">—</span>';
                }
            });
        });
    </script>
<?php endif; ?>
</body>
</html>
