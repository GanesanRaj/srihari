<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $tagId   = (int) ($_POST[ 'tag_id' ] ?? 0);
    $awbNo   = trim ( $_POST[ 'awb_no' ] ?? '' );
    $remarks = sanitizeText ( $_POST[ 'remarks' ] ?? '' );

    if ($tagId <= 0 || $awbNo === '') {
        throw new Exception( 'tag_id and awb_no required' );
        }

    $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE id = :id LIMIT 1" );
    $tagStmt->execute ( [ ':id' => $tagId ] );
    $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    $entries = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
    $found   = false;
    foreach ($entries as &$entry) {
        if ($entry[ 'awb_no' ] === $awbNo) {
            $entry[ 'remarks' ]    = $remarks;
            $entry[ 'updated_at' ] = date ( 'Y-m-d H:i:s' );
            $found                 = true;
            break;
            }
        }
    unset ( $entry );
    if ( ! $found)
        throw new Exception( 'AWB not found in this tag' );

    $updStmt = $pdo->prepare ( "UPDATE tbl_tags SET json_data = :json WHERE id = :id" );
    $updStmt->execute ( [ ':json' => json_encode ( $entries ), ':id' => $tagId ] );

    echo json_encode ( [ 'status' => 'success', 'message' => 'Remark updated' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
