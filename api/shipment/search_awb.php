<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Client-user branch restriction
    $branchWhere  = '';
    $branchParams = [];
    if (isset($_SESSION['username'])) {
        $chk = $pdo->prepare("SELECT clientaccess, branch_ids FROM tbl_user WHERE username = ? LIMIT 1");
        $chk->execute([$_SESSION['username']]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['clientaccess'] == 1) {
            $rawB = $row['branch_ids'] ?? '';
            $bIds = $rawB !== '' ? array_values(array_filter(array_map('intval', explode(',', $rawB)))) : [];
            if (!empty($bIds)) {
                $keys = [];
                foreach ($bIds as $i => $id) {
                    $key = ':sb' . $i;
                    $keys[] = $key;
                    $branchParams[$key] = $id;
                }
                $branchWhere = " AND b.branch_id IN (" . implode(',', $keys) . ")";
            }
        }
    }

    // Search tbl_bookings by waybill_no or booking_ref_id
    $sql = "SELECT b.id, b.waybill_no, b.booking_ref_id, b.consignee_name, b.last_status
            FROM tbl_bookings b
            WHERE (b.waybill_no LIKE :q OR b.booking_ref_id LIKE :q)" . $branchWhere . "
            ORDER BY b.created_at DESC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q', '%' . $q . '%');
    foreach ($branchParams as $k => $v)
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also search child AWBs in tbl_booking_packages
    $pkgSql = "SELECT DISTINCT p.awb_no, p.booking_id
               FROM tbl_booking_packages p
               WHERE p.awb_no LIKE :q AND p.awb_no != ''
               LIMIT 10";
    $pkgStmt = $pdo->prepare($pkgSql);
    $pkgStmt->bindValue(':q', '%' . $q . '%');
    $pkgStmt->execute();
    $pkgRows = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    $seenIds = [];

    foreach ($rows as $r) {
        $seenIds[$r['id']] = true;
        $results[] = [
            'waybill'    => $r['waybill_no'],
            'ref'        => $r['booking_ref_id'],
            'consignee'  => $r['consignee_name'],
            'status'     => $r['last_status'],
            'is_child'   => false,
        ];
    }

    foreach ($pkgRows as $p) {
        if (isset($seenIds[$p['booking_id']])) continue;
        // Get parent booking info
        $parentStmt = $pdo->prepare("SELECT waybill_no, booking_ref_id, consignee_name, last_status FROM tbl_bookings WHERE id = :id LIMIT 1");
        $parentStmt->execute([':id' => $p['booking_id']]);
        $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$parent) continue;
        $seenIds[$p['booking_id']] = true;
        $results[] = [
            'waybill'      => $p['awb_no'],
            'parent_waybill' => $parent['waybill_no'],
            'ref'          => $parent['booking_ref_id'],
            'consignee'    => $parent['consignee_name'],
            'status'       => $parent['last_status'],
            'is_child'     => true,
        ];
    }

    echo json_encode(array_slice($results, 0, 10));
} catch (Exception $e) {
    echo json_encode([]);
}
