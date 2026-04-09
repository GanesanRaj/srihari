<!-- Horizontal Menu Start -->
<?php
if ( ! defined( 'MIDDLEWARE_INCLUDED' ) ) {
    require_once __DIR__ . '/config/middleware.php';
}
// #region agent log
if (function_exists('_agent_debug_log')) {
    _agent_debug_log([
        'hypothesisId' => 'H8',
        'location' => 'horizontal-menu.php:entry',
        'message' => 'Horizontal menu rendered',
        'data' => [
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'session_role_id' => (int) ($_SESSION['role_id'] ?? 0),
            'session_user_id' => (int) ($_SESSION['user_id'] ?? 0)
        ]
    ]);
}
// #endregion
// Show nav item/link if user has any of view, add, edit, delete for that permission (or is super admin)
$nav_can = function( $prefixes ) {
    global $role_id;
    if ( (int) $role_id === 1 ) return true;   // role id 1 = admin, show all menu
    if ( ! is_array( $prefixes ) ) $prefixes = [ $prefixes ];
    foreach ( $prefixes as $p ) {
        if ( can_access_any( $p ) ) return true;
    }
    return false;
};
?>
<style>
        @media (min-width: 992px) {
                .topnav .navbar-nav .nav-item:first-child .nav-link {
                        padding-left: 0 !important;
                }

                .topnav .navbar-nav .nav-item:last-child .nav-link {
                        padding-right: 0 !important;
                }
        }
