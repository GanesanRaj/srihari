<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Require view permission for this page
require_permission ( 'setting-role', 'is_view' );

// Get permissions
$can_add    = can_add ( 'setting-role' );
$can_edit   = can_edit ( 'setting-role' );
$can_delete = can_delete ( 'setting-role' );
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
            <div class="px-0">
                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">User Roles</h4>
                        <p class="text-muted mb-0">Manage system roles and permissions</p>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add) : ?>
                            <a href="setting-role-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i> Add Role
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="rolesTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sl.No</th>
                                                <th>Prefix</th>
                                                <th>Role Name</th>
                                                <th>System Role</th>
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

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
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
                // Set permissions for JavaScript
                const permissions = {
                    canAdd: <?php echo $can_add ? 'true' : 'false'; ?>,
                    canEdit: <?php echo $can_edit ? 'true' : 'false'; ?>,
                    canDelete: <?php echo $can_delete ? 'true' : 'false'; ?>
                };

                $(document).ready(function () {
                    var table = $('#rolesTable').DataTable({
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
                                ]
                            }
                        ],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/role/read.php',
                            type: 'GET'
                        },
                        columns: [
                            {
                                data: null,
                                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                            },
                            { data: 'prefix' },
                            { data: 'name' },
                            {
                                data: 'is_system',
                                render: (data) => {
                                    return (data == '1' || data == 1)
                                        ? '<span class="text-info fw-semibold">Yes</span>'
                                        : '<span class="text-secondary fw-semibold">No</span>';
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                render: (data) => {
                                    const isSystem = data.is_system == '1' || data.is_system == 1;
                                    let buttons = '<div class="d-flex gap-1">';

                                    // Permissions button
                                    buttons += `<a href="setting-permission.php?id=${data.id}" class="btn btn-sm btn-outline-dark" title="Permissions">
                                        <i class="ti ti-lock"></i> Permissions
                                    </a>`;

                                    // Edit button
                                    if (permissions.canEdit) {
                                        buttons += `<a href="setting-role-edit.php?id=${data.id}" class="btn btn-sm btn-soft-primary" title="Edit">
                                            <i class="ti ti-edit"></i> Edit
                                        </a>`;
                                    }

                                    // Delete button
                                    if (permissions.canDelete) {
                                        if (isSystem) {
                                            buttons += `<button class="btn btn-sm btn-soft-secondary" disabled title="System role cannot be deleted">
                                                <i class="ti ti-trash"></i> Delete
                                            </button>`;
                                        } else {
                                            buttons += `<button class="btn btn-sm btn-soft-danger delete-btn" data-id="${data.id}" title="Delete">
                                                <i class="ti ti-trash"></i> Delete
                                            </button>`;
                                        }
                                    }

                                    buttons += '</div>';
                                    return buttons;
                                }
                            }
                        ],
                        order: [[1, 'asc']],
                        pageLength: 25,
                        language: {
                            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        }
                    });

                    // Delete handler
                    $('#rolesTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this role?', function () {
                            $.ajax({
                                url: 'api/role/delete.php',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ id: id }),
                                success: function (response) {
                                    if (response.success) {
                                        showtoastt(response.message || 'Role deleted successfully', 'success');
                                        table.ajax.reload();
                                    } else {
                                        showtoastt(response.message || 'Failed to delete role', 'error');
                                    }
                                },
                                error: function () {
                                    showtoastt('Error deleting role', 'error');
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
    #rolesTable,
    #rolesTable * {
        color: #000000 !important;
    }
</style>

</html>