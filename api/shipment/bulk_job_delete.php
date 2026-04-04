<?php
/**
 * bulk_job_delete.php
 * Superadmin-only: delete one or many bulk-upload jobs.
 *
 * For every booking created under a job:
 *   1. Re-instate the serial allocation (cancel used serials back to available)
 *   2. Delete tbl_booking_packages rows
 *   3. Delete tbl_bookings row
 *   4. Delete tbl_tracking rows
 * Then delete tbl_bulkupload_jobs row(s).
 *
 * POST body (JSON or form-data):
 *   ids  – comma-separated job IDs  OR  a single `id`
 */

header ( 'Content-Type: application/json' );

require_once '../../config/db.php';
require_once '../../config/helper.php';

// ── Auth: superadmin only (role_id = 1) ──────────────────────────────────────
if (session_status () === PHP_SESSION_NONE) {
    session_start ();
    }

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
$body = json_decode ( $raw, true );

if ( ! $body) {
    $body = $_POST; // fallback to form-data
    }

$ids = [];

if ( ! empty ($body[ 'ids' ])) {
    // Accept array or comma-separated string
    if (is_array ( $body[ 'ids' ] )) {
        $ids = array_map ( 'intval', $body[ 'ids' ] );
        } else {
        $ids = array_filter ( array_map ( 'intval', explode ( ',', (string) $body[ 'ids' ] ) ) );
        }
    } elseif ( ! empty ($body[ 'id' ])) {
    $ids = [ (int) $body[ 'id' ] ];
    }

$ids = array_values ( array_filter ( $ids ) );

if (empty ($ids)) {
    echo json_encode ( [ 'status' => 'error', 'message' => 'No job ID(s) provided.' ] );
    exit;
    }

