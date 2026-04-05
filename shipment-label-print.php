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

function asMoney ($value)
    {
    if (is_numeric ( $value )) {
        return (float) $value;
        }
    $clean = preg_replace ( '/[^0-9.\-]/', '', (string) $value );
    if ($clean === '' || $clean === '-' || $clean === '.') {
        return 0.0;
        }
    return (float) $clean;
    }

function indianFormat ($number, $decimals = 2)
    {
    $number   = number_format ( (float) $number, $decimals, '.', '' );
    $parts    = explode ( '.', $number );
    $intPart  = $parts[ 0 ];
    $decPart  = isset ($parts[ 1 ]) ? '.' . $parts[ 1 ] : '';
    $negative = '';
    if (isset ($intPart[ 0 ]) && $intPart[ 0 ] === '-') {
        $negative = '-';
        $intPart  = substr ( $intPart, 1 );
        }
    if (strlen ( $intPart ) <= 3) {
        return $negative . $intPart . $decPart;
        }
    $lastThree = substr ( $intPart, -3 );
    $remaining = substr ( $intPart, 0, strlen ( $intPart ) - 3 );
    $remaining = preg_replace ( '/\B(?=(\d{2})+(?!\d))/', ',', $remaining );
    return $negative . $remaining . ',' . $lastThree . $decPart;
    }

function normalizeLabelResponse ($response)
    {
    if (is_array ( $response )) {
        return $response;
        }
    if (is_string ( $response )) {
        $decoded = json_decode ( $response, true );
        if (is_array ( $decoded )) {
            return $decoded;
            }
        }
    return [];
    }

function cleanAddress ($value)
    {
    $value = trim ( (string) $value );
    if ($value === '') {
        return '';
        }
    $value = preg_replace ( '/\s*-\s*/', ', ', $value );
    $value = preg_replace ( '/\s+/', ' ', $value );
    return trim ( $value, ', ' );
    }

function resolveLogoPath ($logo)
    {
    $logo = trim ( (string) $logo );
    if ($logo === '') {
        return 'assets/images/logo-black.png';
        }
    if (preg_match ( '#^https?://#i', $logo ) || strpos ( $logo, 'data:image' ) === 0) {
        return $logo;
        }
    return ltrim ( $logo, '/' );
    }

$bookingId = isset ($_GET[ 'id' ]) ? (int) $_GET[ 'id' ] : 0;
$waybill   = trim ( $_GET[ 'waybill' ] ?? '' );
$pdfSize   = strtoupper ( trim ( $_GET[ 'pdf_size' ] ?? 'A4' ) );
if ( ! in_array ( $pdfSize, [ 'A4', '4R' ], true )) {
    $pdfSize = 'A4';
    }

$error  = '';
$labels = [];

