<?php include 'header.php'; ?>

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
                                                <i data-lucide="clipboard-list"></i> Run Sheet Management
                                            </h6>
                                        </div>
                                        <div class="text-end">
                                            <a href="runsheet-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Run Sheet
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
                                            data-status="completed">Completed</button>
                                    </div>

                                    <table id="runsheetTable"
                                        class="table table-hover dt-responsive nowrap w-100"
                                        style="font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Runsheet No</th>
                                                <th>Driver</th>
                                                <th>Mobile</th>
                                                <th>Shipments</th>
                                                <th>Date</th>
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
                    const statusColors = { draft: 'warning', dispatched: 'primary', completed: 'success' };
                    const statusLabels = { draft: 'Draft', dispatched: 'Dispatched', completed: 'Completed' };

                    let currentStatus = '';

                    const table = $('#runsheetTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/runsheet/read.php',
                            data: d => { d.status = currentStatus; }
                        },
                        columns: [
                            {
                                data: 'runsheet_no',
                                render: d => `<strong class="text-primary">${d}</strong>`
                            },
                            { data: 'driver_name', defaultContent: '—' },
                            { data: 'mobile_number', defaultContent: '—' },
                            {
                                data: 'shipment_count',
                                className: 'text-center',
                                render: d => `<span class="badge bg-secondary">${d ?? 0}</span>`
                            },
                            {
                                data: 'runsheet_date',
                                render: d => d ? d : '—'
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
                                        <a href="runsheet-create.php?id=${row.id}"
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
                    $('#runsheetTable').on('click', '.btn-print', function () {
                        const id = $(this).data('id');
                        window.open('runsheet-print.php?id=' + id, '_blank');
                    });

                    // Delete
                    $('#runsheetTable').on('click', '.btn-delete', function () {
                        const id = $(this).data('id');
                        if (!confirm('Permanently delete this run sheet?')) return;
                        $.post('api/runsheet/delete.php', { runsheet_id: id }, function (res) {
                            if (res.status === 'success') {
                                table.ajax.reload();
                            } else {
                                alert('Error: ' + res.message);
                            }
                        }, 'json');
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
