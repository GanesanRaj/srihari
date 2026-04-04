<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('coloader', 'is_view');

$can_edit = can_edit('coloader') ? 'true' : 'false';
$can_delete = can_delete('coloader') ? 'true' : 'false';
?>
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
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">Coloader (Master Data)</h4>
                        </div>
                        <div class="text-end">
                            <?php if (can_add('coloader')): ?>
                                <a href="coloader-add.php" class="btn btn-sm btn-soft-primary">
                                    <i class="ti ti-plus me-1"></i> New Coloader
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <table id="coloaderTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name <span class="text-danger">*</span></th>
                                                <th>Mobile Number <span class="text-danger">*</span></th>
                                                <th>Email <span class="text-danger">*</span></th>
                                                <th>Address <span class="text-danger">*</span></th>
                                                <th>Status <span class="text-danger">*</span></th>
                                                <th>Remarks</th>
                                                <th>Created At</th>
                                                <th width="150">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
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
                const userPermissions = { canEdit: <?php echo $can_edit; ?>, canDelete: <?php echo $can_delete; ?> };

                $(document).ready(function () {
                    var table = $('#coloaderTable').DataTable({
                        dom: "<'d-md-flex justify-content-between align-items-center my-2'<'dropdown'B>f>rt<'d-md-flex justify-content-between align-items-center mt-2'ip>",
                        buttons: [
                            {
                                extend: "collection",
                                text: '<i class="ti ti-download me-1"></i> Export',
                                className: "btn btn-sm btn-light dropdown-toggle",
                                autoClose: true,
                                buttons: [
                                    { extend: "copy", text: '<i class="ti ti-copy me-1"></i> Copy', className: "dropdown-item" },
                                    { extend: "csv", text: '<i class="ti ti-file-type-csv me-1"></i> CSV', className: "dropdown-item" },
                                    { extend: "excel", text: '<i class="ti ti-file-spreadsheet me-1"></i> Excel', className: "dropdown-item" },
                                    { extend: "print", text: '<i class="ti ti-printer me-1"></i> Print', className: "dropdown-item" },
                                    { extend: "pdf", text: '<i class="ti ti-file-text me-1"></i> PDF', className: "dropdown-item" }
                                ]
                            }
                        ],
                        processing: true,
                        serverSide: true,
                        fixedHeader: { header: true, headerOffset: 65 },
                        ajax: {
                            url: 'api/coloader/read.php',
                            type: 'GET',
                            data: function (d) { d.status = $('#statusFilter').val(); }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'name' },
                            { data: 'mobile_number' },
                            { data: 'email' },
                            { data: 'address', render: function (v) { return v && v.length > 40 ? v.substring(0, 40) + '…' : (v || '-'); } },
                            {
                                data: 'status',
                                render: function (data) {
                                    return data === 'active'
                                        ? '<span class="text-success fw-semibold">Active</span>'
                                        : '<span class="text-danger fw-semibold">Inactive</span>';
                                }
                            },
                            { data: 'remarks', render: function (v) { return v && v.length > 30 ? v.substring(0, 30) + '…' : (v || '-'); } },
                            { data: 'created_at' },
                            {
                                data: null,
                                orderable: false,
                                render: function (data, type, row) {
                                    let html = '<div class="d-flex gap-1">';
                                    html += '<a href="coloader-view.php?id=' + row.id + '" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i> View</a>';
                                    if (userPermissions.canEdit) {
                                        html += '<a href="coloader-add.php?id=' + row.id + '" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a>';
                                    }
                                    if (userPermissions.canDelete) {
                                        html += '<button class="btn btn-sm btn-soft-danger delete-btn" data-id="' + row.id + '"><i class="ti ti-trash"></i> Delete</button>';
                                    }
                                    html += '</div>';
                                    return html;
                                }
                            }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25,
                        language: {
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        }
                    });

                    $('#statusFilter').on('change', function () { table.ajax.reload(); });

                    $(document).on('click', '.delete-btn', function () {
                        var id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this coloader?', function () {
                            $.ajax({
                                url: 'api/coloader/delete.php?id=' + id,
                                type: 'GET',
                                success: function (response) {
                                    if (response.status === 'success') {
                                        table.ajax.reload(null, false);
                                        showtoastt(response.message, 'success');
                                    } else {
                                        showtoastt(response.message || 'Error', 'error');
                                    }
                                },
                                error: function () { showtoastt('Error deleting coloader', 'error'); }
                            });
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
