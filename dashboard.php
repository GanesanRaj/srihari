<?php
require_once 'config/db.php';
require_once 'config/middleware.php';

include 'header.php';
?>

<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="row align-items-center mb-4">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4 class="fs-20 fw-bold m-0 text-dark">Shipment Intelligence Dashboard</h4>
                            <div id="dashboard-range"
                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-2 cursor-pointer shadow-sm bg-white">
                                <i class="ti ti-calendar fs-18 text-primary"></i>
                                <span class="fw-semibold text-dark"></span>
                                <i class="ti ti-chevron-down fs-14 text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 1: Shipment Status Breakdown -->
                <div class="mb-2 mt-4">
                    <h5 class="fs-16 fw-bold text-dark mb-3"><i class="ti ti-chart-bar me-1"></i> Shipment Status
                        Breakdown</h5>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0" data-status="">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-package fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">Total</p>
                                                <span class="badge bg-primary-subtle text-primary fs-10"
                                                    id="remark-total">+0 Today</span>
                                            </div>
                                            <h3 id="total-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0" data-status="Created">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-clock fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">Booked</p>
                                                <span class="badge bg-warning-subtle text-warning fs-10"
                                                    id="remark-booked">+0 Today</span>
                                            </div>
                                            <h3 id="pending-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0" data-status="Manifested">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-truck fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">In Transit</p>
                                                <span class="badge bg-info-subtle text-info fs-10"
                                                    id="remark-transit">+0 Today</span>
                                            </div>
                                            <h3 id="transit-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0"
                                data-status="Out For Delivery">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-map-pin fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">OFD</p>
                                                <span class="badge bg-primary-subtle text-primary fs-10"
                                                    id="remark-ofd">+0 Today</span>
                                            </div>
                                            <h3 id="ofd-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0" data-status="Delivered">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-circle-check fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">Delivered</p>
                                                <span class="badge bg-success-subtle text-success fs-10"
                                                    id="remark-delivered">+0 Today</span>
                                            </div>
                                            <h3 id="delivered-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border stat-card cursor-pointer mb-0" data-status="RTO">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-danger-subtle text-danger rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-arrow-back-up fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="text-muted fw-medium fs-13 mb-1">Total RTO</p>
                                                <span class="badge bg-danger-subtle text-danger fs-10"
                                                    id="remark-rto">+0 Today</span>
                                            </div>
                                            <h3 id="rto-shipments" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border ndr-card cursor-pointer mb-0"
                                onclick="location.href='ndr-shipments.php'">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-danger-subtle text-danger rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-alert-triangle fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">NDR Actions</p>
                                            <h3 id="ndr-count" class="my-0 text-danger">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Pickup Management -->
                <div class="mb-2 mt-4">
                    <h5 class="fs-16 fw-bold text-dark mb-3"><i class="ti ti-truck-delivery me-1"></i> Pickup Management
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-truck-delivery fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-2 fs-14">Today's Pickups Breakdown</h5>
                                            <div class="row g-0 text-center">
                                                <div class="col-4 border-end">
                                                    <p class="text-muted fs-11 mb-0 text-uppercase fw-bold">Total</p>
                                                    <h3 id="today-pickups" class="my-0">0</h3>
                                                </div>
                                                <div class="col-4 border-end">
                                                    <p class="text-success fs-11 mb-0 text-uppercase fw-bold">Picked</p>
                                                    <h3 id="today-picked" class="my-0 text-success">0</h3>
                                                </div>
                                                <div class="col-4">
                                                    <p class="text-warning fs-11 mb-0 text-uppercase fw-bold">Pending
                                                    </p>
                                                    <h3 id="today-pending" class="my-0 text-warning">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0 h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center h-100">
                                        <div
                                            class="avatar-md bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-calendar-event fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Upcoming Pickups</p>
                                            <h3 id="upcoming-pickups" class="my-0">0</h3>
                                            <p class="text-muted fs-11 mb-0">Next 3 days</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Attendance Overview -->
                <div class="mb-4 mt-4">
                    <h5 class="fs-16 fw-bold text-dark mb-3"><i class="ti ti-users me-1"></i> Attendance Overview</h5>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-check fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Present Today</p>
                                            <h3 id="att-present" class="my-0 text-success">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-danger-subtle text-danger rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-x fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Absent Today</p>
                                            <h3 id="att-absent" class="my-0 text-danger">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-minus fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Half Day</p>
                                            <h3 id="att-half-day" class="my-0 text-warning">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-plane-departure fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">On Leave</p>
                                            <h3 id="att-leave" class="my-0 text-info">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: System Snapshot -->
                <div class="mb-4 mt-4">
                    <h5 class="fs-16 fw-bold text-dark mb-3"><i class="ti ti-database me-1"></i> System Snapshot</h5>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-currency-rupee fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">COD Value</p>
                                            <h3 id="cod-total" class="my-0">₹0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-secondary-subtle text-secondary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-building fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Active Branches</p>
                                            <h3 id="active-branches" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-dark-subtle text-dark rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-building-community fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Companies</p>
                                            <h3 id="active-companies" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card shadow-none border mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="avatar-md bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-users fs-24"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium fs-13 mb-1">Employees</p>
                                            <h3 id="active-employees" class="my-0">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Shipment Timeline Chart (Bar) -->
                    <div class="col-md-8">
                        <div class="card pt-2">
                            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                                <h4 class="card-title mb-0">Daily Shipments (This Month)</h4>
                            </div>
                            <div class="card-body">
                                <div id="shipment-timeline" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Clients -->
                    <div class="col-md-4">
                        <div class="card pt-2">
                            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                                <h4 class="card-title mb-0">Top 5 Clients</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-nowrap align-middle mb-0">
                                        <tbody id="top-clients-list">
                                            <tr>
                                                <td class="text-center py-4 text-muted">Loading clients...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Upload & Other Widgets -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card pt-2">
                            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                                <h4 class="card-title mb-0">Recent Bulk Uploads</h4>
                                <a href="shipment-bulk.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle mb-0"
                                        style="font-size: 12px;">
                                        <thead>
                                            <tr>
                                                <th class="ps-3 text-muted">Date</th>
                                                <th class="text-muted">Filename</th>
                                                <th class="text-center text-muted">Total</th>
                                                <th class="text-center text-muted text-success">Pass</th>
                                                <th class="text-center text-muted text-danger">Fail</th>
                                                <th class="text-center text-muted">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bulk-upload-list">
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Loading
                                                    uploads...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution & Calendar View -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card pt-2">
                            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                                <h4 class="card-title mb-0">Status Distribution</h4>
                            </div>
                            <div class="card-body">
                                <div id="status-distribution" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-transparent">
                                <h4 class="card-title mb-0">Shipment Calendar</h4>
                            </div>
                            <div class="card-body">
                                <div id="shipment-calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php require_once 'footer.php'; ?>

        <!-- Vendors JS -->
        <script src="assets/plugins/jquery/jquery.min.js"></script>
        <script src="assets/plugins/daterangepicker/moment.min.js"></script>
        <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>
        <script src="assets/plugins/apexcharts/apexcharts.min.js"></script>
        <script src="assets/plugins/fullcalendar/index.global.min.js"></script>

        <script>
            $(document).ready(function () {
                let timelineChart, distributionChart, calendar;
                let currentStartDate = moment().startOf('month');

                let start = moment().startOf('month');
                let end = moment().endOf('month');

                function cb(start, end) {
                    $('#dashboard-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                    currentStartDate = start;
                    loadDashboardData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
                }

                $('#dashboard-range').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                }, cb);

                function loadDashboardData(from, to) {
                    $.get('api/dashboard/read.php', { from: from, to: to }, function (d) {
                        if (d.status === 'success') {
                            $('#total-shipments').text(d.data.total_shipments);

                            // Update Status Stats - Precise matching for Steve's data
                            let delivered = 0, transit = 0, pending = 0, ofd = 0, rto = 0;
                            if (d.data.status_counts) {
                                d.data.status_counts.forEach(s => {
                                    let status = (s.last_status || '').toLowerCase().trim();
                                    // Mapping: Created -> Booked, Manifested -> Transit, Out For Delivery -> OFD
                                    if (status.includes('delivered')) delivered += parseInt(s.count);
                                    else if (status.includes('out for delivery') || status === 'ofd' || status.includes('delivery')) ofd += parseInt(s.count);
                                    else if (status.includes('transit') || status.includes('manifest') || status.includes('shipped')) transit += parseInt(s.count);
                                    else if (status.includes('pending') || status.includes('booked') || status.includes('created')) pending += parseInt(s.count);
                                    else if (status.includes('rto') || status.includes('return') || status.includes('failed')) rto += parseInt(s.count);
                                    else pending += parseInt(s.count); // Fallback to pending for unknown
                                });
                            }
                            $('#delivered-shipments').text(delivered);
                            $('#transit-shipments').text(transit);
                            $('#pending-shipments').text(pending);
                            $('#ofd-shipments').text(ofd);
                            $('#rto-shipments').text(rto);

                            // Today Remarks
                            if (d.data.today_status_counts) {
                                let t_del = 0, t_trans = 0, t_pend = 0, t_ofd = 0, t_rto = 0;
                                let total_today = 0;
                                Object.keys(d.data.today_status_counts).forEach(key => {
                                    let status = key.toLowerCase().trim();
                                    let count = parseInt(d.data.today_status_counts[key]);
                                    total_today += count;
                                    if (status.includes('delivered')) t_del += count;
                                    else if (status.includes('out for delivery') || status === 'ofd' || status.includes('delivery')) t_ofd += count;
                                    else if (status.includes('transit') || status.includes('manifest') || status.includes('shipped')) t_trans += count;
                                    else if (status.includes('pending') || status.includes('booked') || status.includes('created')) t_pend += count;
                                    else if (status.includes('rto') || status.includes('return') || status.includes('failed')) t_rto += count;
                                });
                                $('#remark-total').text('+' + total_today + ' Today');
                                $('#remark-booked').text('+' + t_pend + ' Today');
                                $('#remark-transit').text('+' + t_trans + ' Today');
                                $('#remark-ofd').text('+' + t_ofd + ' Today');
                                $('#remark-delivered').text('+' + t_del + ' Today');
                                $('#remark-rto').text('+' + t_rto + ' Today');
                            }

                            // New Metrics
                            $('#cod-total').text('₹' + parseFloat(d.data.cod_total).toLocaleString('en-IN'));
                            $('#active-branches').text(d.data.active_branches);
                            $('#active-companies').text(d.data.active_companies);
                            $('#active-employees').text(d.data.active_employees);
                            $('#today-pickups').text(d.data.today_pickups || 0);
                            $('#today-picked').text(d.data.today_picked || 0);
                            $('#today-pending').text(d.data.today_pending || 0);
                            $('#upcoming-pickups').text(d.data.upcoming_pickups || 0);
                            $('#ndr-count').text(d.data.ndr_count || 0);

                            // Top Clients
                            let clientHtml = '';
                            if (d.data.top_clients && d.data.top_clients.length > 0) {
                                d.data.top_clients.forEach(c => {
                                    clientHtml += `<tr>
                                        <td class="ps-3"><h6 class="fs-13 mb-0">${c.company_name}</h6></td>
                                        <td class="text-end pe-3"><span class="badge bg-primary-subtle text-primary">${c.count} Shipments</span></td>
                                    </tr>`;
                                });
                            } else {
                                clientHtml = '<tr><td colspan="2" class="text-center py-3 text-muted">No client data found</td></tr>';
                            }
                            $('#top-clients-list').html(clientHtml);

                            // Bulk Upload History
                            let bulkHtml = '';
                            if (d.data.recent_bulk_jobs && d.data.recent_bulk_jobs.length > 0) {
                                d.data.recent_bulk_jobs.forEach(j => {
                                    let statusCls = j.status === 'Completed' ? 'bg-success' : 'bg-warning text-dark';
                                    bulkHtml += `<tr>
                                        <td class="ps-3">${moment(j.created_at).format('MMM D, HH:mm')}</td>
                                        <td><span class="text-truncate d-inline-block" style="max-width: 150px;">${j.filename}</span></td>
                                        <td class="text-center fw-medium">${j.total_records}</td>
                                        <td class="text-center text-success fw-bold">${j.success_count}</td>
                                        <td class="text-center text-danger fw-bold">${j.failure_count}</td>
                                        <td class="text-center"><span class="badge ${statusCls} fs-10">${j.status}</span></td>
                                    </tr>`;
                                });
                            } else {
                                bulkHtml = '<tr><td colspan="6" class="text-center py-3 text-muted">No recent uploads</td></tr>';
                            }
                            $('#bulk-upload-list').html(bulkHtml);

                            // Attendance Summary
                            if (d.data.attendance_summary) {
                                $('#att-present').text(d.data.attendance_summary.present || 0);
                                $('#att-absent').text(d.data.attendance_summary.absent || 0);
                                $('#att-half-day').text(d.data.attendance_summary.half_day || 0);
                                $('#att-leave').text(d.data.attendance_summary.leave || 0);
                            }

                            updateTimeline(d.data.daily_stats);
                            updateDistribution(d.data.status_counts);
                            updateCalendar(d.data.calendar_events);
                        }
                    });
                }

                function updateTimeline(stats) {
                    if (!stats) return;
                    const options = {
                        series: [{
                            name: 'Shipments',
                            data: stats.map(s => s.count)
                        }],
                        chart: {
                            height: 300,
                            type: 'bar',
                            toolbar: { show: false }
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                columnWidth: '50%',
                                dataLabels: { position: 'top' }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            offsetY: -20,
                            style: { fontSize: '12px', colors: ["#304758"] }
                        },
                        stroke: { show: true, width: 2, colors: ['transparent'] },
                        xaxis: {
                            categories: stats.map(s => moment(s.date).format('MMM D')),
                        },
                        colors: ['#3e60d5'],
                        fill: { opacity: 1 }
                    };

                    if (timelineChart) timelineChart.destroy();
                    timelineChart = new ApexCharts(document.querySelector("#shipment-timeline"), options);
                    timelineChart.render();
                }

                function updateDistribution(counts) {
                    if (!counts) return;
                    const options = {
                        series: counts.map(s => parseInt(s.count)) || [0],
                        chart: {
                            type: 'donut',
                            height: 350
                        },
                        labels: counts.map(s => s.last_status) || ['No Data'],
                        legend: { position: 'bottom' },
                        colors: ['#3e60d5', '#47ad59', '#16a7e9', '#fcc100', '#f1536e']
                    };

                    if (distributionChart) distributionChart.destroy();
                    distributionChart = new ApexCharts(document.querySelector("#status-distribution"), options);
                    distributionChart.render();
                }

                function updateCalendar(events) {
                    const calendarEl = document.getElementById('shipment-calendar');
                    if (calendar) calendar.destroy();

                    calendar = new FullCalendar.Calendar(calendarEl, {
                        height: 450,
                        initialView: 'dayGridMonth',
                        initialDate: currentStartDate.toDate(),
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        events: events || [],
                        themeSystem: 'bootstrap5'
                    });
                    calendar.render();
                }

                cb(start, end);

                // Click handler for stat cards
                $('.stat-card').on('click', function () {
                    const status = $(this).data('status');
                    const from = start.format('YYYY-MM-DD');
                    const to = end.format('YYYY-MM-DD');
                    window.location.href = `shipment-list.php?status=${encodeURIComponent(status)}&from=${from}&to=${to}`;
                });
            });
        </script>
    </div>
</body>

</html>