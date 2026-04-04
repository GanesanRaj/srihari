<?php
require_once 'header.php';
require_once 'config/middleware.php';

// require_permission('shipment', 'is_view'); 
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<style>
    /* Ensure the table stays compact for many columns */
    #misTable td,
    #misTable th {
        font-size: 12px;
        padding: 8px 4px;
    }

    .dt-buttons {
        margin-bottom: 15px;
    }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">
                    <!-- Page Title -->
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">MIS Reports</h4>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-soft-primary btn-sm" type="button" data-bs-toggle="collapse"
                                data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                                <i class="ti ti-filter me-1"></i> Toggle Filters
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="collapse mb-3" id="filterCollapse">
                                <div class="card card-body mb-0">
                                    <!-- Filters -->
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <select id="companyFilter" class="form-select form-select-sm">
                                                <option value="">All Companies</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="branchFilter" class="form-select form-select-sm">
                                                <option value="">All Branches</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="courierFilter" class="form-select form-select-sm">
                                                <option value="">All Couriers</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div id="report-range"
                                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-primary w-100" id="refreshBtn">
                                                <i class="ti ti-rotate"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">

                                    <div class="table-responsive">
                                        <table id="misTable" class="table table-hover nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Ref ID</th>
                                                    <th>Waybill</th>
                                                    <th>Courier</th>
                                                    <th>Service</th>
                                                    <th>Product</th>
                                                    <th>Consignee</th>
                                                    <th>Consignee Phone</th>
                                                    <th>Consignee Address</th>
                                                    <th>City</th>
                                                    <th>State</th>
                                                    <th>PIN</th>
                                                    <th>Consignor</th>
                                                    <th>Consignor City</th>
                                                    <th>Mode</th>
                                                    <th>Amount</th>
                                                    <th>Weight</th>
                                                    <th>Dimensions</th>
                                                    <th>Qty</th>
                                                    <th>Invoice No</th>
                                                    <th>Invoice Val</th>
                                                    <th>E-Way Bill</th>
                                                    <th>Status</th>
                                                    <th>Company</th>
                                                    <th>Branch</th>
                                                    <th>Creator</th>
                                                    <th width="100">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'footer.php'; ?>
        </div>
    </div>

    <!-- Vendors JS -->
    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="assets/plugins/datatables/buttons.bootstrap5.min.js"></script>
    <script src="assets/plugins/datatables/jszip.min.js"></script>
    <script src="assets/plugins/datatables/pdfmake.min.js"></script>
    <script src="assets/plugins/datatables/vfs_fonts.js"></script>
    <script src="assets/plugins/datatables/buttons.html5.min.js"></script>
    <script src="assets/plugins/datatables/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <script src="assets/plugins/select2/select2.min.js"></script>
    <script src="assets/plugins/daterangepicker/moment.min.js"></script>
    <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

    <script>
        $(document).ready(function () {
            let table;

            // Load filters
            $.get('api/company/read.php?length=1000', function (res) {
                if (res.data) res.data.forEach(c => $('#companyFilter').append(`<option value="${c.id}">${c.company_name}</option>`));
            });

            $.get('api/shipment/get_unique_statuses.php', function (res) {
                if (res.data) res.data.forEach(s => $('#statusFilter').append(`<option value="${s}">${s}</option>`));
            });

            $.get('api/courier_partner/read.php?length=100', function (res) {
                if (res.data) res.data.forEach(c => $('#courierFilter').append(`<option value="${c.id}">${c.partner_name}</option>`));
            });

            $('#companyFilter').change(function () {
                var cid = $(this).val();
                $('#branchFilter').html('<option value="">All Branches</option>');
                if (cid) {
                    $.get('api/branch/read.php?length=1000&company_id=' + cid, function (res) {
                        if (res.data) res.data.forEach(b => $('#branchFilter').append(`<option value="${b.id}">${b.branch_name}</option>`));
                    });
                }
            });

            // Date Range
            let start = moment().startOf('month');
            let end = moment().endOf('month');

            function cb(start, end) {
                $('#report-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                if (table) table.ajax.reload();
            }

            $('#report-range').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);

            cb(start, end);

            table = $('#misTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                dom: '<"d-flex justify-content-between mb-3"fl>rt<"d-flex justify-content-between"ip>',
                ajax: {
                    url: "api/reports/mis_read.php",
                    data: function (d) {
                        d.company_id = $('#companyFilter').val();
                        d.branch_id = $('#branchFilter').val();
                        d.courier_id = $('#courierFilter').val();
                        d.status = $('#statusFilter').val();
                        d.from_date = $('#report-range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        d.to_date = $('#report-range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                },
                columns: [
                    {
                        data: 'created_at',
                        render: data => data ? moment(data).format('DD-MM-YYYY') : ''
                    },
                    { data: 'booking_ref_id' },
                    {
                        data: 'waybill_no',
                        render: data => data ? `<a href="tracking.php?waybill=${data}" target="_blank" class="fw-bold text-primary">${data}</a>` : '<span class="text-muted">Pending</span>'
                    },
                    { data: 'courier_name' },
                    { data: 'shipping_mode' },
                    { data: 'product_desc' },
                    { data: 'consignee_name' },
                    { data: 'consignee_phone' },
                    { data: 'consignee_address' },
                    { data: 'consignee_city' },
                    { data: 'consignee_state' },
                    { data: 'consignee_pin' },
                    { data: 'shipper_name' },
                    { data: 'shipper_city' },
                    { data: 'payment_mode' },
                    {
                        data: 'cod_amount',
                        render: (data, type, row) => row.payment_mode === 'COD' ? `₹${data}` : 'Prepaid'
                    },
                    {
                        data: 'weight',
                        render: data => data ? `${data}g` : '-'
                    },
                    {
                        data: null,
                        render: (data, type, row) => `${row.length}x${row.width}x${row.height}`
                    },
                    { data: 'quantity' },
                    { data: 'invoice_no' },
                    { data: 'invoice_value' },
                    { data: 'ewaybill_no' },
                    {
                        data: 'last_status',
                        render: data => `<span class="badge bg-soft-info text-info">${data}</span>`
                    },
                    { data: 'company_name' },
                    { data: 'branch_name' },
                    { data: 'creator_name' },
                    {
                        data: null,
                        orderable: false,
                        render: (data, type, row) => `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-light" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="order-details.php?id=${row.id}"><i class="ti ti-eye me-1"></i> Details</a>
                                    <a class="dropdown-item" href="shipment-edit.php?id=${row.id}"><i class="ti ti-edit me-1"></i> Edit</a>
                                    <a class="dropdown-item" href="tracking.php?waybill=${row.waybill_no}" target="_blank"><i class="ti ti-truck me-1"></i> Track</a>
                                </div>
                            </div>
                        `
                    }
                ]
            });

            $('#companyFilter, #branchFilter, #courierFilter, #statusFilter').change(() => table.ajax.reload());
            $('#refreshBtn').click(() => table.ajax.reload());
        });
    </script>
</body>

</html>