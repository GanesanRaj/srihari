<?php
// get from grundfos
require 'd:\xampp\htdocs\grundfos\config\config.php';
$stmt   = $pdo->query ( 'SHOW CREATE TABLE staff_privileges' );
$row    = $stmt->fetch ( PDO::FETCH_ASSOC );
$schema = $row[ 'Create Table' ];

// run in steve
require 'd:\xampp\htdocs\steve\config\config.php';
$pdo->exec ( $schema );
echo "staff_privileges table created in steve!\n";