</style>
<header class="topnav">

        <nav class="navbar navbar-expand-lg">
                <div class="px-0 w-100">
                        <div class="collapse navbar-collapse" id="topnav-menu-content">
                                <ul class="navbar-nav">
                                        <!-- Dashboard -->
                                        <li class="nav-item">
                                                <a class="nav-link" href="index.php">
                                                        <img src="assets/images/logo-black.png" alt="logo"
                                                                style="width: 41px;">
                                                </a>
                                        </li>

                                        
                                        <!-- Master Data Module -->
                                        <?php if ( $nav_can( [ 'branch', 'company', 'company_list', 'client', 'consignor', 'consignee', 'pickuppoint', 'status', 'coloader' ] ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-master" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="database"></i></span>
                                                        <span class="menu-text">Master Data</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-master">
                                                        <?php if ( $nav_can( [ 'company', 'company_list' ] ) ) : ?><a href="company-list.php" class="dropdown-item"><i data-lucide="building" style="width:14px;height:14px;" class="me-2"></i>Company</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'branch' ) ) : ?><a href="branch-list.php" class="dropdown-item"><i data-lucide="git-branch" style="width:14px;height:14px;" class="me-2"></i>Branch</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'status' ) ) : ?><a href="master-status-list.php" class="dropdown-item"><i data-lucide="flag" style="width:14px;height:14px;" class="me-2"></i>Status Description</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'custom_description' ) ) : ?><a href="custom-description-list.php" class="dropdown-item"><i data-lucide="pencil" style="width:14px;height:14px;" class="me-2"></i>Custom Description</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'client' ) ) : ?><a href="client-list.php" class="dropdown-item"><i data-lucide="briefcase" style="width:14px;height:14px;" class="me-2"></i>Client</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'consignor' ) ) : ?><a href="consignor-list.php" class="dropdown-item"><i data-lucide="user-check" style="width:14px;height:14px;" class="me-2"></i>Consignor</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'consignee' ) ) : ?><a href="consignee-list.php" class="dropdown-item"><i data-lucide="user" style="width:14px;height:14px;" class="me-2"></i>Consignee</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickuppoint' ) ) : ?><a href="pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Points</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'coloader' ) ) : ?><a href="coloader-list.php" class="dropdown-item"><i data-lucide="users" style="width:14px;height:14px;" class="me-2"></i>Coloader</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                                <!-- Booking: show dropdown if user has view on any booking permission; each link only if that permission is enabled -->
                                        <?php
                                        $booking_can_create = $nav_can( 'shipment-create' );
                                        $booking_can_bulk = $nav_can( 'shipment-bulk' );
                                        $booking_can_list = $nav_can( 'shipment-list' );
                                        $booking_can_status = $nav_can( 'shipment-status-update' );
                                        $booking_can_tracking = $nav_can( 'tracking' );
                                        $booking_can_rate = $nav_can( 'rate-calculator' );
                                        $booking_can_ndr = $nav_can( [ 'ndr-shipments', 'ndr_status' ] );
                                        $booking_can_pickuppoint = $nav_can( 'booking-pickuppoint' );
                                        $booking_can_pickup_request = $nav_can( 'pickup_request' );
                                        $booking_can = $booking_can_create || $booking_can_bulk || $booking_can_list || $booking_can_status || $booking_can_tracking || $booking_can_rate || $booking_can_ndr || $booking_can_pickuppoint || $booking_can_pickup_request;
                                        if ( $booking_can ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-booking" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="package"></i></span>
                                                        <span class="menu-text">Booking</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-booking">
                                                        <?php if ( $booking_can_create ) : ?><a href="shipment-create.php" class="dropdown-item"><i data-lucide="package-plus" style="width:14px;height:14px;" class="me-2"></i>Create Shipment</a><?php endif; ?>
                                                        <?php if ( $booking_can_bulk ) : ?><a href="delhivery-b2c-bulk-upload.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $booking_can_list ) : ?><a href="shiprocke-lists.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>Booking List</a><?php endif; ?>
                                                        <?php if ( $booking_can_status ) : ?><a href="shipment-status-update.php" class="dropdown-item"><i data-lucide="activity" style="width:14px;height:14px;" class="me-2"></i>Update Shipment Status</a><?php endif; ?>
                                                        <?php if ( $booking_can_tracking ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                        <?php if ( $booking_can_rate ) : ?><a href="rate-calculator.php" class="dropdown-item"><i data-lucide="calculator" style="width:14px;height:14px;" class="me-2"></i>Rate Calculator</a><?php endif; ?>
                                                        <?php if ( $booking_can_ndr ) : ?><a href="ndr-shipments.php" class="dropdown-item"><i data-lucide="alert-triangle" style="width:14px;height:14px;" class="me-2"></i>NDR Status</a><?php endif; ?>
                                                        <?php if ( $booking_can_pickuppoint ) : ?><a href="pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Point</a><?php endif; ?>
                                                        <?php if ( $booking_can_pickup_request ) : ?><a href="pickup-request-list.php" class="dropdown-item"><i data-lucide="truck" style="width:14px;height:14px;" class="me-2"></i>Pickup Request</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Delhivery Menu -->
                                        <?php
                                        $delhivery_can_booking = $nav_can( 'delhivery-b2c-booking' );
                                        $delhivery_can_list = $nav_can( 'delhivery-b2c-list' );
                                        $delhivery_can_bulk = $nav_can( 'delhivery-bulk' );
                                        $delhivery_can_pickuppoint = $nav_can( 'delhivery-pickuppoint' );
                                        $delhivery_can_pickup_request = $nav_can( 'delhivery-pickup-request' );
                                        $delhivery_can_ndr = $nav_can( 'delhivery-ndr' );
                                        $delhivery_can_tracking = $nav_can( 'tracking' ) && ( $delhivery_can_booking || $delhivery_can_list || $delhivery_can_bulk || $delhivery_can_pickuppoint || $delhivery_can_pickup_request || $delhivery_can_ndr );
                                        $delhivery_can = $delhivery_can_booking || $delhivery_can_list || $delhivery_can_bulk || $delhivery_can_tracking || $delhivery_can_pickuppoint || $delhivery_can_pickup_request || $delhivery_can_ndr;
                                        if ( $delhivery_can ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-delhivery" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="truck"></i></span>
                                                        <span class="menu-text">Delhivery</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-delhivery">
                                                        <?php if ( $delhivery_can_booking ) : ?><a href="delhivery_create.php" class="dropdown-item"><i data-lucide="zap" style="width:14px;height:14px;" class="me-2"></i>B2C Booking</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_list ) : ?><a href="delhivery-b2c-shipment-list.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>B2C List</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_bulk ) : ?><a href="shipment-bulk.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_tracking ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_pickuppoint ) : ?><a href="delhivery-b2c-pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Point</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_pickup_request ) : ?><a href="delhivery-b2c-pickup-request-list.php" class="dropdown-item"><i data-lucide="clipboard-list" style="width:14px;height:14px;" class="me-2"></i>Pickup Request</a><?php endif; ?>
                                                        <?php if ( $delhivery_can_ndr ) : ?><a href="delhivery-b2c-ndr-shipments.php" class="dropdown-item"><i data-lucide="alert-triangle" style="width:14px;height:14px;" class="me-2"></i>NDR Status</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Shiprocket Menu -->
                                        <?php
                                        $shiprocket_can_list = $nav_can( 'shiprocket-list' );
                                        $shiprocket_can_bulk = $nav_can( 'shiprocket-bulk' );
                                        $shiprocket_can_manifest = $nav_can( 'shiprocket-manifest' );
                                        $shiprocket_can_pickup = $nav_can( 'shiprocket-pickup' );
                                        $shiprocket_can = $shiprocket_can_list || $shiprocket_can_bulk || $shiprocket_can_manifest || $shiprocket_can_pickup;
                                        // #region agent log
                                        if (function_exists ( '_agent_debug_log' )) {
                                                _agent_debug_log ( [
                                                        'hypothesisId' => 'H5',
                                                        'location' => 'horizontal-menu.php:shiprocket_gate',
                                                        'message' => 'Shiprocket menu gate evaluated',
                                                        'data' => [
                                                                'role_id' => (int) ($role_id ?? 0),
                                                                'session_role_id' => (int) ($_SESSION['role_id'] ?? 0),
                                                                'session_user_id' => (int) ($_SESSION['user_id'] ?? 0),
                                                                'shiprocket_can' => (bool) $shiprocket_can
                                                        ]
                                                ] );
                                        }
                                        // #endregion
                                        if ( $shiprocket_can ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-shiprocket" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="rocket"></i></span>
                                                        <span class="menu-text">Shiprocket</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-shiprocket">
                                                        <?php if ( $shiprocket_can_list ) : ?><a href="shiprocket_create.php" class="dropdown-item"><i data-lucide="package-plus" style="width:14px;height:14px;" class="me-2"></i>Create Shipment</a><?php endif; ?>
                                                        <?php if ( $shiprocket_can_list ) : ?><a href="shiprocke-lists.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>Booking List</a><?php endif; ?>
                                                        <?php if ( $shiprocket_can_bulk ) : ?><a href="shiprocket-bulk-upload.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $shiprocket_can_manifest ) : ?><a href="shiprocket-manifest-list.php" class="dropdown-item"><i data-lucide="file-text" style="width:14px;height:14px;" class="me-2"></i>Manifest List</a><?php endif; ?>
                                                        <?php if ( $shiprocket_can_pickup ) : ?><a href="pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Points</a><?php endif; ?>
                                                        <?php if ( $shiprocket_can_list ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>


                                        <!-- SHA & WHMS: show dropdown if any SHA/WHMS permission; each link only if that permission enabled -->
                                        <?php
                                        $sha_whms_prefixes = [ 'whms_booking', 'whms_shipment', 'whms_pickup', 'whms_tag', 'whms_manifest', 'whms_runsheet', 'whms_pod', 'whms_tracking' ];
                                        $sha_whms_can = $nav_can( $sha_whms_prefixes );
                                        if ( $sha_whms_can ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-ownbooking" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="truck"></i></span>
                                                        <span class="menu-text">SHA & WHMS</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu p-0" aria-labelledby="topnav-ownbooking"
                                                        style="min-width: 580px;">
                                                        <div class="d-flex">
                                                                <!-- Booking Column -->
                                                                <div class="py-2 px-1" style="min-width: 190px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Booking</h6>
                                                                        <?php if ( $nav_can( 'whms_booking' ) ) : ?><a href="whms-ownbooking-create.php" class="dropdown-item"><i data-lucide="package-plus" style="width:14px;height:14px;" class="me-2"></i>SHA Booking</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_shipment' ) ) : ?><a href="whms-shipment-bulk.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>SHA Bulk Upload</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_shipment' ) ) : ?><a href="whms-shipment-list.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>SHA List</a><?php endif; ?>
                                                                </div>
                                                                <!-- WHMS Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                WHMS</h6>
                                                                        <?php if ( $nav_can( 'whms_pickup' ) ) : ?><a href="whms-pickup-status-update.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>WHMS Pickup</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_tag' ) ) : ?><a href="whms-tag-create.php" class="dropdown-item"><i data-lucide="tag" style="width:14px;height:14px;" class="me-2"></i>New Tag</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_manifest' ) ) : ?><a href="whms-manifest-list.php" class="dropdown-item"><i data-lucide="file-text" style="width:14px;height:14px;" class="me-2"></i>Manifest List</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_manifest' ) ) : ?><a href="whms-manifest-create.php" class="dropdown-item"><i data-lucide="file-plus" style="width:14px;height:14px;" class="me-2"></i>New Manifest</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_tag' ) ) : ?><a href="whms-tag-list.php" class="dropdown-item"><i data-lucide="tag" style="width:14px;height:14px;" class="me-2"></i>Tag List</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_tag' ) ) : ?><a href="whms-tag-verify.php" class="dropdown-item"><i data-lucide="circle-check" style="width:14px;height:14px;" class="me-2"></i>Verify Tag</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_runsheet' ) ) : ?><a href="whms-runsheet-list.php" class="dropdown-item"><i data-lucide="clipboard-list" style="width:14px;height:14px;" class="me-2"></i>Run Sheet</a><?php endif; ?>
                                                                </div>
                                                                <!-- Tracking & POD Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 190px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Tracking & POD</h6>
                                                                        <?php if ( $nav_can( 'whms_tracking' ) ) : ?><a href="shipment-status-update.php" class="dropdown-item"><i data-lucide="activity" style="width:14px;height:14px;" class="me-2"></i>Update Status</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_tracking' ) ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'whms_pod' ) ) : ?><a href="pod-status-update.php" class="dropdown-item"><i data-lucide="file-check" style="width:14px;height:14px;" class="me-2"></i>POD</a><?php endif; ?>
                                                                </div>
                                                        </div>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Tickets: support both prefix 'ticket' and 'tickets' to match permission.prefix in DB -->
                                        <?php if ( $nav_can( [ 'ticket', 'tickets' ] ) ) : ?>
                                        <li class="nav-item">
                                                <a class="nav-link" href="tickets.php">
                                                        <span class="menu-icon"><i data-lucide="ticket"></i></span>
                                                        <span class="menu-text">Tickets</span>
                                                </a>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Serial Allocation -->
                                        <?php if ( $nav_can( 'serial_allocation' ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-serial" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="barcode"></i></span>
                                                        <span class="menu-text">Serial Allocation</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu p-0" aria-labelledby="topnav-serial"
                                                        style="min-width: 420px;">
                                                        <div class="d-flex">
                                                                <!-- Allocation Column -->
                                                                <div class="py-2 px-1" style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Allocation</h6>
                                                                        <a href="serial-allocation-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="list"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>View
                                                                                Allocations</a>
                                                                        <a href="serial-allocation-add.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="plus-circle"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>New
                                                                                Allocation</a>
                                                                </div>
                                                                <!-- Service Types Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 220px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                By Service Type</h6>
                                                                        <a href="serial-allocation-list.php?service_type=express"
                                                                                class="dropdown-item">
                                                                                <i data-lucide="plane"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Air Serials</a>
                                                                        <a href="serial-allocation-list.php?service_type=surface"
                                                                                class="dropdown-item">
                                                                                <i data-lucide="truck"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Surface
                                                                                Serials</a>
                                                                        <div class="dropdown-divider my-1"></div>
                                                                        <a href="serial-allocation-list.php"
                                                                                class="dropdown-item">
                                                                                <i data-lucide="layers"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>All
                                                                                Allocations</a>
                                                                </div>
                                                        </div>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Employee & HR Management (includes Client Based) -->
                                        <?php if ( $nav_can( [ 'employee', 'department', 'designation', 'salary_template', 'client_based_user' ] ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-hr" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="users"></i></span>
                                                        <span class="menu-text">HR & Payroll</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu p-0" aria-labelledby="topnav-hr"
                                                        style="min-width: 620px;">
                                                        <div class="d-flex">
                                                                <!-- Employee Column -->
                                                                <div class="py-2 px-1" style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Employee</h6>
                                                                        <?php if ( $nav_can( 'employee' ) ) : ?><a href="employee-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="user"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Employee</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'department' ) ) : ?><a href="department-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="layers"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Departments</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'designation' ) ) : ?><a href="designation-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="award"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Designation</a>
                                                                        <?php endif; ?>
                                                                </div>
                                                                <!-- HR Management Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 220px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                HR Management</h6>
                                                                        <?php if ( $nav_can( 'salary_template' ) ) : ?><a href="salary-template-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="file-text"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Salary
                                                                                Templates</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'salary_template' ) ) : ?><a href="employee-salary-assign.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="user-plus"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Salary
                                                                                Assignment</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'employee' ) ) : ?><a href="shift-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="clock"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Shift
                                                                                Management</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'employee' ) ) : ?><a href="attendance-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="calendar-check"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Attendance
                                                                                Tracking</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'salary_template' ) ) : ?><a href="payroll-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="banknote"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Payroll
                                                                                Processing</a>
                                                                        <?php endif; ?>
                                                                </div>
                                                                <!-- Client Based Column -->
                                                                <div class="py-2 px-1 border-start" style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Client Based</h6>
                                                                        <?php if ( $nav_can( 'client_based_user' ) ) : ?><a href="client-based-user-list.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>Client List</a><?php endif; ?>
                                                                        <?php if ( $nav_can( 'client_based_user' ) ) : ?><a href="client-based-user-add.php" class="dropdown-item"><i data-lucide="user-plus" style="width:14px;height:14px;" class="me-2"></i>Add User</a><?php endif; ?>
                                                                </div>
                                                        </div>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Reports & Others -->
                                        <?php if ( $nav_can( [ 'setting-role', 'mis-reports', 'account-reports', 'status-reports', 'shipment-reports', 'attendance-reports', 'payroll-reports', 'whatsapp-settings', 'mail-settings' ] ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-more" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="bar-chart-3"></i></span>
                                                        <span class="menu-text">Reports & Tools</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu p-0" aria-labelledby="topnav-more"
                                                        style="min-width: 600px;">
                                                        <div class="d-flex">
                                                                <!-- Reports Column -->
                                                                <div class="py-2 px-1" style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Reports</h6>
                                                                        <?php if ( $nav_can( 'mis-reports' ) ) : ?><a href="mis-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="bar-chart-2"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>MIS Reports</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'account-reports' ) ) : ?><a href="account-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="trending-up"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Account
                                                                                Reports</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'status-reports' ) ) : ?><a href="status-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="pie-chart"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Status
                                                                                Reports</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'shipment-reports' ) ) : ?><a href="shipment-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="package"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Shipment
                                                                                Reports</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'attendance-reports' ) ) : ?><a href="attendance-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="calendar"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Attendance
                                                                                Reports</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'payroll-reports' ) ) : ?><a href="payroll-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="dollar-sign"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Payroll
                                                                                Reports</a>
                                                                        <?php endif; ?>
                                                                        <div class="dropdown-divider my-1"></div>
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Access</h6>
                                                                        <?php if ( $nav_can( 'setting-role' ) ) : ?><a href="roles.php" class="dropdown-item"><i
                                                                                        data-lucide="shield"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Roles</a>
                                                                        <?php endif; ?>
                                                                </div>
                                                                <!-- WhatsApp Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                WhatsApp</h6>
                                                                        <?php if ( $nav_can( 'whatsapp-settings' ) ) : ?><a href="whatsapp-settings.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="message-circle"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Settings</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'whatsapp-settings' ) ) : ?><a href="whatsapp-template.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="layout-template"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Template</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'whatsapp-settings' ) ) : ?><a href="whatsapp-logs.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="scroll"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Logs</a>
                                                                        <?php endif; ?>
                                                                </div>
                                                                <!-- Mail Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Mail</h6>
                                                                        <?php if ( $nav_can( 'mail-settings' ) ) : ?><a href="mail-settings.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="mail"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Settings</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'mail-settings' ) ) : ?><a href="mail-template.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="mail-plus"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Template</a>
                                                                        <?php endif; ?>
                                                                        <?php if ( $nav_can( 'mail-settings' ) ) : ?><a href="mail-logs.php" class="dropdown-item"><i
                                                                                        data-lucide="mail-open"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Logs</a>
                                                                        <?php endif; ?>
                                                                </div>
                                                        </div>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Settings -->
                                        <?php if ( $nav_can( [ 'apis', 'couriers', 'courier_partner', 'about', 'setting-role' ] ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-settings" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="settings"></i></span>
                                                        <span class="menu-text">Settings</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-settings">
                                                        <?php if ( $nav_can( 'apis' ) ) : ?><a href="apis.php" class="dropdown-item"><i data-lucide="plug"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>APIs</a>
                                                        <?php endif; ?>
                                                        <?php if ( $nav_can( 'couriers' ) ) : ?><a href="couriers.php" class="dropdown-item"><i
                                                                        data-lucide="truck"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>Couriers</a>
                                                        <?php endif; ?>
                                                        <?php if ( $nav_can( 'courier_partner' ) ) : ?><a href="courier-partner-list.php" class="dropdown-item"><i
                                                                        data-lucide="link"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>Courier Partner Setup</a>
                                                        <?php endif; ?>
                                                        <?php if ( $nav_can( 'about' ) ) : ?><a href="about.php" class="dropdown-item"><i data-lucide="info"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>About</a>
                                                        <?php endif; ?>
                                                        <?php if ( $nav_can( 'setting-role' ) ) : ?><a href="setting-role.php" class="dropdown-item"><i data-lucide="shield"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>Roles</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        
                                </ul>
                        </div>
                </div>
        </nav>
</header>
<!-- Horizontal Menu End -->