<?php
require 'config/db.php';
$stmt = $pdo->query ( "SHOW COLUMNS FROM tbl_bookings LIKE 'last_status'" );
print_r ( $stmt->fetch () );
