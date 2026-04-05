<?php
/**
 * Own Courier Cancel Service
 */

function cancelBookingWithOwnCourier($pdo, $courierData, $bookingData)
{
    try {
        $bookingId = $bookingData['id'];
        $waybillNo = $bookingData['waybill_no'] ?? '';

        if (empty($waybillNo)) {
            return ['success' => true, 'message' => 'No waybill to restore'];
        }

        // Fetch packages to restore serials
        $pkgStmt = $pdo->prepare("
            SELECT DISTINCT awb_no, child_ewaybill_no 
            FROM tbl_booking_packages 
            WHERE booking_id = :bid
        ");
        $pkgStmt->execute([':bid' => $bookingId]);
        $pkgRows = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

        $serialsToRestore = [];
        foreach ($pkgRows as $pr) {
            $sn = trim($pr['child_ewaybill_no'] ?? $pr['awb_no'] ?? '');
            if ($sn !== '' && !preg_match('/-\d+$/', $sn)) {
                $serialsToRestore[] = $sn;
            }
        }
        if (!preg_match('/-\d+$/', $waybillNo)) {
            $serialsToRestore[] = $waybillNo;
        }
        $serialsToRestore = array_values(array_unique(array_filter($serialsToRestore)));

        foreach ($serialsToRestore as $sn) {
            $chkSer = $pdo->prepare("
                SELECT id, allocation_id FROM tbl_serial_numbers
                WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:sn)) LIMIT 1
            ");
            $chkSer->execute([':sn' => $sn]);
            $serRow = $chkSer->fetch(PDO::FETCH_ASSOC);

            if ($serRow) {
                // Reset to cancelled/available
                $pdo->prepare("UPDATE tbl_serial_numbers SET status = 'cancelled', is_used = 0 WHERE id = :id")
                    ->execute([':id' => $serRow['id']]);

                if ($serRow['allocation_id']) {
                    $pdo->prepare("
                        UPDATE tbl_serial_allocation
                        SET used_serials = GREATEST(0, used_serials - 1)
                        WHERE id = :aid
                    ")->execute([':aid' => $serRow['allocation_id']]);
                }
            } else {
                // Re-insert deleted serial
                $allocStmt = $pdo->prepare("
                    SELECT sa.id, sa.branch_id, sa.service_type
                    FROM tbl_serial_allocation sa
                    JOIN tbl_bookings bk ON bk.branch_id = sa.branch_id
                    WHERE bk.id = :bid LIMIT 1
                ");
                $allocStmt->execute([':bid' => $bookingId]);
                $allocRow = $allocStmt->fetch(PDO::FETCH_ASSOC);

                if ($allocRow) {
                    $pdo->prepare("
                        INSERT INTO tbl_serial_numbers
                            (allocation_id, branch_id, serial_number, service_type, status, is_used, created_at)
                        VALUES (:aid, :bid, :sn, :st, 'cancelled', 0, NOW())
                    ")->execute([
                        ':aid' => $allocRow['id'],
                        ':bid' => $allocRow['branch_id'],
                        ':sn' => $sn,
                        ':st' => $allocRow['service_type'] ?? 'surface',
                    ]);

                    $pdo->prepare("
                        UPDATE tbl_serial_allocation
                        SET total_serials = total_serials + 1
                        WHERE id = :aid
                    ")->execute([':aid' => $allocRow['id']]);
                }
            }
        }

        return ['success' => true, 'serials_restored' => count($serialsToRestore)];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
