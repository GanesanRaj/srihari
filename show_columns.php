<?php
require 'd:\xampp\htdocs\steve\config\config.php';
$tables = [ 'roles', 'permission', 'permission_modules' ];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt = $pdo->query ( "EXPLAIN $t" );
    while ($row = $stmt->fetch ( PDO::FETCH_ASSOC )) {
        echo $row[ 'Field' ] . ' - ' . $row[ 'Type' ] . "\n";
        }
    echo "\n";
    }
