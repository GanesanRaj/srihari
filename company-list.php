<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('company', 'is_view');

// Get permissions for JS
$can_edit = can_edit('company') ? 'true' : 'false';
$can_delete = can_delete('company') ? 'true' : 'false';
?>
<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css" />

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">

                    <!-- Page Title -->
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">Company Management</h4>
                        </div>
                        <div class="text-end">
                            <?php if (can_add('company')): ?>
                                <a href="company-add.php" class="btn btn-sm btn-soft-primary">
                                    <i class="ti ti-plus me-1"></i> New Company
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
                                        <div class="col-md-2">
                                            <input type="text" id="filter_city" class="form-control form-control-sm"
                                                placeholder="City">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" id="filter_state" class="form-control form-control-sm"
                                                placeholder="State">
                                        </div>
                                        <div class="col-md-2">
                                            <select id="filter_status" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
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

                                    <table id="companyTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Company Name</th>
                                                <th>Phone</th>
                                                <th>City</th>
                                                <th>State</th>
                                                <th>Status</th>
                                                <th width="150">Action</th>
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

            <script>
                const userPermissions = {
                    canEdit: <?php echo $can_edit; ?>,
                    canDelete: <?php echo $can_delete; ?>
                };

                $(document).ready(function () {

                    const table = $('#companyTable').DataTable({
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
                        fixedHeader: {
                            header: true,
                            headerOffset: 65
                        },
                        ajax: {
                            url: 'api/company/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.city = $('#filter_city').val();
                                d.state = $('#filter_state').val();
                                d.status = $('#filter_status').val();
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'company_name' },
                            { data: 'phone_number' },
                            { data: 'city' },
                            { data: 'state' },
                            {
                                data: 'status',
                                render: function (data) {
                                    return data === 'active' ?
                                        '<span class="text-success fw-semibold">Active</span>' :
                                        '<span class="text-danger fw-semibold">Inactive</span>';
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                render: function (data, type, row) {
                                    let actionButtons = `<div class="d-flex gap-1">`;
                                    actionButtons += `<a href="company-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i> View</a>`;
                                    if (userPermissions.canEdit) {
                                        actionButtons += `<a href="company-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a>`;
                                    }
                                    if (userPermissions.canDelete) {
                                        actionButtons += `<button class="btn btn-sm btn-soft-danger delete-btn" data-id="${row.id}"><i class="ti ti-trash"></i> Delete</button>`;
                                    }
                                    actionButtons += `</div>`;
                                    return actionButtons;
                                }
                            }
                        ],
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

                    // Toggle Filter
                    $('#toggleFilterBtn').on('click', function () {
                        $('#filterCard').slideToggle();
                    });

                    // Filter button click
                    $('#filterBtn').on('click', function () {
                        table.ajax.reload();
                    });

                    // Reset button click
                    $('#resetBtn').on('click', function () {
                        $('#filter_city').val('');
                        $('#filter_state').val('');
                        $('#filter_status').val('');
                        table.ajax.reload();
                    });

                    // Delete Handler
                    $(document).on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this company?', function () {
                            $.ajax({
                                url: `api/company/delete.php?id=${id}`,
                                type: 'GET',
                                success: function (response) {
                                    if (response.status === 'success') {
                                        table.ajax.reload(null, false);
                                        showtoastt(response.message, 'success');
                                    } else {
                                        showtoastt(response.message, 'error');
                                    }
                                },
                                error: function () {
                                    showtoastt('Error deleting company', 'error');
                                }
                            });
                        });
                    });
                });
            </script>
        </div>
    </div>
    </div>
</body>

<style>
    .table-sm th,
    .table-sm td {
        padding: 5px !important;
        font-size: 13px;
    }

    .col-form-label {
        padding-bottom: 2px !important;
        padding-top: 2px !important;
        margin-bottom: 2px !important;
    }

    #companyTable,
    #companyTable * {
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