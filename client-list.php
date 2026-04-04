<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('client', 'is_view');

// Get permissions
$can_add = can_add('client');
$can_edit = can_edit('client');
$can_delete = can_delete('client');
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
                <div class="">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Client List</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="client-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i> New Client
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row g-2 mb-4">
                            <div class="col-md-3">
                                <select id="branchFilter" class="form-select form-select-sm">
                                    <option value="">All Branches</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-soft-primary" id="filterBtn">
                                        <i class="ti ti-search me-1"></i> Search
                                    </button>
                                    <button type="button" class="btn btn-light" id="resetBtn">
                                        <i class="ti ti-rotate me-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="clientTable" class="table table-hover dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Branch</th>
                                        <th>Client Name</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th style="width: 100px;">Assign</th>
                                        <th>Status</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
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

            <!-- Modal for assigning courier partners to client -->
            <div class="modal fade" id="assignClientCouriersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Courier Partners by Priority</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="selectedClientId">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <strong>Priority Levels:</strong> 1 = DelhiVert (Primary), 2 = Secondary, 3 =
                                        Tertiary
                                    </div>
                                </div>
                            </div>
                            <div id="priorityAssignmentContainer">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Primary (1st)</label>
                                        <select class="form-select select2-modal" id="priority_1">
                                            <option value="">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Secondary (2nd)</label>
                                        <select class="form-select select2-modal" id="priority_2">
                                            <option value="">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tertiary (3rd)</label>
                                        <select class="form-select select2-modal" id="priority_3">
                                            <option value="">None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-soft-primary" id="saveClientAssignmentsBtn">
                                <i class="ti ti-device-floppy me-1"></i> Save Assignments
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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

                #clientTable,
                #clientTable * {
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

                .select2-container--default .select2-selection--single {
                    height: 31px;
                    line-height: 31px;
                    font-size: 13px;
                }

                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    line-height: 31px;
                }

                .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 31px;
                }
            </style>

            <script>
                const userPermissions = {
                    canEdit: <?php echo $can_edit ? 'true' : 'false'; ?>,
                    canDelete: <?php echo $can_delete ? 'true' : 'false'; ?>
                };

                $(document).ready(function () {
                    // Load branches for filter
                    $.get('api/branch/read.php?length=1000&status=active', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branchFilter').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                        }
                    });

                    // Initialize DataTable
                    var table = $('#clientTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/client/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                                d.branch_id = $('#branchFilter').val();
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'branch_name' },
                            { data: 'client_name' },
                            { data: 'contact_no' },
                            { data: 'email' },
                            { data: 'city' },
                            {
                                data: null,
                                orderable: false,
                                render: function (data, type, row) {
                                    return `<button class="btn btn-sm btn-outline-dark assign-client-couriers-btn" data-id="${row.id}" data-bs-toggle="modal" data-bs-target="#assignClientCouriersModal">
                                        <i class="ti ti-settings me-1"></i> Assign
                                    </button>`;
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
                                    actions += `<a href="client-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i> View</a> `;
                                    if (userPermissions.canEdit) {
                                        actions += `<a href="client-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a> `;
                                    }
                                    if (userPermissions.canDelete) {
                                        actions += `<button class="btn btn-sm btn-soft-danger delete-btn" data-id="${row.id}"><i class="ti ti-trash"></i> Delete</button>`;
                                    }
                                    actions += '</div>';
                                    return actions;
                                }
                            }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25,
                        fixedHeader: {
                            header: true,
                            headerOffset: 65
                        },
                        language: {
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        },
                        dom: "<'d-md-flex justify-content-between align-items-center my-2'<'dropdown'B>f>rt<'d-md-flex justify-content-between align-items-center mt-2'ip>",
                        buttons: [
                            {
                                extend: 'collection',
                                text: '<i class="ti ti-download me-1"></i> Export',
                                className: 'btn btn-sm btn-light dropdown-toggle',
                                autoClose: true,
                                buttons: [
                                    { extend: 'copy', text: '<i class="ti ti-copy me-1"></i> Copy', className: 'dropdown-item' },
                                    { extend: 'csv', text: '<i class="ti ti-file-type-csv me-1"></i> CSV', className: 'dropdown-item' },
                                    { extend: 'excel', text: '<i class="ti ti-file-spreadsheet me-1"></i> Excel', className: 'dropdown-item' },
                                    { extend: 'print', text: '<i class="ti ti-printer me-1"></i> Print', className: 'dropdown-item' },
                                    { extend: 'pdf', text: '<i class="ti ti-file-text me-1"></i> PDF', className: 'dropdown-item' }
                                ]
                            }
                        ]
                    });

                    // Filter button click
                    $('#filterBtn').on('click', function () {
                        table.ajax.reload();
                    });

                    // Reset button click
                    $('#resetBtn').on('click', function () {
                        $('#branchFilter').val('');
                        $('#statusFilter').val('');
                        table.ajax.reload();
                    });

                    // Delete handler
                    $('#clientTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        if (confirm('Are you sure you want to delete this client?')) {
                            $.post('api/client/delete.php', { id: id }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    table.ajax.reload();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            });
                        }
                    });

                    // Assign Courier Partners to Client Modal Handler
                    $(document).on('click', '.assign-client-couriers-btn', function () {
                        let clientId = $(this).data('id');
                        $('#selectedClientId').val(clientId);
                        loadClientAssignmentModal(clientId);
                    });

                    function loadClientAssignmentModal(clientId) {
                        $.ajax({
                            url: 'api/courier_partner/read.php',
                            type: 'GET',
                            data: { length: 1000, status: 'active' },
                            success: function (response) {
                                if (response.data) {
                                    // Populate select dropdowns
                                    let options = '<option value="">None</option>';
                                    response.data.forEach(function (courier) {
                                        options += `<option value="${courier.id}">${courier.partner_name} (${courier.partner_code})</option>`;
                                    });

                                    $('#priority_1, #priority_2, #priority_3').html(options);

                                    // Initialize Select2 for modal
                                    $('.select2-modal').select2({
                                        dropdownParent: $('#assignClientCouriersModal'),
                                        width: '100%'
                                    });

                                    // Get current assignments for this client
                                    $.ajax({
                                        url: `api/client/read_single.php?id=${clientId}`,
                                        type: 'GET',
                                        success: function (clientResponse) {
                                            if (clientResponse.status === 'success') {
                                                let clientAssignments = {};
                                                try {
                                                    if (clientResponse.data.courier_assignments) {
                                                        clientAssignments = JSON.parse(clientResponse.data.courier_assignments);
                                                    }
                                                } catch (e) {
                                                    clientAssignments = {};
                                                }

                                                // Set values in dropdowns
                                                if (clientAssignments['1']) $('#priority_1').val(clientAssignments['1']).trigger('change');
                                                if (clientAssignments['2']) $('#priority_2').val(clientAssignments['2']).trigger('change');
                                                if (clientAssignments['3']) $('#priority_3').val(clientAssignments['3']).trigger('change');
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }

                    // Save client assignments
                    $('#saveClientAssignmentsBtn').on('click', function () {
                        let clientId = $('#selectedClientId').val();
                        let assignments = {};

                        // Collect all priority assignments
                        for (let priority = 1; priority <= 3; priority++) {
                            let selectedValue = $(`#priority_${priority}`).val();
                            if (selectedValue) {
                                assignments[priority] = selectedValue;
                            }
                        }

                        $.ajax({
                            url: 'api/client/update_courier_assignments.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                id: clientId,
                                courier_assignments: assignments
                            }),
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt('Courier assignments updated successfully', 'success');
                                    $('#assignClientCouriersModal').modal('hide');
                                    table.ajax.reload(null, false);
                                } else {
                                    showtoastt(response.message || 'Error saving assignments', 'error');
                                }
                            },
                            error: function () {
                                showtoastt('Error saving assignments', 'error');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
    </div>
</body>

</html>