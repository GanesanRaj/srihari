<?php
// Prevent any output before headers
ob_start ();

require_once '../../config/config.php';
require_once '../../config/middleware.php';

// Check View Permission
require_api_permission ( 'serial_allocation', 'is_view' );

// 1. Validate Input
if ( ! isset ($_GET[ 'id' ]) || empty ($_GET[ 'id' ])) {
    die ("Error: Allocation ID is missing.");
    }

$allocation_id = intval ( $_GET[ 'id' ] );
$filter_type   = isset ($_GET[ 'type' ]) ? $_GET[ 'type' ] : 'all'; // 'used', 'available', 'all'

try {
    // 2. Prepare SQL Query based on Filter
    $sql = "SELECT
                sn.serial_number,
                sn.service_type,
                CASE
                    WHEN b.id IS NOT NULL THEN 'Used'
                    ELSE 'Available'
                END as usage_status,
                b.booking_ref_id as booking_id,
                b.created_at as used_date,
                sn.remarks
            FROM tbl_serial_numbers sn
            LEFT JOIN tbl_bookings b ON b.waybill_no COLLATE utf8mb4_general_ci = sn.serial_number COLLATE utf8mb4_general_ci
            WHERE sn.allocation_id = :id";

    // Append filter conditions based on dynamically calculated usage
    if ($filter_type === 'used') {
        $sql .= " AND b.id IS NOT NULL";
        } elseif ($filter_type === 'available') {
        $sql .= " AND b.id IS NULL";
        }

    $sql .= " ORDER BY sn.serial_number ASC";

    $stmt = $pdo->prepare ( $sql );
    $stmt->bindValue ( ':id', $allocation_id, PDO::PARAM_INT );
    $stmt->execute ();
    $results = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    // 3. Define Filename
    $filename = 'Allocation_' . $allocation_id . '_' . ucfirst ( $filter_type ) . '_List_' . date ( 'Y-m-d' ) . '.csv';

    // 4. Set Headers for Excel/CSV Download
    // Cleaning output buffer to ensure no whitespace before file data
    ob_end_clean ();

    header ( 'Content-Type: text/csv; charset=utf-8' );
    header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );

    // Open output stream
    $output = fopen ( 'php://output', 'w' );

    // 5. Add Necessary Headings (The Excel Header Row)
    $headings = [
        'Serial Number',
        'Service Type',
        'Usage Status',
        'Booking ID',
        'Used Date',
        'Remarks'
    ];
    fputcsv ( $output, $headings );

    // 6. Loop data and write to file
    foreach ($results as $row) {
        // Format date if exists
        $used_date  = ( ! empty ($row[ 'used_date' ])) ? date ( 'Y-m-d H:i', strtotime ( $row[ 'used_date' ] ) ) : '-';
        $booking_id = ( ! empty ($row[ 'booking_id' ])) ? $row[ 'booking_id' ] : '-';

        $lineData = [
            $row[ 'serial_number' ],
            strtoupper ( $row[ 'service_type' ] ),
            $row[ 'usage_status' ],
            $booking_id,
            $used_date,
            $row[ 'remarks' ]
        ];
        fputcsv ( $output, $lineData );
        }

    fclose ( $output );
    exit;

    }
catch ( PDOException $e ) {
    die ("Database Error: " . $e->getMessage ());
    }
?>
