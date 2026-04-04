<?php
/**
 * shipment_delete.php
 * Superadmin-only: delete one or many shipment bookings.
 *
 * For each booking:
 *   1. Re-instate serial allocation (Own Courier)
 *   2. Remove from manifest JSON (mark Cancelled)
 *   3. Delete tbl_booking_packages
 *   4. Delete tbl_tracking
 *   5. Delete tbl_bookings
 *
 * POST body (JSON):
 *   ids  – array OR comma-separated booking IDs  OR single `id`
 */

header ( 'Content-Type: application/json' );

require_once '../../config/db.php';
require_once '../../config/helper.php';

if (session_status () === PHP_SESSION_NONE) {
    session_start ();
    }

// ── Superadmin only (role_id = 1) ────────────────────────────────────────────
$roleId = (int) ($_SESSION[ 'role_id' ] ?? 0);
if ($roleId !== 1) {
    http_response_code ( 403 );
    echo json_encode ( [ 'status' => 'error', 'message' => 'Access denied. Superadmin only.' ] );
    exit;
    }

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid request method.' ] );
    exit;
    }

// ── Parse IDs ────────────────────────────────────────────────────────────────
$raw  = file_get_contents ( 'php://input' );
$body = json_decode ( $raw, true ) ?: $_POST;

$ids = [];
if ( ! empty ($body[ 'ids' ])) {
    $ids = is_array ( $body[ 'ids' ] )
        ? array_map ( 'intval', $body[ 'ids' ] )
        : array_filter ( array_map ( 'intval', explode ( ',', (string) $body[ 'ids' ] ) ) );
    } elseif ( ! empty ($body[ 'id' ])) {
    $ids = [ (int) $body[ 'id' ] ];
    }

$ids = array_values ( array_unique ( array_filter ( $ids ) ) );

if (empty ($ids)) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'No booking ID(s) provided.' ] );
    exit;
    }

