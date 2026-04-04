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

    // From tbl_consignee (saved records)
    try {
        $stmt = $pdo->prepare(
            "SELECT id, name, contact_no, address, city, state, pincode, email, gst_number
             FROM tbl_consignee
             WHERE (name LIKE :s1 OR contact_no LIKE :s2) AND status = 'active'
             ORDER BY name LIMIT 10"
        );
        $stmt->bindValue(':s1', $s);
        $stmt->bindValue(':s2', $s);
        $stmt->execute();
        $consignees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $consignees = [];
    }

    // From tbl_bookings distinct consignees
    try {
        $stmt2 = $pdo->prepare(
            "SELECT MIN(id) as id,
                    consignee_name    as name,
                    consignee_phone   as contact_no,
                    consignee_address as address,
                    consignee_city    as city,
                    consignee_state   as state,
                    consignee_pin     as pincode,
                    consignee_email   as email,
                    consignee_gst     as gst_number
             FROM tbl_bookings
             WHERE (consignee_name LIKE :s1 OR consignee_phone LIKE :s2) AND consignee_name != ''
             GROUP BY consignee_name, consignee_phone, consignee_address, consignee_city, consignee_state, consignee_pin, consignee_email, consignee_gst
             ORDER BY consignee_name
             LIMIT 10"
        );
        $stmt2->bindValue(':s1', $s);
        $stmt2->bindValue(':s2', $s);
        $stmt2->execute();
        $fromBookings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $fromBookings = [];
    }

    // Merge: prefer tbl_consignee, add unique entries from bookings
    $seen = [];
    foreach ($consignees as $c) {
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
