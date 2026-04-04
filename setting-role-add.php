<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Require add permission for this page
require_permission ( 'setting-role', 'is_add' );
?>

<!-- Vendors CSS -->
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
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="setting-role.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Roles
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="addRoleForm" class="row" novalidate>

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">
                                    <!-- Role Prefix -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="prefix">Role Prefix <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="prefix" name="prefix"
                                                placeholder="e.g., ADM, USR, MGR" required>
                                            <div class="invalid-feedback">Prefix is required.</div>
                                        </div>
                                    </div>

                                    <!-- Role Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Role Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="e.g., Administrator" required>
                                            <div class="invalid-feedback">Role name is required.</div>
                                        </div>
                                    </div>

                                    <!-- System Role -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="is_system">System Role</label>
                                        <div class="col-sm-8">
                                            <select class="form-select" id="is_system" name="is_system">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="ri-information-line me-1"></i> Role Information
                                            </h6>
                                            <ul class="small mb-0 ps-3">
                                                <li class="mb-2">Role prefix should be unique and short (3-4 characters)
                                                </li>
                                                <li class="mb-2">Role name should clearly describe the role's purpose
                                                </li>
                                                <li class="mb-2">System roles are protected from deletion</li>
                                                <li class="mb-2">Set permissions after creating the role</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Role
                                    </button>
                                    <a href="setting-role.php" class="btn btn-secondary rounded-pill">
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

            <script>
                $(document).ready(function () {
                    $('#addRoleForm').on('submit', function (e) {
                        e.preventDefault();

                        let isValid = true;
                        let errors = [];

                        $('.is-invalid').removeClass('is-invalid');

                        let prefix = $('#prefix').val().trim();
                        if (!prefix) {
                            $('#prefix').addClass('is-invalid');
                            errors.push("Role Prefix is required.");
                            isValid = false;
                        }

                        let name = $('#name').val().trim();
                        if (!name) {
                            $('#name').addClass('is-invalid');
                            errors.push("Role Name is required.");
                            isValid = false;
                        }

                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                            return;
                        }

                        const btn = $(this).find('button[type="submit"]');
                        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        const formData = {
                            prefix: prefix,
                            name: name,
                            is_system: $('#is_system').val()
                        };

                        $.ajax({
                            url: 'api/role/create.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(formData),
                            success: function (response) {
                                if (response.success || response.status === 'success') {
                                    showtoastt(response.message || 'Role created successfully', 'success');
                                    setTimeout(() => {
                                        window.location.href = 'setting-role.php';
                                    }, 1000);
                                } else {
                                    showtoastt(response.message || 'Failed to create role', 'error');
                                    btn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Role');
                                }
                            },
                            error: function (xhr) {
                                const response = xhr.responseJSON;
                                showtoastt(response?.message || 'Error creating role', 'error');
                                btn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Role');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>