<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $id    = (int) ($_GET[ 'id' ] ?? 0);
    $tagNo = trim ( $_GET[ 'tag_no' ] ?? '' );
    if ($id <= 0 && $tagNo === '')
        throw new Exception( 'id or tag_no required' );

    $sql  = "SELECT t.*,
                u1.username AS created_by_name,
                u2.username AS verified_by_name,
                u3.username AS received_by_name
             FROM tbl_tags t
             LEFT JOIN tbl_user u1 ON u1.user_id = t.created_by
             LEFT JOIN tbl_user u2 ON u2.user_id = t.verified_by
             LEFT JOIN tbl_user u3 ON u3.user_id = t.received_by
             WHERE " . ($id > 0 ? "t.id = :id" : "t.tag_no = :tag_no") . " LIMIT 1";
    $stmt = $pdo->prepare ( $sql );
    $stmt->execute ( $id > 0 ? [ ':id' => $id ] : [ ':tag_no' => $tagNo ] );
    $tag = $stmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    $tag[ 'json_data' ] = json_decode ( $tag[ 'json_data' ] ?: '[]', true );

    // Enrich missing ewaybill_no for older tags
    if (is_array ( $tag[ 'json_data' ] ) && count ( $tag[ 'json_data' ] ) > 0) {
        $awbs         = array_column ( $tag[ 'json_data' ], 'awb_no' );
        $placeholders = str_repeat ( '?,', count ( $awbs ) - 1 ) . '?';

        $enrichSql = "SELECT b.waybill_no, b.ewaybill_no 
                      FROM tbl_bookings b 
                      WHERE b.waybill_no IN ($placeholders)
                      UNION
                      SELECT bp.awb_no as waybill_no, b.ewaybill_no
                      FROM tbl_booking_packages bp
                      JOIN tbl_bookings b ON bp.booking_id = b.id
                      WHERE bp.awb_no IN ($placeholders)";

        $enrichStmt = $pdo->prepare ( $enrichSql );
        $enrichStmt->execute ( array_merge ( $awbs, $awbs ) );

        $ewayMap = [];
        while ($row = $enrichStmt->fetch ( PDO::FETCH_ASSOC )) {
            $ewayMap[$row[ 'waybill_no' ]] = $row[ 'ewaybill_no' ];
            }

        foreach ($tag[ 'json_data' ] as &$entry) {
            if (empty ($entry[ 'ewaybill_no' ]) && ! empty ($ewayMap[$entry[ 'awb_no' ]])) {
                $entry[ 'ewaybill_no' ] = $ewayMap[$entry[ 'awb_no' ]];
                }
            }
        unset ( $entry );
        }

    echo json_encode ( [ 'status' => 'success', 'data' => $tag ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
