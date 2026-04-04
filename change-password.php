<?php
require_once 'header.php';
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">

                    <!-- Page Title -->
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">
                                <i class="ti ti-key me-1"></i> Change Password
                            </h4>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Update Your Password</h5>
                                    <p class="text-muted mb-4">Ensure your account is using a strong password to stay secure.</p>

                                    <form id="changePasswordForm">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" required>
                                                <button class="btn btn-sm btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" required>
                                                <button class="btn btn-sm btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                            </div>
                                            <!-- Password strength indicator -->
                                            <div class="mt-2">
                                                <div class="progress" style="height: 4px;">
                                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                                <small id="password-strength-text" class="text-muted"></small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-sm btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                            </div>
                                            <div id="password-match-message" class="mt-1"></div>
                                        </div>

                                        <!-- Password Requirements -->
                                        <div class="alert alert-light border mb-3">
                                            <h6 class="fw-semibold mb-2">Password Requirements:</h6>
                                            <ul class="mb-0 ps-3" style="font-size: 13px;">
                                                <li id="req-length" class="text-muted">Minimum 8 characters</li>
                                                <li id="req-uppercase" class="text-muted">At least one uppercase letter</li>
                                                <li id="req-lowercase" class="text-muted">At least one lowercase letter</li>
                                                <li id="req-number" class="text-muted">At least one number</li>
                                                <li id="req-special" class="text-muted">At least one special character</li>
                                            </ul>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-sm btn-soft-primary">
                                                <i class="ti ti-lock me-1"></i> Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(document).ready(function() {
                    // Toggle password visibility
                    $('.toggle-password').on('click', function() {
                        var target = $(this).data('target');
                        var input = $('#' + target);
                        var icon = $(this).find('i');

                        if (input.attr('type') === 'password') {
                            input.attr('type', 'text');
                            icon.removeClass('ti-eye').addClass('ti-eye-off');
                        } else {
                            input.attr('type', 'password');
                            icon.removeClass('ti-eye-off').addClass('ti-eye');
                        }
                    });

                    // Password strength checker
                    $('#new_password').on('keyup', function() {
                        var password = $(this).val();
                        var strength = checkPasswordStrength(password);

                        $('#password-strength-bar')
                            .removeClass('bg-danger bg-warning bg-info bg-success')
                            .addClass(strength.colorClass)
                            .css('width', strength.percent + '%');

                        $('#password-strength-text')
                            .removeClass('text-danger text-warning text-info text-success')
                            .addClass(strength.textClass)
                            .text(strength.text);

                        // Update requirements
                        updateRequirements(password);
                    });

                    // Confirm password match
                    $('#confirm_password').on('keyup', function() {
                        var newPass = $('#new_password').val();
                        var confirmPass = $(this).val();

                        if (confirmPass.length > 0) {
                            if (newPass === confirmPass) {
                                $('#password-match-message').html('<small class="text-success"><i class="ti ti-check"></i> Passwords match</small>');
                            } else {
                                $('#password-match-message').html('<small class="text-danger"><i class="ti ti-x"></i> Passwords do not match</small>');
                            }
                        } else {
                            $('#password-match-message').html('');
                        }
                    });

                    // Form submission
                    $('#changePasswordForm').on('submit', function(e) {
                        e.preventDefault();

                        var newPass = $('#new_password').val();
                        var confirmPass = $('#confirm_password').val();

                        if (newPass !== confirmPass) {
                            showtoastt('Passwords do not match', 'error');
                            return;
                        }

                        $.ajax({
                            url: 'api/user/change-password.php',
                            type: 'POST',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    $('#changePasswordForm')[0].reset();
                                    $('#password-strength-bar').css('width', '0%');
                                    $('#password-strength-text').text('');
                                    $('#password-match-message').html('');
                                    resetRequirements();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error changing password', 'error');
                            }
                        });
                    });

                    function checkPasswordStrength(password) {
                        var strength = 0;
                        var result = {
                            percent: 0,
                            text: '',
                            colorClass: '',
                            textClass: ''
                        };

                        if (password.length >= 8) strength += 20;
                        if (password.length >= 12) strength += 20;
                        if (/[a-z]/.test(password)) strength += 20;
                        if (/[A-Z]/.test(password)) strength += 20;
                        if (/[0-9]/.test(password)) strength += 10;
                        if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

                        result.percent = strength;

                        if (strength < 40) {
                            result.text = 'Weak';
                            result.colorClass = 'bg-danger';
                            result.textClass = 'text-danger';
                        } else if (strength < 60) {
                            result.text = 'Fair';
                            result.colorClass = 'bg-warning';
                            result.textClass = 'text-warning';
                        } else if (strength < 80) {
                            result.text = 'Good';
                            result.colorClass = 'bg-info';
                            result.textClass = 'text-info';
                        } else {
                            result.text = 'Strong';
                            result.colorClass = 'bg-success';
                            result.textClass = 'text-success';
                        }

                        return result;
                    }

                    function updateRequirements(password) {
                        $('#req-length').toggleClass('text-success', password.length >= 8).toggleClass('text-muted', password.length < 8);
                        $('#req-uppercase').toggleClass('text-success', /[A-Z]/.test(password)).toggleClass('text-muted', !/[A-Z]/.test(password));
                        $('#req-lowercase').toggleClass('text-success', /[a-z]/.test(password)).toggleClass('text-muted', !/[a-z]/.test(password));
                        $('#req-number').toggleClass('text-success', /[0-9]/.test(password)).toggleClass('text-muted', !/[0-9]/.test(password));
                        $('#req-special').toggleClass('text-success', /[^a-zA-Z0-9]/.test(password)).toggleClass('text-muted', !/[^a-zA-Z0-9]/.test(password));
                    }

                    function resetRequirements() {
                        $('#req-length, #req-uppercase, #req-lowercase, #req-number, #req-special').removeClass('text-success').addClass('text-muted');
                    }
                });
            </script>
        </div>
    </div>
</body>

<style>
    .form-control-sm, .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }
</style>

</html>
