<?php
require_once 'header.php';
require_once 'config/middleware.php';
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
                        <div class="col-md-8">
                            <h4 class="mb-0">Generate Payroll</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="payroll-list.php">
                                <button type="button" class="btn btn-xs rounded-pill btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="payrollGenerateForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="employee_id" id="employeeSelect" required>
                                        <option value="">Select Employee</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Salary Month <span class="text-danger">*</span></label>
                                    <input type="month" class="form-control" name="salary_month"
                                        value="<?= date('Y-m') ?>" required>
                                </div>
                            </div>

                            <div id="calculationDetails" style="display: none;">
                                <hr>
                                <h5>Payroll Details</h5>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Working Days</label>
                                        <input type="number" class="form-control" name="working_days" id="workingDays"
                                            readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Attendance Days</label>
                                        <input type="number" class="form-control" name="attendance_days"
                                            id="attendanceDays" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Leave Days</label>
                                        <input type="number" class="form-control" name="leave_days" id="leaveDays"
                                            readonly>
                                    </div>
                                </div>

                                <h6>Salary Components</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Basic Salary</label>
                                        <input type="number" step="0.01" class="form-control" name="basic_salary"
                                            id="basicSalary" readonly>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">HRA</label>
                                        <input type="number" step="0.01" class="form-control" name="hra" id="hra"
                                            readonly>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">DA</label>
                                        <input type="number" step="0.01" class="form-control" name="da" id="da"
                                            readonly>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Allowances</label>
                                        <input type="number" step="0.01" class="form-control" name="allowances"
                                            id="allowances" readonly>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Shift Allowance</label>
                                        <input type="number" step="0.01" class="form-control" name="shift_allowance"
                                            id="shiftAllowance" readonly>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label"><strong>Gross Salary</strong></label>
                                        <input type="number" step="0.01" class="form-control" name="gross_salary"
                                            id="grossSalary" readonly>
                                    </div>
                                </div>

                                <h6>Deductions</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">PF Deduction</label>
                                        <input type="number" step="0.01" class="form-control" name="pf_deduction"
                                            id="pfDeduction" readonly>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Insurance</label>
                                        <input type="number" step="0.01" class="form-control" name="insurance_deduction"
                                            id="insuranceDeduction" readonly>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Tax</label>
                                        <input type="number" step="0.01" class="form-control" name="tax_deduction"
                                            id="taxDeduction" readonly>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label"><strong>Total Deductions</strong></label>
                                        <input type="number" step="0.01" class="form-control" name="total_deductions"
                                            id="totalDeductions" readonly>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">
                                            <h5>Net Salary</h5>
                                        </label>
                                        <input type="number" step="0.01" class="form-control form-control-lg"
                                            name="net_salary" id="netSalary" readonly
                                            style="font-size: 1.5rem; font-weight: bold;">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="2"></textarea>
                                    </div>
                                </div>

                                <input type="hidden" name="salary_template_id" id="salaryTemplateId">
                                <input type="hidden" name="per_day_salary" id="perDaySalary">
                                <input type="hidden" name="half_days" id="halfDays">
                                <input type="hidden" name="absence_days" id="absenceDays">
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" id="calculateBtn" class="btn btn-info">
                                    <i class="ri-calculator-line"></i> Calculate Payroll
                                </button>
                                <button type="submit" id="saveBtn" class="btn btn-primary" style="display: none;">
                                    <i class="ri-save-line"></i> Save Payroll
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                $(document).ready(function () {
                    $('.select2').select2();

                    // Load employees
                    $.get('api/employee/get_employees.php', function (response) {
                        var data = response.data || response;
                        if (Array.isArray(data)) {
                            data.forEach(emp => {
                                $('#employeeSelect').append(`<option value="${emp.id}">${emp.name}</option>`);
                            });
                        }
                    });

                    // Calculate Payroll
                    $('#calculateBtn').on('click', function () {
                        var employee_id = $('select[name="employee_id"]').val();
                        var salary_month = $('input[name="salary_month"]').val();

                        if (!employee_id || !salary_month) {
                            alert('Please select employee and month');
                            return;
                        }

                        $.ajax({
                            url: 'api/payroll/calculate.php',
                            type: 'POST',
                            data: {
                                employee_id: employee_id,
                                salary_month: salary_month + '-01'
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    var data = response.data;

                                    // Fill in the form
                                    $('#workingDays').val(data.working_days);
                                    $('#attendanceDays').val(data.attendance_days);
                                    $('#leaveDays').val(data.leave_days);
                                    $('#halfDays').val(data.half_days);
                                    $('#absenceDays').val(data.absence_days);

                                    $('#basicSalary').val(data.basic_salary);
                                    $('#hra').val(data.hra);
                                    $('#da').val(data.da);
                                    $('#allowances').val(data.allowances);
                                    $('#shiftAllowance').val(data.shift_allowance);
                                    $('#grossSalary').val(data.gross_salary);

                                    $('#pfDeduction').val(data.pf_deduction);
                                    $('#insuranceDeduction').val(data.insurance_deduction);
                                    $('#taxDeduction').val(data.tax_deduction);
                                    $('#totalDeductions').val(data.total_deductions);

                                    $('#netSalary').val(data.net_salary);

                                    $('#salaryTemplateId').val(data.salary_template_id);
                                    $('#perDaySalary').val(data.per_day_salary);

                                    $('#calculationDetails').show();
                                    $('#saveBtn').show();
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function () {
                                alert('Error calculating payroll');
                            }
                        });
                    });

                    // Submit form
                    $('#payrollGenerateForm').on('submit', function (e) {
                        e.preventDefault();

                        var formData = $(this).serialize();

                        $.ajax({
                            url: 'api/payroll/create.php',
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    alert(response.message);
                                    window.location.href = 'payroll-list.php';
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function () {
                                alert('Error saving payroll');
                            }
                        });
                    });
                });
            </script>