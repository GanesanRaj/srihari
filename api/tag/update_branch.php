<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $tagId      = (int) ($_POST[ 'id' ] ?? 0);
    $fromBranch = ! empty ($_POST[ 'from_branch' ]) ? (int) $_POST[ 'from_branch' ] : null;
    $toBranch   = ! empty ($_POST[ 'to_branch' ]) ? (int) $_POST[ 'to_branch' ] : null;

    if ($tagId <= 0) {
        throw new Exception( 'Tag ID is required' );
        }

    $stmt = $pdo->prepare ( "UPDATE tbl_tags SET from_branch = :from_branch, to_branch = :to_branch WHERE id = :id" );
    $stmt->execute ( [
        ':from_branch' => $fromBranch,
        ':to_branch' => $toBranch,
        ':id' => $tagId
    ] );

    echo json_encode ( [ 'status' => 'success', 'message' => 'Branch updated successfully' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
