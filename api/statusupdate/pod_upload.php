<?php
header ( 'Content-Type: application/json' );
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

try {
    if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
        throw new Exception( 'Invalid request method' );
        }

    // Get current user
    $current_user = get_current_user_info ();
    $userId       = $current_user ? $current_user[ 'id' ] : 1;
    $username     = $current_user ? $current_user[ 'username' ] : 'system';

    // Required fields
    $bookingId     = isset ($_POST[ 'booking_id' ]) ? intval ( $_POST[ 'booking_id' ] ) : 0;
    $isChild       = isset ($_POST[ 'is_child' ]) && intval ( $_POST[ 'is_child' ] ) === 1;
    $packageId     = isset ($_POST[ 'package_id' ]) ? intval ( $_POST[ 'package_id' ] ) : 0;
    $status        = isset ($_POST[ 'status' ]) ? trim ( $_POST[ 'status' ] ) : '';
    $statusDate    = isset ($_POST[ 'status_date' ]) ? trim ( $_POST[ 'status_date' ] ) : '';
    $receiverName  = isset ($_POST[ 'receiver_name' ]) ? trim ( $_POST[ 'receiver_name' ] ) : '';
    $receiverPhone = isset ($_POST[ 'receiver_phone' ]) ? trim ( $_POST[ 'receiver_phone' ] ) : '';
    $location      = isset ($_POST[ 'location' ]) ? trim ( $_POST[ 'location' ] ) : '';
    $remarks       = isset ($_POST[ 'remarks' ]) ? trim ( $_POST[ 'remarks' ] ) : '';
    $imageFolder   = isset ($_POST[ 'images_folder' ]) ? trim ( $_POST[ 'images_folder' ] ) : 'pickup';

    // Validate required fields
    if ($bookingId <= 0) {
        throw new Exception( 'Booking ID is required' );
        }
    if (empty ($status)) {
        throw new Exception( 'Status is required' );
        }
    if (empty ($statusDate)) {
        throw new Exception( 'Status date is required' );
        }

    // Normalize "picked up" (any casing) to "Picked Up" for booking and tracking
    if (strtolower ( trim ( $status ) ) === 'picked up') {
        $status = 'Picked Up';
        }

    // Check if booking exists
    $checkStmt = $pdo->prepare ( "SELECT id, waybill_no, last_status, courier_id, booking_ref_id FROM tbl_bookings WHERE id = :id" );
    $checkStmt->execute ( [ ':id' => $bookingId ] );
    $booking = $checkStmt->fetch ( PDO::FETCH_ASSOC );

    if ( ! $booking) {
        throw new Exception( 'Booking not found' );
        }

    // Handle POD images
    $podImages = [];
    if (isset ($_FILES[ 'pod_images' ]) && is_array ( $_FILES[ 'pod_images' ][ 'name' ] )) {
        $fileCount = count ( $_FILES[ 'pod_images' ][ 'name' ] );

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES[ 'pod_images' ][ 'error' ][$i] === UPLOAD_ERR_OK) {
                $tmpFile      = $_FILES[ 'pod_images' ][ 'tmp_name' ][$i];
                $originalName = $_FILES[ 'pod_images' ][ 'name' ][$i];

                // Create file array for handle_image_upload function
                $fileInput = [
                    'name' => $originalName,
                    'tmp_name' => $tmpFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => $_FILES[ 'pod_images' ][ 'size' ][$i]
                ];

                // Upload and compress image (200KB max)
                $imagePath = handle_image_upload ( $fileInput, $imageFolder, null, 200 );

                if ($imagePath) {
                    $podImages[] = $imagePath;
                    }
                }
            }
        }

    // Convert datetime format
    if (strpos ( $statusDate, 'T' ) !== false) {
        $statusDateTime = str_replace ( 'T', ' ', $statusDate );
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else if (strpos ( $statusDate, ' ' ) !== false) {
        $statusDateTime = $statusDate;
        if (strlen ( $statusDateTime ) === 16) {
            $statusDateTime .= ':00';
            }
        } else {
        $statusDateTime = $statusDate . ' 00:00:00';
        }

    // Begin transaction
    $pdo->beginTransaction ();

    try {
        $trackingId          = null;
        $shouldWriteTracking = false;
        $finalStatus         = $status;

        if ($isChild && $packageId > 0) {
            // ── Child box scan ──────────────────────────────────────────────
            // 1. Update this specific package's status + POD data
            $pdo->prepare (
                "UPDATE tbl_booking_packages
                 SET status = :status, status_date = :dt, pod_images = :imgs,
                     remarks = :rem, updated_by = :uid, updated_at = NOW()
                 WHERE id = :id"
                )->execute ( [
                ':status' => $status,
                ':dt'     => $statusDateTime,
                ':imgs'   => json_encode ( $podImages ),
                ':rem'    => $remarks,
                ':uid'    => $userId,
                ':id'     => $packageId
            ] );

            // 2. Check if ALL packages for this booking now share the same status
            $pkgStmt = $pdo->prepare ( "SELECT status FROM tbl_booking_packages WHERE booking_id = :bid" );
            $pkgStmt->execute ( [ ':bid' => $bookingId ] );
            $allPkgStatuses = $pkgStmt->fetchAll ( PDO::FETCH_COLUMN );
            $uniqueStatuses = array_unique ( array_filter ( $allPkgStatuses ) );

            if (count ( $uniqueStatuses ) === 1) {
                // All packages have the same status — fully updated
                $finalStatus = reset ( $uniqueStatuses );
                $pdo->prepare (
                    "UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id"
                    )->execute ( [ ':status' => $finalStatus, ':uid' => $userId, ':id' => $bookingId ] );
                $shouldWriteTracking = true;
                } else {
                // Mixed statuses — partially picked up
                $pdo->prepare (
                    "UPDATE tbl_bookings SET last_status = 'Partially Picked Up', updated_by = :uid, updated_at = NOW() WHERE id = :id"
                    )->execute ( [ ':uid' => $userId, ':id' => $bookingId ] );
                $shouldWriteTracking = false;
                }
            } else {
            // ── Parent/master scan ──────────────────────────────────────────
            // Update booking status + all packages (all get same status → always fully updated)
            $pdo->prepare (
                "UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id"
                )->execute ( [ ':status' => $status, ':uid' => $userId, ':id' => $bookingId ] );

            $pdo->prepare (
                "UPDATE tbl_booking_packages
                 SET status = :status, status_date = :dt, pod_images = :imgs,
                     remarks = :rem, updated_by = :uid, updated_at = NOW()
                 WHERE booking_id = :bid"
                )->execute ( [
                ':status' => $status,
                ':dt'     => $statusDateTime,
                ':imgs'   => json_encode ( $podImages ),
                ':rem'    => $remarks,
                ':uid'    => $userId,
                ':bid'    => $bookingId
            ] );

            $shouldWriteTracking = true;
            }

        // ── Always update tracking with this scan (common status + tracking)
        $statusToRecord = $shouldWriteTracking ? $finalStatus : $status;
        $existingTrackStmt = $pdo->prepare ( "SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1" );
        $existingTrackStmt->execute ( [ ':wn' => $booking[ 'waybill_no' ] ] );
        $existingTrack = $existingTrackStmt->fetch ( PDO::FETCH_ASSOC );

        $history = [];
        if ($existingTrack && ! empty ($existingTrack[ 'raw_response' ])) {
            $decoded = json_decode ( $existingTrack[ 'raw_response' ], true );
            if (isset ($decoded[ 'scan_details_history' ])) {
                $history = $decoded[ 'scan_details_history' ];
                } else if (isset ($decoded[ 'scan_details' ])) {
                $history = [ $decoded[ 'scan_details' ] ];
                }
            }

        $newScan = [
            'status'           => $statusToRecord,
            'location'         => $location,
            'receiver_name'    => $receiverName,
            'receiver_phone'   => $receiverPhone,
            'datetime'         => $statusDateTime,
            'remarks'          => $remarks,
            'pod_images'       => $podImages,
            'pod_image_count'  => count ( $podImages ),
            'updated_by'       => $userId,
            'updated_at'       => date ( 'Y-m-d H:i:s' ),
            'type'             => 'POD'
        ];
        $history[] = $newScan;

        $rawData = json_encode ( [
            'awb_no'           => $booking[ 'waybill_no' ],
            'shipment_details' => [ 'id' => $bookingId, 'booking_ref_id' => $booking[ 'booking_ref_id' ] ],
            'current_status'   => $statusToRecord,
            'scan_details'     => $newScan,
            'scan_details_history' => $history
        ] );

        if ($existingTrack) {
            $pdo->prepare (
                "UPDATE tbl_tracking SET scan_type=:st, scan_location=:sl, scan_datetime=:dt, status_code=:sc, remarks=:rem, raw_response=:raw WHERE id=:id"
                )->execute ( [
                ':id'  => $existingTrack[ 'id' ],
                ':st'  => $statusToRecord,
                ':sl'  => $location,
                ':dt'  => $statusDateTime,
                ':sc'  => $statusToRecord,
                ':rem' => $remarks,
                ':raw' => $rawData
            ] );
            $trackingId = $existingTrack[ 'id' ];
            } else {
            $pdo->prepare (
                "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response)
                 VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)"
                )->execute ( [
                ':bid' => $bookingId,
                ':wn'  => $booking[ 'waybill_no' ] ?: '',
                ':st'  => $statusToRecord,
                ':sl'  => $location,
                ':dt'  => $statusDateTime,
                ':sc'  => $statusToRecord,
                ':rem' => $remarks,
                ':raw' => $rawData
            ] );
            $trackingId = $pdo->lastInsertId ();
            }

        if ($shouldWriteTracking) {
            syncShipmentStatusAcrossTables ( $pdo, $bookingId, $booking[ 'waybill_no' ], $finalStatus, $remarks, $username );
            }

        $pdo->commit ();

        // Determine booking status for response
        $bookingStatus = $shouldWriteTracking ? $finalStatus : 'Partially Picked Up';

        echo json_encode ( [
            'status'         => 'success',
            'message'        => $shouldWriteTracking
                ? 'Status updated successfully with ' . count ( $podImages ) . ' image(s)'
                : 'Package updated. Booking is Partially Picked Up (not all packages match)',
            'booking_id'     => $bookingId,
            'is_child'       => $isChild,
            'package_id'     => $packageId,
            'new_status'     => $status,
            'booking_status' => $bookingStatus,
            'fully_updated'  => $shouldWriteTracking,
            'tracking_id'    => $trackingId,
            'pod_images'     => $podImages,
            'image_count'    => count ( $podImages )
        ] );

        }
    catch ( Exception $transactionError ) {
        $pdo->rollBack ();
        throw $transactionError;
        }

    }
catch ( Exception $e ) {
    http_response_code ( 400 );
    echo json_encode ( [
        'status' => 'error',
        'message' => $e->getMessage ()
    ] );
    }
?>
