<?php
require 'config/config.php';
$cols = $pdo->query ( "SHOW COLUMNS FROM tbl_booking_packages" )->fetchAll ( PDO::FETCH_ASSOC );
file_put_contents ( 'schema_packages.json', json_encode ( $cols, JSON_PRETTY_PRINT ) );
