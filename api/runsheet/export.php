<?php
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$id = (int) ($_GET[ 'id' ] ?? 0);

if ($id <= 0) {
    die ('Invalid Run Sheet ID');
    }

try {
    // 1. Fetch Runsheet Header Details
    $stmt = $pdo->prepare ( "SELECT * FROM tbl_runsheet WHERE id = :id LIMIT 1" );
    $stmt->execute ( [ ':id' => $id ] );
    $runsheet = $stmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $runsheet) {
        die ('Run Sheet not found');
        }

    // 2. One row per parent booking.
    //    - parent_awb    = booking waybill_no (via booking_id OR via awb lookup in packages)
    //    - scanned_boxes = COUNT of child AWBs scanned for that booking
    //    - ewaybill_no & shipping_mode from tbl_bookings
    $dStmt = $pdo->prepare (
        "SELECT
            COALESCE(b.waybill_no, b2.waybill_no, rd.awb_no) AS parent_awb,
            MIN(rd.consignee_name)                             AS consignee_name,
            MIN(rd.consignee_city)                             AS consignee_city,
            MIN(rd.address)                                    AS address,
            MIN(rd.consignee_phone)                            AS consignee_phone,
            COUNT(rd.id)                                       AS scanned_boxes,
            MAX(COALESCE(b.ewaybill_no, b2.ewaybill_no))      AS ewaybill_no,
            MAX(COALESCE(b.shipping_mode, b2.shipping_mode))   AS shipping_mode,
            MIN(rd.status)                                     AS current_status,
            MAX(COALESCE(b.invoice_value, b2.invoice_value))   AS invoice_value
         FROM tbl_runsheet_details rd
         LEFT JOIN tbl_bookings b   ON b.id  = rd.booking_id
         LEFT JOIN tbl_booking_packages bp ON bp.awb_no = rd.awb_no
         LEFT JOIN tbl_bookings b2  ON b2.id = bp.booking_id
         WHERE rd.runsheet_id = :id
         GROUP BY rd.booking_id, COALESCE(b.waybill_no, b2.waybill_no, rd.awb_no)
         ORDER BY MIN(rd.id) ASC"
    );
    $dStmt->execute ( [ ':id' => $id ] );
    $details = $dStmt->fetchAll ( PDO::FETCH_ASSOC );

    // 3. Set Headers for Excel/CSV Download
    $filename = "Runsheet_" . $runsheet[ 'runsheet_no' ] . ".csv";

    header ( 'Content-Type: text/csv; charset=utf-8' );
    header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );

    // Open output stream
    $output = fopen ( 'php://output', 'w' );

    // Add BOM for proper UTF-8 rendering in Excel
    fputs ( $output, $bom = (chr ( 0xEF ) . chr ( 0xBB ) . chr ( 0xBF )) );

    // 4. Write Header Information
    fputcsv ( $output, [ 'Run Sheet Details' ] );
    fputcsv ( $output, [ 'Run Sheet No:', $runsheet[ 'runsheet_no' ], '', 'Driver Name:', $runsheet[ 'driver_name' ] ] );
    fputcsv ( $output, [ 'Date:', date ( 'd-M-Y', strtotime ( $runsheet[ 'runsheet_date' ] ) ), '', 'Mobile No:', $runsheet[ 'mobile_number' ] ] );
    fputcsv ( $output, [] ); // Empty row for spacing

    // 5. Write Table Column Headers
    fputcsv ( $output, [
        'Sl No',
        'AWB No (Parent)',
        'Consignee Name',
        'City',
        'Address',
        'Phone',
        'No. of Boxes',
        'E-way Bill No',
        'Mode of Shipment',
        'Current Status',
        'Invoice Value'
    ] );

    // 6. Write Data Rows
    $sl = 1;
    foreach ($details as $row) {
        // Force E-way Bill to string so Excel doesn't use scientific notation
        $ewaybill = $row[ 'ewaybill_no' ] ? '="' . $row[ 'ewaybill_no' ] . '"' : '-';

        fputcsv ( $output, [
            $sl++,
            $row[ 'parent_awb' ],
            $row[ 'consignee_name' ],
            $row[ 'consignee_city' ],
            $row[ 'address' ],
            $row[ 'consignee_phone' ],
            $row[ 'scanned_boxes' ],
            $ewaybill,
            $row[ 'shipping_mode' ] ?: '-',
            $row[ 'current_status' ] ?: 'Pending',
            $row[ 'invoice_value' ] ?: '-'
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
