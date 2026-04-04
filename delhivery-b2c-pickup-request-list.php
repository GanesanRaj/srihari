<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('pickup_request', 'is_view');

$can_add = can_add('pickup_request') ? 'true' : 'false';
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">

                    <!-- Page Title -->
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0"><i data-lucide="zap" style="width:18px;height:18px;"></i> Delhivery B2C Pickup Requests</h4>
                        </div>
                        <div class="text-end">
                            <?php if (can_add('pickup_request')): ?>
                                <a href="pickup-request-add.php" class="btn btn-sm btn-soft-primary">
                                    <i class="ti ti-plus me-1"></i> New Pickup Request
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select id="courierFilter" class="form-select form-select-sm">
                                                <option value="">All Couriers</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="Pending">Pending</option>
                                                <option value="Confirmed">Confirmed</option>
                                                <option value="Failed">Failed</option>
                                                <option value="Cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </div>

                                    <table id="pickupRequestTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Pickup Location</th>
                                                <th>Courier</th>
                                                <th>Pickup Date</th>
                                                <th>Pickup Time</th>
                                                <th>Pkg Count</th>
                                                <th>Status</th>
                                                <th>Request ID</th>
                                                <th>Created At</th>
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

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>

            <script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
            <script src="assets/plugins/datatables/buttons.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/jszip.min.js"></script>
            <script src="assets/plugins/datatables/pdfmake.min.js"></script>
            <script src="assets/plugins/datatables/vfs_fonts.js"></script>
            <script src="assets/plugins/datatables/buttons.html5.min.js"></script>
            <script src="assets/plugins/datatables/buttons.print.min.js"></script>

            <script>
            $(document).ready(function () {

                // Load couriers for filter — auto-select Delhivery for B2C
                $.get('api/courier_partner/read.php?length=1000', function (res) {
                    if (res.data) {
                        var delhiveryId = null;
                        res.data.forEach(function (c) {
                            $('#courierFilter').append(`<option value="${c.id}">${c.partner_name}</option>`);
                            if (!delhiveryId && c.partner_name.toLowerCase().indexOf('delhivery') !== -1) {
                                delhiveryId = c.id;
                            }
                        });
                        if (delhiveryId) {
                            $('#courierFilter').val(delhiveryId);
                            table.ajax.reload();
                        }
                    }
                });

                var table = $('#pickupRequestTable').DataTable({
                    dom: "<'d-md-flex justify-content-between align-items-center my-2'<'dropdown'B>f>rt<'d-md-flex justify-content-between align-items-center mt-2'ip>",
                    buttons: [
                        {
                            extend: "collection",
                            text: '<i class="ti ti-download me-1"></i> Export',
                            className: "btn btn-sm btn-light dropdown-toggle",
                            autoClose: true,
                            buttons: [
                                { extend: "copy",  text: '<i class="ti ti-copy me-1 fs-lg align-middle"></i> Copy',  className: "dropdown-item" },
                                { extend: "csv",   text: '<i class="ti ti-file-type-csv me-1 fs-lg align-middle"></i> CSV',   className: "dropdown-item" },
                                { extend: "excel", text: '<i class="ti ti-file-spreadsheet me-1 fs-lg align-middle"></i> Excel', className: "dropdown-item" },
                                { extend: "print", text: '<i class="ti ti-printer me-1 fs-lg align-middle"></i> Print', className: "dropdown-item" },
                                { extend: "pdf",   text: '<i class="ti ti-file-text me-1 fs-lg align-middle"></i> PDF',   className: "dropdown-item" }
                            ]
                        }
                    ],
                    processing: true,
                    serverSide: true,
                    fixedHeader: { header: true, headerOffset: 65 },
                    ajax: {
                        url: 'api/pickup_request/read.php',
                        type: 'GET',
                        data: function (d) {
                            d.status     = $('#statusFilter').val();
                            d.courier_id = $('#courierFilter').val();
                        }
                    },
                    columns: [
                        { data: 'id' },
                        { data: 'pickup_location_name' },
                        { data: 'partner_name', defaultContent: '—' },
                        {
                            data: 'pickup_date',
                            render: function (data) {
                                if (!data) return '—';
                                var d = new Date(data);
                                return d.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
                            }
                        },
                        {
                            data: 'pickup_time',
                            render: function (data) {
                                if (!data) return '—';
                                // Format HH:MM:SS → HH:MM AM/PM
                                var parts = data.split(':');
                                var h = parseInt(parts[0]), m = parts[1];
                                var ampm = h >= 12 ? 'PM' : 'AM';
                                h = h % 12 || 12;
                                return h + ':' + m + ' ' + ampm;
                            }
                        },
                        { data: 'expected_package_count' },
                        {
                            data: 'status',
                            render: function (data) {
                                var cls = { Confirmed: 'success', Pending: 'warning', Failed: 'danger', Cancelled: 'secondary' };
                                var c = cls[data] || 'secondary';
                                return `<span class="badge bg-${c}">${data || '—'}</span>`;
                            }
                        },
                        {
                            data: 'request_id',
                            render: function (data) {
                                return data ? `<code>${data}</code>` : '<span class="text-muted">—</span>';
                            }
                        },
                        {
                            data: 'created_at',
                            render: function (data) {
                                if (!data) return '—';
                                var d = new Date(data);
                                return d.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' })
                                     + ' ' + d.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit' });
                            }
                        }
                    ],
                    order: [[0, 'desc']],
                    pageLength: 25,
                    language: {
                        paginate: {
                            first:    '<i class="ti ti-chevrons-left"></i>',
                            previous: '<i class="ti ti-chevron-left"></i>',
                            next:     '<i class="ti ti-chevron-right"></i>',
                            last:     '<i class="ti ti-chevrons-right"></i>'
                        }
                    }
                });

                $('#statusFilter, #courierFilter').on('change', function () {
                    table.ajax.reload();
                });

            });
            </script>
        </div>
    </div>
</body>

<style>
    #pickupRequestTable,
    #pickupRequestTable * {
        color: #000000 !important;
    }
    .form-control-sm,
    .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }
</style>

</html>
