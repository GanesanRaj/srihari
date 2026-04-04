<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission (using a generic name or specific if you have permission system)
// require_permission('designation', 'is_view');

// Get permissions
$can_add = true; // can_add('designation');
$can_edit = true; // can_edit('designation');
$can_delete = true; // can_delete('designation');
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Designation List</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="designation-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i>Add New Designation
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select id="statusFilter" class="form-select form-select-sm">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <table id="designationTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Designation</th>
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

            <script>
                // User permissions
                const userPermissions = {
                    canEdit: <?php echo $can_edit ? 'true' : 'false'; ?>,
                    canDelete: <?php echo $can_delete ? 'true' : 'false'; ?>
                };

                function confirmDelete(message) {
                    return confirm(message || 'Are you sure you want to delete this item?');
                }

                $(document).ready(function () {
                    // Initialize DataTable
                    var table = $('#designationTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/designation/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                            }
                        },
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"<"d-flex justify-content-end"B>>>rtip',
                        buttons: [
                            {
                                extend: 'collection',
                                text: '<i class="ti ti-download me-1"></i>Export',
                                className: 'btn btn-sm btn-light dropdown-toggle',
                                buttons: ['copy', 'csv', 'excel', 'print', 'pdf']
                            }
                        ],
                        fixedHeader: true,
                        columns: [
                            { data: 'id' },
                            { data: 'designation' },
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
                                    if (userPermissions.canEdit) {
                                        actions += `<a href="designation-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ti ti-edit"></i></a>`;
                                    }
                                    if (userPermissions.canDelete) {
                                        actions += `<button class="btn btn-sm btn-soft-danger delete-btn" data-id="${row.id}" title="Delete"><i class="ti ti-trash"></i></button>`;
                                    }
                                    actions += '</div>';
                                    return actions;
                                }
                            }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25,
                        language: {
                            paginate: {
                                previous: "<i class='ti ti-chevron-left'></i>",
                                next: "<i class='ti ti-chevron-right'></i>"
                            }
                        }
                    });

                    // Filter change events
                    $('#statusFilter').on('change', function () {
                        table.ajax.reload();
                    });

                    // Delete handler
                    $('#designationTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        if (confirmDelete('Are you sure you want to delete this designation?')) {
                            $.post('api/designation/delete.php', { id: id }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    table.ajax.reload();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            });
                        }
                    });
                });
            </script>

            <style>
                .table-sm th, .table-sm td {
                    padding: 5px !important;
                    font-size: 13px;
                }
                .col-form-label {
                    padding-bottom: 2px !important;
                    padding-top: 2px !important;
                    margin-bottom: 2px !important;
                }
                #designationTable,
                #designationTable * {
                    color: #000000 !important;
                }
                .text-primary, .text-info, .text-warning, .text-success, .text-danger {
                    color: #000 !important;
                }
                .form-control-sm, .form-select-sm {
                    padding: 0.25rem 0.5rem !important;
                    font-size: 13px !important;
                }
            </style>
        </div>
    </div>
</body>

</html>