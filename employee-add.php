<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    // require_permission('employee', 'is_edit');
} else {
    // Add Mode
    // require_permission('employee', 'is_add');
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
                                <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Employee
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="employee-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="employeeForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="employeeId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Branch -->
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

                                    <!-- Role -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="role_id">Role <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="role_id" name="role_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Role</option>
                                            </select>
                                            <div class="invalid-feedback">Role is required.</div>
                                        </div>
                                    </div>

                                    <!-- Designation -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="designation_id">Designation <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="designation_id"
                                                name="designation_id" data-toggle="select2" required>
                                                <option value="">Select Designation</option>
                                            </select>
                                            <div class="invalid-feedback">Designation is required.</div>
                                        </div>
                                    </div>

                                    <!-- Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Full Name" required autocomplete="off">
                                            <div class="invalid-feedback">Name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Age -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="age">Age</label>
                                        <div class="col-sm-8">
                                            <input type="number" class="form-control" id="age" name="age"
                                                placeholder="Age" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="email">Email</label>
                                        <div class="col-sm-8">
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Email Address" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="phone">Phone Number</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                placeholder="Phone Number" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Username -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="user_id">Username <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="user_id" name="user_id"
                                                placeholder="Login Username" required autocomplete="off">
                                            <div class="invalid-feedback">Username is required.</div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Father Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="father_name">Father Name</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="father_name" name="father_name"
                                                placeholder="Father's Name" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Mother Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="mother_name">Mother Name</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="mother_name" name="mother_name"
                                                placeholder="Mother's Name" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Education -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="education">Education</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="education" name="education"
                                                placeholder="Education Qualification" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Salary -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="salary">Salary</label>
                                        <div class="col-sm-8">
                                            <input type="number" step="0.01" class="form-control" id="salary"
                                                name="salary" placeholder="Salary" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Experience -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="experience">Experience</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="experience" name="experience"
                                                placeholder="Work Experience" autocomplete="off">
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

                                    <!-- Password -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="password">Password <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Login Password" required autocomplete="new-password">
                                            <div class="invalid-feedback">Password is required.</div>
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="password_confirm">Confirm Password
                                            <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="password" class="form-control" id="password_confirm"
                                                name="password_confirm" placeholder="Confirm Password" required autocomplete="new-password">
                                            <div class="invalid-feedback">Passwords do not match.</div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Address Section (Full Width) -->
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <!-- Country -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="country">Country</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="country" name="country"
                                                value="INDIA" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- State -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="state">State</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="state" name="state"
                                                placeholder="State" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <!-- City -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="city">City</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="city" name="city"
                                                placeholder="City" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Pincode -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="pincode">Pincode</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="pincode" name="pincode"
                                                placeholder="Pincode" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full Width Address -->
                            <div class="row mb-4">
                                <label class="col-sm-2 col-form-label" for="address">Address</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="address" name="address" rows="2"
                                        placeholder="Complete Address"></textarea>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Employee
                                    </button>
                                    <a href="employee-list.php" class="btn btn-secondary rounded-pill ms-2">
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

                    // Get query parameter
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    let selectedId = getQueryParam("id");

                    // Load branches
                    const branchesRequest = $.get('api/branch/read.php?length=-1', function (response) {
                        if (response.status === 'error') {
                            showtoastt('Error loading branches: ' + response.message, 'error');
                            return;
                        }
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branch_id').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                            $('#branch_id').trigger('change');
                        }
                    }).fail(function () {
                        showtoastt('Error loading branches', 'error');
                    });

                    // Load roles
                    const rolesRequest = $.get('api/role/read.php?length=-1', function (response) {
                        if (response.status === 'error') {
                            showtoastt('Error loading roles: ' + response.message, 'error');
                            return;
                        }
                        if (response.data) {
                            response.data.forEach(function (role) {
                                $('#role_id').append(`<option value="${role.id}">${role.name}</option>`);
                            });
                            $('#role_id').trigger('change');
                        }
                    }).fail(function () {
                        showtoastt('Error loading roles', 'error');
                    });

                    // Load designations
                    const designationsRequest = $.get('api/designation/read.php?length=-1', function (response) {
                        if (response.status === 'error') {
                            showtoastt('Error loading designations: ' + response.message, 'error');
                            return;
                        }
                        if (response.data) {
                            response.data.forEach(function (designation) {
                                $('#designation_id').append(`<option value="${designation.id}">${designation.designation}</option>`);
                            });
                            $('#designation_id').trigger('change');
                        }
                    }).fail(function () {
                        showtoastt('Error loading designations', 'error');
                    });

                    // Wait for all dropdowns to load before editing
                    $.when(branchesRequest, rolesRequest, designationsRequest).done(function () {
                        if (selectedId) {
                            editEmployee(selectedId);
                        }
                    });

                    function editEmployee(id) {
                        $.get(`api/employee/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#employeeId').val(data.id);
                                $('#name').val(data.name);
                                $('#branch_id').val(data.branch_id).trigger('change');
                                $('#role_id').val(data.role_id).trigger('change');
                                $('#designation_id').val(data.designation_id).trigger('change');
                                $('#age').val(data.age);
                                $('#email').val(data.email);
                                $('#phone').val(data.phone);
                                $('#user_id').val(data.user_id);
                                // Leave password fields empty on edit - user changes only if needed
                                $('#password').val('');
                                $('#password_confirm').val('');
                                $('#father_name').val(data.father_name);
                                $('#mother_name').val(data.mother_name);
                                $('#education').val(data.education);
                                $('#salary').val(data.salary);
                                $('#experience').val(data.experience);
                                $('#status').val(data.status).trigger('change');
                                $('#country').val(data.country);
                                $('#state').val(data.state);
                                $('#city').val(data.city);
                                $('#pincode').val(data.pincode);
                                $('#address').val(data.address);
                            } else {
                                showtoastt('Employee not found', 'error');
                                setTimeout(() => window.location.href = 'employee-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading employee data', 'error');
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
                                id: "role_id",
                                message: "Role is required.",
                                required: true
                            },
                            {
                                id: "designation_id",
                                message: "Designation is required.",
                                required: true
                            },
                            {
                                id: "name",
                                message: "Name is required.",
                                required: true
                            },
                            {
                                id: "user_id",
                                message: "Username is required.",
                                required: true
                            },
                            {
                                id: "password",
                                message: "Password is required.",
                                required: !selectedId  // Required only for new employees
                            },
                            {
                                id: "phone",
                                message: "Phone number is invalid.",
                                required: false,
                                pattern: /^\d{10}$/,
                                patternMessage: "Phone number must be 10 digits"
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

                        // Check password match
                        let password = $('#password').val();
                        let passwordConfirm = $('#password_confirm').val();

                        if (password && passwordConfirm && password !== passwordConfirm) {
                            $('#password').addClass('is-invalid');
                            $('#password_confirm').addClass('is-invalid');
                            errors.push('Passwords do not match');
                            isValid = false;
                        }

                        // Show first error if validation fails
                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }

                    // Form submission handler
                    $('#employeeForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/employee/update.php' : 'api/employee/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'employee-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Employee');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>