// ── Process ──────────────────────────────────────────────────────────────────
try {
    $pdo->beginTransaction ();

    $deleted = 0;

    foreach ($ids as $bookingId) {
        // Fetch booking
        $bkStmt = $pdo->prepare (
            "SELECT id, courier_id, waybill_no FROM tbl_bookings WHERE id = :id LIMIT 1"
        );
        $bkStmt->execute ( [ ':id' => $bookingId ] );
        $booking = $bkStmt->fetch ( PDO::FETCH_ASSOC );

        if ( ! $booking)
            continue;

        $courierId = (int) $booking[ 'courier_id' ];
        $waybillNo = $booking[ 'waybill_no' ];

        // 1. Own Courier — restore serial allocations
        if ($courierId === 2 && $waybillNo !== '') {
            $pkgStmt = $pdo->prepare (
                "SELECT DISTINCT awb_no, child_ewaybill_no FROM tbl_booking_packages WHERE booking_id = :bid"
            );
            $pkgStmt->execute ( [ ':bid' => $bookingId ] );
            $pkgRows = $pkgStmt->fetchAll ( PDO::FETCH_ASSOC );

            $serialsToRestore = [];
            foreach ($pkgRows as $pr) {
                $sn = trim ( (string) ($pr[ 'child_ewaybill_no' ] ?? '') );
                if ($sn === '')
                    $sn = trim ( (string) ($pr[ 'awb_no' ] ?? '') );
                // Skip derived sub-box AWBs (e.g., BNG-001-1)
                if ($sn !== '' && ! preg_match ( '/-\d+$/', $sn )) {
                    $serialsToRestore[] = $sn;
                    }
                }
            if ( ! preg_match ( '/-\d+$/', $waybillNo )) {
                $serialsToRestore[] = $waybillNo;
                }
            $serialsToRestore = array_values ( array_unique ( array_filter ( $serialsToRestore ) ) );

            foreach ($serialsToRestore as $sn) {
                $chkSer = $pdo->prepare (
                    "SELECT id, allocation_id FROM tbl_serial_numbers
                     WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) LIMIT 1"
                );
                $chkSer->execute ( [ ':sn' => $sn ] );
                $serRow = $chkSer->fetch ( PDO::FETCH_ASSOC );

                if ($serRow) {
                    // Serial exists — reset to cancelled/available
                    $pdo->prepare (
                        "UPDATE tbl_serial_numbers SET status = 'cancelled', is_used = 0 WHERE id = :id"
                    )->execute ( [ ':id' => $serRow[ 'id' ] ] );

                    if ($serRow[ 'allocation_id' ]) {
                        $pdo->prepare (
                            "UPDATE tbl_serial_allocation
                             SET used_serials = GREATEST(0, used_serials - 1)
                             WHERE id = :aid"
                        )->execute ( [ ':aid' => $serRow[ 'allocation_id' ] ] );
                        }
                    } else {
                    // Serial was deleted during booking — re-insert it
                    $allocStmt = $pdo->prepare (
                        "SELECT sa.id, sa.branch_id, sa.service_type
                         FROM tbl_serial_allocation sa
                         JOIN tbl_bookings bk ON bk.branch_id = sa.branch_id
                         WHERE bk.id = :bid LIMIT 1"
                    );
                    $allocStmt->execute ( [ ':bid' => $bookingId ] );
                    $allocRow = $allocStmt->fetch ( PDO::FETCH_ASSOC );

                    if ($allocRow) {
                        $pdo->prepare (
                            "INSERT INTO tbl_serial_numbers
                                (allocation_id, branch_id, serial_number, service_type, status, is_used, created_at)
                             VALUES (:aid, :bid, :sn, :st, 'cancelled', 0, NOW())"
                        )->execute ( [
                                    ':aid' => $allocRow[ 'id' ],
                                    ':bid' => $allocRow[ 'branch_id' ],
                                    ':sn' => $sn,
                                    ':st' => $allocRow[ 'service_type' ] ?? 'surface',
                                ] );
                        $pdo->prepare (
                            "UPDATE tbl_serial_allocation
                             SET total_serials = total_serials + 1
                             WHERE id = :aid"
                        )->execute ( [ ':aid' => $allocRow[ 'id' ] ] );
                        }
                    }
                }
            }

        // 2. Cancel entry in manifest JSON
        try {
            $mfStmt = $pdo->prepare (
                "SELECT id, json_data FROM tbl_manifest WHERE json_data LIKE :wbn"
            );
            $mfStmt->execute ( [ ':wbn' => '%' . $waybillNo . '%' ] );
            $manifests = $mfStmt->fetchAll ( PDO::FETCH_ASSOC );
            foreach ($manifests as $mf) {
                $mfData = json_decode ( $mf[ 'json_data' ], true );
                if ( ! is_array ( $mfData ))
                    continue;
                $changed = false;
                foreach ($mfData as &$entry) {
                    if (($entry[ 'awb_no' ] ?? '') === $waybillNo) {
                        $entry[ 'status' ]  = 'Cancelled';
                        $entry[ 'remarks' ] = 'Booking deleted by superadmin';
                        $changed          = true;
                        }
                    }
                unset ( $entry );
                if ($changed) {
                    $pdo->prepare (
                        "UPDATE tbl_manifest SET json_data = :json, updated_at = NOW() WHERE id = :id"
                    )->execute ( [ ':json' => json_encode ( $mfData ), ':id' => $mf[ 'id' ] ] );
                    }
                }
            }
        catch ( Exception $e ) { /* non-fatal */
            }

        // 3. Delete booking packages
        $pdo->prepare ( "DELETE FROM tbl_booking_packages WHERE booking_id = :bid" )
            ->execute ( [ ':bid' => $bookingId ] );

        // 4. Delete tracking records
        $pdo->prepare ( "DELETE FROM tbl_tracking WHERE booking_id = :bid OR waybill_no = :wbn" )
            ->execute ( [ ':bid' => $bookingId, ':wbn' => $waybillNo ] );

        // 5. Delete the booking
        $pdo->prepare ( "DELETE FROM tbl_bookings WHERE id = :bid" )
            ->execute ( [ ':bid' => $bookingId ] );

        $deleted++;
        }

    $pdo->commit ();

    echo json_encode ( [
        'status' => 'success',
        'message' => "Deleted {$deleted} booking(s). Serial allocations restored.",
        'deleted' => $deleted,
    ] );

    }
catch ( Exception $e ) {
    $pdo->rollBack ();
    http_response_code ( 500 );
    echo json_encode ( [
        'status' => 'error',
        'message' => 'Delete failed: ' . $e->getMessage (),
    ] );
    }