try {
    if ($bookingId <= 0 && $waybill === '') {
        throw new Exception( 'Booking ID or Waybill is required' );
        }

    $sql = "SELECT 
                b.id,
                b.ewaybill_no,
                b.booking_ref_id,
                b.waybill_no,
                b.courier_id,
                b.pickup_point_id,
                b.consignee_name,
                b.consignee_phone,
                b.consignee_address,
                b.consignee_city,
                b.consignee_state,
                b.consignee_pin,
                b.payment_mode,
                b.cod_amount,
                b.invoice_value,
                b.product_desc,
                b.rto_address,
                b.api_response,
                b.created_at,
                b.invoice_no,
                b.shipping_mode,
                b.weight/1000 AS shipment_weight,
                b.quantity AS total_quantity,
                p.name AS pickup_name,
                p.address AS pickup_address,
                p.city AS pickup_city,
                p.pin AS pickup_pin,
                p.courier_id AS pickup_courier_id,
                p.company_id AS pickup_company_id,
                b.shipper_name,
                b.shipper_address,
                b.shipper_city,
                b.shipper_pin,
                b.shipper_state,
                br.company_id AS branch_company_id,
                co.company_name,
                co.company_logo AS company_logo,
                cl.client_logo AS client_logo
            FROM tbl_bookings b
            LEFT JOIN tbl_pickup_points p ON p.id = b.pickup_point_id
            LEFT JOIN tbl_branch br ON br.id = p.branch_id
            LEFT JOIN tbl_company co ON co.id = COALESCE(p.company_id, br.company_id)
            LEFT JOIN tbl_client cl ON cl.id = b.client_id
            WHERE " . ($bookingId > 0 ? "b.id = :id" : "b.waybill_no = :waybill") . "
            LIMIT 1";

    $stmt = $pdo->prepare ( $sql );
    if ($bookingId > 0) {
        $stmt->execute ( [ ':id' => $bookingId ] );
        } else {
        $stmt->execute ( [ ':waybill' => $waybill ] );
        }
    $booking = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $booking) {
        throw new Exception( 'Booking not found' );
        }

    $waybillNo = $booking[ 'waybill_no' ] ?: $waybill;
    // For MPS, collect all child waybills from tbl_booking_packages first, then api_response as fallback
    $allWaybills = [ $waybillNo ];

    // Check tbl_booking_packages for child AWBs
    $pkgAwbStmt = $pdo->prepare ( "SELECT awb_no FROM tbl_booking_packages WHERE booking_id = :bid AND awb_no IS NOT NULL AND awb_no != '' ORDER BY row_no ASC" );
    $pkgAwbStmt->execute ( [ ':bid' => $booking[ 'id' ] ] );
    $pkgAwbs = $pkgAwbStmt->fetchAll ( PDO::FETCH_COLUMN );
    if ( ! empty ($pkgAwbs)) {
        $allWaybills = $pkgAwbs;
    } else {
        // Fallback: extract from api_response
        $apiData = json_decode ( $booking[ 'api_response' ] ?? '', true );
        if ( ! empty ($apiData[ 'packages' ]) && is_array ( $apiData[ 'packages' ] )) {
            $extracted = [];
            foreach ($apiData[ 'packages' ] as $pkg) {
                if ( ! empty ($pkg[ 'waybill' ])) {
                    $extracted[] = $pkg[ 'waybill' ];
                    }
                }
            if ( ! empty ($extracted)) {
                $allWaybills = $extracted;
                }
            }
        }
    // Deduplicate and convert to comma-separated string for API call
    $allWaybills  = array_unique ( $allWaybills );
    $waybillParam = implode ( ',', $allWaybills );

    if ($waybillNo === '') {
        throw new Exception( 'Waybill not generated yet' );
        }

    $courierId = (int) ($booking[ 'courier_id' ] ?: $booking[ 'pickup_courier_id' ]);
    if ($courierId <= 0) {
        throw new Exception( 'Courier not found for this booking' );
        }

    $courierStmt = $pdo->prepare ( "SELECT id, partner_name, partner_code, api_key, api_url FROM tbl_courier_partner WHERE id = :id LIMIT 1" );
    $courierStmt->execute ( [ ':id' => $courierId ] );
    $courierData = $courierStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $courierData) {
        throw new Exception( 'Courier credentials not found' );
        }

    // For OWN COURIER: prefer client_logo, fallback to company_logo
    $rawLogo      = ! empty ($booking[ 'client_logo' ]) ? $booking[ 'client_logo' ] : ($booking[ 'company_logo' ] ?? '');
    $clientLogo   = resolveLogoPath ( $rawLogo );
    $invoiceValue = asMoney ( $booking[ 'invoice_value' ] ?? 0 );
    $bookingCod   = asMoney ( $booking[ 'cod_amount' ] ?? 0 );
    $deliveryType = strtoupper ( trim ( $booking[ 'payment_mode' ] ?? 'Prepaid' ) );
    $lineTotal    = $deliveryType === 'COD' ? $bookingCod : $invoiceValue;

    // ─── OWN COURIER (ID = 2): Build labels from tbl_booking_packages ───────
    if ($courierId == 2) {
        $pkgRows = $pdo->prepare ( "SELECT * FROM tbl_booking_packages WHERE booking_id = :bid ORDER BY row_no ASC" );
        $pkgRows->execute ( [ ':bid' => $booking[ 'id' ] ] );
        $childPackages = $pkgRows->fetchAll ( PDO::FETCH_ASSOC );

        if (empty ($childPackages)) {
            // Fallback: single label with master waybill
            $childPackages = [
                [
                    'awb_no' => $waybillNo,
                    'row_no' => 1,
                    'boxes' => $booking[ 'quantity' ] ?? 1,
                    'actual_weight' => $booking[ 'weight' ] ?? 0,
                    'charged_weight' => $booking[ 'weight' ] ?? 0,
                ]
            ];
            }

        $totalPkgs  = count ( $childPackages );
        $sellerAddr = cleanAddress (
            ($booking[ 'pickup_name' ] ?? '') . ', ' .
            ($booking[ 'pickup_address' ] ?? '') . ', ' .
            ($booking[ 'pickup_city' ] ?? '') . ' - ' .
            ($booking[ 'pickup_pin' ] ?? '')
        );
        $returnAddr = cleanAddress ( $booking[ 'rto_address' ] ?? $booking[ 'pickup_address' ] ?? '' );

        foreach ($childPackages as $i => $pkg) {
            // Single booking label: barcode/waybill text = AWB (awb_no). child_ewaybill_no shown separately when different.
            $childAwb      = isset ($pkg[ 'awb_no' ]) && trim ( (string) $pkg[ 'awb_no' ] ) !== ''
                ? trim ( (string) $pkg[ 'awb_no' ] )
                : ($waybillNo . ($i > 0 ? '-' . $i : ''));
            $childEwaybill = isset ($pkg[ 'child_ewaybill_no' ]) && trim ( (string) $pkg[ 'child_ewaybill_no' ] ) !== ''
                ? trim ( (string) $pkg[ 'child_ewaybill_no' ] ) : $childAwb;
            $labels[]      = [
                'client_logo' => $clientLogo,
                'sha_logo' => resolveLogoPath ( $booking[ 'company_logo' ] ?? '' ),
                'delhivery_logo' => '',
                'courier_name' => $courierData[ 'partner_name' ] ?? 'Own Courier',
                'master_waybill' => $waybillNo,
                'is_master' => ($i === 0),
                'waybill' => $childAwb,
                'child_ewaybill_no' => $childEwaybill,
                'short_code' => '',
                'pincode' => $booking[ 'consignee_pin' ] ?? '',
                'ship_name' => $booking[ 'consignee_name' ] ?? '',
                'ship_phone' => $booking[ 'consignee_phone' ] ?? '',
                'ship_address' => cleanAddress ( $booking[ 'consignee_address' ] ?? '' ),
                'ship_destination' => cleanAddress ( ($booking[ 'consignee_city' ] ?? '') . ' - ' . ($booking[ 'consignee_state' ] ?? '') ),
                'delivery_type' => $booking[ 'payment_mode' ] ?? 'Prepaid',
                'mode' => $booking[ 'shipping_mode' ] ?? '',
                'line_price' => $invoiceValue,
                'line_total' => $lineTotal,
                'product_name' => $booking[ 'product_desc' ] ?: 'Item',
                'seller_name' => $booking[ 'shipper_name' ] ?? $booking[ 'pickup_name' ] ?? $booking[ 'company_name' ] ?? '',
                'seller_address' => cleanAddress (
                    ($booking[ 'shipper_address' ] ?? '') . ' ' .
                    ($booking[ 'shipper_city' ] ?? '') . ' ' .
                    ($booking[ 'shipper_state' ] ?? '') . ' - ' .
                    ($booking[ 'shipper_pin' ] ?? '')
                ),
                'order_id' => $booking[ 'booking_ref_id' ] ?? '',
                'ewaybill_no' => $booking[ 'ewaybill_no' ] ?? '',
                'return_address' => $returnAddr,
                'return_pin' => $booking[ 'pickup_pin' ] ?? '',
                'created_at' => $booking[ 'created_at' ] ?? date ( 'Y-m-d H:i:s' ),
                'invoice_no' => $booking[ 'invoice_no' ] ?? '',
                'invoice_date' => $booking[ 'created_at' ] ?? '',
                'shipment_type' => $booking[ 'shipping_mode' ] ?? '',
                'shipment_weight' => number_format ( $booking[ 'shipment_weight' ] ?? 0, 2 ),
                'total_packages' => $booking[ 'total_quantity' ] ?? 1,
                'child_awb' => $childAwb,
                'total_pkgs' => $totalPkgs,
                'pkg_no' => $i + 1,
                'boxes' => $pkg[ 'boxes' ] ?? 1,
                'actual_weight' => number_format ( $pkg[ 'actual_weight' ] ?? 0, 2 ),
                'charged_weight' => number_format ( $pkg[ 'charged_weight' ] ?? 0, 2 ),
                'dimensions' => number_format ( $pkg[ 'length' ] ?? 0, 2 ) . 'x' . number_format ( $pkg[ 'width' ] ?? 0, 2 ) . 'x' . number_format ( $pkg[ 'height' ] ?? 0, 2 ),
            ];
            }

        if (count ( $labels ) === 0) {
            throw new Exception( 'No package rows found for this own booking' );
            }

        } else {
        // ─── EXTERNAL COURIERS (Delhivery etc.) ─────────────────────────────
        require_once __DIR__ . '/api/label/services/courier_service.php';
        $apiResult = generateLabelFromCourier ( $courierData, [
            'waybill' => $waybillParam,
            'pdf' => false,
            'pdf_size' => $pdfSize
        ] );

        if (empty ($apiResult[ 'success' ])) {
            throw new Exception( $apiResult[ 'message' ] ?? 'Unable to fetch label JSON' );
            }

        $decoded = normalizeLabelResponse ( $apiResult[ 'response' ] ?? [] );
        if ( ! isset ($decoded[ 'packages' ]) || ! is_array ( $decoded[ 'packages' ] ) || count ( $decoded[ 'packages' ] ) === 0) {
            throw new Exception( 'No package records in courier JSON' );
            }

        // Load tbl_booking_packages for MPS package details
        $bpStmt = $pdo->prepare ( "SELECT * FROM tbl_booking_packages WHERE booking_id = :bid ORDER BY row_no ASC" );
        $bpStmt->execute ( [ ':bid' => $booking[ 'id' ] ] );
        $bpRows    = $bpStmt->fetchAll ( PDO::FETCH_ASSOC );
        $bpByAwb   = [];
        foreach ($bpRows as $bp) {
            $bpAwb = trim ( (string) ($bp[ 'awb_no' ] ?? '') );
            if ($bpAwb !== '') {
                $bpByAwb[$bpAwb] = $bp;
                }
            }
        $totalPkgs = max ( count ( $bpRows ), count ( $decoded[ 'packages' ] ) );

        $pkgIdx = 0;
        foreach ($decoded[ 'packages' ] as $package) {
            if ( ! is_array ( $package ))
                continue;
            $pkgIdx++;

            $pDeliveryType = trim ( (string) ($package[ 'pt' ] ?? $booking[ 'payment_mode' ] ?? '') );
            $mode          = trim ( (string) ($package[ 'mot' ] ?? '') );
            $amountFromPkg = asMoney ( $package[ 'rs' ] ?? 0 );
            $codAmt        = asMoney ( $package[ 'cod' ] ?? 0 );
            $lPrice        = $amountFromPkg > 0 ? $amountFromPkg : ($invoiceValue > 0 ? $invoiceValue : ($bookingCod > 0 ? $bookingCod : 0));
            $lTotal        = strtoupper ( $pDeliveryType ) === 'COD'
                ? ($codAmt > 0 ? $codAmt : ($bookingCod > 0 ? $bookingCod : $lPrice))
                : $lPrice;

            $productName = trim ( (string) ($package[ 'prd' ] ?? $booking[ 'product_desc' ] ?? '') ) ?: 'Item';
            $sellerAddr  = trim ( (string) ($package[ 'sadd' ] ?? '') );
            if ($sellerAddr === '') {
                $sellerAddr = trim ( ($booking[ 'pickup_address' ] ?? '') . ', ' . ($booking[ 'pickup_city' ] ?? '') . ' - ' . ($booking[ 'pickup_pin' ] ?? '') );
                }
            $returnAddr = trim ( (string) ($package[ 'radd' ] ?? $booking[ 'rto_address' ] ?? $booking[ 'pickup_address' ] ?? '') );

            // Match with tbl_booking_packages by AWB
            $thisWbn = trim ( (string) ($package[ 'wbn' ] ?? $waybillNo) );
            $bpMatch = $bpByAwb[$thisWbn] ?? ($bpRows[$pkgIdx - 1] ?? []);

            $labels[] = [
                'client_logo' => $clientLogo,
                'sha_logo' => resolveLogoPath ( $booking[ 'company_logo' ] ?? '' ),
                'delhivery_logo' => trim ( (string) ($package[ 'delhivery_logo' ] ?? '') ),
                'courier_name' => trim ( (string) ($package[ 'courier_name' ] ?? $courierData[ 'partner_name' ] ?? '') ),
                'master_waybill' => trim ( (string) ($package[ 'mwn' ] ?? '') ),
                'is_master' => isset ($package[ 'mwn' ], $package[ 'wbn' ]) ? ((string) $package[ 'mwn' ] === (string) $package[ 'wbn' ]) : null,
                'waybill' => $thisWbn,
                'short_code' => trim ( (string) ($package[ 'sort_code' ] ?? '') ),
                'pincode' => trim ( (string) ($package[ 'pin' ] ?? $booking[ 'consignee_pin' ] ?? '') ),
                'ship_name' => trim ( (string) ($package[ 'name' ] ?? $booking[ 'consignee_name' ] ?? '') ),
                'ship_phone' => $booking[ 'consignee_phone' ] ?? '',
                'ship_address' => cleanAddress ( $package[ 'address' ] ?? $booking[ 'consignee_address' ] ?? '' ),
                'ship_destination' => cleanAddress ( $package[ 'destination' ] ?? (($booking[ 'consignee_city' ] ?? '') . ' - ' . ($booking[ 'consignee_state' ] ?? '')) ),
                'delivery_type' => $pDeliveryType,
                'mode' => $mode,
                'line_price' => $lPrice,
                'line_total' => $lTotal,
                'product_name' => $productName,
                'seller_name' => trim ( (string) ($package[ 'snm' ] ?? $booking[ 'pickup_name' ] ?? $booking[ 'company_name' ] ?? '') ),
                'seller_address' => cleanAddress ( $sellerAddr ),
                'order_id' => trim ( (string) ($package[ 'oid' ] ?? $booking[ 'booking_ref_id' ] ?? '') ),
                'return_address' => cleanAddress ( $returnAddr ),
                'return_pin' => trim ( (string) ($package[ 'rpin' ] ?? $booking[ 'pickup_pin' ] ?? '') ),
                'created_at' => trim ( (string) ($package[ 'cd' ] ?? $booking[ 'created_at' ] ?? date ( 'Y-m-d H:i:s' )) ),
                'invoice_no' => $booking[ 'invoice_no' ] ?? '',
                'invoice_date' => $booking[ 'created_at' ] ?? '',
                'shipment_type' => $booking[ 'shipping_mode' ] ?? '',
                'shipment_weight' => number_format ( $booking[ 'shipment_weight' ] ?? 0, 2 ),
                'total_packages' => $booking[ 'total_quantity' ] ?? 1,
                'child_awb' => $thisWbn,
                'total_pkgs' => $totalPkgs,
                'pkg_no' => $pkgIdx,
                'boxes' => $bpMatch[ 'boxes' ] ?? 1,
                'actual_weight' => number_format ( $bpMatch[ 'actual_weight' ] ?? 0, 2 ),
                'charged_weight' => number_format ( $bpMatch[ 'charged_weight' ] ?? 0, 2 ),
                'dimensions' => ! empty ($bpMatch) ? (number_format ( $bpMatch[ 'length' ] ?? 0, 2 ) . 'x' . number_format ( $bpMatch[ 'width' ] ?? 0, 2 ) . 'x' . number_format ( $bpMatch[ 'height' ] ?? 0, 2 )) : '0x0x0',
            ];
            }

        if (count ( $labels ) === 0) {
            throw new Exception( 'No valid package rows found' );
            }
        }

    }
