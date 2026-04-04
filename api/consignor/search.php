<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$search = '';
if (isset($_GET['q'])) {
    $search = trim($_GET['q']);
} elseif (isset($_GET['search']['value'])) {
    $search = trim($_GET['search']['value']);
}

$results = [];

if (strlen($search) >= 1) {
    $s = "%$search%";

    // From tbl_consignor (saved records)
    try {
        $stmt = $pdo->prepare(
            "SELECT id, name, contact_no, address, city, state, pincode, email, gst_number
             FROM tbl_consignor
             WHERE (name LIKE :s1 OR contact_no LIKE :s2) AND status = 'active'
             ORDER BY name LIMIT 10"
        );
        $stmt->bindValue(':s1', $s);
        $stmt->bindValue(':s2', $s);
        $stmt->execute();
        $consignors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $consignors = [];
    }

    // From tbl_bookings distinct shippers
    try {
        $stmt2 = $pdo->prepare(
            "SELECT MIN(id) as id,
                    shipper_name   as name,
                    shipper_phone  as contact_no,
                    shipper_address as address,
                    shipper_city   as city,
                    shipper_state  as state,
                    shipper_pin    as pincode,
                    ''             as email,
                    ''             as gst_number
             FROM tbl_bookings
             WHERE (shipper_name LIKE :s1 OR shipper_phone LIKE :s2) AND shipper_name != ''
             GROUP BY shipper_name, shipper_phone, shipper_address, shipper_city, shipper_state, shipper_pin
             ORDER BY shipper_name
             LIMIT 10"
        );
        $stmt2->bindValue(':s1', $s);
        $stmt2->bindValue(':s2', $s);
        $stmt2->execute();
        $fromBookings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $fromBookings = [];
    }

    // Merge: prefer tbl_consignor, add unique entries from bookings
    $seen = [];
    foreach ($consignors as $c) {
        $key = strtolower(trim($c['name'])) . '|' . trim($c['contact_no']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $results[] = $c;
        }
    }
    foreach ($fromBookings as $c) {
        $key = strtolower(trim($c['name'])) . '|' . trim($c['contact_no']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $results[] = $c;
        }
    }
    $results = array_slice($results, 0, 10);
}

echo json_encode(['data' => $results]);
?>
