<?php
if (session_status () === PHP_SESSION_NONE)
    session_start ();
require_once __DIR__ . '/config/config.php';

if ( ! isset ($_SESSION[ 'user_id' ])) {
    header ( 'Location: login.php' );
    exit;
    }

function h ($v) { return htmlspecialchars ( (string) $v, ENT_QUOTES, 'UTF-8' ); }

$id       = (int) ($_GET[ 'id' ] ?? 0);
$error    = '';
$runsheet = null;
$details  = [];

try {
    if ($id <= 0)
        throw new Exception( 'Run Sheet ID required' );

    $stmt = $pdo->prepare (
        "SELECT r.*,
                u1.username AS created_by_name,
                co.company_name, co.company_logo
         FROM tbl_runsheet r
         LEFT JOIN tbl_user    u1 ON u1.user_id = r.created_by
         LEFT JOIN tbl_company co ON co.id = 1
         WHERE r.id = :id LIMIT 1"
    );
    $stmt->execute ( [ ':id' => $id ] );
    $runsheet = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $runsheet)
        throw new Exception( 'Run Sheet not found' );

    $dStmt = $pdo->prepare (
        "SELECT * FROM tbl_runsheet_details WHERE runsheet_id = :id ORDER BY consignee_name ASC"
    );
    $dStmt->execute ( [ ':id' => $id ] );
    $details = $dStmt->fetchAll ( PDO::FETCH_ASSOC );

    }
catch ( Exception $e ) {
    $error = $e->getMessage ();
    }

// Logo
$logoPath = '';
if ( ! empty ($runsheet[ 'company_logo' ])) {
    $logoPath = 'uploads/' . $runsheet[ 'company_logo' ];
    if ( ! file_exists ( __DIR__ . '/' . $logoPath ))
        $logoPath = '';
    }
if ($logoPath === '')
    $logoPath = 'assets/images/logo-black.png';

$statusLabels = [ 'draft' => 'DRAFT', 'dispatched' => 'DISPATCHED', 'completed' => 'COMPLETED' ];
$statusLabel  = $statusLabels[$runsheet[ 'status' ] ?? 'draft'] ?? strtoupper ( $runsheet[ 'status' ] ?? 'DRAFT' );
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Run Sheet: <?php echo h ( $runsheet[ 'runsheet_no' ] ?? '' ); ?></title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 6mm 6mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #f5f5f5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 182mm;
            min-height: 257mm;
            background: #fff;
            margin: 10mm auto;
            padding: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid #ccc;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        @media print {
            .page {
                width: 100%;
                min-height: 100%;
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }

        /* Company Header */
        .co-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5mm 6mm 4mm;
            border-bottom: 2px solid #222;
        }

        .co-logo img {
            height: 28px;
            max-width: 80px;
            object-fit: contain;
        }

        .co-name {
            flex: 1;
            text-align: center;
            font-size: 15pt;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .co-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #444;
            line-height: 1.5;
        }

        /* Title Bar */
        .rs-title-bar {
            background: #1a1a2e;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2mm 6mm;
        }

        .rs-title-bar .title {
            font-size: 12pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .rs-title-bar .rs-no {
            font-size: 11pt;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 1px;
        }

        .rs-title-bar .status-tag {
            font-size: 8pt;
            font-weight: 700;
            padding: 1.5mm 4mm;
            border-radius: 3px;
            background: rgba(255, 255, 255, .18);
            letter-spacing: .5px;
        }

        /* Details Grid */
        .details-section {
            padding: 3mm 6mm;
            border-bottom: 1px solid #ddd;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2mm 4mm;
        }

        .detail-cell .d-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #888;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .detail-cell .d-value {
            font-size: 9.5pt;
            font-weight: 700;
            color: #111;
            margin-top: 0.3mm;
        }

        .detail-cell.wide {
            grid-column: span 2;
        }

        /* AWB Table */
        .awb-section {
            padding: 3mm 6mm;
            flex: 1;
        }

        .section-title {
            font-size: 8.5pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #1a1a2e;
            border-bottom: 1.5px solid #1a1a2e;
            padding-bottom: 1mm;
            margin-bottom: 2mm;
        }

        .awb-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .awb-table thead tr {
            background: #1a1a2e;
            color: #fff;
        }

        .awb-table thead th {
            padding: 2mm 2mm;
            text-align: left;
            font-weight: 700;
            font-size: 7.5pt;
            letter-spacing: .3px;
        }

        .awb-table thead th.center {
            text-align: center;
        }

        .awb-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .awb-table tbody td {
            padding: 1.5mm 2mm;
            border-bottom: 0.5px solid #e5e7eb;
            vertical-align: middle;
        }

        .awb-table tbody td.center {
            text-align: center;
        }

        .awb-no {
            font-weight: 700;
            font-family: monospace;
            font-size: 8.5pt;
        }

        .no-entries {
            text-align: center;
            padding: 8mm;
            color: #aaa;
            font-style: italic;
        }

        /* Summary Bar */
        .summary-bar {
            display: flex;
            align-items: center;
            justify-content: space-around;
            background: #1a1a2e;
            color: #fff;
            padding: 2.5mm 6mm;
        }

        .sum-cell {
            text-align: center;
        }

        .sum-cell .sum-num {
            font-size: 14pt;
            font-weight: 900;
            font-family: monospace;
            display: block;
            line-height: 1.1;
        }

        .sum-cell .sum-lbl {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: rgba(255, 255, 255, .65);
        }

        .sum-divider {
            width: 0.5px;
            height: 10mm;
            background: rgba(255, 255, 255, .25);
        }

        /* Signature */
        .signature-section {
            padding: 4mm 6mm 5mm;
            border-top: 1px solid #ddd;
        }

        .sig-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5mm;
            margin-top: 2mm;
        }

        .sig-box {
            border: 1px solid #bbb;
            border-radius: 3px;
            overflow: hidden;
        }

        .sig-box-header {
            background: #f3f4f6;
            padding: 1.5mm 3mm;
            font-size: 8pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #333;
            border-bottom: 1px solid #bbb;
            text-align: center;
        }

        .sig-box-body {
            height: 22mm;
            padding: 2mm 3mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .sig-seal-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 8pt;
            letter-spacing: .5px;
            text-transform: uppercase;
            border: 1px dashed #ddd;
            border-radius: 3px;
            margin-bottom: 2mm;
            min-height: 10mm;
        }

        .sig-line {
            border-top: 1px solid #555;
            padding-top: 1mm;
            font-size: 7.5pt;
            color: #555;
            text-align: center;
        }

        /* Footer */
        .print-footer {
            padding: 1.5mm 6mm;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #aaa;
        }

        /* Print Button Bar */
        .print-btn-bar {
            text-align: center;
            padding: 8px;
            background: #f0f0f0;
        }

        .print-btn-bar button {
            padding: 6px 24px;
            background: #1a1a2e;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            margin: 0 4px;
        }

        .print-btn-bar button.secondary {
            background: #666;
        }
    </style>
