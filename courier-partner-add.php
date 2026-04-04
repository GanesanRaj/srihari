<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('courier_partner', 'is_edit');
} else {
    // Add Mode
    require_permission('courier_partner', 'is_add');
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
                            <a href="courier-partner-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Courier Partner List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="courierPartnerForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="partnerId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Partner Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="partner_name">Partner Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="partner_name"
                                                name="partner_name" placeholder="e.g., Delhivery B2B" required>
                                            <div class="invalid-feedback">Partner name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Partner Code -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="partner_code">Partner Code <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="partner_code"
                                                name="partner_code" placeholder="e.g., DELHIVERY_B2B" required>
                                            <div class="invalid-feedback">Partner code is required.</div>
                                        </div>
                                    </div>

                                    <!-- API URL -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="api_url">API URL</label>
                                        <div class="col-sm-8">
                                            <input type="url" class="form-control" id="api_url" name="api_url"
                                                placeholder="https://api.example.com/v1">
                                        </div>
                                    </div>

                                    <!-- API Key -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="api_key">API Key</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="api_key" name="api_key" rows="2"
                                                placeholder="Enter API Key"></textarea>
                                        </div>
                                    </div>

                                    <!-- Username -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="username">Username</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="username" name="username"
                                                placeholder="API Username">
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="password">Password</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="password" name="password"
                                                placeholder="API Password (not encrypted)">
                                            <small class="text-muted">Password is stored as plain text</small>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Token -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="token">Token</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="token" name="token" rows="2"
                                                placeholder="Bearer Token or Access Token (e.g. Shiprocket JWT)"></textarea>
                                            <div id="shiprocketTokenPanel" class="mt-2 p-2 border rounded bg-light d-none">
                                                <div class="small text-muted mb-2">
                                                    <strong>Shiprocket:</strong> Uses <strong>Username</strong> as API login email and <strong>Password</strong> as API password.
                                                    Token is valid ~10 days; use <code>Authorization: Bearer &lt;token&gt;</code> for API calls.
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    <button type="button" class="btn btn-sm btn-success" id="btnShiprocketLogin">
                                                        <i class="ri-key-2-line"></i> Generate token (login)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnShiprocketLogout">
                                                        <i class="ri-logout-box-r-line"></i> Logout token
                                                    </button>
                                                </div>
                                                <div id="shiprocketTokenMsg" class="small mt-2"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Client ID -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="client_id">Client ID</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="client_id" name="client_id"
                                                placeholder="Client ID">
                                        </div>
                                    </div>

                                    <!-- Client Secret -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="client_secret">Client Secret</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="client_secret" name="client_secret"
                                                rows="2" placeholder="Client Secret Key"></textarea>
                                        </div>
                                    </div>

                                    <!-- Preference Order -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="preference_order">Preference
                                            Order</label>
                                        <div class="col-sm-8">
                                            <input type="number" class="form-control" id="preference_order"
                                                name="preference_order" value="0" min="0"
                                                placeholder="Lower = Higher Priority">
                                            <small class="text-muted">Lower number = higher priority</small>
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
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                                placeholder="Additional notes"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Courier Partner
                                    </button>
                                    <a href="courier-partner-list.php" class="btn btn-secondary rounded-pill">
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

                    // If editing, fetch existing data
                    if (selectedId) {
                        editCourierPartner(selectedId);
                    } else {
                        toggleShiprocketTokenPanel();
                    }

                    $('#partner_name, #partner_code').on('input change', toggleShiprocketTokenPanel);

                    $('#btnShiprocketLogin').on('click', function () {
                        const email = ($('#username').val() || '').trim();
                        const password = $('#password').val() || '';
                        const $msg = $('#shiprocketTokenMsg');
                        if (!email || !password) {
                            $msg.removeClass('text-success').addClass('text-danger').text('Enter Shiprocket email in Username and Password first.');
                            return;
                        }
                        const $btn = $(this).prop('disabled', true);
                        $msg.removeClass('text-success text-danger').text('Contacting Shiprocket…');
                        $.ajax({
                            url: 'api/courier_partner/shiprocket_token.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ action: 'login', email: email, password: password }),
                            success: function (res) {
                                if (res.status === 'success' && res.token) {
                                    $('#token').val(res.token);
                                    $msg.removeClass('text-danger').addClass('text-success').text(res.message || 'Token saved in field. Click Save to persist.');
                                    showtoastt(res.message || 'Token generated', 'success');
                                } else {
                                    const parts = [];
                                    if (res.http_code) parts.push('HTTP ' + res.http_code);
                                    if (res.hint) parts.push(res.hint);
                                    if (res.detail) parts.push(typeof res.detail === 'string' ? res.detail : JSON.stringify(res.detail));
                                    if (res.message) parts.push(res.message);
                                    const errLine = parts.filter(Boolean).join(' — ') || 'Login failed';
                                    $msg.removeClass('text-success').addClass('text-danger').text(errLine);
                                    showtoastt(res.message || 'Shiprocket login failed', 'error');
                                }
                            },
                            error: function (xhr) {
                                let m = 'Request failed';
                                try {
                                    const j = JSON.parse(xhr.responseText);
                                    m = j.message || j.detail || m;
                                } catch (e) { /* ignore */ }
                                $msg.removeClass('text-success').addClass('text-danger').text(m);
                                showtoastt(m, 'error');
                            },
                            complete: function () {
                                $btn.prop('disabled', false);
                            }
                        });
                    });

                    $('#btnShiprocketLogout').on('click', function () {
                        let tok = ($('#token').val() || '').trim();
                        const $msg = $('#shiprocketTokenMsg');
                        if (!tok) {
                            $msg.removeClass('text-success').addClass('text-danger').text('No token in Token field.');
                            return;
                        }
                        const $btn = $(this).prop('disabled', true);
                        $msg.removeClass('text-success text-danger').text('Logging out…');
                        $.ajax({
                            url: 'api/courier_partner/shiprocket_token.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ action: 'logout', token: tok }),
                            success: function (res) {
                                if (res.status === 'success') {
                                    $('#token').val('');
                                    $msg.removeClass('text-danger').addClass('text-success').text(res.message || 'Logged out. Token cleared.');
                                    showtoastt(res.message || 'Logged out', 'success');
                                } else {
                                    $msg.removeClass('text-success').addClass('text-danger').text(res.detail || res.message || 'Logout failed');
                                    showtoastt(res.message || 'Logout failed', 'error');
                                }
                            },
                            error: function (xhr) {
                                let m = 'Request failed';
                                try {
                                    const j = JSON.parse(xhr.responseText);
                                    m = j.message || j.detail || m;
                                } catch (e) { /* ignore */ }
                                $msg.removeClass('text-success').addClass('text-danger').text(m);
                                showtoastt(m, 'error');
                            },
                            complete: function () {
                                $btn.prop('disabled', false);
                            }
                        });
                    });

                    function isShiprocketPartner() {
                        const name = ($('#partner_name').val() || '').toLowerCase();
                        const code = ($('#partner_code').val() || '').toLowerCase();
                        return name.includes('shiprocket') || code.includes('shiprocket');
                    }

                    function toggleShiprocketTokenPanel() {
                        if (isShiprocketPartner()) {
                            $('#shiprocketTokenPanel').removeClass('d-none');
                        } else {
                            $('#shiprocketTokenPanel').addClass('d-none');
                            $('#shiprocketTokenMsg').text('');
                        }
                    }

                    function editCourierPartner(id) {
                        $.get(`api/courier_partner/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#partnerId').val(data.id);
                                $('#partner_name').val(data.partner_name);
                                $('#partner_code').val(data.partner_code);
                                $('#api_url').val(data.api_url);
                                $('#api_key').val(data.api_key);
                                $('#username').val(data.username);
                                $('#password').val(data.password);
                                $('#token').val(data.token);
                                $('#client_id').val(data.client_id);
                                $('#client_secret').val(data.client_secret);
                                $('#preference_order').val(data.preference_order);
                                $('#status').val(data.status).trigger('change');
                                $('#remarks').val(data.remarks);
                                toggleShiprocketTokenPanel();
                            } else {
                                showtoastt('Courier partner not found', 'error');
                                setTimeout(() => window.location.href = 'courier-partner-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading courier partner data', 'error');
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
                                id: "partner_name",
                                message: "Partner Name is required.",
                                required: true
                            },
                            {
                                id: "partner_code",
                                message: "Partner Code is required.",
                                required: true,
                                pattern: /^[A-Z0-9_]+$/,
                                patternMessage: "Partner code must contain only uppercase letters, numbers, and underscores"
                            },
                            {
                                id: "api_url",
                                message: "API URL is invalid.",
                                required: false,
                                pattern: /^https?:\/\/.+/,
                                patternMessage: "API URL must be a valid URL (http:// or https://)"
                            },
                            {
                                id: "preference_order",
                                message: "Preference Order is invalid.",
                                required: false,
                                custom: function (value) {
                                    return value === '' || (!isNaN(value) && parseInt(value) >= 0);
                                },
                                patternMessage: "Preference order must be 0 or greater"
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
                            // Check custom validation (if value exists and custom function is defined)
                            else if (field.custom && !field.custom(value)) {
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
                    $('#courierPartnerForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/courier_partner/update.php' : 'api/courier_partner/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'courier-partner-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Courier Partner');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
    </div>
</body>

</html>