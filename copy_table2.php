<?php
$pdoG   = new PDO( 'mysql:host=localhost;dbname=grund_fos', 'root', '' );
$stmt   = $pdoG->query ( 'SHOW CREATE TABLE staff_privileges' );
$row    = $stmt->fetch ( PDO::FETCH_ASSOC );
$schema = $row[ 'Create Table' ];
echo $schema . "\n";

$pdoS = new PDO( 'mysql:host=localhost;dbname=srihari', 'root', '' );
$pdoS->exec ( $schema );
echo "staff_privileges table created in steve!\n";

// Also let's insert the missing roles or permissions? No, the tables are already there in srihari, but if staff_privileges was missing, let's copy the data as well.
/*
$stmt = $pdoG->query('SELECT * FROM staff_privileges');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($rows) {
    $insert = $pdoS->prepare('INSERT INTO staff_privileges (id, role_id, permission_id, is_add, is_edit, is_view, is_delete) VALUES (:id, :role_id, :permission_id, :is_add, :is_edit, :is_view, :is_delete)');
    foreach ($rows as $r) {
        $insert->execute($r);
    }
    echo "Inserted " . count($rows) . " rows.\n";
}
*/
