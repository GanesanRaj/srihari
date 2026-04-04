<?php
if (session_status () === PHP_SESSION_NONE) {
    session_start ();
    }

require_once __DIR__ . '/config/config.php';

if ( ! isset ($_SESSION[ 'user_id' ])) {
    header ( 'Location: login.php' );
    exit;
    }

function h ($value)
    {
    return htmlspecialchars ( (string) $value, ENT_QUOTES, 'UTF-8' );
    }

$id        = isset ($_GET[ 'id' ]) ? (int) $_GET[ 'id' ] : 0;
$error     = '';
$tag       = null;
$entries   = [];
$itemCount = 0;

try {
    if ($id <= 0)
        throw new Exception( 'Tag ID is required' );

    $sql = "SELECT t.*,
                u1.username AS created_by_name,
                u2.username AS verified_by_name,
                bf.branch_name AS from_branch_name,
                bt.branch_name AS to_branch_name
            FROM tbl_tags t
            LEFT JOIN tbl_user  u1 ON u1.user_id  = t.created_by
            LEFT JOIN tbl_user  u2 ON u2.user_id  = t.verified_by
            LEFT JOIN tbl_branch bf ON bf.id       = t.from_branch
            LEFT JOIN tbl_branch bt ON bt.id       = t.to_branch
            WHERE t.id = :id LIMIT 1";

    $stmt = $pdo->prepare ( $sql );
    $stmt->execute ( [ ':id' => $id ] );
    $tag = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $tag)
        throw new Exception( 'Tag not found.' );

    $entries   = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
    $itemCount = count ( $entries );

    }
catch ( Exception $e ) {
    $error = $e->getMessage ();
    }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Print Tag: <?php echo h ( $tag[ 'tag_no' ] ?? '' ); ?></title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        @page {
            size: 100mm 70mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .label-container {
            width: 100mm;
            height: 70mm;
            padding: 2.5mm;
            position: relative;
            box-sizing: border-box;
            border: 1px dotted #999;
            margin: 0 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ─────────────────────────────────── */
        .lbl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1.5mm;
            margin-bottom: 1.5mm;
        }

        .lbl-title {
            font-weight: 900;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .lbl-date {
            font-size: 9px;
            color: #444;
            text-align: right;
        }

        /* ── Branch Row ──────────────────────────────── */
        .lbl-route {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2mm;
            margin-bottom: 1.5mm;
        }

        .lbl-branch-box {
            flex: 1;
            border: 1px solid #000;
            border-radius: 2px;
            padding: 1mm 2mm;
            text-align: center;
        }

        .lbl-branch-label {
            font-size: 7px;
            text-transform: uppercase;
            color: #666;
            font-weight: bold;
        }

        .lbl-branch-name {
            font-size: 11px;
            font-weight: 900;
            line-height: 1.2;
        }

        .lbl-arrow {
            font-size: 16px;
            font-weight: 900;
            color: #000;
            padding: 0 1mm;
        }

        /* ── Mid: Barcode + QR ───────────────────────── */
        .lbl-mid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex: 1;
        }

        .lbl-barcode-wrap {
            flex: 1;
            text-align: center;
        }

        .barcode-svg {
            width: 100%;
            height: 28px;
            display: block;
        }

        .tag-no-text {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 1px;
        }

        .lbl-qr-wrap {
            width: 22mm;
            text-align: center;
            padding-left: 2mm;
        }

        .qr-canvas {
            width: 100%;
            max-width: 20mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* ── Footer ──────────────────────────────────── */
        .lbl-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #000;
            padding-top: 1mm;
            margin-top: 1.5mm;
            font-size: 9px;
        }

        .lbl-items {
            font-weight: 900;
            font-size: 11px;
        }

        .lbl-creator {
            text-align: right;
            font-size: 8px;
            color: #444;
        }

        .error {
            padding: 20px;
            color: red;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php if ($error !== '') : ?>
        <div class="error"><?php echo h ( $error ); ?></div>
    <?php else : ?>
        <div class="label-container" id="printableArea">

            <!-- Header -->
            <div class="lbl-header">
                <div class="lbl-title">Manifest Tag</div>
                <div class="lbl-date">
                    <?php echo h ( date ( 'd M Y, h:i A', strtotime ( $tag[ 'created_at' ] ) ) ); ?>
                </div>
            </div>

            <!-- Branch Route -->
            <div class="lbl-route">
                <div class="lbl-branch-box">
                    <div class="lbl-branch-label">From</div>
                    <div class="lbl-branch-name"><?php echo h ( $tag[ 'from_branch_name' ] ?? '—' ); ?></div>
                </div>
                <div class="lbl-arrow">→</div>
                <div class="lbl-branch-box">
                    <div class="lbl-branch-label">To</div>
                    <div class="lbl-branch-name"><?php echo h ( $tag[ 'to_branch_name' ] ?? '—' ); ?></div>
                </div>
            </div>

            <!-- Barcode + QR -->
            <div class="lbl-mid">
                <div class="lbl-barcode-wrap">
                    <svg class="barcode-svg" id="tagBarcode"></svg>
                    <div class="tag-no-text"><?php echo h ( $tag[ 'tag_no' ] ); ?></div>
                </div>
                <div class="lbl-qr-wrap">
                    <canvas id="tagQrCode" class="qr-canvas"></canvas>
                </div>
            </div>

            <!-- Footer -->
            <div class="lbl-footer">
                <div class="lbl-items">📦 <?php echo $itemCount; ?> Items</div>
                <div class="lbl-creator">
                    By: <?php echo h ( $tag[ 'created_by_name' ] ?? '—' ); ?><br>
                    <?php if ( ! empty ($tag[ 'verified_by_name' ])) : ?>
                        Verified: <?php echo h ( $tag[ 'verified_by_name' ] ); ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var tagNo = "<?php echo h ( $tag[ 'tag_no' ] ); ?>";

                try {
                    JsBarcode("#tagBarcode", tagNo, {
                        format: "CODE128",
                        displayValue: false,
                        margin: 0,
                        width: 1.8,
                        height: 28
                    });
                } catch (err) { console.error("Barcode err:", err); }

                var qrCanvas = document.getElementById('tagQrCode');
                try {
                    QRCode.toCanvas(qrCanvas, tagNo, {
                        margin: 0,
                        width: 55,
                        color: { dark: '#000000', light: '#ffffff' }
                    }, function (error) { if (error) console.error(error); });
                } catch (err) { console.error("QR err:", err); }

                setTimeout(function () { window.print(); }, 600);
            });
        </script>
    <?php endif; ?>
</body>

</html>