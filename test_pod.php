<?php
require 'config/db.php';
$stmt = $pdo->prepare("SELECT GROUP_CONCAT(bp2.pod_images ORDER BY bp2.row_no ASC SEPARATOR '|||') AS pod_images FROM tbl_booking_packages bp2 WHERE bp2.booking_id = 54 AND bp2.pod_images IS NOT NULL AND bp2.pod_images != '[]' AND bp2.pod_images != ''");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "GROUP_CONCAT result:\n";
var_dump($row['pod_images']);

$raw = $row['pod_images'];
$urls = [];
foreach (explode('|||', $raw) as $chunk) {
    $arr = json_decode($chunk, true);
    if (is_array($arr)) foreach ($arr as $u) if (!empty($u)) $urls[] = (string)$u;
}
echo "Decoded URLs:\n";
print_r($urls);
?>
