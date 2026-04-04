<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $currentUser = get_current_user_info ();
    $userId      = $currentUser[ 'id' ] ?? ($_SESSION[ 'user_id' ] ?? 1);

    $tagId = (int) ($_POST[ 'tag_id' ] ?? 0);
    if ($tagId <= 0)
        throw new Exception( 'tag_id required' );

    $stmt = $pdo->prepare ( "UPDATE tbl_tags SET received_by = :uid, received_at = NOW() WHERE id = :id" );
    $stmt->execute ( [ ':uid' => $userId, ':id' => $tagId ] );

    echo json_encode ( [ 'status' => 'success', 'message' => 'Tag marked as received' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