</head>

<body>

    <?php if ($error !== '') : ?>
        <div style="padding:30px;text-align:center;color:red;font-size:16px;font-weight:bold;">
            <?php echo h ( $error ); ?>
        </div>
    <?php else : ?>

        <div class="print-btn-bar no-print">
            <button onclick="window.print()">&#128438; Print</button>
            <button class="secondary" onclick="window.close()">Close</button>
        </div>

        <div class="page">

            <!-- Company Header -->
            <div class="co-header">
                <div class="co-logo">
                    <img src="<?php echo h ( $logoPath ); ?>" alt="Logo">
                </div>
                <div class="co-name">
                    <?php echo h ( $runsheet[ 'company_name' ] ?? 'DELIVERY RUN SHEET' ); ?>
                </div>
                <div class="co-meta">
                    Date: <?php echo date ( 'd M Y', strtotime ( $runsheet[ 'created_at' ] ) ); ?><br>
                    Time: <?php echo date ( 'h:i A', strtotime ( $runsheet[ 'created_at' ] ) ); ?><br>
                    By: <?php echo h ( $runsheet[ 'created_by_name' ] ?? '—' ); ?>
                </div>
            </div>

            <!-- Title Bar -->
            <div class="rs-title-bar">
                <span class="title">Delivery Run Sheet</span>
                <span class="rs-no"><?php echo h ( $runsheet[ 'runsheet_no' ] ); ?></span>
                <span class="status-tag"><?php echo $statusLabel; ?></span>
            </div>

            <!-- Details -->
            <div class="details-section">
                <div class="details-grid">
                    <div class="detail-cell wide">
                        <div class="d-label">Driver Name</div>
                        <div class="d-value"><?php echo h ( $runsheet[ 'driver_name' ] ?: '—' ); ?></div>
                    </div>
                    <div class="detail-cell">
                        <div class="d-label">Mobile Number</div>
                        <div class="d-value"><?php echo h ( $runsheet[ 'mobile_number' ] ?: '—' ); ?></div>
                    </div>
                    <div class="detail-cell">
                        <div class="d-label">Run Sheet Date</div>
                        <div class="d-value">
                            <?php echo $runsheet[ 'runsheet_date' ]
                                ? date ( 'd M Y', strtotime ( $runsheet[ 'runsheet_date' ] ) )
                                : '—'; ?>
                        </div>
                    </div>
                    <div class="detail-cell">
                        <div class="d-label">Total Shipments</div>
                        <div class="d-value"><?php echo count ( $details ); ?></div>
                    </div>
                    <div class="detail-cell">
                        <div class="d-label">Status</div>
                        <div class="d-value"><?php echo $statusLabel; ?></div>
                    </div>
                </div>
            </div>

            <!-- AWB Table -->
            <div class="awb-section">
                <div class="section-title">Shipment Details</div>

                <?php if (empty ($details)) : ?>
                    <div class="no-entries">No shipments in this run sheet</div>
                <?php else : ?>
                    <table class="awb-table">
                        <thead>
                            <tr>
                                <th class="center" style="width:20px;">#</th>
                                <th>AWB No</th>
                                <th>Consignee Details</th>
                                <!--<th>City</th>
                        <th>Phone</th>-->
                                <th>Address</th>
                                <th>Receiver/Sign/Stamp</th>
                                <!--<th>Status</th>
                        <th>Scanned At</th>-->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $i => $row) : ?>
                                <tr>
                                    <td class="center" style="color:#aaa;font-size:7pt;"><?php echo $i + 1; ?></td>
                                    <td><span class="awb-no"><?php echo h ( $row[ 'awb_no' ] ?? '—' ); ?></span></td>
                                    <td><b><?php echo h ( $row[ 'consignee_name' ] ?? '—' ); ?></b>
                                        <br><?php echo h ( $row[ 'consignee_city' ] ?? '—' ); ?>
                                        <br><?php echo h ( $row[ 'consignee_phone' ] ?? '—' ); ?>

                                    </td>
                                    <td style="font-size:7pt;max-width:40mm;white-space:normal;">
                                        <?php echo h ( $row[ 'address' ] ?? '—' ); ?></td>
                                    <!--<td style="font-size:7.5pt;"><?php echo h ( $row[ 'status' ] ?? 'Pending' ); ?></td>
                            <td style="font-size:7pt;color:#555;">
                                <?php echo ! empty ($row[ 'scanned_at' ])
                                    ? date ( 'd/m/y H:i', strtotime ( $row[ 'scanned_at' ] ) )
                                    : '—'; ?>
                            </td>-->
                                    <td>
                                        <div class="sig-box">
                                            <div class="sig-box-header" style="padding:0px;">Received By</div>
                                            <div class="sig-box-body" style="height:19mm">
                                                <div class="sig-seal-area">Seal</div>
                                                <div class="sig-line">Signature &amp; Name</div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Summary Bar -->
            <div class="summary-bar">
                <div class="sum-cell">
                    <span class="sum-num"><?php echo count ( $details ); ?></span>
                    <span class="sum-lbl">Total Shipments</span>
                </div>
                <div class="sum-divider"></div>
                <div class="sum-cell">
                    <span class="sum-num"><?php echo h ( $runsheet[ 'runsheet_no' ] ); ?></span>
                    <span class="sum-lbl">Run Sheet No</span>
                </div>
                <div class="sum-divider"></div>
                <div class="sum-cell">
                    <span
                        class="sum-num"><?php echo $runsheet[ 'runsheet_date' ] ? date ( 'd M Y', strtotime ( $runsheet[ 'runsheet_date' ] ) ) : '—'; ?></span>
                    <span class="sum-lbl">Date</span>
                </div>
            </div>

            <!-- Signature -->
            <div class="signature-section">
                <div class="section-title">Authorisation</div>
                <div class="sig-grid">
                    <div class="sig-box">
                        <div class="sig-box-header">Prepared By</div>
                        <div class="sig-box-body">
                            <div class="sig-seal-area">Seal</div>
                            <div class="sig-line">Signature &amp; Name</div>
                        </div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-box-header">Driver / <?php echo h ( $runsheet[ 'driver_name' ] ?: 'Driver' ); ?></div>
                        <div class="sig-box-body">
                            <div class="sig-seal-area">Seal</div>
                            <div class="sig-line">Signature &amp; Name</div>
                        </div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-box-header">Authorised By</div>
                        <div class="sig-box-body">
                            <div class="sig-seal-area">Seal</div>
                            <div class="sig-line">Signature &amp; Name</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="print-footer">
                <span>Run Sheet No: <?php echo h ( $runsheet[ 'runsheet_no' ] ); ?></span>
                <span>Printed: <?php echo date ( 'd M Y, h:i A' ); ?></span>
                <span>This is a system-generated document.</span>
            </div>

        </div><!-- /page -->

        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 500);
            });
        </script>

    <?php endif; ?>
</body>

</html>