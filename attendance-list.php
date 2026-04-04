<?php
require_once 'header.php';
require_once 'config/middleware.php';

$can_add = true;
$can_edit = true;
$can_delete = true;
?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Attendance Management</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="attendance-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i>Mark Attendance
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
                                        <label class="form-label">Date Range</label>
                                        <div id="attendance-range"
                                            class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                            <i class="ti ti-calendar fs-14"></i>
                                            <span class="fs-12 fw-medium"></span>
                                            <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                        </div>
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
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="leave">Leave</option>
                                            <option value="half_day">Half Day</option>
                                            <option value="weekend">Weekend</option>
                                            <option value="holiday">Holiday</option>
                                        </select>
                                    </div>
                                </div>

                                <table id="attendanceTable" class="table table-hover dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Shift</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Notes</th>
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
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

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

                    // Date Range Picker Setup
                    let startDate = moment().startOf('month').format('YYYY-MM-DD');
                    let endDate = moment().endOf('month').format('YYYY-MM-DD');

                    function cb(start, end) {
                        $('#attendance-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (typeof table !== 'undefined') table.ajax.reload();
                    }

                    $('#attendance-range').daterangepicker({
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
                    var table = $('#attendanceTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/attendance/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.from_date = startDate;
                                d.to_date = endDate;
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
                            data: 'attendance_date'
                        },
                        {
                            data: 'status',
                            render: function (data) {
                                var statusText = {
                                    'present': '<span class="text-success fw-semibold">PRESENT</span>',
                                    'absent': '<span class="text-danger fw-semibold">ABSENT</span>',
                                    'leave': '<span class="text-warning fw-semibold">LEAVE</span>',
                                    'half_day': '<span class="text-info fw-semibold">HALF DAY</span>',
                                    'weekend': '<span class="text-secondary fw-semibold">WEEKEND</span>',
                                    'holiday': '<span class="text-primary fw-semibold">HOLIDAY</span>'
                                };
                                return statusText[data] || data.toUpperCase();
                            }
                        },
                        {
                            data: 'shift_name'
                        },
                        {
                            data: 'check_in_time'
                        },
                        {
                            data: 'check_out_time'
                        },
                        {
                            data: 'notes'
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function (data, type, row) {
                                var actions = '<div class="d-flex gap-1">';
                                if (userPermissions.canEdit) {
                                    actions += '<a href="attendance-add.php?id=' + row.id +
                                        '" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ti ti-edit"></i></a>';
                                }
                                if (userPermissions.canDelete) {
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
                    $('#employeeFilter, #statusFilter').on('change', function () {
                        table.ajax.reload();
                    });

                    // Delete handler
                    $(document).on('click', '.delete-btn', function () {
                        var id = $(this).data('id');
                        if (confirmDelete('Are you sure you want to delete this attendance record?')) {
                            $.ajax({
                                url: 'api/attendance/delete.php',
                                type: 'POST',
                                data: {
                                    id: id
                                },
                                success: function (response) {
                                    table.ajax.reload();
                                    showtoastt('Attendance deleted successfully', 'success');
                                },
                                error: function () {
                                    showtoastt('Error deleting attendance', 'error');
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
                #attendanceTable,
                #attendanceTable * {
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