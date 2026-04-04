<?php
require 'config/config.php';

$alloc_id = 2;
$stmt     = $pdo->prepare ( "SELECT * FROM tbl_serial_allocation WHERE id = 2" );
$stmt->execute ();
$alloc = $stmt->fetch ( PDO::FETCH_ASSOC );

if ( ! $alloc)
    die ("Allocation 2 not found");

$prefix = 'BS0';
$start  = 1;
$end    = 500;

for ($i = $start; $i <= $end; $i++) {
    $sn  = "BS0000" . str_pad ( $i, 3, "0", STR_PAD_LEFT );
    $chk = $pdo->prepare ( "SELECT id FROM tbl_serial_numbers WHERE serial_number = ? AND allocation_id = 2" );
    $chk->execute ( [ $sn ] );
    if ( ! $chk->fetch ()) {
        $ins = $pdo->prepare ( "INSERT INTO tbl_serial_numbers (allocation_id, branch_id, serial_number, service_type, status, is_used, created_at)
                              VALUES (?, ?, ?, ?, 'used', 1, NOW())" );
        $ins->execute ( [ 2, $alloc[ 'branch_id' ], $sn, $alloc[ 'service_type' ] ] );
        echo "Restored: $sn\n";
        }
    }

// Fix stats in tbl_serial_allocation
$pdo->query ( "UPDATE tbl_serial_allocation SET total_serials = 500 WHERE id = 2" );
echo ("Restored missing serials.\n");
?>
