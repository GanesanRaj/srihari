<?php
require_once 'header.php';
require_once 'config/middleware.php';

$edit_mode = false;
$attendance = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $attendance_id = $_GET['id'];

    require_once 'config/db.php';
    $stmt = $pdo->prepare("SELECT * FROM tbl_attendance WHERE id = ?");
    $stmt->execute([$attendance_id]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-6">
                            <h4 class="mb-0"><?= $edit_mode ? 'Edit Attendance' : 'Bulk Attendance' ?></h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="attendance-list.php">
                                <button type="button" class="btn btn-sm rounded-pill btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if ($edit_mode): ?>
                            <!-- SINGLE EDIT MODE -->
                            <form id="attendanceForm">
                                <input type="hidden" name="id" value="<?= $attendance['id'] ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                                        <select class="form-select select2" name="employee_id" required>
                                            <option value="">Select Employee</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Attendance Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="attendance_date"
                                            value="<?= $attendance['attendance_date'] ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="present" <?= $attendance['status'] == 'present' ? 'selected' : '' ?>>Present
                                            </option>
                                            <option value="absent" <?= $attendance['status'] == 'absent' ? 'selected' : '' ?>>
                                                Absent
                                            </option>
                                            <option value="leave" <?= $attendance['status'] == 'leave' ? 'selected' : '' ?>>
                                                Leave</option>
                                            <option value="half_day" <?= $attendance['status'] == 'half_day' ? 'selected' : '' ?>>Half Day
                                            </option>
                                            <option value="weekend" <?= $attendance['status'] == 'weekend' ? 'selected' : '' ?>>Weekend</option>
                                            <option value="holiday" <?= $attendance['status'] == 'holiday' ? 'selected' : '' ?>>Holiday</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Shift</label>
                                        <select class="form-select select2" name="shift_id">
                                            <option value="">No Shift</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Check In Time</label>
                                        <input type="time" class="form-control" name="check_in_time"
                                            value="<?= $attendance['check_in_time'] ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Check Out Time</label>
                                        <input type="time" class="form-control" name="check_out_time"
                                            value="<?= $attendance['check_out_time'] ?>">
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes"
                                            rows="3"><?= htmlspecialchars($attendance['notes']) ?></textarea>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line"></i> Update Attendance
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- BULK ADD MODE -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Attendance Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="bulk_attendance_date"
                                        value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Default Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="bulk_default_status">
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="leave">Leave</option>
                                        <option value="half_day">Half Day</option>
                                        <option value="weekend">Weekend</option>
                                        <option value="holiday">Holiday</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Branch Filter</label>
                                    <select class="form-select" id="bulk_branch_filter">
                                        <option value="">All Branches</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary w-100" id="loadEmployeesBtn">
                                        <i class="ri-refresh-line"></i> Load Employees
                                    </button>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-success btn-sm" id="markAllPresentBtn">
                                        <i class="ri-check-double-line"></i> Mark All Present
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" id="markAllAbsentBtn">
                                        <i class="ri-close-circle-line"></i> Mark All Absent
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-bordered table-sm" id="bulkEmployeeTable">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAllEmployees">
                                            </th>
                                            <th width="10%">ID</th>
                                            <th width="20%">Employee Name</th>
                                            <th width="15%">Branch</th>
                                            <th width="15%">Shift</th>
                                            <th width="15%">Status</th>
                                            <th width="10%">Check In</th>
                                            <th width="10%">Check Out</th>
                                            <th width="20%">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkEmployeeList">
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                Select filters and click "Load Employees"
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-primary" id="saveBulkAttendance">
                                    <i class="ri-save-line"></i> Save Bulk Attendance
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>


            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                $(document).ready(function () {
                    $('.select2').select2({ width: '100%' });

                    <?php if ($edit_mode): ?>
                        // ====== SINGLE EDIT MODE LOGIC ======

                        // Load employees (for single edit)
                        $.get('api/employee/get_employees.php', function (response) {
                            var data = response.data || response;
                            if (Array.isArray(data)) {
                                data.forEach(emp => {
                                    var selected = <?= $attendance['employee_id'] ?>;
                                    $('select[name="employee_id"]').append(
                                        `<option value="${emp.id}" ${emp.id == selected ? 'selected' : ''}>${emp.name}</option>`
                                    );
                                });
                            }
                        });

                        // Load shifts (for single edit)
                        $.get('api/shift/read.php?length=100', function (response) {
                            response.data.forEach(shift => {
                                var selected = <?= $attendance['shift_id'] ?: 0 ?>;
                                $('select[name="shift_id"]').append(
                                    `<option value="${shift.id}" ${shift.id == selected ? 'selected' : ''}>${shift.shift_name}</option>`
                                );
                            });
                        });

                        // Form Submission for Single Edit
                        $('#attendanceForm').on('submit', function (e) {
                            e.preventDefault();
                            var formData = $(this).serialize();
                            $.ajax({
                                url: 'api/attendance/update.php',
                                type: 'POST',
                                data: formData,
                                dataType: 'json',
                                success: function (response) {
                                    if (response.success) {
                                        showtoastt('Attendance updated successfully', 'success');
                                        setTimeout(() => window.location.href = 'attendance-list.php', 1000);
                                    } else {
                                        alert('Error: ' + response.message);
                                    }
                                },
                                error: function () {
                                    alert('Error processing request');
                                }
                            });
                        });

                    <?php else: ?>
                        // ====== BULK ADD MODE LOGIC ======

                        // Load branches for bulk filter
                        $.get('api/branch/read.php?length=-1', function (response) {
                            if (response.data) {
                                response.data.forEach(function (branch) {
                                    $('#bulk_branch_filter').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                                });
                            }
                        });

                        // Load employees for bulk marking
                        $('#loadEmployeesBtn').on('click', function () {
                            var date = $('#bulk_attendance_date').val();
                            var branchId = $('#bulk_branch_filter').val();

                            if (!date) {
                                alert('Please select attendance date');
                                return;
                            }

                            // Show loader or text
                            $('#bulkEmployeeList').html('<tr><td colspan="9" class="text-center">Loading...</td></tr>');

                            $.ajax({
                                url: 'api/employee/get_employees.php',
                                type: 'GET',
                                data: { branch_id: branchId },
                                success: function (response) {
                                    var data = response.data || response;
                                    if (Array.isArray(data) && data.length > 0) {
                                        var html = '';
                                        data.forEach(function (emp) {
                                            html += `
                                            <tr data-employee-id="${emp.id}" data-shift-id="${emp.shift_id || ''}">
                                                <td>
                                                    <input type="checkbox" class="employee-checkbox" value="${emp.id}" checked>
                                                </td>
                                                <td>${emp.id}</td>
                                                <td>${emp.name}</td>
                                                <td>${emp.branch_name || 'N/A'}</td>
                                                <td>
                                                    <select class="form-select form-select-sm shift-select">
                                                        <option value="">No Shift</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm status-select">
                                                        <option value="present">Present</option>
                                                        <option value="absent">Absent</option>
                                                        <option value="leave">Leave</option>
                                                        <option value="half_day">Half Day</option>
                                                        <option value="weekend">Weekend</option>
                                                        <option value="holiday">Holiday</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm check-in-input">
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control form-control-sm check-out-input">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm notes-input" placeholder="Notes">
                                                </td>
                                            </tr>
                                        `;
                                        });
                                        $('#bulkEmployeeList').html(html);

                                        // Load shifts for dropdowns
                                        $.get('api/shift/read.php?length=100', function (response) {
                                            if (response.data) {
                                                var shiftData = response.data;
                                                
                                                // Populate each row's shift dropdown
                                                $('#bulkEmployeeList tr').each(function() {
                                                    var row = $(this);
                                                    var empShiftId = row.data('shift-id');
                                                    var select = row.find('.shift-select');
                                                    
                                                    var options = '<option value="">No Shift</option>';
                                                    shiftData.forEach(shift => {
                                                        var selected = (empShiftId == shift.id) ? 'selected' : '';
                                                        options += `<option value="${shift.id}" ${selected}>${shift.shift_name}</option>`;
                                                    });
                                                    select.html(options);
                                                });
                                            }
                                        });

                                        // Set default status
                                        var defaultStatus = $('#bulk_default_status').val();
                                        $('.status-select').val(defaultStatus);
                                    } else {
                                        $('#bulkEmployeeList').html('<tr><td colspan="9" class="text-center text-danger">No employees found</td></tr>');
                                    }
                                },
                                error: function () {
                                    alert('Error loading employees');
                                }
                            });
                        });

                        // Select all employees
                        $('#selectAllEmployees').on('change', function () {
                            $('.employee-checkbox').prop('checked', $(this).prop('checked'));
                        });

                        // Mark all present
                        $('#markAllPresentBtn').on('click', function () {
                            $('.status-select').val('present');
                        });

                        // Mark all absent
                        $('#markAllAbsentBtn').on('click', function () {
                            $('.status-select').val('absent');
                        });

                        // Save bulk attendance
                        $('#saveBulkAttendance').on('click', function () {
                            var date = $('#bulk_attendance_date').val();
                            var attendanceData = [];

                            $('.employee-checkbox:checked').each(function () {
                                var row = $(this).closest('tr');
                                var employeeId = $(this).val();

                                attendanceData.push({
                                    employee_id: employeeId,
                                    attendance_date: date,
                                    status: row.find('.status-select').val(),
                                    shift_id: row.find('.shift-select').val() || null,
                                    check_in_time: row.find('.check-in-input').val(),
                                    check_out_time: row.find('.check-out-input').val(),
                                    notes: row.find('.notes-input').val()
                                });
                            });

                            if (attendanceData.length === 0) {
                                alert('Please select at least one employee');
                                return;
                            }

                            if (confirm(`Mark attendance for ${attendanceData.length} employee(s)?`)) {
                                $.ajax({
                                    url: 'api/attendance/bulk_create.php',
                                    type: 'POST',
                                    data: JSON.stringify(attendanceData),
                                    contentType: 'application/json',
                                    dataType: 'json',
                                    success: function (response) {
                                        if (response.success) {
                                            showtoastt(`Success: ${response.message}`, 'success');
                                            setTimeout(() => window.location.href = 'attendance-list.php', 1500);
                                        } else {
                                            alert('Error: ' + response.message);
                                        }
                                    },
                                    error: function () {
                                        alert('Error saving bulk attendance');
                                    }
                                });
                            }
                        });
                    <?php endif; ?>
                });
            </script>