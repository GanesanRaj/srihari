<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $tagId = (int) ($_POST[ 'tag_id' ] ?? 0);
    $tagNo = trim ( $_POST[ 'tag_no' ] ?? '' );

    if ($tagId <= 0 || $tagNo === '') {
        throw new Exception( 'tag_id and tag_no are required' );
        }

    // Check uniqueness (ignore current tag)
    $chk = $pdo->prepare ( "SELECT id FROM tbl_tags WHERE tag_no = :tag_no AND id != :id LIMIT 1" );
    $chk->execute ( [ ':tag_no' => $tagNo, ':id' => $tagId ] );
    if ($chk->fetch ()) {
        throw new Exception( "Tag number '{$tagNo}' is already in use." );
        }

    $stmt = $pdo->prepare ( "UPDATE tbl_tags SET tag_no = :tag_no WHERE id = :id" );
    $stmt->execute ( [ ':tag_no' => $tagNo, ':id' => $tagId ] );

    echo json_encode ( [ 'status' => 'success', 'tag_no' => $tagNo ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
