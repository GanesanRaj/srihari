<?php
/**
 * Allocate next global auto order number for Shiprocket (and persisted on tbl_bookings).
 */
function booking_allocate_auto_order_no(PDO $pdo): int
{
    $pdo->beginTransaction();
    try {
        $pdo->exec('INSERT INTO tbl_booking_auto_order_seq () VALUES ()');
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
