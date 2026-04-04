<?php
require_once 'header.php';
require_once 'config/middleware.php';

if (isset($_GET['id'])) {
    require_permission('coloader', 'is_edit');
} else {
    require_permission('coloader', 'is_add');
}
?>
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<style>
    .col-form-label { padding-bottom: 2px !important; padding-top: 2px !important; margin-bottom: 2px !important; }
    .mb-4 { margin-bottom: 3px !important; }
    .form-control, .form-select { padding: 5px !important; }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">
                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8">
                            <h4 class="mb-0"><?= isset($_GET['id']) ? 'Edit Coloader' : 'Add Coloader' ?></h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="coloader-list.php" class="btn btn-sm rounded-pill btn-primary waves-effect waves-light">
                                <i class="ri-arrow-left-circle-fill"></i> Back to Coloader List
                            </a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="coloaderForm" class="row" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" id="coloaderId" name="id" value="">

                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="name">Name <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required>
                                            <div class="invalid-feedback">Name is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="mobile_number">Mobile Number <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="mobile_number" name="mobile_number" placeholder="Enter Mobile Number" required>
                                            <div class="invalid-feedback">Mobile Number is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="email">Email <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email" required>
                                            <div class="invalid-feedback">Email is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status">Status <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="status" name="status" required>
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                            <div class="invalid-feedback">Status is required.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="address">Address <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Address" required></textarea>
                                            <div class="invalid-feedback">Address is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="remarks">Remarks</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Remarks"></textarea>
                                        </div>
                                    </div>
                                    <div class="audit-info" style="display:none;">
                                        <hr>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Created At</label>
                                            <div class="col-sm-8"><input type="text" class="form-control-plaintext" id="created_at" readonly></div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Updated At</label>
                                            <div class="col-sm-8"><input type="text" class="form-control-plaintext" id="updated_at" readonly></div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Created By</label>
                                            <div class="col-sm-8"><input type="text" class="form-control-plaintext" id="created_by_name" readonly></div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Updated By</label>
                                            <div class="col-sm-8"><input type="text" class="form-control-plaintext" id="updated_by_name" readonly></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill"><i class="ri-save-line"></i> Save Coloader</button>
                                    <a href="coloader-list.php" class="btn btn-sm btn-secondary rounded-pill"><i class="ri-close-line"></i> Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    if ($('.select2').length) {
                        $('.select2').select2({ minimumResultsForSearch: Infinity });
                    }

                    var id = new URLSearchParams(window.location.search).get("id");
                    if (id) {
                        $.get('api/coloader/read_single.php?id=' + id, function (response) {
                            if (response.status === 'success') {
                                var d = response.data;
                                $('#coloaderId').val(d.id);
                                $('#name').val(d.name);
                                $('#mobile_number').val(d.mobile_number || '');
                                $('#email').val(d.email || '');
                                $('#address').val(d.address || '');
                                $('#status').val(d.status || 'active').trigger('change');
                                $('#remarks').val(d.remarks || '');
                                $('#created_at').val(d.created_at || '');
                                $('#updated_at').val(d.updated_at || '');
                                $('#created_by_name').val(d.created_by_name || '');
                                $('#updated_by_name').val(d.updated_by_name || '');
                                $('.audit-info').show();
                            } else {
                                showtoastt('Coloader not found', 'error');
                                setTimeout(function () { window.location.href = 'coloader-list.php'; }, 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading coloader', 'error');
                        });
                    }

                    $('#coloaderForm').on('submit', function (e) {
                        e.preventDefault();
                        var valid = true;
                        $('.is-invalid').removeClass('is-invalid');
                        if (!$('#name').val().trim()) {
                            $('#name').addClass('is-invalid');
                            showtoastt('Name is required', 'error');
                            valid = false;
                        }
                        if (!$('#mobile_number').val().trim()) {
                            $('#mobile_number').addClass('is-invalid');
                            if (valid) showtoastt('Mobile Number is required', 'error');
                            valid = false;
                        }
                        if (!$('#email').val().trim()) {
                            $('#email').addClass('is-invalid');
                            if (valid) showtoastt('Email is required', 'error');
                            valid = false;
                        }
                        if (!$('#address').val().trim()) {
                            $('#address').addClass('is-invalid');
                            if (valid) showtoastt('Address is required', 'error');
                            valid = false;
                        }
                        if (!$('#status').val()) {
                            $('#status').addClass('is-invalid');
                            if (valid) showtoastt('Status is required', 'error');
                            valid = false;
                        }
                        if (!valid) return;

                        var $btn = $(this).find('button[type="submit"]');
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        var formData = new FormData(this);
                        $.ajax({
                            url: 'api/coloader/create.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(function () { window.location.href = 'coloader-list.php'; }, 1500);
                                } else {
                                    showtoastt(response.message || 'Error', 'error');
                                }
                            },
                            error: function () { showtoastt('Error saving', 'error'); },
                            complete: function () {
                                $btn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Coloader');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
