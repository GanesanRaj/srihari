<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('pickuppoint', 'is_edit');
} else {
    // Add Mode
    require_permission('pickuppoint', 'is_add');
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

    .section-header {
        background-color: #f8f9fa;
        padding: 8px 12px;
        margin: 15px 0 10px 0;
        border-left: 3px solid #007bff;
        font-weight: 600;
        font-size: 14px;
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
                            <a href="pickuppoint-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Pickup Point List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="pickupPointForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="pickupPointId" name="id" value="">

                            <!-- Basic Information Section -->
                            <div class="section-header">Basic Information</div>

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

                                    <!-- Courier Partner -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="courier_id">Courier Partner <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="courier_id" name="courier_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Courier</option>
                                            </select>
                                            <div class="invalid-feedback">Courier partner is required.</div>
                                        </div>
                                    </div>

                                    <!-- Pickup Point Code -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pickup_point_code">Pickup Point
                                            Code</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pickup_point_code"
                                                name="pickup_point_code" placeholder="e.g., PP001">
                                            <small class="text-muted">Non-unique identifier</small>
                                        </div>
                                    </div>

                                    <!-- Warehouse Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Warehouse Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="e.g., Mumbai Warehouse" required>
                                            <small class="text-muted">Case-sensitive for Delhivery</small>
                                            <div class="invalid-feedback">Warehouse name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Registered Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="registered_name">Registered
                                            Name</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="registered_name"
                                                name="registered_name" placeholder="Registered account name">
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Phone -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="phone">Phone <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                placeholder="10-digit phone number" required>
                                            <div class="invalid-feedback">Phone number is required.</div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="email">
                                            Email <span class="text-danger d-none" id="emailRequiredMark">*</span>
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="warehouse@example.com">
                                            <div class="invalid-feedback">Email is required.</div>
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="address">Address <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="address" name="address" rows="2"
                                                placeholder="Complete pickup address" required></textarea>
                                            <div class="invalid-feedback">Address is required.</div>
                                        </div>
                                    </div>

                                    <!-- City -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="city">City <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="city" name="city"
                                                placeholder="e.g., Mumbai" required>
                                            <div class="invalid-feedback">City is required.</div>
                                        </div>
                                    </div>

                                    <!-- Pickup State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pickup_state">Pickup State <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pickup_state" name="pickup_state"
                                                placeholder="e.g., Maharashtra" required>
                                            <div class="invalid-feedback">Pickup state is required.</div>
                                        </div>
                                    </div>

                                    <!-- PIN Code -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pin">PIN Code <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pin" name="pin"
                                                placeholder="6-digit PIN code" required>
                                            <div class="invalid-feedback">PIN code is required.</div>
                                        </div>
                                    </div>

                                    <!-- Country -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="country">Country</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="country" name="country"
                                                value="India" placeholder="Country">
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Return Address Section -->
                            <div class="section-header">Return Address Information</div>

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Return Address -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="return_address">Return Address <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="return_address" name="return_address"
                                                rows="2" placeholder="Complete return address" required></textarea>
                                            <div class="invalid-feedback">Return address is required.</div>
                                        </div>
                                    </div>

                                    <!-- Return City -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="return_city">Return City</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="return_city" name="return_city"
                                                placeholder="e.g., Mumbai">
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Return PIN -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="return_pin">Return PIN</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="return_pin" name="return_pin"
                                                placeholder="6-digit PIN code">
                                        </div>
                                    </div>

                                    <!-- Return State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="return_state">Return State</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="return_state"
                                                name="return_state" placeholder="e.g., Maharashtra">
                                        </div>
                                    </div>

                                    <!-- Return Country -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="return_country">Return
                                            Country</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="return_country"
                                                name="return_country" value="India" placeholder="Country">
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Additional Settings Section -->
                            <div class="section-header">Additional Settings</div>

                            <div class="row mb-4">
                                <div class="col-sm-6">

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

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Pickup Point
                                    </button>
                                    <a href="pickuppoint-list.php" class="btn btn-secondary rounded-pill">
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
                    const selectedId = getQueryParam("id");

                    // Initialize Select2
                    if ($('.select2').length) {
                        $('.select2').select2({
                            minimumResultsForSearch: Infinity
                        });
                    }

                    // Load dependencies first
                    const loadCompanies = $.get('api/company/read.php?length=1000&status=active');
                    const loadCouriers = $.get('api/courier_partner/read.php?length=1000&status=active');

                    $.when(loadCompanies, loadCouriers).done(function (companiesRes, couriersRes) {
                        // Populate Companies
                        if (companiesRes[0].data) {
                            companiesRes[0].data.forEach(function (company) {
                                $('#company_id').append(`<option value="${company.id}">${company.company_name}</option>`);
                            });
                        }

                        // Populate Couriers
                        if (couriersRes[0].data) {
                            couriersRes[0].data.forEach(function (courier) {
                                $('#courier_id').append(`<option value="${courier.id}">${courier.partner_name}</option>`);
                            });
                        }

                        // Special handling for Own Courier (ID 2)
                        $('#courier_id').on('change', function () {
                            const val = $(this).val();
                            const courierText = ($('#courier_id').find('option:selected').text() || '').toLowerCase();
                            const isShiprocket = courierText.includes('shiprocket');
                            $('.own-courier-alert').remove();
                            if (val == 2) {
                                $(this).after('<div class="own-courier-alert alert alert-info py-1 px-2 mt-2" style="font-size:12px"><i class="ri-information-line"></i> Own Courier: No API sync required.</div>');
                            }

                            // Shiprocket requires email + pickup_state
                            $('#email').prop('required', isShiprocket);
                            $('#pickup_state').prop('required', true); // keep field required in UI (generic good data)
                            $('#emailRequiredMark').toggleClass('d-none', !isShiprocket);
                        }).trigger('change');

                        // Check for edit mode after dropdowns are populated
                        if (selectedId) {
                            editPickupPoint(selectedId);
                        }
                    });

                    // Function to load branches based on company
                    function loadBranches(companyId, selectedBranchId = null) {
                        if (!companyId) {
                            $('#branch_id').html('<option value="">Select Branch</option>').trigger('change');
                            return;
                        }

                        $('#branch_id').html('<option value="">Loading...</option>').trigger('change');

                        $.get(`api/branch/read.php?company_id=${companyId}&length=1000&status=active`, function (response) {
                            let options = '<option value="">Select Branch</option>';
                            if (response.data) {
                                response.data.forEach(function (branch) {
                                    options += `<option value="${branch.id}">${branch.branch_name}</option>`;
                                });
                            }
                            $('#branch_id').html(options).trigger('change');

                            if (selectedBranchId) {
                                $('#branch_id').val(selectedBranchId).trigger('change');
                            }
                        });
                    }

                    // Handle company change
                    $('#company_id').on('change', function () {
                        const companyId = $(this).val();
                        loadBranches(companyId);
                    });

                    // Get query parameter
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    function editPickupPoint(id) {
                        $.get(`api/pickuppoint/readone.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#pickupPointId').val(data.id);
                                $('#company_id').val(data.company_id).trigger('change');
                                loadBranches(data.company_id, data.branch_id);
                                $('#courier_id').val(data.courier_id).trigger('change');
                                $('#pickup_point_code').val(data.pickup_point_code);
                                $('#name').val(data.name);
                                $('#registered_name').val(data.registered_name);

                                $('#phone').val(data.phone);
                                $('#email').val(data.email);
                                $('#address').val(data.address);
                                $('#city').val(data.city);
                                $('#pickup_state').val(data.pickup_state);
                                $('#pin').val(data.pin);
                                $('#country').val(data.country);
                                $('#return_address').val(data.return_address);
                                $('#return_city').val(data.return_city);
                                $('#return_pin').val(data.return_pin);
                                $('#return_state').val(data.return_state);
                                $('#return_country').val(data.return_country);
                                $('#status').val(data.status).trigger('change');

                                // If synced, restrict editing to only API-supported fields (Moved to end to ensure it applies)
                                if (data.delhivery_synced == 1) {
                                    // 1. Disable/Readonly EVERYTHING first
                                    $('#pickupPointForm input, #pickupPointForm textarea, #pickupPointForm select').prop('readonly', true);
                                    $('#pickupPointForm select').prop('disabled', true);

                                    // 2. Enable ONLY the editable fields supported by Update API + Dropdowns
                                    $('#phone, #address, #city, #pin, #company_id, #branch_id, #courier_id').prop('readonly', false).prop('disabled', false);
                                    $('#pickupPointId').prop('disabled', false); // Ensure ID is not disabled for submission

                                    // 3. Visual feedback
                                    $('#name').addClass('bg-light');
                                    // Prevent duplicate alerts
                                    $('.sync-alert').remove();
                                    $('.section-header').first().after('<div class="alert alert-warning py-1 px-2 mb-2 sync-alert" style="font-size:12px"><i class="ri-alert-line"></i> Synced with Courier: Only Phone, Address, and PIN can be edited.</div>');

                                    // Identifier message
                                    $('#name').next('small').remove();
                                    $('#name').after('<small class="text-danger d-block">Cannot be updated (Identifier)</small>');
                                }
                            } else {
                                showtoastt('Pickup point not found', 'error');
                                setTimeout(() => window.location.href = 'pickuppoint-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading pickup point data', 'error');
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
                                id: "branch_id",
                                message: "Branch is required.",
                                required: true
                            },
                            {
                                id: "courier_id",
                                message: "Courier Partner is required.",
                                required: true
                            },
                            {
                                id: "name",
                                message: "Warehouse Name is required.",
                                required: true
                            },
                            {
                                id: "phone",
                                message: "Phone number is required.",
                                required: true,
                                pattern: /^\d{10}$/,
                                patternMessage: "Phone number must be 10 digits"
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
                                id: "pin",
                                message: "PIN code is required.",
                                required: true,
                                pattern: /^\d{6}$/,
                                patternMessage: "PIN code must be 6 digits"
                            },
                            {
                                id: "return_address",
                                message: "Return address is required.",
                                required: true
                            },
                            {
                                id: "email",
                                message: "Email is invalid.",
                                required: false,
                                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                patternMessage: "Invalid email format"
                            },
                            {
                                id: "pickup_state",
                                message: "Pickup State is required.",
                                required: false
                            },
                            {
                                id: "return_pin",
                                message: "Return PIN is invalid.",
                                required: false,
                                pattern: /^\d{6}$/,
                                patternMessage: "Return PIN must be 6 digits"
                            }
                        ];

                        // If selected courier is Shiprocket, enforce Email + Pickup State.
                        const courierText = ($('#courier_id').find('option:selected').text() || '').toLowerCase();
                        const isShiprocket = courierText.includes('shiprocket');
                        if (isShiprocket) {
                            const emailField = fields.find(f => f.id === 'email');
                            const pickupStateField = fields.find(f => f.id === 'pickup_state');
                            if (emailField) emailField.required = true;
                            if (pickupStateField) pickupStateField.required = true;
                        }

                        // Validate each field
                        fields.forEach(function (field) {
                            let value = $('#' + field.id).val();
                            if (typeof value === 'string') {
                                value = value.trim();
                            }

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
                    $('#pickupPointForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/pickuppoint/update.php' : 'api/pickuppoint/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');

                                    // Clear form for new entry (don't redirect)
                                    if (!selectedId) {
                                        // Only clear if it was a new entry
                                        $('#pickupPointForm')[0].reset();
                                        $('#company_id').val('').trigger('change');
                                        $('#courier_id').val('').trigger('change');
                                        $('#status').val('active').trigger('change');
                                        $('.is-invalid').removeClass('is-invalid');
                                    }
                                    // If editing, keep the form as is so user can continue editing
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Pickup Point');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>