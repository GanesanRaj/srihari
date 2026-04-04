<?php include 'header.php'; ?>
<?php if (!defined('MIDDLEWARE_INCLUDED')) { require_once __DIR__ . '/config/middleware.php'; } require_permission('whms_manifest', 'is_view'); ?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <!-- Title + New button -->
                                    <div class="py-1 d-flex align-items-center flex-row mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0">
                                                <i data-lucide="file-text"></i> Manifest Management
                                            </h6>
                                        </div>
                                        <div class="text-end">
                                            <a href="whms-manifest-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Manifest
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Status Filter Pills -->
                                    <div class="mb-3 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-outline-secondary status-filter active"
                                            data-status="">All</button>
                                        <button class="btn btn-sm btn-outline-warning status-filter"
                                            data-status="draft">Draft</button>
                                        <button class="btn btn-sm btn-outline-primary status-filter"
                                            data-status="dispatched">Dispatched</button>
                                        <button class="btn btn-sm btn-outline-success status-filter"
                                            data-status="received">Received</button>
                                    </div>

                                    <table id="manifestTable"
                                        class="table table-hover dt-responsive nowrap w-100"
                                        style="font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Manifest No</th>
                                                <th>Route</th>
                                                <th>Dispatch Mode</th>
                                                <th>Coloader / Vehicle</th>
                                                <th>Driver</th>
                                                <th>Bags</th>
                                                <th>Wt (kg)</th>
                                                <th>Boxes</th>
                                                <th>Shipments</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created At</th>
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

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>

            <script>
                $(function () {
                    const statusColors  = { draft: 'warning', dispatched: 'primary', received: 'success' };
                    const statusLabels  = { draft: 'Draft', dispatched: 'Dispatched', received: 'Received' };

                    let currentStatus = '';

                    const table = $('#manifestTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/manifest/read.php',
                            data: d => { d.status = currentStatus; }
                        },
                        columns: [
                            {
                                data: 'manifest_no',
                                render: d => `<strong class="text-primary">${d}</strong>`
                            },
                            {
                                data: null,
                                render: (d, t, row) => {
                                    const from = row.from_branch_name || '—';
                                    const to   = row.to_branch_name   || '—';
                                    return `<span class="fs-11">${from}</span>
                                            <i class="ti ti-arrow-narrow-right mx-1 text-muted"></i>
                                            <span class="fs-11">${to}</span>`;
                                }
                            },
                            {
                                data: 'dispatch_mode',
                                render: d => d ? `<span class="badge bg-soft-info text-info border border-info">${d}</span>` : '—'
                            },
                            {
                                data: null,
                                render: (d, t, row) => {
                                    const coloader = row.coloader  || '—';
                                    const vehicle  = row.vehicle_no || '';
                                    return vehicle
                                        ? `${coloader}<br><span class="text-muted fs-11">${vehicle}</span>`
                                        : coloader;
                                }
                            },
                            {
                                data: null,
                                render: (d, t, row) => {
                                    const driver = row.driver_name || '—';
                                    const mobile = row.mobile_no   || '';
                                    return mobile
                                        ? `${driver}<br><span class="text-muted fs-11">${mobile}</span>`
                                        : driver;
                                }
                            },
                            { data: 'bag_count',   className: 'text-center', defaultContent: '0' },
                            { data: 'weight',      className: 'text-center', defaultContent: '0' },
                            { data: 'total_box',   className: 'text-center', defaultContent: '0' },
                            {
                                data: 'total_count',
                                className: 'text-center',
                                render: d => `<span class="badge bg-secondary">${d}</span>`
                            },
                            {
                                data: 'status',
                                render: d => {
                                    const c = statusColors[d] || 'secondary';
                                    const l = statusLabels[d] || d;
                                    return `<span class="badge bg-${c} ${c === 'warning' ? 'text-dark' : ''}">${l}</span>`;
                                }
                            },
                            { data: 'created_by_name', defaultContent: '—' },
                            {
                                data: 'created_at',
                                render: d => d ? new Date(d).toLocaleString('en-IN') : '—'
                            },
                            {
                                data: null,
                                orderable: false,
                                render: (d, t, row) => `
                                    <div class="d-flex gap-1">
                                        <a href="whms-manifest-create.php?id=${row.id}"
                                            class="btn btn-sm btn-soft-primary" title="Open / Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-dark btn-print"
                                            data-id="${row.id}" title="Print">
                                            <i class="ti ti-printer"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete"
                                            data-id="${row.id}" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>`
                            }
                        ]
                    });

                    // Status filter pills
                    $('.status-filter').on('click', function () {
                        $('.status-filter').removeClass('active');
                        $(this).addClass('active');
                        currentStatus = $(this).data('status');
                        table.ajax.reload();
                    });

                    // Print
                    $('#manifestTable').on('click', '.btn-print', function () {
                        const id = $(this).data('id');
                        window.open('manifest-print.php?id=' + id, '_blank');
                    });

                    // Delete
                    $('#manifestTable').on('click', '.btn-delete', function () {
                        const id = $(this).data('id');
                        if (!confirm('Permanently delete this manifest?')) return;
                        $.post('api/manifest/delete.php', { manifest_id: id }, function (res) {
                            if (res.status === 'success') {
                                table.ajax.reload();
                            } else {
                                showtoastt('Error: ' + res.message, 'error');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
