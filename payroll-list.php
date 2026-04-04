<?php
require_once 'header.php';
require_once 'config/middleware.php';

$can_add = true;
$can_edit = true;
$can_delete = true;
?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Payroll Management</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="payroll-generate.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i>Generate Payroll
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
                                        <label class="form-label">Month/Year</label>
                                        <input type="month" id="monthFilter" class="form-control form-control-sm"
                                            value="<?= date('Y-m') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Employee</label>
                                        <select id="employeeFilter" class="form-select form-select-sm select2">
                                            <option value="">All Employees</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select id="statusFilter" class="form-select form-select-sm">
                                            <option value="">All Status</option>
                                            <option value="draft">Draft</option>
                                            <option value="approved">Approved</option>
                                            <option value="paid">Paid</option>
                                        </select>
                                    </div>
                                </div>

                                <table id="payrollTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee</th>
                                            <th>Month</th>
                                            <th>Working Days</th>
                                            <th>Attendance Days</th>
                                            <th>Gross Salary</th>
                                            <th>Deductions</th>
                                            <th>Net Salary</th>
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

            <script src="assets/plugins/jquery/jquery.min.js"></script>
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
                    // Load employees
                    $.get('api/employee/get_employees.php', function (response) {
                        var data = response.data || response;
                        if (Array.isArray(data)) {
                            data.forEach(emp => {
                                $('#employeeFilter').append(`<option value="${emp.id}">${emp.name}</option>`);
                            });
                        }
                    });

                    // Initialize DataTable
                    var table = $('#payrollTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/payroll/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.month = $('#monthFilter').val();
                                d.employee_id = $('#employeeFilter').val();
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
                        columns: [{
                            data: 'id'
                        },
                        {
                            data: 'employee_name'
                        },
                        {
                            data: 'salary_month',
                            render: function (data) {
                                return new Date(data).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long'
                                });
                            }
                        },
                        {
                            data: 'working_days'
                        },
                        {
                            data: 'attendance_days'
                        },
                        {
                            data: 'gross_salary',
                            render: function (data) {
                                return '₹' + parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'total_deductions',
                            render: function (data) {
                                return '₹' + parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: 'net_salary',
                            render: function (data) {
                                return '<strong>₹' + parseFloat(data).toFixed(2) + '</strong>';
                            }
                        },
                        {
                            data: 'status',
                            render: function (data) {
                                var statusText = {
                                    'draft': '<span class="text-secondary fw-semibold">DRAFT</span>',
                                    'approved': '<span class="text-primary fw-semibold">APPROVED</span>',
                                    'paid': '<span class="text-success fw-semibold">PAID</span>'
                                };
                                return statusText[data] || data.toUpperCase();
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function (data, type, row) {
                                var actions = '<div class="d-flex gap-1">';
                                actions += '<a href="payroll-view.php?id=' + row.id +
                                    '" class="btn btn-sm btn-soft-dark" title="View"><i class="ti ti-eye"></i></a>';

                                actions += '<a href="payroll-view.php?id=' + row.id + '&print=true" class="btn btn-sm btn-soft-primary" title="Pay Slip"><i class="ti ti-printer"></i></a>';

                                if (row.status === 'draft') {
                                    actions += '<button class="btn btn-sm btn-soft-success approve-btn" data-id="' +
                                        row.id + '" title="Approve"><i class="ti ti-check"></i></button>';
                                }

                                if (row.status === 'approved') {
                                    actions += '<button class="btn btn-sm btn-soft-info paid-btn" data-id="' +
                                        row.id + '" title="Mark Paid"><i class="ti ti-cash"></i></button>';
                                }

                                if (userPermissions.canDelete && row.status === 'draft') {
                                    actions += '<button class="btn btn-sm btn-soft-danger delete-btn" data-id="' +
                                        row.id + '" title="Delete"><i class="ti ti-trash"></i></button>';
                                }

                                actions += '</div>';
                                return actions;
                            }
                        }
                        ],
                        order: [
                            [2, 'desc']
                        ],
                        pageLength: 25,
                        language: {
                            paginate: {
                                previous: "<i class='ti ti-chevron-left'></i>",
                                next: "<i class='ti ti-chevron-right'></i>"
                            }
                        }
                    });

                    // Filter triggers
                    $('#monthFilter, #employeeFilter, #statusFilter').on('change', function () {
                        table.ajax.reload();
                    });

                    // Approve handler
                    $(document).on('click', '.approve-btn', function () {
                        if (confirm('Approve this payroll?')) {
                            var id = $(this).data('id');
                            $.ajax({
                                url: 'api/payroll/update_status.php',
                                type: 'POST',
                                data: {
                                    id: id,
                                    status: 'approved'
                                },
                                success: function (response) {
                                    table.ajax.reload();
                                    showtoastt('Payroll approved successfully', 'success');
                                }
                            });
                        }
                    });

                    // Mark as Paid handler
                    $(document).on('click', '.paid-btn', function () {
                        if (confirm('Mark this payroll as paid?')) {
                            var id = $(this).data('id');
                            $.ajax({
                                url: 'api/payroll/update_status.php',
                                type: 'POST',
                                data: {
                                    id: id,
                                    status: 'paid'
                                },
                                success: function (response) {
                                    table.ajax.reload();
                                    showtoastt('Payroll marked as paid', 'success');
                                }
                            });
                        }
                    });

                    // Delete handler
                    $(document).on('click', '.delete-btn', function () {
                        var id = $(this).data('id');
                        if (confirmDelete('Are you sure you want to delete this payroll?')) {
                            $.ajax({
                                url: 'api/payroll/delete.php',
                                type: 'POST',
                                data: {
                                    id: id
                                },
                                success: function (response) {
                                    table.ajax.reload();
                                    showtoastt('Payroll deleted successfully', 'success');
                                }
                            });
                        }
                    });
                });
            </script>

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

                #payrollTable,
                #payrollTable * {
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