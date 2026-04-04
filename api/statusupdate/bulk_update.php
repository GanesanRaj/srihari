<?php
header ( 'Content-Type: application/json' );
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

// Check for PhpSpreadsheet
require_once __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
        throw new Exception( 'Invalid request method' );
        }

    $current_user = get_current_user_info ();
    $userId       = $current_user ? $current_user[ 'id' ] : 1;
    $username     = $current_user ? $current_user[ 'username' ] : 'system';

    if ( ! isset ($_FILES[ 'bulk_file' ]) || $_FILES[ 'bulk_file' ][ 'error' ] !== UPLOAD_ERR_OK) {
        throw new Exception( 'File upload failed' );
        }

    $fileTmpPath = $_FILES[ 'bulk_file' ][ 'tmp_name' ];
    $fileName    = $_FILES[ 'bulk_file' ][ 'name' ];

    // Load spreadsheet
    try {
        $internalErrors = libxml_use_internal_errors ( true );
        $spreadsheet    = IOFactory::load ( $fileTmpPath );
        libxml_use_internal_errors ( $internalErrors );
        $sheetData = $spreadsheet->getActiveSheet ()->toArray ( null, true, true, false );
        }
    catch ( Exception $e ) {
        throw new Exception( 'Error reading file: ' . $e->getMessage () );
        }

    if (empty ($sheetData) || count ( $sheetData ) < 2) {
        throw new Exception( 'File is empty or has no data rows' );
        }

    // Create Job Record
    $stmt = $pdo->prepare ( "INSERT INTO tbl_bulkupload_jobs (filename, status, created_by, created_at) VALUES (:fn, 'Processing', :uid, NOW())" );
    $stmt->execute ( [ ':fn' => 'StatusUpdate_' . $fileName, ':uid' => $userId ] );
    $jobId = $pdo->lastInsertId ();

    $headers      = array_shift ( $sheetData );
    $successCount = 0;
    $failCount    = 0;
    $resultRows   = [];

    // Add result headers
    $resultHeader   = $headers;
    $resultHeader[] = 'Upload Status';
    $resultHeader[] = 'Remarks';
    $resultRows[]   = $resultHeader;

    foreach ($sheetData as $index => $row) {
        if (empty (array_filter ( $row )))
            continue;

        $identifier = trim ( $row[ 0 ] ?? '' ); // AWB or Ref ID
        $statusStr  = trim ( $row[ 1 ] ?? '' );
        $statusDate = trim ( $row[ 2 ] ?? '' );
        $location   = trim ( $row[ 3 ] ?? '' );
        $remarks    = trim ( $row[ 4 ] ?? '' );

        $errorMsg  = '';
        $errCol    = -1;
        $processed = false;

        try {
            if (empty ($identifier)) {
                $errCol = 0;
                throw new Exception( "Missing AWB/Ref ID" );
                }
            if (empty ($statusStr)) {
                $errCol = 1;
                throw new Exception( "Missing Status" );
                }

            // Find booking
            $stmt = $pdo->prepare ( "SELECT id, waybill_no, courier_id, booking_ref_id FROM tbl_bookings WHERE waybill_no = :id OR booking_ref_id = :id OR id = :id LIMIT 1" );
            $stmt->execute ( [ ':id' => $identifier ] );
            $booking = $stmt->fetch ( PDO::FETCH_ASSOC );

            if ( ! $booking) {
                $errCol = 0;
                throw new Exception( "Shipment not found" );
                }

            // Standardize Status Date
            if (empty ($statusDate)) {
                $statusDateTime = date ( 'Y-m-d H:i:s' );
                } else {
                // Try to parse date
                $ts = strtotime ( $statusDate );
                if ( ! $ts) {
                    $errCol = 2;
                    throw new Exception( "Invalid Date Format" );
                    }
                $statusDateTime = date ( 'Y-m-d H:i:s', $ts );
                }

            // Call centralized helper function
            updateTrackingAndStatus ( $pdo, $booking[ 'id' ], $statusStr, $location, $remarks, $userId, $username );

            $pdo->commit ();
            $successCount++;
            $processed = true;
            }
        catch ( Exception $e ) {
            if ($pdo->inTransaction ())
                $pdo->rollBack ();
            $failCount++;
            $errorMsg = $e->getMessage ();
            }

        $resultRow    = $row;
        $resultRow[]  = $processed ? 'Success' : 'Failed';
        $resultRow[]  = $errorMsg;
        $resultRow[]  = $errCol; // For highlighting
        $resultRows[] = $resultRow;
        }

    $finalStatus = ($failCount > 0) ? (($successCount > 0) ? 'Completed with Errors' : 'Failed') : 'Completed';
    $pdo->prepare ( "UPDATE tbl_bulkupload_jobs SET status = :st, total_records = :tot, success_count = :suc, failure_count = :fail, result_file = :res, updated_at = NOW() WHERE id = :id" )
        ->execute ( [
            ':st' => $finalStatus,
            ':tot' => ($successCount + $failCount),
            ':suc' => $successCount,
            ':fail' => $failCount,
            ':res' => json_encode ( $resultRows ),
            ':id' => $jobId
        ] );

    echo json_encode ( [
        'status' => 'success',
        'message' => "Bulk update completed: $successCount success, $failCount failed",
        'job_id' => $jobId
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
