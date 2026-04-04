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

    $runsheetId = (int) ($_POST[ 'runsheet_id' ] ?? 0);
    if ($runsheetId <= 0)
        throw new Exception( 'runsheet_id required' );

    $fields = [ 'updated_by = :updated_by' ];
    $params = [ ':updated_by' => $userId, ':id' => $runsheetId ];

    if (array_key_exists ( 'runsheet_no', $_POST )) {
        $newNo = trim ( $_POST[ 'runsheet_no' ] );
        if ($newNo !== '') {
            $chk = $pdo->prepare ( "SELECT id FROM tbl_runsheet WHERE runsheet_no = :no AND id != :id LIMIT 1" );
            $chk->execute ( [ ':no' => $newNo, ':id' => $runsheetId ] );
            if ($chk->fetch ())
                throw new Exception( "Run Sheet No '{$newNo}' already exists" );
            $fields[]                 = 'runsheet_no = :runsheet_no';
            $params[ ':runsheet_no' ] = $newNo;
            }
        }
    if (array_key_exists ( 'driver_name', $_POST )) {
        $fields[]                 = 'driver_name = :driver_name';
        $params[ ':driver_name' ] = sanitizeText ( $_POST[ 'driver_name' ] );
        }
    if (array_key_exists ( 'mobile_number', $_POST )) {
        $fields[]                   = 'mobile_number = :mobile_number';
        $params[ ':mobile_number' ] = sanitizeText ( $_POST[ 'mobile_number' ] );
        }
    if (array_key_exists ( 'runsheet_date', $_POST )) {
        $fields[]                   = 'runsheet_date = :runsheet_date';
        $params[ ':runsheet_date' ] = $_POST[ 'runsheet_date' ] ?: null;
        }
    if (array_key_exists ( 'status', $_POST )) {
        $fields[]            = 'status = :status';
        $params[ ':status' ] = sanitizeText ( $_POST[ 'status' ] );
        }

    // Handle optional file uploads (images, pdf, excel, csv) for the runsheet
    $uploadedFiles = [];
    if (isset ($_FILES[ 'attachments' ]) && is_array ( $_FILES[ 'attachments' ][ 'name' ] )) {
        $uploadDir = __DIR__ . '/../../uploads/runsheet/';
        if ( ! is_dir ( $uploadDir )) {
            mkdir ( $uploadDir, 0777, true );
            }
        $allowedExts = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'xls', 'xlsx', 'csv', 'doc', 'docx' ];
        $fileCount   = count ( $_FILES[ 'attachments' ][ 'name' ] );
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES[ 'attachments' ][ 'error' ][$i] === UPLOAD_ERR_OK) {
                $origName = $_FILES[ 'attachments' ][ 'name' ][$i];
                $ext      = strtolower ( pathinfo ( $origName, PATHINFO_EXTENSION ) );
                if ( ! in_array ( $ext, $allowedExts ))
                    continue;
                $newName  = uniqid ( 'rshm_' ) . '.' . $ext;
                $destPath = $uploadDir . $newName;
                if (move_uploaded_file ( $_FILES[ 'attachments' ][ 'tmp_name' ][$i], $destPath )) {
                    $uploadedFiles[] = [
                        'name' => $origName,
                        'path' => 'uploads/runsheet/' . $newName,
                        'ext' => $ext,
                        'size' => $_FILES[ 'attachments' ][ 'size' ][$i]
                    ];
                    }
                }
            }
        }

    if ( ! empty ($uploadedFiles)) {
        $fields[] = 'attachments = :attachments';
        // Get existing attachments if any to append or just overwrite (we'll overwrite or append, let's just save the new ones as it's a completion step)
        // If we wanted to append, we would select it first. For simplicity, just store it.
        $params[ ':attachments' ] = json_encode ( $uploadedFiles );
        }

    $stmt = $pdo->prepare ( "UPDATE tbl_runsheet SET " . implode ( ', ', $fields ) . " WHERE id = :id" );
    $stmt->execute ( $params );

    // If status changed, propagate to all AWBs in this run sheet
    if (array_key_exists ( 'status', $_POST )) {
        $newStatus = sanitizeText ( $_POST[ 'status' ] );

        // Fetch runsheet details to get its shipments
        $dStmt = $pdo->prepare ( "SELECT booking_id, awb_no FROM tbl_runsheet_details WHERE runsheet_id = :id" );
        $dStmt->execute ( [ ':id' => $runsheetId ] );
        $details = $dStmt->fetchAll ( PDO::FETCH_ASSOC );

        if ( ! empty ($details)) {
            $username     = $currentUser[ 'username' ] ?? 'system';
            $remarks      = "Status updated via Run Sheet ({$newStatus})";
            $mappedStatus = ($newStatus === 'dispatched') ? 'Out For Delivery' : ucfirst ( $newStatus );

            foreach ($details as $row) {
                updateTrackingAndStatus ( $pdo, $row[ 'booking_id' ], $mappedStatus, 'Run Sheet Location', $remarks, $userId, $username );
                }
            }
        }

    echo json_encode ( [ 'status' => 'success', 'message' => 'Run Sheet and associated shipments updated' ] );
    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
