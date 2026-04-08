<!-- Horizontal Menu Start -->
<?php
if ( ! defined( 'MIDDLEWARE_INCLUDED' ) ) {
    require_once __DIR__ . '/config/middleware.php';
}
// Show nav item/link if user has any of view, add, edit, delete for that permission (or is super admin)
$nav_can = function( $prefixes ) {
    global $role_id;
    $userId = (int) ( $_SESSION['user_id'] ?? 0 );
    if ( $userId === 1 ) return true;   // user id 1 = super admin, show all menu
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
                                        $booking_prefixes = [ 'shipment-create', 'shipment-bulk', 'shipment-list', 'shipment-status-update', 'tracking', 'rate-calculator', 'ndr-shipments', 'ndr_status', 'shipment', 'booking', 'pickuppoint', 'pickup_request' ];
                                        $booking_can = $nav_can( $booking_prefixes );
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
                                                        <?php if ( $nav_can( 'shipment-create' ) ) : ?><a href="shipment-create.php" class="dropdown-item"><i data-lucide="package-plus" style="width:14px;height:14px;" class="me-2"></i>Create Shipment</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-bulk' ) ) : ?><a href="delhivery-b2c-bulk-upload.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-list' ) ) : ?><a href="shiprocke-lists.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>Booking List</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-status-update' ) ) : ?><a href="shipment-status-update.php" class="dropdown-item"><i data-lucide="activity" style="width:14px;height:14px;" class="me-2"></i>Update Shipment Status</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'tracking' ) ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'rate-calculator' ) ) : ?><a href="rate-calculator.php" class="dropdown-item"><i data-lucide="calculator" style="width:14px;height:14px;" class="me-2"></i>Rate Calculator</a><?php endif; ?>
                                                        <?php if ( $nav_can( [ 'ndr-shipments', 'ndr_status' ] ) ) : ?><a href="ndr-shipments.php" class="dropdown-item"><i data-lucide="alert-triangle" style="width:14px;height:14px;" class="me-2"></i>NDR Status</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickuppoint' ) ) : ?><a href="pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Point</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickup_request' ) ) : ?><a href="pickup-request-list.php" class="dropdown-item"><i data-lucide="truck" style="width:14px;height:14px;" class="me-2"></i>Pickup Request</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Delhivery Menu -->
                                        <?php
                                        $delhivery_prefixes = [ 'shipment-create', 'shipment-bulk', 'shipment-list', 'tracking', 'pickuppoint', 'pickup_request', 'ndr-shipments', 'ndr_status' ];
                                        $delhivery_can = $nav_can( $delhivery_prefixes );
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
                                                        <?php if ( $nav_can( 'shipment-create' ) ) : ?><a href="delhivery-b2c-shipment-create.php" class="dropdown-item"><i data-lucide="zap" style="width:14px;height:14px;" class="me-2"></i>B2C Booking</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-list' ) ) : ?><a href="delhivery-b2c-shipment-list.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>B2C List</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-bulk' ) ) : ?><a href="shipment-bulk.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'tracking' ) ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickuppoint' ) ) : ?><a href="delhivery-b2c-pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Point</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickup_request' ) ) : ?><a href="delhivery-b2c-pickup-request-list.php" class="dropdown-item"><i data-lucide="clipboard-list" style="width:14px;height:14px;" class="me-2"></i>Pickup Request</a><?php endif; ?>
                                                        <?php if ( $nav_can( [ 'ndr-shipments', 'ndr_status' ] ) ) : ?><a href="delhivery-b2c-ndr-shipments.php" class="dropdown-item"><i data-lucide="alert-triangle" style="width:14px;height:14px;" class="me-2"></i>NDR Status</a><?php endif; ?>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Shiprocket Menu -->
                                        <?php
                                        $shiprocket_prefixes = [ 'shipment-create', 'shipment-bulk', 'shipment-list', 'tracking', 'pickuppoint', 'pickup_request', 'ndr-shipments', 'ndr_status' ];
                                        $shiprocket_can = $nav_can( $shiprocket_prefixes );
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
                                                        <?php if ( $nav_can( 'shipment-list' ) ) : ?><a href="shiprocke-lists.php" class="dropdown-item"><i data-lucide="list" style="width:14px;height:14px;" class="me-2"></i>Booking List</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-bulk' ) ) : ?><a href="shiprocket-bulk-upload.php" class="dropdown-item"><i data-lucide="upload" style="width:14px;height:14px;" class="me-2"></i>Bulk Upload</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'shipment-list' ) ) : ?><a href="shiprocket-manifest-list.php" class="dropdown-item"><i data-lucide="file-text" style="width:14px;height:14px;" class="me-2"></i>Manifest List</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'pickuppoint' ) ) : ?><a href="pickuppoint-list.php" class="dropdown-item"><i data-lucide="map-pin" style="width:14px;height:14px;" class="me-2"></i>Pickup Points</a><?php endif; ?>
                                                        <?php if ( $nav_can( 'tracking' ) ) : ?><a href="tracking.php" class="dropdown-item"><i data-lucide="navigation" style="width:14px;height:14px;" class="me-2"></i>Tracking</a><?php endif; ?>
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
                                                                        <a href="employee-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="user"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Employee</a>
                                                                        <a href="department-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="layers"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Departments</a>
                                                                        <a href="designation-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="award"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Designation</a>
                                                                </div>
                                                                <!-- HR Management Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 220px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                HR Management</h6>
                                                                        <a href="salary-template-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="file-text"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Salary
                                                                                Templates</a>
                                                                        <a href="employee-salary-assign.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="user-plus"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Salary
                                                                                Assignment</a>
                                                                        <a href="shift-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="clock"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Shift
                                                                                Management</a>
                                                                        <a href="attendance-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="calendar-check"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Attendance
                                                                                Tracking</a>
                                                                        <a href="payroll-list.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="banknote"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Payroll
                                                                                Processing</a>
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
                                        <?php if ( $nav_can( [ 'setting-role', 'shipment', 'support' ] ) ) : ?>
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
                                                                        <a href="mis-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="bar-chart-2"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>MIS Reports</a>
                                                                        <a href="account-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="trending-up"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Account
                                                                                Reports</a>
                                                                        <a href="status-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="pie-chart"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Status
                                                                                Reports</a>
                                                                        <a href="shipment-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="package"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Shipment
                                                                                Reports</a>
                                                                        <a href="attendance-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="calendar"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Attendance
                                                                                Reports</a>
                                                                        <a href="payroll-reports.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="dollar-sign"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Payroll
                                                                                Reports</a>
                                                                        <div class="dropdown-divider my-1"></div>
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Access</h6>
                                                                        <a href="roles.php" class="dropdown-item"><i
                                                                                        data-lucide="shield"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Roles</a>
                                                                </div>
                                                                <!-- WhatsApp Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                WhatsApp</h6>
                                                                        <a href="whatsapp-settings.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="message-circle"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Settings</a>
                                                                        <a href="whatsapp-template.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="layout-template"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Template</a>
                                                                        <a href="whatsapp-logs.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="scroll"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Logs</a>
                                                                </div>
                                                                <!-- Mail Column -->
                                                                <div class="py-2 px-1 border-start"
                                                                        style="min-width: 200px;">
                                                                        <h6 class="dropdown-header text-uppercase fw-bold"
                                                                                style="font-size:10px; letter-spacing:.8px;">
                                                                                Mail</h6>
                                                                        <a href="mail-settings.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="mail"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Settings</a>
                                                                        <a href="mail-template.php"
                                                                                class="dropdown-item"><i
                                                                                        data-lucide="mail-plus"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Template</a>
                                                                        <a href="mail-logs.php" class="dropdown-item"><i
                                                                                        data-lucide="mail-open"
                                                                                        style="width:14px;height:14px;"
                                                                                        class="me-2"></i>Logs</a>
                                                                </div>
                                                        </div>
                                                </div>
                                        </li>
                                        <?php endif; ?>

                                        <!-- Settings -->
                                        <?php if ( $nav_can( [ 'courier_partner' ] ) ) : ?>
                                        <li class="nav-item dropdown">
                                                <a class="nav-link dropdown-toggle drop-arrow-none" href="#"
                                                        id="topnav-settings" role="button" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <span class="menu-icon"><i data-lucide="settings"></i></span>
                                                        <span class="menu-text">Settings</span>
                                                        <div class="menu-arrow"></div>
                                                </a>
                                                <div class="dropdown-menu" aria-labelledby="topnav-settings">
                                                        <a href="apis.php" class="dropdown-item"><i data-lucide="plug"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>APIs</a>
                                                        <a href="couriers.php" class="dropdown-item"><i
                                                                        data-lucide="truck"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>Couriers</a>
                                                        <a href="courier-partner-list.php" class="dropdown-item"><i
                                                                        data-lucide="link"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>Courier Partner Setup</a>
                                                        <a href="about.php" class="dropdown-item"><i data-lucide="info"
                                                                        style="width:14px;height:14px;"
                                                                        class="me-2"></i>About</a>
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