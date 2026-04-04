<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('branch', 'is_edit');
} else {
    // Add Mode
    require_permission('branch', 'is_add');
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
                            <!-- Title Removed as requested -->
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="branch-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Branch List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="branchForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="branchId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Select Company -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="company_id">Select Company <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="company_id" name="company_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Company</option>
                                            </select>
                                            <div class="invalid-feedback">Company is required.</div>
                                        </div>
                                    </div>

                                    <!-- Branch Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_name">Branch Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="branch_name" name="branch_name"
                                                placeholder="e.g., Head Office" required>
                                            <div class="invalid-feedback">Branch name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Branch Code -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_code">Branch Code <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="branch_code" name="branch_code"
                                                placeholder="e.g., HO001" required>
                                            <div class="invalid-feedback">Branch code is required.</div>
                                        </div>
                                    </div>

                                    <!-- Contact No -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="contact_no">Contact No <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="contact_no" name="contact_no"
                                                placeholder="10-digit phone number" required>
                                            <div class="invalid-feedback">Contact number is required.</div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Address -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="address">Address <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="address" name="address" rows="2"
                                                placeholder="Enter full address" required></textarea>
                                            <div class="invalid-feedback">Address is required.</div>
                                        </div>
                                    </div>

                                    <!-- State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="state">State <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="state" name="state"
                                                placeholder="e.g., Karnataka" required>
                                            <div class="invalid-feedback">State is required.</div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="email">Email</label>
                                        <div class="col-sm-8">
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="branch@example.com">
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status">Status</label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="status" name="status"
                                                data-toggle="select2">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="remarks">Remarks</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="remarks" name="remarks" rows="2"
                                                placeholder="Additional notes"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Branch
                                    </button>
                                    <a href="branch-list.php" class="btn btn-secondary rounded-pill">
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
                    // Initialize Select2
                    if ($('.select2').length) {
                        $('.select2').select2({
                            minimumResultsForSearch: Infinity
                        });
                    }

                    // Load companies
                    $.get('api/company/read.php?length=1000&status=active', function (response) {
                        if (response.data) {
                            response.data.forEach(function (company) {
                                $('#company_id').append(`<option value="${company.id}">${company.company_name}</option>`);
                            });
                        }
                    });

                    // Get query parameter
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    let selectedId = getQueryParam("id");

                    // If editing, fetch existing data
                    if (selectedId) {
                        editBranch(selectedId);
                    }

                    function editBranch(id) {
                        $.get(`api/branch/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#branchId').val(data.id);
                                $('#company_id').val(data.company_id).trigger('change');
                                $('#branch_name').val(data.branch_name);
                                $('#branch_code').val(data.branch_code);
                                $('#contact_no').val(data.contact_no);
                                $('#address').val(data.address);
                                $('#state').val(data.state);
                                $('#email').val(data.email);
                                $('#status').val(data.status).trigger('change');
                                $('#remarks').val(data.remarks);
                            } else {
                                showtoastt('Branch not found', 'error');
                                setTimeout(() => window.location.href = 'branch-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading branch data', 'error');
                        });
                    }

                    // Validation function
                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        // Clear previous validation errors
                        $('.is-invalid').removeClass('is-invalid');

                        // Define validation rules
                        let fields = [
                            {
                                id: "company_id",
                                message: "Company is required.",
                                required: true
                            },
                            {
                                id: "branch_name",
                                message: "Branch Name is required.",
                                required: true
                            },
                            {
                                id: "branch_code",
                                message: "Branch Code is required.",
                                required: true
                            },
                            {
                                id: "contact_no",
                                message: "Contact Number is required.",
                                required: true,
                                pattern: /^\d{10}$/,
                                patternMessage: "Contact number must be 10 digits"
                            },
                            {
                                id: "address",
                                message: "Address is required.",
                                required: true
                            },
                            {
                                id: "state",
                                message: "State is required.",
                                required: true
                            },
                            {
                                id: "email",
                                message: "Email is invalid.",
                                required: false,
                                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                patternMessage: "Invalid email format"
                            }
                        ];

                        // Validate each field
                        fields.forEach(function (field) {
                            let value = $('#' + field.id).val().trim();

                            // Check required fields
                            if (field.required && !value) {
                                $('#' + field.id).addClass('is-invalid');
                                errors.push(field.message);
                                isValid = false;
                            }
                            // Check pattern validation (if value exists and pattern is defined)
                            else if (value && field.pattern && !field.pattern.test(value)) {
                                $('#' + field.id).addClass('is-invalid');
                                errors.push(field.patternMessage || field.message);
                                isValid = false;
                            }
                        });

                        // Show first error if validation fails
                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }

                    // Form submission handler
                    $('#branchForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/branch/update.php' : 'api/branch/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'branch-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Branch');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>