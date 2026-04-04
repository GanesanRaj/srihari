<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('courier_partner', 'is_view');

// Get permissions
$can_add = can_add('courier_partner');
$can_edit = can_edit('courier_partner') ? 'true' : 'false';
$can_delete = can_delete('courier_partner') ? 'true' : 'false';
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
                        <h4 class="fs-18 fw-semibold m-0">Courier Partner List</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="courier-partner-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i> New Partner
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-soft-info" data-bs-toggle="modal" data-bs-target="#assignPartnerModal">
                            <i class="ti ti-user-plus me-1"></i> Assign Partner
                        </button>
                    </div>
                </div>

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

                        <table id="courierTable" class="table table-striped table-bordered dt-responsive nowrap"
                            style="width:100%; font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Partner Name</th>
                                    <th>Partner Code</th>
                                    <th>Username</th>
                                    <th>Display Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Modal for Assigning Partner -->
            <div class="modal fade" id="assignPartnerModal" tabindex="-1" aria-labelledby="assignPartnerModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="assignPartnerModalLabel">Assign Courier Partner to Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="assignPartnerForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="company_id" class="form-label">Select Company</label>
                                        <select class="form-select select2" id="company_id" name="company_id" required>
                                            <option value="">Choose Company...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="courier_partner_id" class="form-label">Select Courier
                                            Partner</label>
                                        <select class="form-select select2" id="courier_partner_id"
                                            name="courier_partner_id" required>
                                            <option value="">Choose Partner...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="api_key" class="form-label">API Key</label>
                                        <input type="text" class="form-control" id="api_key" name="api_key">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="api_secret" class="form-label">API Secret / URL</label>
                                        <input type="text" class="form-control" id="api_secret" name="api_secret">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save Assignment</button>
                                </div>
                            </form>
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
                const userPermissions = {
                    canEdit: <?php echo $can_edit; ?>,
                    canDelete: <?php echo $can_delete; ?>
                };

                $(document).ready(function () {
                    $('.select2').select2({
                        dropdownParent: $('#assignPartnerModal')
                    });

                    // Initialize DataTable
                    var table = $('#courierTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/courier_partner/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'partner_name' },
                            { data: 'partner_code' },
                            { data: 'username' },
                            { data: 'preference_order' },
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
                                    actionButtons += `<a href="courier-partner-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i> View</a>`;
                                    if (userPermissions.canEdit) {
                                        actionButtons += `<a href="courier-partner-add.php?id=${row.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a>`;
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
                        fixedHeader: {
                            header: true,
                            headerOffset: 65
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
                        ],
                        language: {
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        }
                    });

                    $('#statusFilter').on('change', function () {
                        table.ajax.reload();
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
                    $('#filter_status').val('');
                    table.ajax.reload();
                });

                // Delete Handler
                $(document).on('click', '.delete-btn', function () {
                    let id = $(this).data('id');
                    confirmDelete('Are you sure you want to delete this courier partner?', function () {
                        $.ajax({
                            url: `api/courier_partner/delete.php?id=${id}`,
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
                                showtoastt('Error deleting courier partner', 'error');
                            }
                        });
                    });
                });

                // Assign Couriers Modal Handler
                $(document).on('click', '.assign-couriers-btn', function () {
                    let courierId = $(this).data('id');
                    $('#selectedCourierId').val(courierId);
                    loadAssignmentModal(courierId);
                });

                function loadAssignmentModal(courierId) {
                    $.ajax({
                        url: 'api/courier_partner/read.php',
                        type: 'GET',
                        data: { length: 1000, status: 'active' },
                        success: function (response) {
                            if (response.data) {
                                let availableHtml = '';
                                let assignedIds = [];

                                // Get currently assigned couriers for this partner
                                $.ajax({
                                    url: `api/courier_partner/read_single.php?id=${courierId}`,
                                    type: 'GET',
                                    success: function (singleResponse) {
                                        if (singleResponse.status === 'success' && singleResponse.data.assigned_couriers) {
                                            try {
                                                assignedIds = JSON.parse(singleResponse.data.assigned_couriers);
                                            } catch (e) {
                                                assignedIds = [];
                                            }
                                        }

                                        // Build available couriers list
                                        response.data.forEach(function (courier) {
                                            let isAssigned = assignedIds.includes(String(courier.id));
                                            let checked = isAssigned ? 'checked' : '';
                                            let itemClass = isAssigned ? 'courier-item assigned' : 'courier-item';

                                            availableHtml += `
                                                    <div class="${itemClass}">
                                                        <input type="checkbox" class="courier-checkbox" value="${courier.id}" ${checked}>
                                                        <label style="margin: 0; cursor: pointer; flex: 1;">
                                                            ${courier.partner_name} (${courier.partner_code})
                                                        </label>
                                                    </div>
                                                `;
                                        });

                                        $('#availableCouriersList').html(availableHtml);

                                        // Update assigned list on checkbox change
                                        $(document).on('change', '.courier-checkbox', function () {
                                            updateAssignedList();
                                        });

                                        updateAssignedList();
                                    }
                                });
                            }
                        }
                    });
                }

                function updateAssignedList() {
                    let assignedHtml = '<h6>Selected:</h6>';
                    let hasAssigned = false;

                    $('.courier-checkbox:checked').each(function () {
                        let parentText = $(this).siblings('label').text();
                        assignedHtml += `<div class="courier-item assigned" style="margin: 4px 0;">✓ ${parentText}</div>`;
                        hasAssigned = true;
                    });

                    if (!hasAssigned) {
                        assignedHtml += '<div style="color: #999; padding: 20px 0; text-align: center;">No couriers assigned</div>';
                    }

                    $('#assignedCouriersList').html(assignedHtml);
                }

                // Save assignments
                $('#saveAssignmentsBtn').on('click', function () {
                    let courierId = $('#selectedCourierId').val();
                    let selectedCouriers = [];

                    $('.courier-checkbox:checked').each(function () {
                        selectedCouriers.push($(this).val());
                    });

                    $.ajax({
                        url: 'api/courier_partner/update_assignments.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            id: courierId,
                            assigned_couriers: selectedCouriers
                        }),
                        success: function (response) {
                            if (response.status === 'success') {
                                showtoastt('Courier assignments updated successfully', 'success');
                                $('#assignCouriersModal').modal('hide');
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
</div> <!-- .content wrapper -->
</body>

<!-- Assign Couriers Modal -->
<div class="modal fade" id="assignCouriersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Courier Partners</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedCourierId">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Available Courier Partners</label>
                        <div id="availableCouriersList"
                            style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                            <!-- Will be populated via AJAX -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned Courier Partners</label>
                        <div id="assignedCouriersList"
                            style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                            <!-- Will be populated via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAssignmentsBtn">Save Assignments</button>
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

    .form-control {
        padding: 5px !important;
    }

    .form-select {
        padding: 5px !important;
    }

    .courier-item {
        display: flex;
        align-items: center;
        padding: 8px;
        margin: 4px 0;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        cursor: pointer;
        background-color: #f5f5f5;
        transition: all 0.2s;
    }

    .courier-item:hover {
        background-color: #e8f4f8;
        border-color: #0d6efd;
    }

    .courier-item.assigned {
        background-color: #d4edda;
        border-color: #28a745;
    }

    .courier-item input[type="checkbox"] {
        margin-right: 8px;
        cursor: pointer;
    }
</style>


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

    #courierTable,
    #courierTable * {
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

</html>