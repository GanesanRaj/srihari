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

    // Auto-generate tag_no: TAG-YYYYMMDD-NNN using MAX to avoid duplicates
    $date = date ( 'Ymd' );
    $tagNo = '';
    $maxAttempts = 5;
    $attempt = 0;
    $success = false;

    while ($attempt < $maxAttempts && !$success) {
        $attempt++;
        
        // Get the last used sequence for today
        $stmt = $pdo->prepare("SELECT tag_no FROM tbl_tags WHERE tag_no LIKE :pattern ORDER BY tag_no DESC LIMIT 1");
        $stmt->execute([':pattern' => "TAG-{$date}-%"]);
        $lastTag = $stmt->fetchColumn();
        
        $seq = 1;
        if ($lastTag) {
            $parts = explode('-', $lastTag);
            $lastSeq = (int) end($parts);
            $seq = $lastSeq + 1;
        }
        
        $tagNo = "TAG-{$date}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
        $remarks = sanitizeText ( $_POST[ 'remarks' ] ?? '' );

        try {
            $stmt = $pdo->prepare ( "INSERT INTO tbl_tags (tag_no, total_count, status, created_by, json_data, remarks)
                                   VALUES (:tag_no, 0, 'packed', :created_by, '[]', :remarks)" );
            $stmt->execute ( [ ':tag_no' => $tagNo, ':created_by' => $createdBy, ':remarks' => $remarks ] );
            $tagId = $pdo->lastInsertId ();
            $success = true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                // Concurrent request might have taken this number, loop will retry with next sequence
                continue;
            }
            throw $e;
        }
    }

    if (!$success) {
        throw new Exception("Could not generate a unique tag number after $maxAttempts attempts.");
    }

    echo json_encode ( [ 'status' => 'success', 'tag_id' => $tagId, 'tag_no' => $tagNo ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
