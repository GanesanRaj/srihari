<?php

// Prevent multiple inclusions
if ( ! defined ( 'HELPER_INCLUDED' )) {
    define ( 'HELPER_INCLUDED', true );

    // Include database connection if not already loaded
    if ( ! isset ($pdo)) {
        require_once __DIR__ . '/config.php';
        }

    // Start session if not already started
    if (session_status () == PHP_SESSION_NONE) {
        session_start ();
        }

    // Security-first default: if role_id is missing, do NOT grant admin access.
    $role_id = (int) ($_SESSION[ 'role_id' ] ?? 0);

    function _agent_debug_log ($payload)
        {
        // Disable heavy debug transport in normal runtime to avoid request slowdowns/timeouts.
        return;
        $logPath = __DIR__ . '/../debug-2786da.log';
        $base    = [
            'sessionId' => '2786da',
            'runId' => 'pre-fix',
            'timestamp' => round ( microtime ( true ) * 1000 )
        ];
        $event = array_merge ( $base, $payload );
        @file_put_contents ( $logPath, json_encode ( $event, JSON_UNESCAPED_SLASHES ) . PHP_EOL, FILE_APPEND );

        // Fallback transport: send logs to debug ingest endpoint.
        $endpoint = 'http://127.0.0.1:7662/ingest/b885f117-86a6-4da6-a625-6bae27d642bd';
        $json     = json_encode ( $event, JSON_UNESCAPED_SLASHES );
        if ($json !== false) {
            $ctx = stream_context_create ( [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nX-Debug-Session-Id: 2786da\r\n",
                    'content' => $json,
                    'timeout' => 1
                ]
            ] );
            @file_get_contents ( $endpoint, false, $ctx );
            }
        }

    function get_permission ($permission, $can)
        {
        global $role_id; // Ensure access to global $role_id

        // #region agent log
        _agent_debug_log ( [
            'hypothesisId' => 'H1',
            'location' => 'config/helper.php:get_permission:entry',
            'message' => 'Evaluating permission',
            'data' => [
                'role_id' => (int) $role_id,
                'permission' => (string) $permission,
                'can' => (string) $can
            ]
        ] );
        // #endregion

        if ((int) $role_id === 1) {
            // #region agent log
            _agent_debug_log ( [
                'hypothesisId' => 'H2',
                'location' => 'config/helper.php:get_permission:admin_bypass',
                'message' => 'Permission granted by admin bypass',
                'data' => [
                    'role_id' => (int) $role_id,
                    'permission' => (string) $permission,
                    'can' => (string) $can
                ]
            ] );
            // #endregion
            return true; // Admin has all permissions
            }

        $permissions = get_staff_permissions ();
        $permission_alt = str_replace( '-', '_', $permission );
        $permission_alt2 = str_replace( '_', '-', $permission );

        foreach ($permissions as $permObject) {
            $p = $permObject[ 'permission_prefix' ] ?? '';
            if ( $p === $permission || $p === $permission_alt || $p === $permission_alt2 ) {
                // #region agent log
                _agent_debug_log ( [
                    'hypothesisId' => 'H3',
                    'location' => 'config/helper.php:get_permission:match',
                    'message' => 'Matched permission row',
                    'data' => [
                        'role_id' => (int) $role_id,
                        'requested_permission' => (string) $permission,
                        'matched_prefix' => (string) $p,
                        'can' => (string) $can,
                        'value' => $permObject[$can] ?? null
                    ]
                ] );
                // #endregion
                return ! empty ($can) && isset ($permObject[$can]) && $permObject[$can] == '1';
                }
            }

        return false;
        }

    function get_staff_permissions ()
        {
        global $pdo, $role_id; // Access global variables
        static $permissions_cache = [];

        // Check if database connection exists
        if ( ! $pdo) {
            error_log ( "Database connection not available in get_staff_permissions" );
            return []; // Return empty array if no connection
            }

        $cache_key = (int) $role_id;
        if (isset ( $permissions_cache[ $cache_key ] )) {
            return $permissions_cache[ $cache_key ];
            }

        try {
            $sql = "SELECT sp.*, p.id AS permission_id, p.prefix AS permission_prefix 
                    FROM staff_privileges sp
                    JOIN permission p ON p.id = sp.permission_id 
                    WHERE sp.role_id = ?";

            $stmt = $pdo->prepare ( $sql );
            $stmt->execute ( [ $role_id ] );
            $rows = $stmt->fetchAll ( PDO::FETCH_ASSOC ) ?: [];
            // #region agent log
            _agent_debug_log ( [
                'hypothesisId' => 'H4',
                'location' => 'config/helper.php:get_staff_permissions:result',
                'message' => 'Fetched staff permissions',
                'data' => [
                    'role_id' => (int) $role_id,
                    'rows' => count ( $rows )
                ]
            ] );
            // #endregion
            $permissions_cache[ $cache_key ] = $rows;
            return $rows; // Return empty array if no permissions found
            }
        catch ( PDOException $e ) {
            error_log ( "Database error in get_staff_permissions: " . $e->getMessage () );
            return []; // Return empty array on error
            }
        }

    function access_denied ()
        {
        global $site_url;

        $redirect_url = 'index.php';
        echo "<script>alert('Access Denied'); window.location.href='$redirect_url';</script>";
        exit ();
        }

    function ajax_access_denied ()
        {
        echo json_encode ( [ 'status' => 'access_denied' ] );
        exit ();
        }

    /**
     * Get current user's information including user_type
     */
    function get_current_user_info ()
        {
        global $pdo;

        if ( ! isset ($_SESSION[ 'username' ])) {
            return null;
            }

        try {
            $stmt = $pdo->prepare ( "SELECT id, username, user_id, role_id, user_type, status 
                                   FROM tbl_user 
                                   WHERE username = ?" );
            $stmt->execute ( [ $_SESSION[ 'username' ] ] );
            return $stmt->fetch ( PDO::FETCH_ASSOC );
            }
        catch ( PDOException $e ) {
            error_log ( "Error fetching user info: " . $e->getMessage () );
            return null;
            }
        }

    /**
     * Check if current user is an employee
     */
    function is_employee ()
        {
        // Check session first for better performance
        if (isset ($_SESSION[ 'user_type' ])) {
            return $_SESSION[ 'user_type' ] === 'employee';
            }

        // Fallback to database query
        $userInfo = get_current_user_info ();
        return $userInfo && $userInfo[ 'user_type' ] === 'employee';
        }

    /**
     * Check if current user is a regular user (not employee)
     */
    function is_user_only ()
        {
        // Check session first for better performance
        if (isset ($_SESSION[ 'user_type' ])) {
            return $_SESSION[ 'user_type' ] === 'user';
            }

        // Fallback to database query
        $userInfo = get_current_user_info ();
        return $userInfo && $userInfo[ 'user_type' ] === 'user';
        }

    /**
     * Check if current user is both employee and user
     */
    function is_both ()
        {
        // Check session first for better performance
        if (isset ($_SESSION[ 'user_type' ])) {
            return $_SESSION[ 'user_type' ] === 'both';
            }

        // Fallback to database query
        $userInfo = get_current_user_info ();
        return $userInfo && $userInfo[ 'user_type' ] === 'both';
        }

    /**
     * Get current user's employee ID from tbl_employee
     */
    function get_current_user_employee_id ()
        {
        global $pdo;

        // Check for both regular and mobile sessions
        if ( ! isset ($_SESSION[ 'username' ]) && ! isset ($_SESSION[ 'mobile_username' ])) {
            return null;
            }

        // For mobile sessions, return the employee ID directly
        if (isset ($_SESSION[ 'mobile_employee_id' ])) {
            return $_SESSION[ 'mobile_employee_id' ];
            }

        try {
            // First get user info from tbl_user
            $userInfo = get_current_user_info ();
            if ( ! $userInfo) {
                return null;
                }

            // If user_type is 'employee' or 'both', find the corresponding employee record
            if (in_array ( $userInfo[ 'user_type' ], [ 'employee', 'both' ] )) {
                // Try to match by tbl_user.user_id = tbl_employee.id
                $stmt = $pdo->prepare ( "
                    SELECT e.id
                    FROM tbl_employee e
                    WHERE e.id = ? AND e.status = 'active'
                " );
                $stmt->execute ( [ $userInfo[ 'user_id' ] ] );
                $employee = $stmt->fetch ( PDO::FETCH_ASSOC );

                if ($employee) {
                    return $employee[ 'id' ];
                    }

                // If no match found, try to match by email
                $stmt = $pdo->prepare ( "
                    SELECT e.id
                    FROM tbl_employee e
                    WHERE e.email = ? AND e.status = 'active'
                " );
                $stmt->execute ( [ $userInfo[ 'username' ] ] );
                $employee = $stmt->fetch ( PDO::FETCH_ASSOC );

                if ($employee) {
                    return $employee[ 'id' ];
                    }
                }

            return null;
            }
        catch ( PDOException $e ) {
            error_log ( "Error getting employee ID: " . $e->getMessage () );
            return null;
            }
        }
    /**
     * Fetch extended user details (Employee Name, Designation)
     * Returns an array with 'name' and 'designation'
     */
    function fetch_user_extended_details ($user_id, $username, $role_id, $user_type)
        {
        global $pdo;

        $fullName    = $username; // Fallback
        $designation = 'User'; // Fallback

        // Get Role Name as default designation
        try {
            $stmtRole = $pdo->prepare ( "SELECT name FROM roles WHERE id = :role_id" );
            $stmtRole->execute ( [ ':role_id' => $role_id ] );
            $roleRow = $stmtRole->fetch ( PDO::FETCH_ASSOC );
            if ($roleRow) {
                $designation = $roleRow[ 'name' ];
                }
            }
        catch ( Exception $e ) {
            // Ignore role fetch error
            error_log ( "Error fetching role: " . $e->getMessage () );
            }

        // If employee, get employee specific details
        if (in_array ( $user_type, [ 'employee', 'both' ] )) {
            try {
                $empInfo = null;
                try {
                    // Legacy schema
                    $stmtEmp = $pdo->prepare ( "SELECT e.name as employee_name, d.name as designation_name
                                          FROM tbl_employee e
                                          LEFT JOIN tbl_designation d ON e.designation_id = d.id
                                          WHERE e.id = :uid OR e.email = :uname
                                          LIMIT 1" );
                    $stmtEmp->execute ( [ ':uid' => $user_id, ':uname' => $username ] );
                    $empInfo = $stmtEmp->fetch ( PDO::FETCH_ASSOC );
                    }
                catch ( Exception $legacySchemaException ) {
                    // Newer schema fallback
                    $stmtEmp = $pdo->prepare ( "SELECT e.name as employee_name, d.designation as designation_name
                                          FROM tbl_employees e
                                          LEFT JOIN tbl_designations d ON e.designation_id = d.id
                                          WHERE e.id = :uid OR e.email = :uname
                                          LIMIT 1" );
                    $stmtEmp->execute ( [ ':uid' => $user_id, ':uname' => $username ] );
                    $empInfo = $stmtEmp->fetch ( PDO::FETCH_ASSOC );
                    }

                if ($empInfo) {
                    if ( ! empty ($empInfo[ 'employee_name' ])) {
                        $fullName = $empInfo[ 'employee_name' ];
                        }
                    if ( ! empty ($empInfo[ 'designation_name' ])) {
                        $designation = $empInfo[ 'designation_name' ];
                        }
                    }
                }
            catch ( Exception $e ) {
                // Ignore employee fetch error
                error_log ( "Error fetching employee details: " . $e->getMessage () );
                }
            }

        return [ 'name' => $fullName, 'designation' => $designation ];
        }

    /**
     * Get the display name of the current logged-in user
     */
    function get_logged_user_name ()
        {
        if (isset ($_SESSION[ 'employee_name' ])) {
            return $_SESSION[ 'employee_name' ];
            }
        return $_SESSION[ 'username' ] ?? 'User';
        }

    /**
     * Get the designation/role of the current logged-in user
     */
    function get_logged_user_designation ()
        {
        return $_SESSION[ 'designation' ] ?? 'Role';
        }

    /**
     * Handle Image Upload with Compression (under 50KB)
     * @param array $file_input - The $_FILES element
     * @param string $folder - Subfolder in assets/images/
     * @param string|null $old_file - Path of the old file to delete
     * @param int $max_kb - Maximum file size in KB
     * @return string|false - The new relative file path or false on failure
     */
    function handle_image_upload ($file_input, $folder, $old_file = null, $max_kb = 50)
        {
        if ( ! isset ($file_input) || $file_input[ 'error' ] !== UPLOAD_ERR_OK) {
            error_log ( "Upload error: " . ($file_input[ 'error' ] ?? 'No file input') );
            return false;
            }

        $base_dir = __DIR__ . "/../assets/images/" . trim ( $folder, '/' ) . "/";
        if ( ! file_exists ( $base_dir )) {
            if ( ! mkdir ( $base_dir, 0777, true )) {
                error_log ( "Failed to create directory: $base_dir" );
                return false;
                }
            }

        $extension     = strtolower ( pathinfo ( $file_input[ 'name' ], PATHINFO_EXTENSION ) );
        $new_filename  = uniqid () . '.' . $extension;
        $target_path   = $base_dir . $new_filename;
        $relative_path = "assets/images/" . trim ( $folder, '/' ) . "/" . $new_filename;

        // Compression logic using GD
        $tmp_name   = $file_input[ 'tmp_name' ];
        $image_info = getimagesize ( $tmp_name );
        if ( ! $image_info) {
            error_log ( "Failed to get image size for $tmp_name" );
            return false;
            }
        list( $width, $height, $type ) = $image_info;

        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists ( 'imagecreatefromjpeg' )) {
                    $image = imagecreatefromjpeg ( $tmp_name );
                    }
                break;
            case IMAGETYPE_PNG:
                if (function_exists ( 'imagecreatefrompng' )) {
                    $image = imagecreatefrompng ( $tmp_name );
                    // Convert extension to jpg for output
                    $target_path   = preg_replace ( '/\.(png)$/i', '.jpg', $target_path );
                    $relative_path = preg_replace ( '/\.(png)$/i', '.jpg', $relative_path );
                    }
                break;
            case IMAGETYPE_GIF:
                if (function_exists ( 'imagecreatefromgif' )) {
                    $image = imagecreatefromgif ( $tmp_name );
                    }
                break;
            }

        if ( ! $image) {
            error_log ( "Failed to create image resource or GD functions missing for type $type" );
            return false;
            }

        // Start with quality 80 and reduce until under max_kb
        $quality = 80;
        $success = false;
        do {
            ob_start ();
            if (imagejpeg ( $image, null, $quality )) {
                $content = ob_get_clean ();
                $size    = strlen ( $content );
                if ($size <= $max_kb * 1024 || $quality <= 10) {
                    if (file_put_contents ( $target_path, $content ) !== false) {
                        $success = true;
                        } else {
                        error_log ( "Failed to write file to $target_path" );
                        }
                    break;
                    }
                } else {
                ob_end_clean ();
                error_log ( "imagejpeg failed at quality $quality" );
                break;
                }
            $quality -= 10;
            } while ($quality > 0);

        imagedestroy ( $image );

        if ( ! $success) {
            error_log ( "Compression/Save failed for $target_path" );
            return false;
            }

        // Delete old file if provided
        if ($old_file) {
            delete_image ( $old_file );
            }

        return $relative_path;
        }

    /**
     * Delete an image file from the system
     * @param string $file_path - Relative path (e.g., assets/images/...)
     * @return bool
     */
    /**
     * Delete an image file from the system
     * @param string $file_path - Relative path (e.g., assets/images/...)
     * @return bool
     */
    function delete_image ($file_path)
        {
        if ( ! $file_path)
            return false;
        $full_path = __DIR__ . "/../" . $file_path;
        if (file_exists ( $full_path ) && is_file ( $full_path )) {
            return unlink ( $full_path );
            }
        return false;
        }

    /**
     * Sanitize text input
     * @param string $input
     * @return string
     */
    function sanitizeText ($input)
        {
        return htmlspecialchars ( strip_tags ( trim ( $input ) ) );
        }

    /**
     * Synchronize shipment status across different tables (Manifest, Run Sheet, etc.)
     * @param PDO $pdo
     * @param int $bookingId
     * @param string $awbNo
     * @param string $status
     * @param string $remarks
     * @param string $username
     * @return void
     */
    function syncShipmentStatusAcrossTables ($pdo, $bookingId, $awbNo, $status, $remarks, $username)
        {
        // 1. Update Run Sheet Details
        try {
            $stmt = $pdo->prepare ( "UPDATE tbl_runsheet_details SET status = :status, remarks = :remarks, scanned_at = NOW(), scanned_by = :uname WHERE booking_id = :bid OR awb_no = :awb" );
            $stmt->execute ( [
                ':status' => $status,
                ':remarks' => $remarks,
                ':uname' => $username,
                ':bid' => $bookingId,
                ':awb' => $awbNo
            ] );
            }
        catch ( Exception $e ) {
            error_log ( "Error syncing to runsheet_details: " . $e->getMessage () );
            }

        // 2. Update Manifest (JSON manipulation)
        try {
            // Find manifests containing this AWB in their json_data
            $stmt = $pdo->prepare ( "SELECT id, json_data FROM tbl_manifest WHERE json_data LIKE :awb" );
            $stmt->execute ( [ ':awb' => '%' . $awbNo . '%' ] );
            $manifests = $stmt->fetchAll ( PDO::FETCH_ASSOC );

            foreach ($manifests as $manifest) {
                $jsonData = json_decode ( $manifest[ 'json_data' ], true );
                if ( ! is_array ( $jsonData ))
                    continue;

                $changed = false;
                foreach ($jsonData as &$entry) {
                    if ($entry[ 'awb_no' ] === $awbNo) {
                        $entry[ 'status' ]     = $status;
                        $entry[ 'remarks' ]    = $remarks;
                        $entry[ 'updated_at' ] = date ( 'Y-m-d H:i:s' );
                        $entry[ 'updated_by' ] = $username;
                        $changed               = true;
                        }
                    }
                unset ( $entry );

                if ($changed) {
                    $upd = $pdo->prepare ( "UPDATE tbl_manifest SET json_data = :json, updated_at = NOW(), updated_by = :uname WHERE id = :id" );
                    $upd->execute ( [
                        ':json' => json_encode ( $jsonData ),
                        ':uname' => $username,
                        ':id' => $manifest[ 'id' ]
                    ] );
                    }
                }
            }
        catch ( Exception $e ) {
            error_log ( "Error syncing to manifest: " . $e->getMessage () );
            }
        }

    /**
     * Update Shipment Status and Tracking History (Centralized)
     */
    function updateTrackingAndStatus ($pdo, $bookingId, $status, $location, $remarks, $userId, $username)
        {
        $status = trim ( $status );
        $now    = date ( 'Y-m-d H:i:s' );

        // 1. Fetch booking details
        $stmt = $pdo->prepare ( "SELECT id, waybill_no, courier_id, booking_ref_id, last_status FROM tbl_bookings WHERE id = :id" );
        $stmt->execute ( [ ':id' => $bookingId ] );
        $booking = $stmt->fetch ( PDO::FETCH_ASSOC );
        if ( ! $booking)
            return false;

        // 2. Update Booking Status (same status as scan, e.g. Booked, Booked @ branch)
        $stmt = $pdo->prepare ( "UPDATE tbl_bookings SET last_status = :status, updated_by = :uid, updated_at = NOW() WHERE id = :id" );
        $stmt->execute ( [ ':status' => $status, ':uid' => $userId, ':id' => $bookingId ] );

        // 3. Tracking Update logic
        if ($booking[ 'courier_id' ] == 2) {
            // Own Courier: JSON History logic
            $stmt = $pdo->prepare ( "SELECT id, raw_response FROM tbl_tracking WHERE waybill_no = :wn LIMIT 1" );
            $stmt->execute ( [ ':wn' => $booking[ 'waybill_no' ] ] );
            $existingTrack = $stmt->fetch ( PDO::FETCH_ASSOC );

            $history = [];
            if ($existingTrack && ! empty ($existingTrack[ 'raw_response' ])) {
                $decoded = json_decode ( $existingTrack[ 'raw_response' ], true );
                if (isset ($decoded[ 'scan_details_history' ])) {
                    $history = $decoded[ 'scan_details_history' ];
                    } else if (isset ($decoded[ 'scan_details' ])) {
                    $history = [ $decoded[ 'scan_details' ] ];
                    }
                }

            $newScan   = [
                'status' => $status,
                'location' => $location,
                'datetime' => $now,
                'remarks' => $remarks,
                'updated_by' => $userId,
                'updated_at' => $now
            ];
            $history[] = $newScan;

            $rawData = json_encode ( [
                'awb_no' => $booking[ 'waybill_no' ],
                'shipment_details' => [ 'id' => $bookingId, 'booking_ref_id' => $booking[ 'booking_ref_id' ] ],
                'current_status' => $status,
                'scan_details' => $newScan,
                'scan_details_history' => $history
            ] );

            if ($existingTrack) {
                $stmt = $pdo->prepare ( "UPDATE tbl_tracking SET scan_type = :st, scan_location = :sl, scan_datetime = :dt, status_code = :sc, remarks = :rem, raw_response = :raw WHERE id = :id" );
                $stmt->execute ( [ ':id' => $existingTrack[ 'id' ], ':st' => $status, ':sl' => $location, ':dt' => $now, ':sc' => $status, ':rem' => $remarks, ':raw' => $rawData ] );
                } else {
                $stmt = $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)" );
                $stmt->execute ( [ ':bid' => $bookingId, ':wn' => $booking[ 'waybill_no' ], ':st' => $status, ':sl' => $location, ':dt' => $now, ':sc' => $status, ':rem' => $remarks, ':raw' => $rawData ] );
                }
            } else {
            // Other Couriers: New log row
            $stmt = $pdo->prepare ( "INSERT INTO tbl_tracking (booking_id, waybill_no, scan_type, scan_location, scan_datetime, status_code, remarks, raw_response) VALUES (:bid, :wn, :st, :sl, :dt, :sc, :rem, :raw)" );
            $stmt->execute ( [ ':bid' => $bookingId, ':wn' => $booking[ 'waybill_no' ], ':st' => $status, ':sl' => $location, ':dt' => $now, ':sc' => $status, ':rem' => $remarks, ':raw' => json_encode ( [ 'manual_update' => true, 'user_id' => $userId ] ) ] );
            }

        // 4. Propagate to other tables (Run Sheet, Manifest) - using existing helper function
        syncShipmentStatusAcrossTables ( $pdo, $bookingId, $booking[ 'waybill_no' ], $status, $remarks, $username );

        return true;
        }
    }
