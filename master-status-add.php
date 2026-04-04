<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    // require_permission('master_status', 'is_edit');
} else {
    // Add Mode
    // require_permission('master_status', 'is_add');
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
                                <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Status Description
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="master-status-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="statusForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="statusId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">
                                    <!-- Status Name -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Status Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="e.g., Pending" required>
                                            <div class="invalid-feedback">Status name is required.</div>
                                        </div>
                                    </div>

                                    <!-- Status Code -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="code">Status Code <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="code" name="code"
                                                placeholder="e.g., PENDING" required>
                                            <div class="invalid-feedback">Status code is required.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">
                                    <!-- Status (Active/Inactive) -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status">System Status</label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="status" name="status"
                                                data-toggle="select2">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Status Query -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status_query">Status Query</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="status_query" name="status_query"
                                                rows="3" placeholder="SQL query or logic"></textarea>
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
                                        <i class="ri-save-line"></i> Save Status
                                    </button>
                                    <a href="master-status-list.php" class="btn btn-secondary rounded-pill">
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
                        editStatus(selectedId);
                    }

                    function editStatus(id) {
                        $.get(`api/master_status/read_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                $('#statusId').val(data.id);
                                $('#name').val(data.name);
                                $('#code').val(data.code);
                                $('#status').val(data.status).trigger('change');
                                $('#status_query').val(data.status_query);
                                $('#remarks').val(data.remarks);
                            } else {
                                showtoastt('Status not found', 'error');
                                setTimeout(() => window.location.href = 'master-status-list.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading status data', 'error');
                        });
                    }

                    // Validation function
                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        $('.is-invalid').removeClass('is-invalid');

                        let fields = [
                            { id: "name", message: "Status Name is required.", required: true },
                            { id: "code", message: "Status Code is required.", required: true }
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
                    $('#statusForm').on('submit', function (e) {
                        e.preventDefault();

                        if (!validateForm()) {
                            return;
                        }

                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        let formData = new FormData(this);
                        let url = selectedId ? 'api/master_status/update.php' : 'api/master_status/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'master-status-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Status');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>