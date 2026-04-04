<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
// require_permission('employee', 'is_view');

// Get permissions
$can_add = true; // can_add('employee');
$can_edit = true; // can_edit('employee');
$can_delete = true; // can_delete('employee');
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

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
                        <h4 class="fs-18 fw-semibold m-0">Employee List</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="employee-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i>Add New Employee
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
                                        <select id="branchFilter" class="form-select form-select-sm select2">
                                            <option value="">All Branches</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="designationFilter" class="form-select form-select-sm select2">
                                            <option value="">All Designations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select id="statusFilter" class="form-select form-select-sm">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <table id="employeeTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Branch</th>
                                            <th>Designation</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Shift</th>
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

            <!-- Shift Assignment Modal -->
            <div class="modal fade" id="shiftAssignModal" tabindex="-1" aria-labelledby="shiftAssignModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="shiftAssignModalLabel">Assign Shift</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="shiftAssignForm">
                                <input type="hidden" name="employee_id" id="modal_employee_id">
                                <div class="mb-3">
                                    <label class="form-label">Employee Name</label>
                                    <input type="text" class="form-control" id="modal_employee_name" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Shift <span class="text-danger">*</span></label>
                                    <select class="form-select" name="shift_id" id="modal_shift_id" required>
                                        <option value="">Select Shift</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assigned Date</label>
                                    <input type="date" class="form-control" name="assigned_date"
                                        value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveShiftAssignment">Assign Shift</button>
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

            <script src="assets/plugins/select2/select2.min.js"></script>

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
                    // Initialize Select2
                    $('.select2').select2({
                        theme: 'bootstrap-5',
                        placeholder: $(this).data('placeholder'),
                    });

                    // Load branches for filter
                    $.get('api/branch/read.php?length=-1', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branchFilter').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                        }
                    });

                    // Load designations for filter
                    $.get('api/designation/read.php?length=-1', function (response) {
                        if (response.data) {
                            response.data.forEach(function (designation) {
                                $('#designationFilter').append(`<option value="${designation.id}">${designation.designation}</option>`);
                            });
                        }
                    });

                    // Initialize DataTable
                    var table = $('#employeeTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/employee/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                                d.branch_id = $('#branchFilter').val();
                                d.designation_id = $('#designationFilter').val();
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
                            { data: 'name' },
                            { data: 'branch_name' },
                            { data: 'designation' },
                            { data: 'phone' },
                            { data: 'email' },
                            {
                                data: 'shift_name',
                                render: function (data) {
                                    return data ? `<span class="text-info fw-semibold">${data}</span>` : '<span class="text-secondary fw-semibold">Not Assigned</span>';
                                }
                            },
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
                                    actions += `<button class="btn btn-sm btn-soft-info assign-shift-btn" data-id="${row.id}" data-name="${row.name}" title="Assign Shift"><i class="ti ti-clock"></i></button>`;
                                    if (userPermissions.canEdit) {
                                        actions += `<a href="employee-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ti ti-edit"></i></a>`;
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
                    $('#statusFilter, #branchFilter, #designationFilter').on('change', function () {
                        table.ajax.reload();
                    });

                    // Load shifts for modal
                    $.get('api/shift/read.php?length=100', function (response) {
                        if (response.data) {
                            response.data.forEach(shift => {
                                $('#modal_shift_id').append(`<option value="${shift.id}">${shift.shift_name} (${shift.start_time} - ${shift.end_time})</option>`);
                            });
                        }
                    });

                    // Assign Shift button handler
                    $('#employeeTable').on('click', '.assign-shift-btn', function () {
                        let id = $(this).data('id');
                        let name = $(this).data('name');

                        $('#modal_employee_id').val(id);
                        $('#modal_employee_name').val(name);
                        $('#modal_shift_id').val('');

                        $('#shiftAssignModal').modal('show');
                    });

                    // Save shift assignment
                    $('#saveShiftAssignment').on('click', function () {
                        let formData = $('#shiftAssignForm').serialize();

                        $.ajax({
                            url: 'api/employee_shift/assign.php',
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    alert('Shift assigned successfully');
                                    $('#shiftAssignModal').modal('hide');
                                    table.ajax.reload();
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function () {
                                alert('Error assigning shift');
                            }
                        });
                    });

                    // Delete handler
                    $('#employeeTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        if (confirmDelete('Are you sure you want to delete this employee?')) {
                            $.post('api/employee/delete.php', { id: id }, function (response) {
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
                #employeeTable,
                #employeeTable * {
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