// ── Process each job ─────────────────────────────────────────────────────────
try {
    $pdo->beginTransaction ();

    $deletedJobs     = 0;
    $deletedBookings = 0;

    foreach ($ids as $jobId) {
        // 1. Find all bookings created by this bulk-upload job.
        //    bulk_upload.php stores the job_id in tbl_bulkupload_jobs but does NOT
        //    store job_id on tbl_bookings. We identify them by matching every
        //    waybill stored in the job's result_file against tbl_bookings.
        $jobStmt = $pdo->prepare ( "SELECT id, result_file FROM tbl_bulkupload_jobs WHERE id = :id LIMIT 1" );
        $jobStmt->execute ( [ ':id' => $jobId ] );
        $job = $jobStmt->fetch ( PDO::FETCH_ASSOC );

        if ( ! $job) {
            continue; // Job not found – skip silently
            }

        // Collect waybill numbers from result_file JSON (column index 35 = Waybill)
        $waybillsFromResult = [];
        if ( ! empty ($job[ 'result_file' ])) {
            $resultRows = json_decode ( $job[ 'result_file' ], true );
            if (is_array ( $resultRows )) {
                // Row 0 is the header row, skip it
                foreach ($resultRows as $ri => $rrow) {
                    if ($ri === 0)
                        continue; // header
                    if ( ! is_array ( $rrow ))
                        continue;
                    // Status is at index 36, waybill at 35
                    $wbn = trim ( (string) ($rrow[ 35 ] ?? '') );
                    $sts = trim ( (string) ($rrow[ 36 ] ?? '') );
                    if ($wbn !== '' && strtolower ( $sts ) !== 'failed') {
                        $waybillsFromResult[] = $wbn;
                        }
                    }
                }
            }

        $waybillsFromResult = array_values ( array_unique ( array_filter ( $waybillsFromResult ) ) );

        // 2. For each waybill, find the booking and clean up
        foreach ($waybillsFromResult as $wbn) {
            $bkStmt = $pdo->prepare ( "SELECT id, courier_id, waybill_no FROM tbl_bookings WHERE waybill_no = :wbn LIMIT 1" );
            $bkStmt->execute ( [ ':wbn' => $wbn ] );
            $booking = $bkStmt->fetch ( PDO::FETCH_ASSOC );

            if ( ! $booking) {
                continue;
                }

            $bookingId = (int) $booking[ 'id' ];
            $courierId = (int) $booking[ 'courier_id' ];
            $waybillNo = $booking[ 'waybill_no' ];

            // 2a. Own Courier (id=2): restore serial allocations
            //     The waybill itself is the serial number. Child-box AWBs like BNG-013-1
            //     are derived (not stored in tbl_serial_numbers), so only the parent needs restoring.
            if ($courierId === 2 && $waybillNo !== '') {
                // Collect all root AWBs from tbl_booking_packages (child_ewaybill_no or awb_no)
                $pkgStmt = $pdo->prepare (
                    "SELECT DISTINCT awb_no, child_ewaybill_no FROM tbl_booking_packages WHERE booking_id = :bid"
                );
                $pkgStmt->execute ( [ ':bid' => $bookingId ] );
                $pkgRows = $pkgStmt->fetchAll ( PDO::FETCH_ASSOC );

                $serialsToRestore = [];
                foreach ($pkgRows as $pr) {
                    $sn = trim ( (string) ($pr[ 'child_ewaybill_no' ] ?? '') );
                    if ($sn === '') {
                        $sn = trim ( (string) ($pr[ 'awb_no' ] ?? '') );
                        }
                    // Derived sub-box serials end with -N; skip them (not in tbl_serial_numbers)
                    if ($sn !== '' && ! preg_match ( '/-\d+$/', $sn )) {
                        $serialsToRestore[] = $sn;
                        }
                    }
                // Also add the parent waybill itself
                if ( ! preg_match ( '/-\d+$/', $waybillNo )) {
                    $serialsToRestore[] = $waybillNo;
                    }
                $serialsToRestore = array_values ( array_unique ( array_filter ( $serialsToRestore ) ) );

                foreach ($serialsToRestore as $sn) {
                    // Check if serial already exists (might have been deleted during booking)
                    $chkSer = $pdo->prepare (
                        "SELECT id, allocation_id FROM tbl_serial_numbers
                         WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) LIMIT 1"
                    );
                    $chkSer->execute ( [ ':sn' => $sn ] );
                    $serRow = $chkSer->fetch ( PDO::FETCH_ASSOC );

                    if ($serRow) {
                        // Serial still exists: just reset to available / not-used
                        $pdo->prepare (
                            "UPDATE tbl_serial_numbers SET status = 'cancelled', is_used = 0 WHERE id = :id"
                        )->execute ( [ ':id' => $serRow[ 'id' ] ] );

                        // Fix allocation counter (decrement used_serials, keep total intact)
                        if ($serRow[ 'allocation_id' ]) {
                            $pdo->prepare (
                                "UPDATE tbl_serial_allocation
                                 SET used_serials = GREATEST(0, used_serials - 1)
                                 WHERE id = :aid"
                            )->execute ( [ ':aid' => $serRow[ 'allocation_id' ] ] );
                            }
                        } else {
                        // Serial was deleted during booking – try to find the allocation for this branch
                        // and re-insert the serial as 'cancelled' (available for reuse)
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
                                 VALUES
                                    (:aid, :bid, :sn, :st, 'cancelled', 0, NOW())"
                            )->execute ( [
                                        ':aid' => $allocRow[ 'id' ],
                                        ':bid' => $allocRow[ 'branch_id' ],
                                        ':sn' => $sn,
                                        ':st' => $allocRow[ 'service_type' ] ?? 'surface',
                                    ] );
                            // Increase total_serials back
                            $pdo->prepare (
                                "UPDATE tbl_serial_allocation
                                 SET total_serials = total_serials + 1
                                 WHERE id = :aid"
                            )->execute ( [ ':aid' => $allocRow[ 'id' ] ] );
                            }
                        }
                    }
                }

            // 2b. Remove from manifest JSON (mark as cancelled)
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
                            $entry[ 'remarks' ] = 'Deleted via bulk job delete';
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
            catch ( Exception $e ) {
                // non-fatal
                }

            // 2c. Delete booking packages
            $pdo->prepare ( "DELETE FROM tbl_booking_packages WHERE booking_id = :bid" )
                ->execute ( [ ':bid' => $bookingId ] );

            // 2d. Delete tracking records
            $pdo->prepare ( "DELETE FROM tbl_tracking WHERE booking_id = :bid OR waybill_no = :wbn" )
                ->execute ( [ ':bid' => $bookingId, ':wbn' => $waybillNo ] );

            // 2e. Delete the booking
            $pdo->prepare ( "DELETE FROM tbl_bookings WHERE id = :bid" )
                ->execute ( [ ':bid' => $bookingId ] );

            $deletedBookings++;
            }

        // 3. Delete the bulk-upload job record
        $pdo->prepare ( "DELETE FROM tbl_bulkupload_jobs WHERE id = :id" )
            ->execute ( [ ':id' => $jobId ] );

        $deletedJobs++;
        }

    $pdo->commit ();

    echo json_encode ( [
        'status' => 'success',
        'message' => "Deleted {$deletedJobs} job(s) and {$deletedBookings} booking(s). Serial allocations restored.",
        'deleted_jobs' => $deletedJobs,
        'deleted_bookings' => $deletedBookings,
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
