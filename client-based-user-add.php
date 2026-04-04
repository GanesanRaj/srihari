<?php
require_once 'header.php';
require_once 'config/middleware.php';
require_permission('client_based_user', 'is_add');
?>
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<style>
    .col-form-label { padding-bottom: 2px !important; padding-top: 2px !important; }
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
                            <h4 class="mb-0">Add User (Client Based)</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="setting-role.php"><button type="button" class="btn btn-xs rounded-pill btn-primary waves-effect waves-light"><i class="ri-arrow-left-circle-fill"></i> Back</button></a>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="clientUserForm" class="row" method="POST" novalidate>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="username">User ID (Username) <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Login username" required>
                                            <div class="invalid-feedback">Username is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="password">Password <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                            <div class="invalid-feedback">Password is required.</div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="role_id">Role <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="role_id" name="role_id" data-toggle="select2" required>
                                                <option value="">Select Role</option>
                                            </select>
                                            <div class="invalid-feedback">Role is required.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_ids">Branch (optional)</label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="branch_ids" name="branch_ids[]" data-toggle="select2" multiple>
                                            </select>
                                            <small class="text-muted">Multiselect – leave empty for all branches</small>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="client_ids">Client (optional)</label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="client_ids" name="client_ids[]" data-toggle="select2" multiple>
                                            </select>
                                            <small class="text-muted">Select branch(es) to show clients; leave empty for all clients</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill"><i class="ri-save-line"></i> Add User</button>
                                    <a href="setting-role.php" class="btn btn-secondary rounded-pill ms-2"><i class="ri-close-line"></i> Cancel</a>
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
                document.addEventListener('DOMContentLoaded', function () {
                    if ($('.select2').length) {
                        $('.select2').select2({ width: '100%' });
                    }
                    $.get('api/role/read.php?length=-1', function (res) {
                        if (res.data) res.data.forEach(function (r) {
                            $('#role_id').append('<option value="' + r.id + '">' + r.name + '</option>');
                        });
                    });
                    $.get('api/branch/read.php?length=-1', function (res) {
                        if (res.data) res.data.forEach(function (b) {
                            $('#branch_ids').append('<option value="' + b.id + '">' + (b.branch_name || b.id) + '</option>');
                        });
                    }).fail(function () { $('#branch_ids').append('<option value="">No branches</option>'); });

                    function loadClients() {
                        var branchIds = $('#branch_ids').val();
                        var url = 'api/client/read.php?length=-1';
                        if (branchIds && branchIds.length) url += '&branch_ids=' + branchIds.join(',');
                        $('#client_ids').empty();
                        $.get(url, function (res) {
                            if (res.data && res.data.length) {
                                res.data.forEach(function (c) {
                                    $('#client_ids').append('<option value="' + c.id + '">' + (c.client_name || c.id) + (c.branch_name ? ' (' + c.branch_name + ')' : '') + '</option>');
                                });
                            } else {
                                $('#client_ids').append('<option value="">' + (branchIds && branchIds.length ? 'No clients in selected branch(es)' : 'No clients') + '</option>');
                            }
                            $('#client_ids').trigger('change');
                        }).fail(function () {
                            $('#client_ids').append('<option value="">Error loading clients</option>');
                        });
                    }
                    $('#branch_ids').on('change', loadClients);
                    loadClients();

                    $('#clientUserForm').on('submit', function (e) {
                        e.preventDefault();
                        var $btn = $(this).find('button[type="submit"]');
                        $btn.prop('disabled', true);
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'api/client_based_user/create.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success') {
                                    showtoastt(res.message || 'User added successfully', 'success');
                                    setTimeout(function () { window.location.href = 'client-based-user-list.php'; }, 1500);
                                } else {
                                    showtoastt(res.message || 'Error', 'error');
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function (xhr) {
                                var msg = 'Request failed';
                                try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (e) {}
                                showtoastt(msg, 'error');
                                $btn.prop('disabled', false);
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>
