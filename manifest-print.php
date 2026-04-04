<?php
if (session_status () === PHP_SESSION_NONE) session_start ();
require_once __DIR__ . '/config/config.php';

if ( ! isset ($_SESSION[ 'user_id' ])) {
    header ( 'Location: login.php' );
    exit;
    }

function h ($v) { return htmlspecialchars ( (string) $v, ENT_QUOTES, 'UTF-8' ); }

$id       = (int) ($_GET[ 'id' ] ?? 0);
$error    = '';
$manifest = null;
$entries  = [];
$company  = null;

try {
    if ($id <= 0)
        throw new Exception( 'Manifest ID required' );

    $sql  = "SELECT m.*,
                u1.username AS created_by_name,
                u2.username AS updated_by_name,
                bf.branch_name  AS from_branch_name,
                bf.address      AS from_branch_address,
                bf.contact_no   AS from_branch_phone,
                bt.branch_name  AS to_branch_name,
                bt.address      AS to_branch_address,
                co.company_name,
                co.company_logo
             FROM tbl_manifest m
             LEFT JOIN tbl_branch bf  ON bf.id        = m.from_branch
             LEFT JOIN tbl_branch bt  ON bt.id        = m.to_branch
             LEFT JOIN tbl_company co ON co.id        = bf.company_id
             LEFT JOIN tbl_user u1    ON u1.user_id   = m.created_by
             LEFT JOIN tbl_user u2    ON u2.user_id   = m.updated_by
             WHERE m.id = :id LIMIT 1";
    $stmt = $pdo->prepare ( $sql );
    $stmt->execute ( [ ':id' => $id ] );
    $manifest = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $manifest)
        throw new Exception( 'Manifest not found' );

    $entries = json_decode ( $manifest[ 'json_data' ] ?: '[]', true );
    }
catch ( Exception $e ) {
    $error = $e->getMessage ();
    }

// Helpers
$logoPath = '';
if ( ! empty ($manifest[ 'company_logo' ])) {
    $logoPath = 'uploads/' . $manifest[ 'company_logo' ];
    if ( ! file_exists ( __DIR__ . '/' . $logoPath ))
        $logoPath = '';
    }
if ($logoPath === '')
    $logoPath = 'assets/images/logo-black.png';

$statusLabels = [ 'draft' => 'DRAFT', 'dispatched' => 'DISPATCHED', 'received' => 'RECEIVED' ];
$statusLabel  = $statusLabels[ $manifest[ 'status' ] ?? 'draft' ] ?? strtoupper ( $manifest[ 'status' ] ?? 'DRAFT' );

