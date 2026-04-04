<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('serial_allocation', 'is_view');

// Get permissions
$can_add = can_add('serial_allocation');
$can_edit = can_edit('serial_allocation');
$can_delete = can_delete('serial_allocation');
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
            <div class="content">
                <div class="px-0">
                    <!-- List (UI based on shipment-list) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Page Title (same as shipment-list) -->
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"><i data-lucide="layers"></i> Serial Allocation Management</h6>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($can_add): ?>
                                                <a href="serial-allocation-add.php" class="btn btn-primary btn-sm">
                                                    <i class="ti ti-plus me-1"></i> New Allocation
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Filters (branch, service, status, warning low, date range) -->
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <select id="branchFilter" class="form-select form-select-sm">
                                                <option value="">All Branches</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="serviceTypeFilter" class="form-select form-select-sm">
                                                <option value="">All Service Types</option>
                                                <option value="express">Air</option>
                                                <option value="surface">Surface</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="expired">Expired</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="warningLowFilter" class="form-select form-select-sm">
                                                <option value="">All</option>
                                                <option value="1">Warning low (&lt;20% available)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div id="allocation-range"
                                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                    </div>

                                <table id="allocationTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Branch</th>
                                            <th>Service Type</th>
                                            <th>Allocation #</th>
                                            <th>Serial Range</th>
                                            <th>Total</th>
                                            <th>Used</th>
                                            <th>Available</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th width="180">Actions</th>
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
        <!-- Status Update Modal -->
        <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fs-14 fw-semibold" id="statusModalLabel">Change Status</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="statusAllocationId">
                        <div class="mb-3">
                            <label for="newStatusSelect" class="form-label fs-13">Select New Status</label>
                            <select class="form-select form-select-sm" id="newStatusSelect">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" id="saveStatusBtn">Update Status</button>
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
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                $(document).ready(function () {
                    // Load branches for filter
                    $.get('api/branch/read.php?length=1000', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branchFilter').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                        }
                    });

                    // Date Range Picker Setup
                    let startDate = moment().startOf('month').format('YYYY-MM-DD');
                    let endDate = moment().endOf('month').format('YYYY-MM-DD');

                    function cb(start, end) {
                        $('#allocation-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (typeof table !== 'undefined') table.ajax.reload();
                    }

                    $('#allocation-range').daterangepicker({
                        startDate: moment().startOf('month'),
                        endDate: moment().endOf('month'),
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);

                    cb(moment().startOf('month'), moment().endOf('month'));

                    // Initialize DataTable
                    var table = $('#allocationTable').DataTable({
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
                            url: 'api/serial_allocation/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.branch_id = $('#branchFilter').val();
                                d.service_type = $('#serviceTypeFilter').val();
                                d.status = $('#statusFilter').val();
                                d.warning_low = $('#warningLowFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        columns: [
                            { data: 'id' },
                            {
                                data: 'branch_name',
                                render: function (data, type, row) {
                                    return data + '<br><small class="text-muted">' + row.branch_code + '</small>';
                                }
                            },
                            {
                                data: 'service_type_display',
                                render: function (data, type, row) {
                                    let icon = row.service_type === 'air' ? '✈️' : (row.service_type === 'express' ? '⚡' : '🚛');
                                    return '<span class="text-dark">' + icon + ' ' + data + '</span>';
                                }
                            },
                            {
                                data: 'serial_number',
                                render: function (data) {
                                    return '<span class="fw-bold text-dark">' + data + '</span>';
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return '<span class="text-dark">' + row.serial_from + '</span> to <span class="text-dark">' + row.serial_to + '</span>';
                                }
                            },
                            {
                                data: 'total_serials',
                                render: function (data) {
                                    return '<span class="text-dark">' + data + '</span>';
                                }
                            },
                            {
                                data: 'used_serials',
                                render: function (data) {
                                    return '<span class="text-dark">' + data + '</span>';
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                    let avail = parseInt(row.available_serials, 10) || 0;
                                    let total = parseInt(row.total_serials, 10) || 1;
                                    let pct = total > 0 ? (avail / total) : 0;
                                    let isLow = pct < 0.20 && row.status === 'active';
                                    let alert = isLow ? ' <span class="text-danger ms-1">Low!</span>' : '';
                                    return '<span class="text-dark">' + avail + '</span>' + alert;
                                }
                            },
                            {
                                data: 'allocation_date',
                                render: function (data) {
                                    return data ? new Date(data).toLocaleDateString() : '';
                                }
                            },
                            {
                                data: 'status',
                                render: function (data) {
                                    return '<span class="text-dark">' + (data ? data.toUpperCase() : '') + '</span>';
                                }
                            },
                            // {
                            //     data: null,
                            //     orderable: false,
                            //     render: function (data, type, row) {
                            //         let used = parseInt(row.used_serials, 10) || 0;
                            //         let canDeleteRow = used === 0;
                            //         let actions = '<div class="d-flex gap-1">';
                            //         actions += `<a href="serial-allocation-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark" title="View"><i class="ti ti-eye"></i></a>`;
                            //         <?php if ($can_edit): ?>
                            //             actions += `<button class="btn btn-sm btn-soft-warning status-btn" data-id="${row.id}" data-status="${row.status}" title="Change Status"><i class="ti ti-settings"></i></button>`;
                            //         <?php endif; ?>
                            //         <?php if ($can_delete): ?>
                            //             if (canDeleteRow) {
                            //                 actions += `<button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Delete"><i class="ti ti-trash"></i></button>`;
                            //             } else {
                            //                 actions += `<span class="text-muted small" title="Serials used in bookings – cannot delete">—</span>`;
                            //             }
                            //         <?php endif; ?>
                            //         actions += '</div>';
                            //         return actions;
                            //     }
                            // }
                            {
                                data: null,
                                orderable: false,
                                width: "160px", // Increased slightly to fit buttons comfortably
                                render: function (data, type, row) {
                                    // Logic to check if delete is allowed (same as before)
                                    let used = parseInt(row.used_serials, 10) || 0;
                                    let canDeleteRow = used === 0;
                                    
                                    // Container
                                    let actions = '<div class="d-flex align-items-center gap-1">';
                            
                                    // 1. NEW: Export Dropdown (Excel)
                                    actions += `
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-soft-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Export Excel">
                                                <i class="ti ti-file-spreadsheet"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">Export Options</h6></li>
                                                <li>
                                                    <a class="dropdown-item" href="api/serial_allocation/export_allocation_list.php?id=${row.id}&type=available">
                                                        <i class="ti ti-circle-check text-success me-1"></i> Available List
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="api/serial_allocation/export_allocation_list.php?id=${row.id}&type=used">
                                                        <i class="ti ti-circle-x text-danger me-1"></i> Used List
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="api/serial_allocation/export_allocation_list.php?id=${row.id}&type=all">
                                                        <i class="ti ti-list text-primary me-1"></i> All Serial List
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    `;
                            
                                    // 2. EXISTING: View Button
                                    actions += `<a href="serial-allocation-view.php?id=${row.id}" class="btn btn-sm btn-outline-dark" title="View"><i class="ti ti-eye"></i></a>`;
                            
                                    // 3. EXISTING: Edit Status Button (Checks permissions)
                                    <?php if ($can_edit): ?>
                                        actions += `<button class="btn btn-sm btn-soft-warning status-btn" data-id="${row.id}" data-status="${row.status}" title="Change Status"><i class="ti ti-settings"></i></button>`;
                                    <?php endif; ?>
                            
                                    // 4. EXISTING: Delete Button (Checks permissions & usage)
                                    <?php if ($can_delete): ?>
                                        if (canDeleteRow) {
                                            actions += `<button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Delete"><i class="ti ti-trash"></i></button>`;
                                        } else {
                                            // I improved this slightly: instead of a dash, it shows a disabled lock icon, which looks better but means "Cannot Delete"
                                            actions += `<button class="btn btn-sm btn-light text-muted" disabled title="Cannot delete: Serials used"><i class="ti ti-lock"></i></button>`;
                                        }
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

                    // Refresh table on filter change (same as shipment-list)
                    $('#branchFilter, #serviceTypeFilter, #statusFilter, #warningLowFilter').change(function () {
                        table.ajax.reload();
                    });

                    // Check URL for service_type parameter and set filter
                    const urlParams = new URLSearchParams(window.location.search);
                    const serviceType = urlParams.get('service_type');
                    if (serviceType) {
                        $('#serviceTypeFilter').val(serviceType);
                        table.ajax.reload();
                    }

                    // Change status handler
                    // $('#allocationTable').on('click', '.status-btn', function () {
                    //     let id = $(this).data('id');
                    //     let currentStatus = $(this).data('status');

                    //     let newStatus = prompt('Enter new status (active/inactive/expired):', currentStatus);

                    //     if (newStatus && ['active', 'inactive', 'expired'].includes(newStatus.toLowerCase())) {
                    //         $.post('api/serial_allocation/update.php', {
                    //             id: id,
                    //             status: newStatus.toLowerCase()
                    //         }, function (response) {
                    //             if (response.status === 'success') {
                    //                 showtoastt(response.message, 'success');
                    //                 table.ajax.reload();
                    //             } else {
                    //                 showtoastt(response.message, 'error');
                    //             }
                    //         });
                    //     } else if (newStatus) {
                    //         showtoastt('Invalid status. Please enter: active, inactive, or expired', 'error');
                    //     }
                    // });
                    
                    // 1. Open the Software Modal when clicking "Change Status" icon
                    $('#allocationTable').on('click', '.status-btn', function () {
                        let id = $(this).data('id');
                        let currentStatus = $(this).data('status');
                    
                        // Populate hidden ID field
                        $('#statusAllocationId').val(id);
                        
                        // Set the dropdown to the current status
                        if (currentStatus) {
                            $('#newStatusSelect').val(currentStatus.toLowerCase());
                        }
                    
                        // Show the Bootstrap Modal
                        $('#statusModal').modal('show');
                    });
                    
                    // 2. Handle the "Update Status" button click inside the Modal
                    $('#saveStatusBtn').click(function () {
                        let id = $('#statusAllocationId').val();
                        let newStatus = $('#newStatusSelect').val();
                        
                        // Change button text to show loading state
                        let $btn = $(this);
                        let originalText = $btn.text();
                        $btn.html('<i class="ti ti-loader fa-spin me-1"></i> Saving...').prop('disabled', true);
                    
                        // Make the AJAX request
                        $.post('api/serial_allocation/update.php', {
                            id: id,
                            status: newStatus
                        }, function (response) {
                            // Reset button state
                            $btn.html(originalText).prop('disabled', false);
                    
                            if (response.status === 'success') {
                                showtoastt(response.message, 'success');
                                $('#statusModal').modal('hide'); // Close the popup
                                table.ajax.reload(null, false);  // Reload table while staying on the current page
                            } else {
                                showtoastt(response.message, 'error');
                            }
                        }).fail(function() {
                            // Handle server errors smoothly
                            $btn.html(originalText).prop('disabled', false);
                            showtoastt('A server error occurred.', 'error');
                        });
                    });

                    // Delete handler
                    $('#allocationTable').on('click', '.delete-btn', function () {
                        let id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this allocation? This will delete all unused serial numbers.', function () {
                            $.post('api/serial_allocation/delete.php', { id: id }, function (response) {
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
    #allocationTable,
    #allocationTable * {
        color: #000000 !important;
    }

    .form-control-sm,
    .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }
</style>

</html>
