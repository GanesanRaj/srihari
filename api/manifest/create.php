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
    $createdBy   = $currentUser[ 'id' ] ?? ($_SESSION[ 'user_id' ] ?? 1);

    // Auto-generate manifest_no: MAN-YYYYMMDD-NNN
    $date      = date ( 'Ymd' );
    $countStmt = $pdo->query ( "SELECT COUNT(*) FROM tbl_manifest WHERE DATE(created_at) = CURDATE()" );
    $seq        = str_pad ( (int) $countStmt->fetchColumn () + 1, 3, '0', STR_PAD_LEFT );
    $manifestNo = "MAN-{$date}-{$seq}";

    $stmt = $pdo->prepare ( "INSERT INTO tbl_manifest (manifest_no, total_count, status, created_by, json_data)
                           VALUES (:manifest_no, 0, 'draft', :created_by, '[]')" );
    $stmt->execute ( [ ':manifest_no' => $manifestNo, ':created_by' => $createdBy ] );
    $manifestId = $pdo->lastInsertId ();

    echo json_encode ( [ 'status' => 'success', 'manifest_id' => $manifestId, 'manifest_no' => $manifestNo ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
