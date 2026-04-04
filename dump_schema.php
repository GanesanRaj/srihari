<?php
require 'config/config.php';
$stmt = $pdo->query ( "SHOW COLUMNS FROM tbl_booking_packages" );
print_r ( $stmt->fetchAll ( PDO::FETCH_ASSOC ) );
$stmt = $pdo->query ( "SHOW COLUMNS FROM tbl_bookings" );
print_r ( $stmt->fetchAll ( PDO::FETCH_ASSOC ) );
