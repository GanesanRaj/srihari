<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('branch', 'is_view');

// Get permissions
$can_add = can_add('branch');
$can_edit = can_edit('branch');
$can_delete = can_delete('branch');
?>

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
            <div class="px-0">
                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Branch Management</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="branch-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i> New Branch
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
                                    <div class="col-md-4">
                                        <select id="companyFilter" class="form-select form-select-sm">
                                            <option value="">All Companies</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="statusFilter" class="form-select form-select-sm">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-soft-primary flex-grow-1"
                                                id="filterBtn">
                                                <i class="ti ti-search me-1"></i> Search
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light" id="resetBtn">
                                                <i class="ti ti-rotate"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <table id="branchTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Company</th>
                                            <th>Branch Name</th>
                                            <th>Code</th>
                                            <th>Contact</th>
                                            <th>State</th>
                                            <th>Status</th>
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

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <!-- Datatables js -->
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>

            <!-- Datatables Buttons js -->
            <script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
            <script src="assets/plugins/datatables/buttons.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/jszip.min.js"></script>
            <script src="assets/plugins/datatables/pdfmake.min.js"></script>
            <script src="assets/plugins/datatables/vfs_fonts.js"></script>
            <script src="assets/plugins/datatables/buttons.html5.min.js"></script>
            <script src="assets/plugins/datatables/buttons.print.min.js"></script>

            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                $(document).ready(function () {
                    // Load companies for filter
                    $.get('api/company/read.php?length=1000', function (response) {
                        if (response.data) {
                            response.data.forEach(function (company) {
                                $('#companyFilter').append(`<option value="${company.id}">${company.company_name}</option>`);
                            });
                        }
                    });

                    // Initialize DataTable
                    var table = $('#branchTable').DataTable({
                        dom: "<'d-md-flex justify-content-between align-items-center my-2'<'dropdown'B>f>rt<'d-md-flex justify-content-between align-items-center mt-2'ip>",
                        buttons: [
                            {
                                extend: "collection",
                                text: '<i class="ti ti-download me-1"></i> Export',
                                className: "btn btn-sm btn-light dropdown-toggle",
                                autoClose: true,
                                buttons: [
                                    { extend: "copy", text: '<i class="ti ti-copy me-1 fs-lg align-middle"></i> Copy', className: "dropdown-item" },
                                    { extend: "csv", text: '<i class="ti ti-file-type-csv me-1 fs-lg align-middle"></i> CSV', className: "dropdown-item" },
                                    { extend: "excel", text: '<i class="ti ti-file-spreadsheet me-1 fs-lg align-middle"></i> Excel', className: "dropdown-item" },
                                    { extend: "print", text: '<i class="ti ti-printer me-1 fs-lg align-middle"></i> Print', className: "dropdown-item" },
                                    { extend: "pdf", text: '<i class="ti ti-file-text me-1 fs-lg align-middle"></i> PDF', className: "dropdown-item" }
                                ]
                            }
                        ],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/branch/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                                d.company_id = $('#companyFilter').val();
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'company_name' },
                            { data: 'branch_name' },
                            { data: 'branch_code' },
                            { data: 'contact_no' },
                            { data: 'state' },
                            {
                                data: 'status',
                                render: function (data) {
                                    return data === 'active'
                                        ? '<span class="text-success fw-semibold">Active</span>'
                                        : '<span class="text-danger fw-semibold">Inactive</span>';
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                render: function (data, type, row) {
                                    let actions = '<div class="d-flex gap-1">';
                                    actions += `<a href="branch-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark" title="View"><i class="ti ti-eye"></i> View</a>`;
                                    <?php if ($can_edit): ?>
                                        actions += `<a href="branch-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ti ti-edit"></i> Edit</a>`;
                                    <?php endif; ?>
                                    <?php if ($can_delete): ?>
                                        actions += `<button class="btn btn-sm btn-soft-danger delete-btn" data-id="${row.id}" title="Delete"><i class="ti ti-trash"></i> Delete</button>`;
                                    <?php endif; ?>
                                    actions += '</div>';
                                    return actions;
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

                    // Filter change events
                    $('#filterBtn').on('click', function () {
                        table.ajax.reload();
                    });

                    $('#resetBtn').on('click', function () {
                        $('#statusFilter').val('');
                        $('#companyFilter').val('');
                        table.ajax.reload();
                    });

                    // Delete handler
                    $('#branchTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this branch?', function () {
                            $.post('api/branch/delete.php', { id: id }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    table.ajax.reload();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            });
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

<style>
    #branchTable,
    #branchTable * {
        color: #000000 !important;
    }

    .text-primary,
    .text-info,
    .text-warning,
    .text-success,
    .text-danger {
        color: #000 !important;
    }

    .form-control-sm,
    .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }
</style>

</html>