catch ( Exception $e ) {
    $error = $e->getMessage ();
    }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Shipment Label Print</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        @page {
            size: 101.6mm 152.4mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 101.6mm;
            height: 152.4mm;
            margin: 0;
            padding: 1mm;
            page-break-after: always;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .label-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border: 2px solid #000;
            font-size: 9px;
            table-layout: fixed;
            margin: 0;
        }

        .label-table>tbody>tr>td {
            border-top: 1px solid #000;
            border-left: none;
            border-right: none;
            border-bottom: none;
            vertical-align: top;
            padding: 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .label-table>tbody>tr:first-child>td {
            border-top: none;
        }

        .label-table>tbody>tr:last-child>td {
            border-bottom: none;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .inner-table td {
            border: 1px solid #000;
            padding: 1.8mm;
            vertical-align: top;
        }

        .inner-table td:not(:first-child) {
            border-left: 1px solid #000;
        }

        .inner-table tr:not(:first-child) td {
            border-top: 1px solid #000;
        }

        .center {
            text-align: center;
            vertical-align: middle;
        }

        .v-top {
            vertical-align: top;
        }

        .v-middle {
            vertical-align: middle;
        }

        .fw-bold {
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .logo-section {
            padding: 1.5mm;
            text-align: center;
            vertical-align: middle;
            background: #fff;
        }

        .logo-img {
            max-width: 65px;
            max-height: 20px;
            display: inline-block;
            vertical-align: middle;
        }

        .barcode-section {
            padding: 10px 2mm 2mm 2mm;
            text-align: center;
        }

        .barcode-svg {
            width: 90%;
            height: 40px;
            display: block;
            margin: 0 auto 1.5mm;
        }

        .order-barcode-svg {
            width: 78%;
            height: 34px;
            display: block;
            margin: 0 auto 1mm;
        }

        .waybill-text {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            margin: 1mm 0;
        }

        .pin-sc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1mm 2mm 0;
        }

        .pin-text,
        .sc-text {
            font-size: 10.5px;
            font-weight: 700;
        }

        .section-header {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.8mm;
            color: #000;
            letter-spacing: 0.3px;
        }

        .ship-name {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 0.8mm;
            line-height: 1.2;
        }

        .ship-address {
            font-size: 8.5px;
            line-height: 1.4;
            max-height: 40px;
            overflow: hidden;
        }

        .ship-pin {
            font-size: 10px;
            font-weight: 700;
            margin-top: 0.8mm;
        }

        .payment-box {
            text-align: center;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .payment-type {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 1mm;
        }

        .payment-mode {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 1mm;
        }

        .payment-currency {
            font-size: 8.5px;
            margin-bottom: 0.5mm;
        }

        .payment-amount {
            font-size: 11px;
            font-weight: 700;
        }

        .seller-label {
            font-size: 9px;
            font-weight: 700;
        }

        .seller-address {
            font-size: 8px;
            line-height: 1.4;
            max-height: 28px;
            overflow: hidden;
            margin-top: 0.8mm;
        }

        .date-box {
            font-size: 8.5px;
            line-height: 1.5;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .date-label {
            font-weight: 700;
            font-size: 9px;
            margin-bottom: 0.5mm;
        }

        .product-header {
            background: #f5f5f5;
            font-weight: 700;
            font-size: 8.5px;
            padding: 1.2mm;
            text-align: left;
            vertical-align: middle;
        }

        .product-cell {
            font-size: 8.5px;
            padding: 1.5mm;
        }

        .product-name {
            max-height: 20px;
            overflow: hidden;
            line-height: 1.35;
        }

        .product-price {
            font-size: 8.5px;
            text-align: center;
            line-height: 1.4;
        }

        .product-price b {
            font-size: 9px;
        }

        .order-section {
            padding: 2mm;
            text-align: center;
        }

        .order-text {
            font-size: 9.5px;
            font-weight: 700;
            margin-top: 1mm;
            letter-spacing: 0.3px;
        }

        .return-section {
            padding: 2mm;
        }

        .return-label {
            font-size: 9px;
            font-weight: 700;
        }

        .return-address {
            font-size: 8px;
            line-height: 1.4;
            max-height: 26px;
            overflow: hidden;
            margin-top: 0.8mm;
        }

        .small {
            font-size: 8.5px;
        }

        .error {
            text-align: center;
            color: #b91c1c;
            font-weight: 700;
            padding: 20px;
        }

        /* Redesigned Label Styles */
        .label-grid {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .label-grid td {
            border: 1px solid #000;
            padding: 1.5mm;
            vertical-align: top;
            word-wrap: break-word;
            overflow: hidden;
        }

        .label-grid .header-cell {
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            color: #555;
            display: block;
            margin-bottom: 0.5mm;
        }

        .label-grid .content-cell {
            font-size: 10px;
            font-weight: bold;
            display: block;
        }

        .logo-img {
            max-width: 100px;
            max-height: 35px;
            object-fit: contain;
        }

        .courier-type {
            font-size: 14px;
            font-weight: 900;
            text-align: center;
        }

        .barcode-container {
            text-align: center;
            padding: 5px !important;
        }

        .barcode-svg {
            width: 95%;
            height: 40px;
        }

        .waybill-num {
            font-size: 12px;
            font-weight: 800;
            margin-top: 2px;
        }

        .details-small {
            font-size: 8.5px;
            line-height: 1.2;
        }

        .tc-box {
            font-size: 7px;
            line-height: 1.1;
            text-align: justify;
        }

        .sign-box {

            border-top: 1px dashed #000;
            margin-top: 45px;
            text-align: center;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <?php if ($error !== '') : ?>
        <div class="error"><?php echo h ( $error ); ?></div>
    <?php else : ?>
        <?php foreach ($labels as $idx => $label) : ?>
            <div class="page">
                <table class="label-grid">
                    <!-- Row 1: Client Logo | SHA Logo -->
                    <tr>
                        <!--<td style="width: 50%; text-align:center; vertical-align:middle;">
                            <img class="logo-img" src="<?php echo h ( $label[ 'client_logo' ] ); ?>" alt="Client Logo">
                        </td>-->
                        <td rowspan="2" style="width: 50%; text-align:center; vertical-align:middle;">
                            <img class="logo-img" src="<?php echo h ( $label[ 'sha_logo' ] ); ?>" alt="SHA Logo">
                        </td>
                        <td style="padding-bottom: 0px;">
                            <span class="header-cell">Ref NO:</span>
                            <div class="content-cell"><b style="font-size:12px;"><?php echo h ( $label[ 'order_id' ] ); ?></b>
                            </div>
                        </td>

                    </tr>
                    <tr>
                        <td style="padding-bottom: 0px;">
                            <span class="header-cell">DATE:</span>
                            <div class="content-cell">
                                <b style="font-size:12px;">
                                    <?php echo h ( $label[ 'invoice_date' ] ? date ( 'd-M-Y', strtotime ( $label[ 'invoice_date' ] ) ) : '' ); ?></b>
                            </div>
                        </td>
                    </tr>

                    <!-- Row 2: Sender Details | Receiver Details -->
                    <tr>
                        <td>
                            <span class="header-cell">SENDER:</span>
                            <div class="details-small">
                                <b style="font-size:10px;"><?php echo h ( $label[ 'seller_name' ] ); ?></b><br>
                                <?php echo h ( $label[ 'seller_address' ] ); ?>
                            </div>
                        </td>
                        <td>
                            <span class="header-cell">RECEIVER:</span>
                            <div class="details-small">
                                <b style="font-size:10px;"><?php echo h ( $label[ 'ship_name' ] ); ?></b><br>
                                <?php if ( ! empty ($label[ 'ship_phone' ])) : ?>
                                    <span style="font-size:8.5px;">&#128222; <?php echo h ( $label[ 'ship_phone' ] ); ?></span><br>
                                <?php endif; ?>
                                <?php echo h ( $label[ 'ship_address' ] ); ?><br>
                                <b>PIN: <?php echo h ( $label[ 'pincode' ] ); ?></b>
                            </div>
                        </td>
                    </tr>

                    <!-- Row 3: Date Of Shipment | Shipment Type -->
                    <tr>
                        <td>
                            <span class="header-cell">DATE OF SHIPMENT:</span>
                            <div class="content-cell">
                                <b
                                    style="font-size:12px;"><?php echo h ( date ( 'd-M-Y', strtotime ( $label[ 'created_at' ] ) ) ); ?></b>
                            </div>
                        </td>
                        <td>
                            <span class="header-cell">SHIPMENT TYPE:</span>
                            <div class="content-cell"><b style="font-size:12px;"><?php
                            $type = $label[ 'shipment_type' ] ?? 'Normal';

                            if ($type === 'Express') {
                                $type = 'Air';
                                }

                            echo h ( $type );
                            ?></b>
                            </div>
                        </td>
                    </tr>

                    <!-- Row 4: Barcode -->
                    <!--<tr>-->
                    <!--    <td colspan="2" class="barcode-container">-->
                    <!--        <svg class="barcode-svg" data-value="<?php echo h ( $label[ 'waybill' ] ); ?>"></svg>-->
                    <!--        <div class="waybill-num"><?php echo h ( $label[ 'waybill' ] ); ?></div>-->
                    <!--    </td>-->
                    <!--</tr>-->
                    <?php
                    // Check if E-waybill exists
                    $hasEway = (isset ($label[ 'ewaybill_no' ]) && $label[ 'ewaybill_no' ] != '');

                    // If E-waybill is MISSING: Use height 85px, Width 80%, and Middle Alignment
                    // If E-waybill EXISTS:  Use height 55px, Width 98%, and Top Alignment
                    $barHeight = $hasEway ? '65px' : '85px';
                    $barWidth  = $hasEway ? '110%' : '80%';
                    $tdAlign   = $hasEway ? 'padding: 5px !important;' : 'vertical-align: middle !important; padding: 0 !important;';
                    ?>
                    <tr>
                        <td colspan="2" class="barcode-container" style="<?php echo $tdAlign; ?>">
                            <svg class="barcode-svg"
                                style="height: <?php echo $barHeight; ?> !important; width: <?php echo $barWidth; ?> !important;"
                                data-value="<?php echo h ( $label[ 'waybill' ] ); ?>"></svg>
                            <div class="waybill-num"
                                style="font-size: 28px; font-weight: 900; letter-spacing: 1px; margin-top: 0px;">
                                <?php echo h ( $label[ 'waybill' ] ); ?></div>
                        </td>
                    </tr>

                    <!-- Row 5: Shipment Parent No | Desc -->
                    <tr>
                        <td>
                            <span class="header-cell">SHIPMENT PARENT NO:</span>
                            <div class="content-cell"><?php echo h ( $label[ 'master_waybill' ] ); ?></div>
                        </td>
                        <td>
                            <span class="header-cell">No of Box</span>
                            <div class="details-small"><b
                                    style="font-size:12px;"><?php echo h ( $label[ 'total_packages' ] ); ?></b></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="header-cell">DESC:</span>
                            <div class="details-small"><?php echo h ( $label[ 'product_name' ] ); ?></div>
                        </td>
                    </tr>

                    <!-- Row 8: Invoice Value | Payment Mode -->
                    <tr>
                        <td>
                            <span class="header-cell">INVOICE VALUE:</span>
                            <div class="content-cell" style="font-size: 13px;">
                                &#8377;<?php echo h ( indianFormat ( $label[ 'line_price' ] ) ); ?>
                            </div>
                        </td>
                        <td>
                            <span class="header-cell">PAYMENT MODE:</span>
                            <div class="content-cell" style="font-size: 13px;"><?php echo h ( $label[ 'delivery_type' ] ); ?>
                            </div>
                        </td>
                    </tr>

                    <?php if (isset ($label[ 'ewaybill_no' ]) && $label[ 'ewaybill_no' ] != '') { ?>
                        <!-- Row 9: Invoice No | Invoice Date -->
                        <tr>
                            <!--<td colspan="1" class="barcode-container">
                            Reference Number
                            <svg style="height: 40px;width: 100%;" class="barcode-svg" data-value="<?php echo h ( $label[ 'ewaybill_no' ] ); ?>"></svg>
                            <div class="waybill-num"><?php echo h ( $label[ 'ewaybill_no' ] ); ?></div>
                        </td>-->
                            <!--<td colspan="2" class="barcode-container">-->
                            <!--    E-waybill Number-->
                            <!--    <svg style="height: 40px !important;width: 100%;" class="barcode-svg" data-value="<?php echo h ( $label[ 'ewaybill_no' ] ); ?>"></svg>-->
                            <!--    <div class="waybill-num"><?php echo h ( $label[ 'ewaybill_no' ] ); ?></div>-->
                            <!--</td>-->
                            <td colspan="2" class="barcode-container"
                                style="padding-top: 2px !important; padding-bottom: 2px !important;">
                                <span style="font-size: 8px; font-weight: bold;">E-waybill Number</span>
                                <!-- Changed height from 40px to 28px and width from 100% to 75% -->
                                <svg style="height: 25px !important; width: 50%;" class="barcode-svg"
                                    data-value="<?php echo h ( $label[ 'ewaybill_no' ] ); ?>"></svg>
                                <!-- Added inline style to reduce font size from 12px to 10px -->
                                <div class="waybill-num" style="font-size: 8px; margin-top: 0;">
                                    <?php echo h ( $label[ 'ewaybill_no' ] ); ?></div>
                            </td>
                        </tr>
                    <?php } ?>



                    <!--<tr>
                        <td>
                            <span class="header-cell">INVOICE NO:</span>
                            <div class="content-cell"><?php echo h ( $label[ 'invoice_no' ] ); ?></div>
                        </td>
                        <td>
                            <span class="header-cell">INVOICE DATE:</span>
                            <div class="content-cell">
                                <?php echo h ( $label[ 'invoice_date' ] ? date ( 'd-M-Y', strtotime ( $label[ 'invoice_date' ] ) ) : '' ); ?>
                            </div>
                        </td>
                    </tr>-->

                    <!-- Row 10: T&C | Customer Sign -->
                    <tr>
                        <td>
                            <span class="header-cell">T&C:</span>
                            <div class="tc-box">
                                All disputes are subject to local jurisdiction. Goods once received will not be returned.
                                Carrier is not responsible for any delay.
                            </div>
                        </td>
                        <td style="vertical-align: bottom;">
                            <div class="sign-box">
                                Customer Seal & Signature
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>

        <script>
            (function () {
                function renderBarcodes() {
                    if (typeof JsBarcode !== 'function') {
                        return;
                    }
                    document.querySelectorAll('svg.barcode-svg, svg.order-barcode-svg').forEach(function (svg) {
                        var value = (svg.getAttribute('data-value') || '').trim();
                        if (!value) {
                            return;
                        }
                        try {
                            JsBarcode(svg, value, {
                                format: 'CODE128',
                                displayValue: false,
                                margin: 0,
                                width: 1.4,
                                height: 40
                            });
                        } catch (e) {
                            // keep text fallback only
                        }
                    });
                }

                function doPrint() {
                    setTimeout(function () {
                        window.print();
                    }, 300);
                }

                var js = document.createElement('script');
                js.src = 'https://cdn.jsdelivr.net/npm/jsbarcode@3.12.1/dist/JsBarcode.all.min.js';
                js.onload = function () {
                    renderBarcodes();
                    doPrint();
                };
                js.onerror = function () {
                    doPrint();
                };
                document.head.appendChild(js);
            })();
        </script>


    <?php endif; ?>
</body>
<style>
    .ref-barcode-svg {
        width: 95%;
        height: 40px;
    }
</style>

</html>