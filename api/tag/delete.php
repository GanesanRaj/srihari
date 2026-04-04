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

    if ($tagId <= 0) {
        throw new Exception( 'tag_id required' );
        }

    $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE id = :id LIMIT 1" );
    $tagStmt->execute ( [ ':id' => $tagId ] );
    $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    // Optional: add any conditions if some tags shouldn't be deleted (like fully verified).
    // Let's allow deleting any tag for now, or just warn in UI.

    $delStmt = $pdo->prepare ( "DELETE FROM tbl_tags WHERE id = :id" );
    $delStmt->execute ( [ ':id' => $tagId ] );

    echo json_encode ( [ 'status' => 'success', 'message' => 'Tag deleted successfully' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
