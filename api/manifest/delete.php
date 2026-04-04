<?php
header ( 'Content-Type: application/json' );
require_once '../../config/config.php';
require_once '../../config/middleware.php';

if ($_SERVER[ 'REQUEST_METHOD' ] !== 'POST') {
    echo json_encode ( [ 'status' => 'error', 'message' => 'Invalid method' ] );
    exit;
    }

try {
    $manifestId = (int) ($_POST[ 'manifest_id' ] ?? 0);
    if ($manifestId <= 0)
        throw new Exception( 'manifest_id required' );

    $stmt = $pdo->prepare ( "DELETE FROM tbl_manifest WHERE id = :id" );
    $stmt->execute ( [ ':id' => $manifestId ] );

    if ($stmt->rowCount () === 0)
        throw new Exception( 'Manifest not found' );

    echo json_encode ( [ 'status' => 'success', 'message' => 'Manifest deleted' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
