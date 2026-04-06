<?php
header ( 'Content-Type: application/json' );
require '../../config/db.php';
require '../../config/middleware.php';

// require_permission('shipment', 'is_view');

try {
    // Pagination parameters
    $start       = isset ($_GET[ 'start' ]) ? (int) $_GET[ 'start' ] : 0;
    $length      = isset ($_GET[ 'length' ]) ? (int) $_GET[ 'length' ] : 10;
    $searchValue = isset ($_GET[ 'search' ][ 'value' ]) ? $_GET[ 'search' ][ 'value' ] : '';

    // Handle -1 (get all records) - set to a large number
    if ($length <= 0) {
        $length = 999999;
        }

    // Detect client-type user — read branch_ids/client_ids directly from DB
    $clientBranchWhere = '';
    $clientClientWhere = '';
    $clientNamedParams = [];
    if (isset ($_SESSION[ 'username' ])) {
        $chkR = $pdo->prepare ( "SELECT clientaccess, branch_ids, client_ids FROM tbl_user WHERE username = ? LIMIT 1" );
        $chkR->execute ( [ $_SESSION[ 'username' ] ] );
        $chkRRow = $chkR->fetch ( PDO::FETCH_ASSOC );
        if ($chkRRow && $chkRRow[ 'clientaccess' ] == 1) {
            $rawB = $chkRRow[ 'branch_ids' ] ?? '';
            $bIds = $rawB !== '' ? array_values ( array_filter ( array_map ( 'intval', explode ( ',', $rawB ) ) ) ) : [];
            if ( ! empty ($bIds)) {
                $bKeys = [];
                foreach ($bIds as $i => $id) {
                    $key                     = ':cb' . $i;
                    $bKeys[]                 = $key;
                    $clientNamedParams[$key] = $id;
                    }
                $clientBranchWhere = " AND b.branch_id IN (" . implode ( ',', $bKeys ) . ")";
                }
            $rawC = $chkRRow[ 'client_ids' ] ?? '';
            $cIds = $rawC !== '' ? array_values ( array_filter ( array_map ( 'intval', explode ( ',', $rawC ) ) ) ) : [];
            if ( ! empty ($cIds)) {
                $cKeys = [];
                foreach ($cIds as $i => $id) {
                    $key                     = ':cc' . $i;
                    $cKeys[]                 = $key;
                    $clientNamedParams[$key] = $id;
                    }
                $clientClientWhere = " AND b.client_id IN (" . implode ( ',', $cKeys ) . ")";
                }
            }
        }

    $sql = "SELECT b.id, b.courier_id, b.pickup_point_id, b.booking_ref_id, b.auto_order_no, b.waybill_no,
            b.quantity, b.created_by, b.created_at,
            b.consignee_name, b.consignee_phone, b.consignee_address, b.consignee_city, b.consignee_pin, b.consignee_state,
            b.shipper_name, b.shipper_phone, b.shipper_address, b.shipper_city, b.shipper_pin,
            b.payment_mode, b.cod_amount, b.invoice_value, b.last_status, b.shipping_mode,
            b.status_type, b.nsl_code, b.add_to_pickup, b.pickup_date, b.is_manifest,
            b.shiprocket_courier_company_name, b.shiprocket_courier_company_id,
            b.ewaybill_no, b.invoice_no, b.ewb_update_status, b.ewb_update_at,
            c.partner_name as courier_name, p.name as pickup_point_name,
            br.branch_name, cl.client_name AS company_name,
            u.username as created_by_name,
            b.api_response AS api_response_json,
            (SELECT GROUP_CONCAT(bp.awb_no ORDER BY bp.row_no ASC SEPARATOR ',')
             FROM tbl_booking_packages bp WHERE bp.booking_id = b.id) AS child_awbs,
            (SELECT GROUP_CONCAT(bp2.pod_images ORDER BY bp2.row_no ASC SEPARATOR '|||')
             FROM tbl_booking_packages bp2
             WHERE bp2.booking_id = b.id AND bp2.pod_images IS NOT NULL AND bp2.pod_images != '[]' AND bp2.pod_images != '') AS pod_images,
            (SELECT GROUP_CONCAT(bp3.delivery_pod_images ORDER BY bp3.row_no ASC SEPARATOR '|||')
             FROM tbl_booking_packages bp3
             WHERE bp3.booking_id = b.id AND bp3.delivery_pod_images IS NOT NULL AND bp3.delivery_pod_images != '[]' AND bp3.delivery_pod_images != '') AS delivery_pod_images_raw,
            t.raw_response AS tracking_raw
            FROM tbl_bookings b
            LEFT JOIN tbl_courier_partner c ON b.courier_id = c.id
            LEFT JOIN tbl_pickup_points p ON b.pickup_point_id = p.id
            LEFT JOIN tbl_branch br ON b.branch_id = br.id
            LEFT JOIN tbl_client cl ON b.client_id = cl.id
            LEFT JOIN tbl_user u ON u.user_id = b.created_by
            LEFT JOIN tbl_tracking t ON t.waybill_no = b.waybill_no
            WHERE 1=1" . $clientBranchWhere . $clientClientWhere;

    // Search
    if ( ! empty ($searchValue)) {
        $sql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR b.shipper_name LIKE :search OR CAST(b.auto_order_no AS CHAR) LIKE :search)";
        }

    // Filter by Company
    if ( ! empty ($_GET[ 'company_id' ])) {
        $sql .= " AND br.company_id = :company_id";
        }

    // Filter by Branch
    if ( ! empty ($_GET[ 'branch_id' ])) {
        $sql .= " AND b.branch_id = :branch_id";
        }

    // Filter by Client
    if ( ! empty ($_GET[ 'client_id' ])) {
        $sql .= " AND b.client_id = :client_id";
        }

    // Filter by Status
    if ( ! empty ($_GET[ 'status' ])) {
        if ($_GET[ 'status' ] === 'In Transit') {
            $sql .= " AND (LOWER(b.last_status) LIKE '%transit%' OR LOWER(b.last_status) LIKE '%pickup%'
                      OR LOWER(b.last_status) LIKE '%dispatch%' OR LOWER(b.last_status) LIKE '%out for%'
                      OR LOWER(b.last_status) LIKE '%manifest%' OR LOWER(b.last_status) LIKE '%booked%'
                      OR LOWER(b.last_status) LIKE '%in-transit%')
                      AND LOWER(b.last_status) NOT LIKE '%out for delivery%'";
            } else {
            $sql .= " AND b.last_status = :filter_status";
            }
        }

    // Filter by Courier
    if ( ! empty ($_GET[ 'courier_id' ])) {
        $sql .= " AND b.courier_id = :courier_id";
        }

    // Filter by Pickup Point
    if ( ! empty ($_GET[ 'pickup_point_id' ])) {
        $sql .= " AND b.pickup_point_id = :pickup_point_id";
        }

    // Filter by Date Range
    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ])) {
        $sql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
        }

    // Filter by Status Tab (B2C tab-based filtering)
    $NDR_IN = "'EOD-74','EOD-15','EOD-104','EOD-43','EOD-86','EOD-11','EOD-69','EOD-6'";
    $notCancelledSQL = " AND LOWER(IFNULL(b.last_status,'')) != 'cancelled'";
    $statusTabDefs = [
        'soft_date_uploaded'   => $notCancelledSQL,
        'synced_ready_to_ship' => $notCancelledSQL . " AND b.status_type = 'UD' AND b.add_to_pickup = 1 AND (b.pickup_date IS NULL OR b.pickup_date = '')",
        'ready_for_pickup'     => $notCancelledSQL . " AND b.status_type = 'UD' AND (b.add_to_pickup = 0 OR b.add_to_pickup IS NULL) AND (b.pickup_date IS NULL OR b.pickup_date = '')",
        'in_transit'           => $notCancelledSQL . " AND b.status_type = 'UD' AND b.last_status IN ('In Transit','Pending','Dispatched') AND (b.nsl_code IS NULL OR b.nsl_code NOT IN ($NDR_IN))",
        'return_to_origin'     => $notCancelledSQL . " AND b.status_type = 'DL' AND LOWER(IFNULL(b.last_status,'')) IN ('rto','return to origin')",
        'delivered'            => $notCancelledSQL . " AND b.status_type = 'DL' AND LOWER(IFNULL(b.last_status,'')) = 'delivered'",
        'ndr_shipment'         => $notCancelledSQL . " AND b.nsl_code IN ($NDR_IN)",
        'cancelled'            => " AND LOWER(IFNULL(b.last_status,'')) = 'cancelled'",
    ];
    $statusTab = trim($_GET['status_tab'] ?? '');
    if ($statusTab !== '' && isset($statusTabDefs[$statusTab])) {
        $sql .= $statusTabDefs[$statusTab];
        }

    $sql .= " ORDER BY b.created_at DESC LIMIT :start, :length";

    $stmt = $pdo->prepare ( $sql );

    // Bind values
    if ( ! empty ($searchValue)) {
        $stmt->bindValue ( ':search', "%$searchValue%", PDO::PARAM_STR );
        }
    if ( ! empty ($_GET[ 'company_id' ])) {
        $stmt->bindValue ( ':company_id', $_GET[ 'company_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'branch_id' ])) {
        $stmt->bindValue ( ':branch_id', $_GET[ 'branch_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'client_id' ])) {
        $stmt->bindValue ( ':client_id', $_GET[ 'client_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'status' ]) && $_GET[ 'status' ] !== 'In Transit') {
        $stmt->bindValue ( ':filter_status', $_GET[ 'status' ], PDO::PARAM_STR );
        }
    if ( ! empty ($_GET[ 'courier_id' ])) {
        $stmt->bindValue ( ':courier_id', $_GET[ 'courier_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'pickup_point_id' ])) {
        $stmt->bindValue ( ':pickup_point_id', $_GET[ 'pickup_point_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ])) {
        $stmt->bindValue ( ':from_date', $_GET[ 'from_date' ], PDO::PARAM_STR );
        $stmt->bindValue ( ':to_date', $_GET[ 'to_date' ], PDO::PARAM_STR );
        }

    foreach ($clientNamedParams as $key => $val) {
        $stmt->bindValue ( $key, $val, PDO::PARAM_INT );
        }
    $stmt->bindValue ( ':start', $start, PDO::PARAM_INT );
    $stmt->bindValue ( ':length', $length, PDO::PARAM_INT );
    $stmt->execute ();
    $data = $stmt->fetchAll ( PDO::FETCH_ASSOC );

    // Helper: decode GROUP_CONCAT of JSON arrays (separated by |||) into flat image list
    $decodePkgImages = function ($raw)
        {
        $urls = [];
        if (empty ($raw))
            return $urls;
        foreach (explode ( '|||', $raw ) as $chunk) {
            $arr = json_decode ( $chunk, true );
            if (is_array ( $arr )) {
                foreach ($arr as $u) {
                    if ( ! empty ($u))
                        $urls[] = (string) $u;
                    }
                }
            }
        return $urls;
        };

    // Helper: normalize image URL — strip leading slashes to prevent double-slash in output
    $normalizeUrl = function ($url)
        {
        return ltrim ( (string) $url, '/' );
        };

    // Parse tracking history: collect ALL pickup and ALL delivery POD images with url + uploaded date
    foreach ($data as &$row) {
        $row[ 'pickup_pod' ]   = [];
        $row[ 'delivery_pod' ] = [];
        if ( ! empty ($row[ 'tracking_raw' ])) {
            $trackData = json_decode ( $row[ 'tracking_raw' ], true );
            $history   = $trackData[ 'scan_details_history' ] ?? [];
            foreach ($history as $scan) {
                $scanDate = $scan[ 'datetime' ] ?? $scan[ 'ScanDateTime' ] ?? $scan[ 'updated_at' ] ?? '';
                $st       = strtolower ( $scan[ 'status' ] ?? '' );

                // Pickup images — stored under pod_images key
                $pickupImgs = $scan[ 'pod_images' ] ?? [];
                if ( ! empty ($pickupImgs) && is_array ( $pickupImgs ) && strpos ( $st, 'pick' ) !== false) {
                    foreach ($pickupImgs as $url) {
                        if ( ! empty ($url))
                            $row[ 'pickup_pod' ][] = [ 'url' => $normalizeUrl ( $url ), 'date' => $scanDate ];
                        }
                    }

                // Delivery images — stored under delivery_pod_images key
                $delivImgs = $scan[ 'delivery_pod_images' ] ?? [];
                if ( ! empty ($delivImgs) && is_array ( $delivImgs )) {
                    foreach ($delivImgs as $url) {
                        if ( ! empty ($url))
                            $row[ 'delivery_pod' ][] = [ 'url' => $normalizeUrl ( $url ), 'date' => $scanDate ];
                        }
                    }
                }
            }

        // Always prefer tbl_booking_packages.pod_images for pickup (covers all boxes)
        $pkgPickupUrls = $decodePkgImages ( $row[ 'pod_images' ] ?? '' );
        if ( ! empty ($pkgPickupUrls)) {
            $row[ 'pickup_pod' ] = [];
            foreach ($pkgPickupUrls as $url) {
                $row[ 'pickup_pod' ][] = [ 'url' => $normalizeUrl ( $url ), 'date' => '' ];
                }
            }

        // Always prefer tbl_booking_packages.delivery_pod_images for delivery POD
        $pkgDelivUrls = $decodePkgImages ( $row[ 'delivery_pod_images_raw' ] ?? '' );
        if ( ! empty ($pkgDelivUrls)) {
            $row[ 'delivery_pod' ] = [];
            foreach ($pkgDelivUrls as $url) {
                $row[ 'delivery_pod' ][] = [ 'url' => $normalizeUrl ( $url ), 'date' => '' ];
                }
            }

        // Shiprocket extracted fields (for Shiprocket list/module columns)
        $shiprocketOrderId = '';
        $shiprocketShipmentId = '';
        $shiprocketAwbCode = '';
        $shiprocketCourierName = '';
        $shiprocketChildCourierName = '';
        $shiprocketCourierCompanyId = '';
        $apiResp = $row[ 'api_response_json' ] ?? null;
        if (!empty($apiResp)) {
            $apiRespArr = null;
            if (is_string($apiResp)) {
                $apiRespArr = json_decode((string)$apiResp, true);
            } else if (is_array($apiResp)) {
                $apiRespArr = $apiResp;
            }
            if (is_array($apiRespArr)) {
                $shiprocketOrderId = trim((string)($apiRespArr['order_id'] ?? $apiRespArr['response']['data']['order_id'] ?? ''));
                $shiprocketShipmentId = trim((string)($apiRespArr['shipment_id'] ?? $apiRespArr['response']['data']['shipment_id'] ?? ''));
                $shiprocketAwbCode = trim((string)($apiRespArr['awb_code'] ?? $apiRespArr['response']['data']['awb_code'] ?? ''));
                $shiprocketCourierName = trim((string)($apiRespArr['courier_name'] ?? $apiRespArr['response']['data']['courier_name'] ?? ''));
                $shiprocketChildCourierName = trim((string)($apiRespArr['child_courier_name'] ?? $apiRespArr['response']['data']['child_courier_name'] ?? ''));
                $shiprocketCourierCompanyId = trim((string)($apiRespArr['courier_company_id'] ?? $apiRespArr['response']['data']['courier_company_id'] ?? ''));

                // If AWB assignment response is nested under api_response.awb_assign, prefer that.
                if (!empty($apiRespArr['awb_assign']) && is_array($apiRespArr['awb_assign'])) {
                    $assign = $apiRespArr['awb_assign'];
                    $shiprocketOrderId = trim((string)($assign['response']['data']['order_id'] ?? $shiprocketOrderId));
                    $shiprocketShipmentId = trim((string)($assign['response']['data']['shipment_id'] ?? $shiprocketShipmentId));
                    $shiprocketAwbCode = trim((string)($assign['response']['data']['awb_code'] ?? $shiprocketAwbCode));
                    $shiprocketCourierName = trim((string)($assign['response']['data']['courier_name'] ?? $shiprocketCourierName));
                    $shiprocketChildCourierName = trim((string)($assign['response']['data']['child_courier_name'] ?? $shiprocketChildCourierName));
                    $shiprocketCourierCompanyId = trim((string)($assign['response']['data']['courier_company_id'] ?? $shiprocketCourierCompanyId));
                }
            }
        }
        if ($shiprocketOrderId === '' && ! empty ($row[ 'auto_order_no' ])) {
            $shiprocketOrderId = (string) (int) $row[ 'auto_order_no' ];
            }
        $row[ 'shiprocket_order_id' ] = $shiprocketOrderId;
        $row[ 'shiprocket_shipment_id' ] = $shiprocketShipmentId;
        $row[ 'shiprocket_awb_code' ] = $shiprocketAwbCode;
        $row[ 'shiprocket_courier_name' ] = $shiprocketCourierName;
        $row[ 'shiprocket_child_courier_name' ] = $shiprocketChildCourierName;
        $row[ 'shiprocket_courier_company_id' ] = $shiprocketCourierCompanyId;

        unset ( $row[ 'tracking_raw' ], $row[ 'pod_images' ], $row[ 'delivery_pod_images_raw' ], $row[ 'api_response_json' ] );
        }
    unset ( $row );

    // Count filter records
    $countSql = "SELECT COUNT(*) FROM tbl_bookings b
                 LEFT JOIN tbl_branch br ON b.branch_id = br.id
                 WHERE 1=1" . $clientBranchWhere . $clientClientWhere;

    if ( ! empty ($searchValue)) {
        $countSql .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR CAST(b.auto_order_no AS CHAR) LIKE :search)";
        }
    if ( ! empty ($_GET[ 'company_id' ])) {
        $countSql .= " AND br.company_id = :company_id";
        }
    if ( ! empty ($_GET[ 'branch_id' ])) {
        $countSql .= " AND b.branch_id = :branch_id";
        }
    if ( ! empty ($_GET[ 'client_id' ])) {
        $countSql .= " AND b.client_id = :client_id";
        }
    if ( ! empty ($_GET[ 'status' ])) {
        if ($_GET[ 'status' ] === 'In Transit') {
            $countSql .= " AND (LOWER(b.last_status) LIKE '%transit%' OR LOWER(b.last_status) LIKE '%pickup%'
                           OR LOWER(b.last_status) LIKE '%dispatch%' OR LOWER(b.last_status) LIKE '%out for%'
                           OR LOWER(b.last_status) LIKE '%manifest%' OR LOWER(b.last_status) LIKE '%booked%'
                           OR LOWER(b.last_status) LIKE '%in-transit%')
                           AND LOWER(b.last_status) NOT LIKE '%out for delivery%'";
            } else {
            $countSql .= " AND b.last_status = :filter_status";
            }
        }
    if ( ! empty ($_GET[ 'courier_id' ])) {
        $countSql .= " AND b.courier_id = :courier_id";
        }
    if ( ! empty ($_GET[ 'pickup_point_id' ])) {
        $countSql .= " AND b.pickup_point_id = :pickup_point_id";
        }

    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ])) {
        $countSql .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
        }
    // Status tab filter for count
    if ($statusTab !== '' && isset($statusTabDefs[$statusTab])) {
        $countSql .= $statusTabDefs[$statusTab];
        }

    $countStmt = $pdo->prepare ( $countSql );
    if ( ! empty ($searchValue)) {
        $countStmt->bindValue ( ':search', "%$searchValue%", PDO::PARAM_STR );
        }
    if ( ! empty ($_GET[ 'company_id' ])) {
        $countStmt->bindValue ( ':company_id', $_GET[ 'company_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'branch_id' ])) {
        $countStmt->bindValue ( ':branch_id', $_GET[ 'branch_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'client_id' ])) {
        $countStmt->bindValue ( ':client_id', $_GET[ 'client_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'status' ]) && $_GET[ 'status' ] !== 'In Transit') {
        $countStmt->bindValue ( ':filter_status', $_GET[ 'status' ], PDO::PARAM_STR );
        }
    if ( ! empty ($_GET[ 'courier_id' ])) {
        $countStmt->bindValue ( ':courier_id', $_GET[ 'courier_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'pickup_point_id' ])) {
        $countStmt->bindValue ( ':pickup_point_id', $_GET[ 'pickup_point_id' ], PDO::PARAM_INT );
        }
    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ])) {
        $countStmt->bindValue ( ':from_date', $_GET[ 'from_date' ], PDO::PARAM_STR );
        $countStmt->bindValue ( ':to_date', $_GET[ 'to_date' ], PDO::PARAM_STR );
        }
    foreach ($clientNamedParams as $key => $val) {
        $countStmt->bindValue ( $key, $val, PDO::PARAM_INT );
        }
    $countStmt->execute ();
    $totalRecords = $countStmt->fetchColumn ();

    // Stat card counts (scoped to same filters, no pagination)
    $statBase = "SELECT b.last_status FROM tbl_bookings b
                 LEFT JOIN tbl_branch br ON b.branch_id = br.id
                 WHERE 1=1" . $clientBranchWhere . $clientClientWhere;
    if ( ! empty ($searchValue))
        $statBase .= " AND (b.booking_ref_id LIKE :search OR b.waybill_no LIKE :search OR b.consignee_name LIKE :search OR b.shipper_name LIKE :search OR CAST(b.auto_order_no AS CHAR) LIKE :search)";
    if ( ! empty ($_GET[ 'company_id' ]))
        $statBase .= " AND br.company_id = :company_id";
    if ( ! empty ($_GET[ 'branch_id' ]))
        $statBase .= " AND b.branch_id = :branch_id";
    if ( ! empty ($_GET[ 'client_id' ]))
        $statBase .= " AND b.client_id = :client_id";
    if ( ! empty ($_GET[ 'courier_id' ]))
        $statBase .= " AND b.courier_id = :courier_id";
    if ( ! empty ($_GET[ 'pickup_point_id' ]))
        $statBase .= " AND b.pickup_point_id = :pickup_point_id";
    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ]))
        $statBase .= " AND DATE(b.created_at) BETWEEN :from_date AND :to_date";
    // Note: no status filter here — we always want all-status counts

    $statStmt = $pdo->prepare ( $statBase );
    if ( ! empty ($searchValue))
        $statStmt->bindValue ( ':search', "%$searchValue%", PDO::PARAM_STR );
    if ( ! empty ($_GET[ 'company_id' ]))
        $statStmt->bindValue ( ':company_id', $_GET[ 'company_id' ], PDO::PARAM_INT );
    if ( ! empty ($_GET[ 'branch_id' ]))
        $statStmt->bindValue ( ':branch_id', $_GET[ 'branch_id' ], PDO::PARAM_INT );
    if ( ! empty ($_GET[ 'client_id' ]))
        $statStmt->bindValue ( ':client_id', $_GET[ 'client_id' ], PDO::PARAM_INT );
    if ( ! empty ($_GET[ 'courier_id' ]))
        $statStmt->bindValue ( ':courier_id', $_GET[ 'courier_id' ], PDO::PARAM_INT );
    if ( ! empty ($_GET[ 'pickup_point_id' ]))
        $statStmt->bindValue ( ':pickup_point_id', $_GET[ 'pickup_point_id' ], PDO::PARAM_INT );
    if ( ! empty ($_GET[ 'from_date' ]) && ! empty ($_GET[ 'to_date' ])) {
        $statStmt->bindValue ( ':from_date', $_GET[ 'from_date' ], PDO::PARAM_STR );
        $statStmt->bindValue ( ':to_date', $_GET[ 'to_date' ], PDO::PARAM_STR );
        }
    foreach ($clientNamedParams as $key => $val) {
        $statStmt->bindValue ( $key, $val, PDO::PARAM_INT );
        }
    $statStmt->execute ();
    $allStatuses = $statStmt->fetchAll ( PDO::FETCH_COLUMN );

    $stats = [ 'total' => count ( $allStatuses ), 'created' => 0, 'transit' => 0, 'delivered' => 0, 'rto' => 0, 'ofd' => 0 ];
    foreach ($allStatuses as $s) {
        $sl = strtolower ( $s ?? '' );
        if (str_contains ( $sl, 'out for delivery' )) {
            $stats[ 'ofd' ]++;
            } elseif (str_contains ( $sl, 'created' )) {
            $stats[ 'created' ]++;
            } elseif (str_contains ( $sl, 'deliver' )) {
            $stats[ 'delivered' ]++;
            } elseif (str_contains ( $sl, 'rto' ) || str_contains ( $sl, 'return' )) {
            $stats[ 'rto' ]++;
            } elseif (
            str_contains ( $sl, 'transit' ) || str_contains ( $sl, 'pickup' ) ||
            str_contains ( $sl, 'dispatch' ) || str_contains ( $sl, 'out for' ) ||
            str_contains ( $sl, 'manifest' ) || str_contains ( $sl, 'booked' ) ||
            str_contains ( $sl, 'in-transit' )
        ) {
            $stats[ 'transit' ]++;
            }
        }

    echo json_encode ( [
        'draw' => intval ( $_GET[ 'draw' ] ?? 1 ),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
        'stats' => $stats,
        'status' => 'success'
    ] );

    }
catch ( Exception $e ) {
    echo json_encode ( [ 'status' => 'error', 'message' => $e->getMessage () ] );
    }
?>

