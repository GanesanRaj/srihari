<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    // require_permission('designation', 'is_edit');
} else {
    // Add Mode
    // require_permission('designation', 'is_add');
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
                                <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Designation
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="designation-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="designationForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="designationId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">
                                    <!-- Designation -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="designation">Designation <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="designation" name="designation"
                                                placeholder="Enter Designation" required>
                                            <div class="invalid-feedback">Designation is required.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">
                                    <!-- Status (Active/Inactive) -->
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
                                        <i class="ri-save-line"></i> Save Designation
                                    </button>
                                    <a href="designation-list.php" class="btn btn-secondary rounded-pill">
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
                        editDesignation(selectedId);
                    }

                    function editDesignation(id) {
                        $.get(`api/designation/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#designationId').val(data.id);
                                $('#designation').val(data.designation);
                                $('#status').val(data.status).trigger('change');
                            } else {
                                showtoastt('Designation not found', 'error');
                                setTimeout(() => window.location.href = 'designation-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading designation data', 'error');
                        });
                    }

                    // Validation function
                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        $('.is-invalid').removeClass('is-invalid');

                        let fields = [
                            { id: "designation", message: "Designation is required.", required: true }
                        ];

                        fields.forEach(function (field) {
                            let value = $('#' + field.id).val().trim();
                            if (field.required && !value) {
                                $('#' + field.id).addClass('is-invalid');
                                errors.push(field.message);
                                isValid = false;
                            }
                        });

                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }

                    // Form submission handler
                    $('#designationForm').on('submit', function (e) {
                        e.preventDefault();

                        if (!validateForm()) {
                            return;
                        }

                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/designation/update.php' : 'api/designation/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'designation-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Designation');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>