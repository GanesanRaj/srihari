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
    $awbNo = trim ( $_POST[ 'awb_no' ] ?? '' );

    if ($tagId <= 0 || $awbNo === '') {
        throw new Exception( 'tag_id and awb_no required' );
        }

    $tagStmt = $pdo->prepare ( "SELECT * FROM tbl_tags WHERE id = :id LIMIT 1" );
    $tagStmt->execute ( [ ':id' => $tagId ] );
    $tag = $tagStmt->fetch ( PDO::FETCH_ASSOC );
    if ( ! $tag)
        throw new Exception( 'Tag not found' );

    $entries    = json_decode ( $tag[ 'json_data' ] ?: '[]', true );
    $newEntries = [];
    $found      = false;

    foreach ($entries as $entry) {
        if ($entry[ 'awb_no' ] === $awbNo) {
            $found = true;
            // Skip this one to "delete" it
            continue;
            }
        $newEntries[] = $entry;
        }

    if ( ! $found)
        throw new Exception( 'AWB not found in this tag' );

    // Recalculate tag status
    $statuses = array_column ( $newEntries, 'status' );
    $hasHold  = in_array ( 'hold', $statuses );
    // While scanning/updating, it should remain 'packed' or 'hold'.
    if ($hasHold)
        $tagStatus = 'hold';
    else
        $tagStatus = 'packed';

    $totalCount = count ( $newEntries );

    $updStmt = $pdo->prepare ( "UPDATE tbl_tags SET json_data = :json, status = :status, total_count = :cnt WHERE id = :id" );
    $updStmt->execute ( [ ':json' => json_encode ( $newEntries ), ':status' => $tagStatus, ':cnt' => $totalCount, ':id' => $tagId ] );

    echo json_encode ( [ 'status' => 'success', 'tag_status' => $tagStatus, 'total_count' => $totalCount, 'message' => 'AWB deleted successfully' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
