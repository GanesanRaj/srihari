<?php include 'header.php'; ?>
<?php // require_permission('shipment', 'is_view'); ?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">



                    <!-- List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <!-- Page Title -->
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"> <i data-lucide="database"></i> Shipment
                                                Management</h4>
                                        </div>
                                        <div class="text-end">
                                            <a href="shipment-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Shipment
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Filters -->
                                    <div class="row mb-3">
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
                                            <div id="shipment-range"
                                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <style>
                                        #shipmentTable,
                                        .text-primary,
                                        .text-info,
                                        .text-warning,
                                        .text-success,
                                        .text-danger {
                                            color: #000 !important;
                                        }
                                    </style>

                                    <table id="shipmentTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Shipment Info</th>
                                                <th>Status Info</th>
                                                <th>Sender Info</th>
                                                <th>Receiver Info</th>
                                                <th>Action</th>
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


            <?php include 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <!-- Datatables js -->
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>

            <!-- Select2 js -->
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                $(document).ready(function () {
                    let table;
                    const urlParams = new URLSearchParams(window.location.search);
                    const preStatus = urlParams.get('status');
                    const preFrom = urlParams.get('from');
                    const preTo = urlParams.get('to');

                    // Load Companies
                    $.get('api/company/read.php?length=1000', function (res) {
                        if (res.data) {
                            res.data.forEach(c => {
                                $('#companyFilter').append(`<option value="${c.id}">${c.company_name}</option>`);
                            });
                        }
                    });

                    // Load Used Statuses for Filter
                    $.get('api/shipment/get_unique_statuses.php', function (res) {
                        if (res.data) {
                            res.data.forEach(s => {
                                let selected = (preStatus && preStatus === s) ? 'selected' : '';
                                $('#statusFilter').append(`<option value="${s}" ${selected}>${s}</option>`);
                            });
                            // If table is already initialized, reload it
                            if (table) table.ajax.reload();
                        }
                    });

                    // Load Couriers for Filter
                    $.get('api/courier_partner/read.php?length=100', function (res) {
                        if (res.data) {
                            res.data.forEach(c => {
                                $('#courierFilter').append(`<option value="${c.id}">${c.partner_name}</option>`);
                            });
                        }
                    });

                    // Load Branches when Company changes
                    $('#companyFilter').change(function () {
                        var companyId = $(this).val();
                        $('#branchFilter').html('<option value="">All Branches</option>');
                        if (companyId) {
                            $.get('api/branch/read.php?length=1000&company_id=' + companyId, function (res) {
                                if (res.data) {
                                    res.data.forEach(b => {
                                        $('#branchFilter').append(`<option value="${b.id}">${b.branch_name}</option>`);
                                    });
                                }
                            });
                        }
                    });

                    // Date Range Picker
                    let initialStart = preFrom ? moment(preFrom) : moment().startOf('month');
                    let initialEnd = preTo ? moment(preTo) : moment().endOf('month');

                    let startDate = initialStart.format('YYYY-MM-DD');
                    let endDate = initialEnd.format('YYYY-MM-DD');

                    function cb(start, end) {
                        $('#shipment-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#shipment-range').daterangepicker({
                        startDate: initialStart,
                        endDate: initialEnd,
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);

                    cb(initialStart, initialEnd);


                    table = $('#shipmentTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "api/shipment/read.php",
                            type: "GET",
                            data: function (d) {
                                d.company_id = $('#companyFilter').val();
                                d.branch_id = $('#branchFilter').val();
                                d.courier_id = $('#courierFilter').val();
                                d.status = $('#statusFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        columns: [
                            /*{ data: 'id' },*/
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<i class="fa fa-address-book"></i> <span class="light">Waybill No :</span> <span style="font-weight:bold;color:#da7d41;">${row.waybill_no}</span>
                                    <br><span class="light">Ref No : </span> <span style="font-weight:bolsd;">${row.booking_ref_id}</span>
                                    <br><span class="light">Branch Name : </span> <span style="font-weight:bolsd;">${row.branch_name}</span>
                                    <br><span class="light">Created At </span> <span style="font-weight:bolsd;">${row.created_at ? new Date(row.created_at).toLocaleDateString() : ''}</span>
                                    
                                    `;
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `
                                    <span class="light">Status : </span> <span style="font-weight:bolsd;">${row.last_status}</span>
                                    <br><span class="light">Shipping Mode : </span> <span style="font-weight:bolsd;">${row.shipping_mode}</span>
                                    <br><span class="light">Payment Mode : </span> <span style="font-weight:bolsd;">${row.payment_mode}</span>
                                    <br><span class="light">Invoice Date </span> <span style="font-weight:bolsd;">${row.created_at ? new Date(row.created_at).toLocaleDateString() : ''}</span>
                                    <br><span class="light">Last Status Date </span> <span style="font-weight:bolsd;">${row.created_at ? new Date(row.created_at).toLocaleDateString() : ''}</span>
                                    
                                    `;
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<i class="fa fa-address-book"></i> <span style="font-weight:bold;">REALME MOBILE TELECOMMUNICATIONS PVT LTD</span><br><i class="fa fa-map-marker"></i> <span style="font-weight:bolsd;">Sy.no 44/1, 44/2, 55/1, 55/2, Devalapura Village Devanagundi</span><br><i class="fa fa-map-pin"></i> <span style="font-weight:bolsd;">Bangalore-560027,Karnataka</span><br><i class="fa fa-mobile"></i> <span style="font-weight:bolsd;">9611785704</span>`;
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<i class="fa fa-address-book"></i> <span style="font-weight:bold;">REALME MOBILE TELECOMMUNICATIONS PVT LTD</span><br><i class="fa fa-map-marker"></i> <span style="font-weight:bolsd;">Sy.no 44/1, 44/2, 55/1, 55/2, Devalapura Village Devanagundi</span><br><i class="fa fa-map-pin"></i> <span style="font-weight:bolsd;">Bangalore-560027,Karnataka</span><br><i class="fa fa-mobile"></i> <span style="font-weight:bolsd;">9611785704</span>`;
                                }
                            },

                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<div class="d-flex gap-1 flex-wrap">
                            <a href="order-details.php?id=${row.id}" class="btn btn-sm btn-outline-dark">Details</a>
                            <a href="shipment-edit.php?id=${row.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a>
                            <button class="btn btn-sm btn-outline-dark btn-label-print" data-id="${row.id}" data-waybill="${row.waybill_no}" data-size="A4">
                                <i class="ti ti-printer"></i> Print Label
                            </button>
                        </div>`;
                                }
                            }
                        ]
                    });

                    // Refresh table on filter change
                    $('#companyFilter, #branchFilter, #courierFilter, #statusFilter').change(function () {
                        table.ajax.reload();
                    });

                    // Handle Custom Print Label
                    $('#shipmentTable').on('click', '.btn-label-print', function () {
                        var id = $(this).data('id');
                        var waybill = $(this).data('waybill');
                        var size = $(this).data('size') || 'A4';

                        if (!waybill) {
                            alert('No Waybill generated yet.');
                            return;
                        }

                        var url = 'shipment-label-print.php?id=' + encodeURIComponent(id) +
                            '&waybill=' + encodeURIComponent(waybill) +
                            '&pdf_size=' + encodeURIComponent(size);

                        window.open(url, '_blank');
                    });
                });
            </script>