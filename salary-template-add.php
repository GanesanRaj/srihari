<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions
if (isset($_GET['id'])) {
    // Edit Mode
    // require_permission('salary_template', 'is_edit');
} else {
    // Add Mode
    // require_permission('salary_template', 'is_add');
}
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<style>
    .col-form-label {
        padding-bottom: 2px !important;
        padding-top: 2px !important;
        margin-bottom: 2px !important;
    }

    .mb-4 {
        margin-bottom: 3px !important;
    }

    .form-control {
        padding: 5px !important;
    }

    .form-select {
        padding: 5px !important;
    }

    .section-divider {
        margin-top: 20px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #007bff;
    }

    .section-title {
        font-weight: bold;
        color: #007bff;
        font-size: 14px;
        text-transform: uppercase;
    }

    .calculation-box {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .calculation-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-weight: 500;
    }

    .calculation-value {
        font-weight: bold;
        color: #28a745;
    }

    .total-row {
        border-top: 2px solid #007bff;
        padding-top: 10px;
        margin-top: 10px;
    }
</style>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8">
                            <h4 class="mb-0">
                                <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Salary Template
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="salary-template-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="salaryTemplateForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="templateId" name="id" value="">

                            <!-- Basic Information Section -->
                            <div class="row mb-4">
                                <label class="col-sm-2 col-form-label" for="template_name">Template Name <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" id="template_name" name="template_name"
                                        placeholder="e.g., Senior Level, Manager" required>
                                    <div class="invalid-feedback">Template name is required.</div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-2 col-form-label" for="description">Description</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="description" name="description" rows="2"
                                        placeholder="Template description (optional)"></textarea>
                                </div>
                            </div>

                            <!-- Allowances Section -->
                            <div class="section-divider">
                                <span class="section-title">💰 Earnings & Allowances</span>
                            </div>

                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="basic_salary">Basic Salary <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="basic_salary" name="basic_salary" placeholder="0.00" required>
                                            <div class="invalid-feedback">Basic salary is required.</div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="hra">HRA (House Rent)</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency" id="hra"
                                                name="hra" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="da">DA (Dearness Allowance)</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency" id="da"
                                                name="da" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="medical_allowance">Medical
                                            Allowance</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="medical_allowance" name="medical_allowance" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="conveyance">Conveyance
                                            Allowance</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="conveyance" name="conveyance" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="other_allowances">Other
                                            Allowances</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="other_allowances" name="other_allowances" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label">&nbsp;</label>
                                        <div class="col-sm-6">
                                            <div class="alert alert-info" style="margin: 0;">
                                                <small><strong>Total Earnings:</strong> <span
                                                        id="totalEarnings">₹0.00</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deductions Section -->
                            <div class="section-divider">
                                <span class="section-title">📉 Deductions</span>
                            </div>

                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="pf_deduction">PF (Provident
                                            Fund)</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="pf_deduction" name="pf_deduction" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label"
                                            for="insurance_deduction">Insurance</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="insurance_deduction" name="insurance_deduction" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="tax_deduction">Tax
                                            Deduction</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="tax_deduction" name="tax_deduction" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label" for="other_deductions">Other
                                            Deductions</label>
                                        <div class="col-sm-6">
                                            <input type="number" step="0.01" class="form-control currency"
                                                id="other_deductions" name="other_deductions" placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-6 col-form-label">&nbsp;</label>
                                        <div class="col-sm-6">
                                            <div class="alert alert-warning" style="margin: 0;">
                                                <small><strong>Total Deductions:</strong> <span
                                                        id="totalDeductions">₹0.00</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div class="calculation-box">
                                <div class="section-title" style="margin-bottom: 15px;">📊 Salary Calculation Summary
                                </div>
                                <div class="calculation-row">
                                    <span>Total Earnings (Gross):</span>
                                    <span class="calculation-value" id="grossSalary">₹0.00</span>
                                </div>
                                <div class="calculation-row">
                                    <span>Total Deductions:</span>
                                    <span class="calculation-value" id="deductionsSummary">₹0.00</span>
                                </div>
                                <div class="calculation-row total-row">
                                    <span style="font-size: 16px;">Net Salary (Take Home):</span>
                                    <span class="calculation-value" style="font-size: 16px; color: #28a745;"
                                        id="netSalary">₹0.00</span>
                                </div>
                            </div>

                            <!-- Status Section -->
                            <div class="row mb-4" style="margin-top: 20px;">
                                <label class="col-sm-2 col-form-label" for="status">Status</label>
                                <div class="col-sm-4">
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Salary Template
                                    </button>
                                    <a href="salary-template-list.php" class="btn btn-secondary rounded-pill ms-2">
                                        <i class="ri-close-line"></i> Cancel
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Get query parameter
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    let selectedId = getQueryParam("id");

                    // If editing, fetch existing data
                    if (selectedId) {
                        editTemplate(selectedId);
                    }

                    function editTemplate(id) {
                        $.get(`api/salary_template/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#templateId').val(data.id);
                                $('#template_name').val(data.template_name);
                                $('#description').val(data.description);
                                $('#basic_salary').val(data.basic_salary);
                                $('#hra').val(data.hra);
                                $('#da').val(data.da);
                                $('#medical_allowance').val(data.medical_allowance);
                                $('#conveyance').val(data.conveyance);
                                $('#other_allowances').val(data.other_allowances);
                                $('#pf_deduction').val(data.pf_deduction);
                                $('#insurance_deduction').val(data.insurance_deduction);
                                $('#tax_deduction').val(data.tax_deduction);
                                $('#other_deductions').val(data.other_deductions);
                                $('#status').val(data.status);
                                calculateSalary();
                            } else {
                                showtoastt('Template not found', 'error');
                                setTimeout(() => window.location.href = 'salary-template-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading template data', 'error');
                        });
                    }

                    // Calculate salary on input change
                    $('.currency').on('input', function () {
                        calculateSalary();
                    });

                    function calculateSalary() {
                        const basicSalary = parseFloat($('#basic_salary').val()) || 0;
                        const hra = parseFloat($('#hra').val()) || 0;
                        const da = parseFloat($('#da').val()) || 0;
                        const medical = parseFloat($('#medical_allowance').val()) || 0;
                        const conveyance = parseFloat($('#conveyance').val()) || 0;
                        const otherAllowances = parseFloat($('#other_allowances').val()) || 0;

                        const pf = parseFloat($('#pf_deduction').val()) || 0;
                        const insurance = parseFloat($('#insurance_deduction').val()) || 0;
                        const tax = parseFloat($('#tax_deduction').val()) || 0;
                        const otherDeductions = parseFloat($('#other_deductions').val()) || 0;

                        const totalEarnings = basicSalary + hra + da + medical + conveyance + otherAllowances;
                        const totalDeductions = pf + insurance + tax + otherDeductions;
                        const netSalary = totalEarnings - totalDeductions;

                        $('#totalEarnings').text('₹' + totalEarnings.toFixed(2));
                        $('#totalDeductions').text('₹' + totalDeductions.toFixed(2));
                        $('#grossSalary').text('₹' + totalEarnings.toFixed(2));
                        $('#deductionsSummary').text('₹' + totalDeductions.toFixed(2));
                        $('#netSalary').text('₹' + netSalary.toFixed(2));
                    }

                    // Form validation and submission
                    $('#salaryTemplateForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/salary_template/update.php' : 'api/salary_template/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'salary-template-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Salary Template');
                            }
                        });
                    });

                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        $('.is-invalid').removeClass('is-invalid');

                        const templateName = $('#template_name').val().trim();
                        const basicSalary = parseFloat($('#basic_salary').val());

                        if (!templateName) {
                            $('#template_name').addClass('is-invalid');
                            errors.push('Template name is required');
                            isValid = false;
                        }

                        if (!basicSalary || basicSalary < 0) {
                            $('#basic_salary').addClass('is-invalid');
                            errors.push('Basic salary must be greater than 0');
                            isValid = false;
                        }

                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }
                });
            </script>
        </div>
    </div>
</body>

</html>