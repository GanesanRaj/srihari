<?php include 'header.php'; ?>
<?php if (!defined('MIDDLEWARE_INCLUDED')) { require_once __DIR__ . '/config/middleware.php'; } require_permission('whms_tag', 'is_view'); ?>

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

                                    <div class="py-1 d-flex align-items-center flex-row mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0">
                                                <i data-lucide="tag"></i> Tag Management
                                            </h6>
                                        </div>
                                        <div class="text-end">
                                            <a href="tag-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Tag
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Status Filter Pills -->
                                    <div class="mb-3 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-outline-secondary status-filter active"
                                            data-status="">All</button>
                                        <button class="btn btn-sm btn-outline-warning status-filter"
                                            data-status="packed">Packed</button>
                                        <button class="btn btn-sm btn-outline-danger status-filter"
                                            data-status="hold">Hold</button>
                                        <button class="btn btn-sm btn-outline-info status-filter"
                                            data-status="partially_verified">Partially Verified</button>
                                        <button class="btn btn-sm btn-outline-success status-filter"
                                            data-status="fully_verified">Fully Verified</button>
                                    </div>

                                    <table id="tagTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Tag No</th>
                                                <th>Count</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created At</th>
                                                <th>Verified By</th>
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
                    const statusColors = {
                        packed: 'warning', hold: 'danger',
                        partially_verified: 'info', fully_verified: 'success'
                    };
                    const statusLabels = {
                        packed: 'Packed', hold: 'Hold',
                        partially_verified: 'Partially Verified', fully_verified: 'Fully Verified'
                    };

                    let currentStatus = '';
                    const table = $('#tagTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/tag/read.php',
                            data: d => { d.status = currentStatus; }
                        },
                        columns: [
                            { data: 'tag_no', render: d => `<strong>${d}</strong>` },
                            { data: 'total_count', className: 'text-center', render: d => `<span class="badge bg-secondary">${d}</span>` },
                            {
                                data: 'status',
                                render: d => {
                                    const c = statusColors[d] || 'secondary';
                                    return `<span class="badge bg-${c}">${statusLabels[d] || d}</span>`;
                                }
                            },
                            { data: 'created_by_name', defaultContent: '-' },
                            { data: 'created_at', render: d => d ? new Date(d).toLocaleString('en-IN') : '' },
                            { data: 'verified_by_name', defaultContent: '-' },
                            {
                                data: null,
                                render: (d, t, row) => `
                            <div class="d-flex gap-1">
                                ${row.status !== 'fully_verified' ? `
                                <a href="tag-create.php?id=${row.id}" class="btn btn-sm btn-soft-primary">
                                    <i class="ti ti-edit"></i> Edit / Scan
                                </a>
                                <button class="btn btn-sm btn-outline-success btn-verify" data-id="${row.id}">
                                    <i class="ti ti-check"></i> Verify
                                </button>` : `
                                <a href="tag-create.php?id=${row.id}" class="btn btn-sm btn-soft-info" title="View Tag">
                                    <i class="ti ti-eye"></i>
                                </a>`}
                                <a href="tag-print.php?id=${row.id}" target="_blank" class="btn btn-sm btn-dark" title="Print 80x50 Label">
                                    <i class="ti ti-printer"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}" title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>`
                            }
                        ]
                    });

                    // Status filter pills
                    $('.status-filter').click(function () {
                        $('.status-filter').removeClass('active');
                        $(this).addClass('active');
                        currentStatus = $(this).data('status');
                        table.ajax.reload();
                    });

                    // Verify button
                    $('#tagTable').on('click', '.btn-verify', function () {
                        const id = $(this).data('id');
                        if (!confirm('Mark this tag as Fully Verified?')) return;
                        $.post('api/tag/verify.php', { tag_id: id }, res => {
                            if (res.status === 'success') {
                                table.ajax.reload();
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });

                    // Delete button
                    $('#tagTable').on('click', '.btn-delete', function () {
                        const id = $(this).data('id');
                        if (!confirm('Are you sure you want to permanently delete this tag?')) return;
                        $.post('api/tag/delete.php', { tag_id: id }, res => {
                            if (res.status === 'success') {
                                table.ajax.reload();
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>