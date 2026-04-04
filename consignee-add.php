<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('consignee', 'is_edit');
} else {
    // Add Mode
    require_permission('consignee', 'is_add');
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
                            <h4 class="mb-0">
                                <?= isset($_GET['id']) ? 'Edit Consignee' : 'Add Consignee' ?>
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="consignee-list.php"><button type="button"
                                    class="btn btn-sm rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Consignee List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="consigneeForm" class="row" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" id="consigneeId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Select Branch -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_id">Select Branch <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="branch_id" name="branch_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Branch</option>
                                            </select>
                                            <div class="invalid-feedback">Branch is required.</div>
                                        </div>
                                    </div>

                                    <!-- Select Client -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="client_id">Select Client <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="client_id" name="client_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Client</option>
                                            </select>
                                            <div class="invalid-feedback">Client is required.</div>
                                        </div>
                                    </div>

                                    <!-- Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Enter Name" required>
                                            <div class="invalid-feedback">Name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="email">Email Address</label>
                                        <div class="col-sm-8">
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Enter Email Address">
                                        </div>
                                    </div>

                                    <!-- Contact Number -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="contact_no">Contact Number <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="contact_no" name="contact_no"
                                                placeholder="Enter Mobile Number" required>
                                            <div class="invalid-feedback">Contact number is required.</div>
                                        </div>
                                    </div>

                                    <!-- Alternate Contact Number -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="alt_contact_no">Alternate Contact
                                            <br> Number</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="alt_contact_no"
                                                name="alt_contact_no" placeholder="Enter Alternate Mobile Number">
                                        </div>
                                    </div>

                                    <!-- GST Number -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="gst_number">GST Number</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="gst_number" name="gst_number"
                                                placeholder="Enter GST Number">
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
                                                placeholder="Address" required></textarea>
                                            <div class="invalid-feedback">Address is required.</div>
                                        </div>
                                    </div>

                                    <!-- Location -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="location">Location</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="location" name="location"
                                                placeholder="Enter Location">
                                        </div>
                                    </div>

                                    <!-- City -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="city">City <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="city" name="city"
                                                placeholder="Enter City" required>
                                            <div class="invalid-feedback">City is required.</div>
                                        </div>
                                    </div>

                                    <!-- State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="state">State <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="state" name="state"
                                                placeholder="Enter State" required>
                                            <div class="invalid-feedback">State is required.</div>
                                        </div>
                                    </div>

                                    <!-- Pincode -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pincode">Pincode <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pincode" name="pincode"
                                                placeholder="Enter Pincode" required>
                                            <div class="invalid-feedback">Pincode is required.</div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status">Status <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="status" name="status"
                                                data-toggle="select2">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Audit Info (Read Only, only show in Update) -->
                                    <div class="audit-info" style="display:none;">
                                        <hr>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Created At</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control-plaintext" id="created_at"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Updated At</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control-plaintext" id="updated_at"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Created By</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control-plaintext" id="created_by_name"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Updated By</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control-plaintext" id="updated_by_name"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Consignee
                                    </button>
                                    <a href="consignee-list.php" class="btn btn-sm btn-secondary rounded-pill">
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

                    // Load branches
                    $.get('api/branch/read.php?length=1000&status=active', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branch_id').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                        }
                    });

                    // Load clients
                    $.get('api/client/read.php?length=1000&status=active', function (response) {
                        if (response.data) {
                            response.data.forEach(function (client) {
                                $('#client_id').append(`<option value="${client.id}">${client.client_name}</option>`);
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
                        editConsignee(selectedId);
                        $('.audit-info').show();
                    }

                    function editConsignee(id) {
                        $.get(`api/consignee/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#consigneeId').val(data.id);
                                $('#branch_id').val(data.branch_id).trigger('change');
                                $('#client_id').val(data.client_id).trigger('change');
                                $('#name').val(data.name);
                                $('#contact_no').val(data.contact_no);
                                $('#alt_contact_no').val(data.alt_contact_no);
                                $('#email').val(data.email);
                                $('#gst_number').val(data.gst_number);
                                $('#address').val(data.address);
                                $('#location').val(data.location);
                                $('#city').val(data.city);
                                $('#state').val(data.state);
                                $('#pincode').val(data.pincode);
                                $('#status').val(data.status).trigger('change');

                                // Audit info
                                $('#created_at').val(data.created_at);
                                $('#updated_at').val(data.updated_at);
                                $('#created_by_name').val(data.created_by_name);
                                $('#updated_by_name').val(data.updated_by_name);

                            } else {
                                showtoastt('Consignee not found', 'error');
                                setTimeout(() => window.location.href = 'consignee-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading consignee data', 'error');
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
                                id: "branch_id",
                                message: "Branch is required.",
                                required: true
                            },
                            {
                                id: "client_id",
                                message: "Client is required.",
                                required: true
                            },
                            {
                                id: "name",
                                message: "Name is required.",
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
                                id: "city",
                                message: "City is required.",
                                required: true
                            },
                            {
                                id: "state",
                                message: "State is required.",
                                required: true
                            },
                            {
                                id: "pincode",
                                message: "Pincode is required.",
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
                    $('#consigneeForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = 'api/consignee/create.php'; // The same PHP file handles both update and create based on ID existence

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'consignee-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Consignee');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>