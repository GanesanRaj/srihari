<?php
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$id = (int) ($_GET[ 'id' ] ?? 0);

if ($id <= 0) {
    die ('Invalid Manifest ID');
    }

try {
    // 1. Fetch Manifest Details
    $stmt = $pdo->prepare ( "
        SELECT m.*, 
               b1.branch_name AS from_branch_name, 
               b2.branch_name AS to_branch_name,
               c.name AS coloader_name
        FROM tbl_manifest m
        LEFT JOIN tbl_branch b1 ON b1.id = m.from_branch
        LEFT JOIN tbl_branch b2 ON b2.id = m.to_branch
        LEFT JOIN tbl_coloader c ON c.id = m.coloader_id
        WHERE m.id = :id 
        LIMIT 1
    " );
    $stmt->execute ( [ ':id' => $id ] );
    $manifest = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $manifest) {
        die ('Manifest not found');
        }

    $entries = json_decode ( $manifest[ 'json_data' ] ?: '[]', true );
    if ( ! is_array ( $entries )) {
        $entries = [];
        }

    // Load additional booking details (E-way bill, shipping mode, address, phone) if necessary
    $bookingIds  = array_filter ( array_column ( $entries, 'booking_id' ) );
    $bookingData = [];
    if ( ! empty ($bookingIds)) {
        $placeholders = implode ( ',', array_fill ( 0, count ( $bookingIds ), '?' ) );
        $bStmt        = $pdo->prepare ( "SELECT id, ewaybill_no, shipping_mode, consignee_address, consignee_phone FROM tbl_bookings WHERE id IN ($placeholders)" );
        $bStmt->execute ( array_values ( $bookingIds ) );
        while ($row = $bStmt->fetch ( PDO::FETCH_ASSOC )) {
            $bookingData[$row[ 'id' ]] = $row;
            }
        }

    // 3. Set Headers for Excel/CSV Download
    $filename = "Manifest_" . $manifest[ 'manifest_no' ] . ".csv";

    header ( 'Content-Type: text/csv; charset=utf-8' );
    header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );

    // Open output stream
    $output = fopen ( 'php://output', 'w' );

    // Add BOM for proper UTF-8 rendering in Excel
    fputs ( $output, $bom = (chr ( 0xEF ) . chr ( 0xBB ) . chr ( 0xBF )) );

    // 4. Write Header Information
    fputcsv ( $output, [ 'Manifest Details' ] );
    fputcsv ( $output, [ 'Manifest No:', $manifest[ 'manifest_no' ], '', 'Status:', $manifest[ 'status' ] ] );
    fputcsv ( $output, [ 'From Branch:', $manifest[ 'from_branch_name' ] ?: '-', '', 'To Branch:', $manifest[ 'to_branch_name' ] ?: '-' ] );
    fputcsv ( $output, [ 'Coloader:', $manifest[ 'coloader_name' ] ?: '-', '', 'CD No:', $manifest[ 'cd_no' ] ?: '-' ] );
    fputcsv ( $output, [ 'Vehicle No:', $manifest[ 'vehicle_no' ] ?: '-', '', 'Driver:', $manifest[ 'driver_name' ] ?: '-' ] );
    fputcsv ( $output, [ 'Mobile No:', $manifest[ 'mobile_no' ] ?: '-', '', 'Total Bags:', $manifest[ 'bag_count' ] ?: '0' ] );
    fputcsv ( $output, [ 'Total Boxes:', $manifest[ 'total_box' ] ?: '0', '', 'Weight (kg):', $manifest[ 'weight' ] ?: '0' ] );
    fputcsv ( $output,[ 'Dispatch Mode:', $manifest[ 'dispatch_mode' ] ?: '-' ] );
    fputcsv ( $output, [] ); // Empty row for spacing

    // 5. Write Table Column Headers
    fputcsv ( $output, [
        'Sl No',
        'AWB No',
        'Tag No',
        'Consignee Name',
        'City',
        'Address',
        'Phone',
        'E-way Bill No',
        'Mode of Shipment',
        'Scanned At'
    ] );

    // 6. Write Data Rows
    $sl = 1;
    foreach ($entries as $row) {
        $bId   = $row[ 'booking_id' ] ?? null;
        $bData = $bId && isset ($bookingData[$bId]) ? $bookingData[$bId] : [];

        $ewaybill      = ! empty ($bData[ 'ewaybill_no' ]) ? '="' . $bData[ 'ewaybill_no' ] . '"' : '-';
        $shipping_mode = ! empty ($bData[ 'shipping_mode' ]) ? $bData[ 'shipping_mode' ] : '-';
        $address       = ! empty ($bData[ 'consignee_address' ]) ? $bData[ 'consignee_address' ] : ($row[ 'address' ] ?? ($row[ 'consignee_address' ] ?? ''));
        $phone         = ! empty ($bData[ 'consignee_phone' ]) ? $bData[ 'consignee_phone' ] : ($row[ 'phone' ] ?? ($row[ 'consignee_phone' ] ?? '-'));
        $scannedAt     = ! empty ($row[ 'scanned_at' ]) ? date ( 'd-M-Y H:i:s', strtotime ( $row[ 'scanned_at' ] ) ) : '-';

        fputcsv ( $output, [
            $sl++,
            $row[ 'awb_no' ] ?? '-',
            $row[ 'tag_no' ] ?? '-',
            $row[ 'consignee_name' ] ?? '-',
            $row[ 'consignee_city' ] ?? '-',
            $address,
            $phone,
            $ewaybill,
            $shipping_mode,
            $scannedAt
        ] );
        }

    // Close stream
    fclose ( $output );
    exit;

    }
catch ( Exception $e ) {
    die ("Export Error: " . $e->getMessage ());
    }
?>
