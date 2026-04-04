<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('company', 'is_edit');
} else {
    // Add Mode
    require_permission('company', 'is_add');
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
                            <a href="company-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Company List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="companyForm" class="row" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" id="companyId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Company Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="company_name">Company Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="company_name"
                                                name="company_name" placeholder="Enter Company Name" required>
                                            <div class="invalid-feedback">Company name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="phone_number">Phone Number <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="phone_number"
                                                name="phone_number" placeholder="10 Digit Mobile No" required>
                                            <div class="invalid-feedback">Valid 10-digit phone number is required.</div>
                                        </div>
                                    </div>

                                    <!-- GST No -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="gst_no">GST No</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="gst_no" name="gst_no"
                                                placeholder="Enter GST Number">
                                            <div class="invalid-feedback">Invalid GST format.</div>
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

                                    <!-- Logo -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="logo">Company Logo</label>
                                        <div class="col-sm-8">
                                            <input type="file" class="form-control" id="logo" name="logo"
                                                accept="image/*">
                                            <div id="currentLogoContainer" class="mt-2 d-none">
                                                <p class="mb-1 small text-muted">Current Logo:</p>
                                                <img id="currentLogo" src="" alt="Current Logo" class="img-thumbnail"
                                                    style="max-height: 60px;">
                                            </div>
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
                                                placeholder="Complete Registered Address" required></textarea>
                                            <div class="invalid-feedback">Address is required.</div>
                                        </div>
                                    </div>

                                    <!-- City -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="city">City <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="city" name="city"
                                                placeholder="City" required>
                                            <div class="invalid-feedback">City is required.</div>
                                        </div>
                                    </div>

                                    <!-- State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="state">State <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="state" name="state"
                                                placeholder="State" required>
                                            <div class="invalid-feedback">State is required.</div>
                                        </div>
                                    </div>

                                    <!-- Pincode -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pincode">Pincode</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pincode" name="pincode"
                                                placeholder="6 Digit Pincode">
                                            <div class="invalid-feedback">Pincode must be 6 digits.</div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="remarks">Remarks</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="remarks" name="remarks" rows="2"
                                                placeholder="Any additional notes..."></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Company
                                    </button>
                                    <a href="company-list.php" class="btn btn-secondary rounded-pill">
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
                $(document).ready(function () {
                    // Initialize Select2
                    if (jQuery().select2) {
                        $('.select2').each(function () {
                            $(this).select2({
                                dropdownParent: $(this).parent(),
                                minimumResultsForSearch: Infinity
                            });
                        });
                    }

                    // Get query parameter
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    let selectedId = getQueryParam("id");

                    // If editing, fetch existing data
                    if (selectedId) {
                        editCompany(selectedId);
                    }

                    function editCompany(id) {
                        $.get(`api/company/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#companyId').val(data.id);
                                $('#company_name').val(data.company_name);
                                $('#phone_number').val(data.phone_number);
                                $('#gst_no').val(data.gst_no);
                                $('#address').val(data.address);
                                $('#city').val(data.city);
                                $('#state').val(data.state);
                                $('#pincode').val(data.pincode);
                                $('#status').val(data.status).trigger('change');
                                $('#remarks').val(data.remarks);

                                if (data.company_logo) {
                                    $('#currentLogo').attr('src', data.company_logo);
                                    $('#currentLogoContainer').removeClass('d-none');
                                }
                            } else {
                                showtoastt('Company not found', 'error');
                                setTimeout(() => window.location.href = 'company-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading company data', 'error');
                        });
                    }

                    // Validation function
                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        $('.is-invalid').removeClass('is-invalid');

                        let fields = [
                            { id: "company_name", message: "Company Name is required.", required: true },
                            {
                                id: "phone_number",
                                message: "Phone Number is required.",
                                required: true,
                                pattern: /^\d{10}$/,
                                patternMessage: "Phone number must be 10 digits"
                            },
                            { id: "address", message: "Address is required.", required: true },
                            { id: "city", message: "City is required.", required: true },
                            { id: "state", message: "State is required.", required: true },
                            {
                                id: "gst_no",
                                message: "GST No is invalid.",
                                required: false,
                                pattern: /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/,
                                patternMessage: "Invalid GST number format"
                            },
                            {
                                id: "pincode",
                                message: "Pincode is invalid.",
                                required: false,
                                pattern: /^\d{6}$/,
                                patternMessage: "Pincode must be 6 digits"
                            }
                        ];

                        fields.forEach(function (field) {
                            let value = $('#' + field.id).val().trim();
                            if (field.required && !value) {
                                $('#' + field.id).addClass('is-invalid');
                                errors.push(field.message);
                                isValid = false;
                            } else if (value && field.pattern && !field.pattern.test(value)) {
                                $('#' + field.id).addClass('is-invalid');
                                errors.push(field.patternMessage || field.message);
                                isValid = false;
                            }
                        });

                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }

                    // Form submission handler
                    $('#companyForm').on('submit', function (e) {
                        e.preventDefault();

                        if (!validateForm()) {
                            return;
                        }

                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/company/update.php' : 'api/company/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'company-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Company');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>