// Gather unique tag nos
$tagNosInEntries = array_unique ( array_filter ( array_column ( $entries, 'tag_no' ) ) );
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manifest: <?php echo h ( $manifest[ 'manifest_no' ] ?? '' ); ?></title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    @page {
        size: A4 portrait;
        margin: 12mm 14mm;
    }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #000;
        background: #f5f5f5;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Page Shell ─────────────────────────────────────────────── */
    .page {
        width: 182mm;
        min-height: 257mm;
        background: #fff;
        margin: 10mm auto;
        padding: 0;
        display: flex;
        flex-direction: column;
        border: 1px solid #ccc;
        box-shadow: 0 2px 12px rgba(0,0,0,.12);
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

    /* ── Company Header ─────────────────────────────────────────── */
    .co-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5mm 6mm 4mm;
        border-bottom: 2px solid #222;
        background: #fff;
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

    /* ── Manifest Title Bar ─────────────────────────────────────── */
    .manifest-title-bar {
        background: #1a1a2e;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 2mm 6mm;
    }
    .manifest-title-bar .title {
        font-size: 12pt;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }
    .manifest-title-bar .manifest-no {
        font-size: 11pt;
        font-weight: 700;
        font-family: monospace;
        letter-spacing: 1px;
    }
    .manifest-title-bar .status-tag {
        font-size: 8pt;
        font-weight: 700;
        padding: 1.5mm 4mm;
        border-radius: 3px;
        background: rgba(255,255,255,.18);
        letter-spacing: .5px;
    }

    /* ── Route Banner ───────────────────────────────────────────── */
    .route-banner {
        display: flex;
        align-items: stretch;
        border-bottom: 1px solid #ddd;
    }
    .route-box {
        flex: 1;
        padding: 3mm 5mm;
        text-align: center;
    }
    .route-box .label {
        font-size: 7pt;
        text-transform: uppercase;
        color: #777;
        letter-spacing: .8px;
        font-weight: 700;
    }
    .route-box .branch {
        font-size: 13pt;
        font-weight: 900;
        color: #1a1a2e;
        line-height: 1.2;
    }
    .route-box .addr {
        font-size: 7.5pt;
        color: #555;
        margin-top: 0.5mm;
    }
    .route-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 14mm;
        font-size: 20pt;
        font-weight: 900;
        color: #1a1a2e;
        background: #f8f9fa;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
    }
    .from-box { border-right: none; background: #f0f4ff; }
    .to-box   { border-left: none;  background: #f0fff4; }

    /* ── Details Grid ───────────────────────────────────────────── */
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

    /* ── AWB Table ──────────────────────────────────────────────── */
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
        font-size: 8.5pt;
    }
    .awb-table thead tr {
        background: #1a1a2e;
        color: #fff;
    }
    .awb-table thead th {
        padding: 2mm 2.5mm;
        text-align: left;
        font-weight: 700;
        font-size: 8pt;
        letter-spacing: .3px;
    }
    .awb-table thead th.center { text-align: center; }
    .awb-table tbody tr:nth-child(even) { background: #f8f9fa; }
    .awb-table tbody tr:hover { background: #eef2ff; }
    .awb-table tbody td {
        padding: 1.5mm 2.5mm;
        border-bottom: 0.5px solid #e5e7eb;
        vertical-align: middle;
    }
    .awb-table tbody td.center { text-align: center; }
    .awb-no { font-weight: 700; font-family: monospace; font-size: 9pt; }
    .tag-chip {
        font-size: 7pt;
        font-family: monospace;
        padding: 0.5mm 2mm;
        border-radius: 8px;
        background: #e8f4fd;
        border: 0.5px solid #bee3f8;
        color: #1a6fa8;
        white-space: nowrap;
    }
    .no-entries {
        text-align: center;
        padding: 8mm;
        color: #aaa;
        font-style: italic;
    }

    /* ── Summary Bar ────────────────────────────────────────────── */
    .summary-bar {
        display: flex;
        align-items: center;
        justify-content: space-around;
        background: #1a1a2e;
        color: #fff;
        padding: 2.5mm 6mm;
    }
    .sum-cell { text-align: center; }
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
        color: rgba(255,255,255,.65);
    }
    .sum-divider {
        width: 0.5px;
        height: 10mm;
        background: rgba(255,255,255,.25);
    }

    /* ── Tags Row ───────────────────────────────────────────────── */
    .tags-row {
        padding: 2mm 6mm;
        background: #f8f9fa;
        border-top: 1px solid #e5e7eb;
        font-size: 8pt;
    }
    .tags-row .tl { font-weight: 700; color: #555; }
    .tags-row .tc { font-family: monospace; font-weight: 700; color: #1a6fa8; }

    /* ── Signature Section ──────────────────────────────────────── */
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

    /* ── Footer ─────────────────────────────────────────────────── */
    .print-footer {
        padding: 1.5mm 6mm;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        font-size: 7pt;
        color: #aaa;
    }

    /* ── Print Button ───────────────────────────────────────────── */
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

<!-- Print toolbar (hidden on print) -->
<div class="print-btn-bar no-print">
    <button onclick="window.print()">&#128438; Print</button>
    <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="page">

    <!-- ── Company Header ──────────────────────────────────────── -->
    <div class="co-header">
        <div class="co-logo">
            <img src="<?php echo h ( $logoPath ); ?>" alt="Logo">
        </div>
        <div class="co-name">
            <?php echo h ( $manifest[ 'company_name' ] ?? 'DISPATCH MANIFEST' ); ?>
        </div>
        <div class="co-meta">
            Date: <?php echo date ( 'd M Y', strtotime ( $manifest[ 'created_at' ] ) ); ?><br>
            Time: <?php echo date ( 'h:i A', strtotime ( $manifest[ 'created_at' ] ) ); ?><br>
            By: <?php echo h ( $manifest[ 'created_by_name' ] ?? '—' ); ?>
        </div>
    </div>

    <!-- ── Manifest Title Bar ───────────────────────────────────── -->
    <div class="manifest-title-bar">
        <span class="title">Dispatch Manifest</span>
        <span class="manifest-no"><?php echo h ( $manifest[ 'manifest_no' ] ); ?></span>
        <span class="status-tag"><?php echo $statusLabel; ?></span>
    </div>

    <!-- ── Route Banner ─────────────────────────────────────────── -->
    <div class="route-banner">
        <div class="route-box from-box">
            <div class="label">Origin Branch</div>
            <div class="branch"><?php echo h ( $manifest[ 'from_branch_name' ] ?? '—' ); ?></div>
            <?php if ( ! empty ($manifest[ 'from_branch_address' ])) : ?>
                <div class="addr"><?php echo h ( $manifest[ 'from_branch_address' ] ); ?></div>
            <?php endif; ?>
        </div>
        <div class="route-arrow">&#8594;</div>
        <div class="route-box to-box">
            <div class="label">Destination Branch</div>
            <div class="branch"><?php echo h ( $manifest[ 'to_branch_name' ] ?? '—' ); ?></div>
            <?php if ( ! empty ($manifest[ 'to_branch_address' ])) : ?>
                <div class="addr"><?php echo h ( $manifest[ 'to_branch_address' ] ); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Vehicle / Transport Details ─────────────────────────── -->
    <div class="details-section">
        <div class="details-grid">
            <div class="detail-cell wide">
                <div class="d-label">Coloader / Vehicle</div>
                <div class="d-value"><?php echo h ( $manifest[ 'coloader' ] ?: '—' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Vehicle No</div>
                <div class="d-value"><?php echo h ( $manifest[ 'vehicle_no' ] ?: '—' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Driver Name</div>
                <div class="d-value"><?php echo h ( $manifest[ 'driver_name' ] ?: '—' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Mobile No</div>
                <div class="d-value"><?php echo h ( $manifest[ 'mobile_no' ] ?: '—' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Bag Count</div>
                <div class="d-value"><?php echo h ( $manifest[ 'bag_count' ] ?: '0' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Weight (kg)</div>
                <div class="d-value"><?php echo h ( $manifest[ 'weight' ] ?: '0' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Total Boxes</div>
                <div class="d-value"><?php echo h ( $manifest[ 'total_box' ] ?: '0' ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Total Shipments</div>
                <div class="d-value"><?php echo count ( $entries ); ?></div>
            </div>
            <div class="detail-cell">
                <div class="d-label">Dispatch Mode</div>
                <div class="d-value"><?php echo h ( $manifest[ 'dispatch_mode' ] ?? '—' ); ?></div>
            </div>
        </div>
        <?php if ( ! empty ($tagNosInEntries)) : ?>
            <div style="margin-top:2mm;">
                <span style="font-size:7.5pt;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;">Tags Included: </span>
                <?php foreach ($tagNosInEntries as $tn) : ?>
                    <span class="tag-chip"><?php echo h ( $tn ); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── AWB Table ─────────────────────────────────────────────── -->
    <div class="awb-section">
        <div class="section-title">Shipment Details</div>

        <?php if (empty ( $entries )) : ?>
            <div class="no-entries">No shipments in this manifest</div>
        <?php else : ?>
            <table class="awb-table">
                <thead>
                    <tr>
                        <th class="center" style="width:22px;">#</th>
                        <th>AWB No</th>
                        <th>Consignee</th>
                        <th>City</th>
                        <th>Tag No</th>
                        <th>Scanned At</th>
                        <th class="center">By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $i => $e) : ?>
                        <tr>
                            <td class="center" style="color:#aaa;font-size:7.5pt;"><?php echo $i + 1; ?></td>
                            <td><span class="awb-no"><?php echo h ( $e[ 'awb_no' ] ?? '—' ); ?></span></td>
                            <td><?php echo h ( $e[ 'consignee_name' ] ?? '—' ); ?></td>
                            <td><?php echo h ( $e[ 'consignee_city' ] ?? '—' ); ?></td>
                            <td>
                                <?php if ( ! empty ($e[ 'tag_no' ])) : ?>
                                    <span class="tag-chip"><?php echo h ( $e[ 'tag_no' ] ); ?></span>
                                <?php else : ?>
                                    <span style="color:#ccc;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:7.5pt;color:#555;">
                                <?php echo ! empty ($e[ 'scanned_at' ])
                                    ? date ( 'd/m/y H:i', strtotime ( $e[ 'scanned_at' ] ) )
                                    : '—'; ?>
                            </td>
                            <td class="center" style="font-size:7.5pt;color:#777;">
                                <?php echo h ( $e[ 'scanned_by' ] ?? '—' ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ── Summary Bar ───────────────────────────────────────────── -->
    <div class="summary-bar">
        <div class="sum-cell">
            <span class="sum-num"><?php echo count ( $entries ); ?></span>
            <span class="sum-lbl">Total Shipments</span>
        </div>
        <div class="sum-divider"></div>
        <div class="sum-cell">
            <span class="sum-num"><?php echo (int) ($manifest[ 'bag_count' ] ?? 0); ?></span>
            <span class="sum-lbl">Bags</span>
        </div>
        <div class="sum-divider"></div>
        <div class="sum-cell">
            <span class="sum-num"><?php echo number_format ( (float) ($manifest[ 'weight' ] ?? 0), 2 ); ?></span>
            <span class="sum-lbl">Weight (kg)</span>
        </div>
        <div class="sum-divider"></div>
        <div class="sum-cell">
            <span class="sum-num"><?php echo (int) ($manifest[ 'total_box' ] ?? 0); ?></span>
            <span class="sum-lbl">Boxes</span>
        </div>
        <div class="sum-divider"></div>
        <div class="sum-cell">
            <span class="sum-num"><?php echo count ( $tagNosInEntries ); ?></span>
            <span class="sum-lbl">Tags</span>
        </div>
    </div>

    <!-- ── Seal & Signature ──────────────────────────────────────── -->
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
                <div class="sig-box-header">Checked / Dispatched By</div>
                <div class="sig-box-body">
                    <div class="sig-seal-area">Seal</div>
                    <div class="sig-line">Signature &amp; Name</div>
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-box-header">Received By</div>
                <div class="sig-box-body">
                    <div class="sig-seal-area">Seal</div>
                    <div class="sig-line">Signature &amp; Name</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Print Footer ─────────────────────────────────────────── -->
    <div class="print-footer">
        <span>Manifest No: <?php echo h ( $manifest[ 'manifest_no' ] ); ?></span>
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
