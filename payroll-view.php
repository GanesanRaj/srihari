<?php
require_once 'header.php';
require_once 'config/middleware.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
?>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column no-print">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Payroll Details / Pay Slip</h4>
                    </div>
                    <div class="text-end">
                        <button onclick="window.print()" class="btn btn-sm btn-soft-primary">
                            <i class="ti ti-printer me-1"></i>Print Pay Slip
                        </button>
                        <a href="payroll-list.php" class="btn btn-sm btn-soft-secondary ms-1">
                            <i class="ti ti-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card" id="printablePayslip">
                            <div class="card-body">
                                <!-- Payslip Header -->
                                <div class="text-center mb-4">
                                    <div id="companyLogoContainer" class="mb-2">
                                        <img id="companyLogoDisplay" src="" alt="Logo" style="max-height: 80px; display: none;">
                                    </div>
                                    <h3 class="fw-bold mb-1" id="companyNameDisplay">COMPANY NAME</h3>
                                    <p class="text-muted mb-0" id="companyAddressDisplay">Company Address</p>
                                    <p class="text-muted small mb-0" id="companyGstDisplay" style="display: none;"></p>
                                    <h4 class="mt-3 text-decoration-underline">PAY SLIP</h4>
                                    <h5 id="salaryMonthDisplay"></h5>
                                </div>

                                <!-- Employee Details -->
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="150" class="fw-bold">Employee Name:</td>
                                                <td id="employeeName"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Employee Code:</td>
                                                <td id="employeeCode"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Designation:</td>
                                                <td id="employeeDesignation"></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-6 text-end">
                                        <table class="table table-sm table-borderless text-end">
                                            <tr>
                                                <td class="fw-bold">Branch:</td>
                                                <td id="employeeBranch"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Working Days:</td>
                                                <td id="workingDays"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Attendance Days:</td>
                                                <td id="attendanceDays"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <!-- Salary Breakdown -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Earnings</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Basic Salary</td>
                                                    <td class="text-end" id="basicSalary"></td>
                                                </tr>
                                                <tr>
                                                    <td>HRA</td>
                                                    <td class="text-end" id="hra"></td>
                                                </tr>
                                                <tr>
                                                    <td>DA</td>
                                                    <td class="text-end" id="da"></td>
                                                </tr>
                                                <tr>
                                                    <td>Allowances</td>
                                                    <td class="text-end" id="allowances"></td>
                                                </tr>
                                                <tr>
                                                    <td>Shift Allowance</td>
                                                    <td class="text-end" id="shiftAllowance"></td>
                                                </tr>
                                                <tr class="fw-bold">
                                                    <td>Gross Earnings</td>
                                                    <td class="text-end" id="grossSalary"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Deductions</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>PF Deduction</td>
                                                    <td class="text-end" id="pfDeduction"></td>
                                                </tr>
                                                <tr>
                                                    <td>Insurance</td>
                                                    <td class="text-end" id="insuranceDeduction"></td>
                                                </tr>
                                                <tr>
                                                    <td>Tax</td>
                                                    <td class="text-end" id="taxDeduction"></td>
                                                </tr>
                                                <tr>
                                                    <td>Other Deductions</td>
                                                    <td class="text-end" id="otherDeductions"></td>
                                                </tr>
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td class="text-end">&nbsp;</td>
                                                </tr>
                                                <tr class="fw-bold">
                                                    <td>Total Deductions</td>
                                                    <td class="text-end" id="totalDeductions"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Net Salary -->
                                <div class="alert alert-secondary mt-3 mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <h4 class="mb-0">Net Salary:</h4>
                                        </div>
                                        <div class="col-6 text-end">
                                            <h3 class="mb-0 fw-bold" id="netSalary"></h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-5 pt-4">
                                    <div class="col-4 text-center">
                                        <hr class="mx-auto w-75">
                                        <p>Employee Signature</p>
                                    </div>
                                    <div class="col-4"></div>
                                    <div class="col-4 text-center">
                                        <hr class="mx-auto w-75">
                                        <p>Authorized Signatory</p>
                                    </div>
                                </div>

                                <div class="mt-4 text-muted small no-print">
                                    <strong>Notes:</strong> <span id="payrollNotes"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(document).ready(function () {
                    const payrollId = '<?= $id ?>';
                    if (!payrollId) {
                        alert('No payroll ID provided');
                        window.location.href = 'payroll-list.php';
                        return;
                    }

                    $.get('api/payroll/read_single.php?id=' + payrollId, function (response) {
                        if (response.success) {
                            const data = response.data;

                            // Month/Year formatting
                            const monthDate = new Date(data.salary_month);
                            const monthStr = monthDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long'
                            });
                            $('#salaryMonthDisplay').text('Month of ' + monthStr);

                            // Company details
                            $('#companyNameDisplay').text(data.company_name || 'N/A');
                            
                            // Logo handling
                            if (data.company_logo) {
                                $('#companyLogoDisplay').attr('src', data.company_logo).show();
                            }

                            // GST handling
                            if (data.gst_no) {
                                $('#companyGstDisplay').text('GST NO: ' + data.gst_no).show();
                            }

                            let address = data.company_address || '';
                            if (data.company_city) address += ', ' + data.company_city;
                            if (data.company_state) address += ', ' + data.company_state;
                            if (data.company_pincode) address += ' - ' + data.company_pincode;
                            if (data.company_phone) address += ' | Phone: ' + data.company_phone;
                            $('#companyAddressDisplay').text(address || 'N/A');

                            // Employee details
                            $('#employeeName').text(data.employee_name);
                            $('#employeeCode').text(data.employee_id || '-');
                            $('#employeeDesignation').text(data.employee_designation || '-');
                            $('#employeeBranch').text(data.employee_branch || '-');
                            $('#workingDays').text(data.working_days);
                            $('#attendanceDays').text(data.attendance_days);

                            // Money format
                            const formatter = new Intl.NumberFormat('en-IN', {
                                style: 'currency',
                                currency: 'INR'
                            });

                            // Earnings
                            $('#basicSalary').text(formatter.format(data.basic_salary));
                            $('#hra').text(formatter.format(data.hra));
                            $('#da').text(formatter.format(data.da));
                            $('#allowances').text(formatter.format(data.allowances));
                            $('#shiftAllowance').text(formatter.format(data.shift_allowance));
                            $('#grossSalary').text(formatter.format(data.gross_salary));

                            // Deductions
                            $('#pfDeduction').text(formatter.format(data.pf_deduction));
                            $('#insuranceDeduction').text(formatter.format(data.insurance_deduction));
                            $('#taxDeduction').text(formatter.format(data.tax_deduction));
                            $('#otherDeductions').text(formatter.format(data.other_deductions));
                            $('#totalDeductions').text(formatter.format(data.total_deductions));

                            // Net
                            $('#netSalary').text(formatter.format(data.net_salary));
                            $('#payrollNotes').text(data.notes || 'N/A');

                            // Auto-print if requested
                            const urlParams = new URLSearchParams(window.location.search);
                            if (urlParams.get('print') === 'true') {
                                setTimeout(function () {
                                    window.print();
                                }, 1000);
                            }

                        } else {
                            alert('Error: ' + response.message);
                        }
                    });
                });
            </script>

            <style>
                @media print {
                    .no-print {
                        display: none !important;
                    }

                    .wrapper {
                        padding: 0 !important;
                        margin: 0 !important;
                    }

                    .content-page {
                        margin: 0 !important;
                        padding: 0 !important;
                    }

                    .card {
                        border: none !important;
                        box-shadow: none !important;
                    }

                    .sidebar,
                    .topbar,
                    footer {
                        display: none !important;
                    }

                    body {
                        background-color: white !important;
                    }

                    #printablePayslip {
                        width: 100%;
                        margin: 0;
                    }
                }

                #printablePayslip {
                    color: #000;
                }

                .table-bordered th,
                .table-bordered td {
                    border: 1px solid #000 !important;
                }
            </style>
        </div>
    </div>
</body>

</html>