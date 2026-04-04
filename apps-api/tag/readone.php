<?php
/**
 * Tag API – Read Single Tag
 * Location: /apps-api/tag/readone.php
 * Method: GET
 * Params:
 *   id     (required if no tag_no) – tag id
 *   tag_no (required if no id)     – tag number
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

try {
    $id     = (int)($req['id']     ?? 0);
    $tag_no = trim($req['tag_no']  ?? '');

    if ($id <= 0 && $tag_no === '') {
        echo json_encode(['status' => 'error', 'message' => 'id or tag_no is required']);
        exit;
    }

    $sql  = "SELECT t.id, t.tag_no, t.from_branch, t.to_branch, t.total_count,
                    t.status, t.created_by, t.verified_by, t.verified_at,
                    t.received_by, t.received_at, t.remarks, t.created_at,
                    t.json_data,
                    u1.username AS created_by_name,
                    u2.username AS verified_by_name,
                    u3.username AS received_by_name,
                    br_from.branch_name AS from_branch_name,
                    br_to.branch_name   AS to_branch_name
             FROM tbl_tags t
             LEFT JOIN tbl_user u1      ON u1.user_id  = t.created_by
             LEFT JOIN tbl_user u2      ON u2.user_id  = t.verified_by
             LEFT JOIN tbl_user u3      ON u3.user_id  = t.received_by
             LEFT JOIN tbl_branch br_from ON br_from.id = t.from_branch
             LEFT JOIN tbl_branch br_to   ON br_to.id   = t.to_branch
             WHERE " . ($id > 0 ? "t.id = :val" : "t.tag_no = :val") . " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':val' => $id > 0 ? $id : $tag_no]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        echo json_encode(['status' => 'error', 'message' => 'Tag not found']);
        exit;
    }

    $tag['json_data'] = json_decode($tag['json_data'] ?: '[]', true);

    // Enrich ewaybill_no from tbl_bookings / tbl_booking_packages
    if (is_array($tag['json_data']) && count($tag['json_data']) > 0) {
        $awbs         = array_column($tag['json_data'], 'awb_no');
        $placeholders = str_repeat('?,', count($awbs) - 1) . '?';

        $enrichSql = "SELECT b.waybill_no, b.ewaybill_no
                      FROM tbl_bookings b WHERE b.waybill_no IN ($placeholders)
                      UNION
                      SELECT bp.awb_no AS waybill_no, b.ewaybill_no
                      FROM tbl_booking_packages bp
                      JOIN tbl_bookings b ON bp.booking_id = b.id
                      WHERE bp.awb_no IN ($placeholders)";

        $enrichStmt = $pdo->prepare($enrichSql);
        $enrichStmt->execute(array_merge($awbs, $awbs));

        $ewayMap = [];
        while ($row = $enrichStmt->fetch(PDO::FETCH_ASSOC)) {
            $ewayMap[$row['waybill_no']] = $row['ewaybill_no'];
        }

        foreach ($tag['json_data'] as &$entry) {
            if (empty($entry['ewaybill_no']) && !empty($ewayMap[$entry['awb_no']])) {
                $entry['ewaybill_no'] = $ewayMap[$entry['awb_no']];
            }
        }
        unset($entry);
    }

    echo json_encode(['status' => 'success', 'data' => $tag]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
