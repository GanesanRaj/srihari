<?php
require_once 'header.php';
require_once 'config/middleware.php';
require_permission('client_based_user', 'is_view');

$can_add  = can_add('client_based_user');
$can_edit = can_edit('client_based_user');
?>
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Client Based User List</h4>
                    </div>
                    <div class="text-end">
                        <?php if ($can_add): ?>
                            <a href="client-based-user-add.php" class="btn btn-sm btn-soft-primary">
                                <i class="ti ti-plus me-1"></i> Add User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row g-2 mb-4">
                            <div class="col-md-3">
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-soft-primary btn-sm" id="filterBtn"><i class="ti ti-search me-1"></i> Search</button>
                                <button type="button" class="btn btn-light btn-sm" id="resetBtn"><i class="ti ti-rotate me-1"></i> Reset</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="clientUserTable" class="table table-hover dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Branches</th>
                                        <th>Clients</th>
                                        <th>Status</th>
                                        <?php if ($can_edit): ?>
                                        <th>Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'footer.php'; ?>

            <!-- Edit Modal -->
            <?php if ($can_edit): ?>
            <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Client-Based User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editUserForm">
                            <input type="hidden" id="edit_id" name="id">
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Username <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="edit_username" name="username" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">New Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Role <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2-modal" id="edit_role_id" name="role_id" required>
                                            <option value="">Select Role</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Branches</label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2-modal" id="edit_branch_ids" name="branch_ids[]" multiple>
                                        </select>
                                        <small class="text-muted">Leave empty for all branches</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Clients</label>
                                    <div class="col-sm-9">
                                        <select class="form-control select2-modal" id="edit_client_ids" name="client_ids[]" multiple>
                                        </select>
                                        <small class="text-muted">Leave empty for all clients</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Status</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="edit_status" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="editSaveBtn"><i class="ri-save-line me-1"></i>Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script>
                var canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;
                var allRoles = [];
                var allBranches = [];
                var allClients = [];

                $(document).ready(function () {
                    // Pre-load roles and branches for modal
                    $.get('api/role/read.php?length=-1', function (res) {
                        if (res.data) {
                            allRoles = res.data;
                            res.data.forEach(function (r) {
                                $('#edit_role_id').append('<option value="' + r.id + '">' + r.name + '</option>');
                            });
                        }
                    });
                    $.get('api/branch/read.php?length=-1', function (res) {
                        if (res.data) {
                            allBranches = res.data;
                            res.data.forEach(function (b) {
                                $('#edit_branch_ids').append('<option value="' + b.id + '">' + (b.branch_name || b.id) + '</option>');
                            });
                        }
                    });
                    $.get('api/client/read.php?length=-1', function (res) {
                        if (res.data) {
                            allClients = res.data;
                            res.data.forEach(function (c) {
                                $('#edit_client_ids').append('<option value="' + c.id + '">' + (c.client_name || c.id) + (c.branch_name ? ' (' + c.branch_name + ')' : '') + '</option>');
                            });
                        }
                    });

                    // Init Select2 in modal
                    $('#editUserModal').on('shown.bs.modal', function () {
                        $('.select2-modal').select2({ width: '100%', dropdownParent: $('#editUserModal') });
                    });

                    var columns = [
                        { data: 'id' },
                        { data: 'username' },
                        { data: 'role_name', defaultContent: '—' },
                        {
                            data: 'branch_ids',
                            render: function (data) {
                                if (!data || data === '' || data === null) return '<span class="text-muted">All</span>';
                                return '<span title="' + data + '">' + (data.split(',').length) + ' branch(es)</span>';
                            }
                        },
                        {
                            data: 'client_ids',
                            render: function (data) {
                                if (!data || data === '' || data === null) return '<span class="text-muted">All</span>';
                                return '<span title="' + data + '">' + (data.split(',').length) + ' client(s)</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function (data) {
                                return data === 'active'
                                    ? '<span class="badge bg-success">Active</span>'
                                    : '<span class="badge bg-danger">Inactive</span>';
                            }
                        }
                    ];

                    if (canEdit) {
                        columns.push({
                            data: null,
                            orderable: false,
                            render: function (data, type, row) {
                                return '<button class="btn btn-xs btn-soft-warning btn-edit" data-row=\'' + JSON.stringify(row).replace(/'/g, "&#39;") + '\'><i class="ti ti-edit"></i> Edit</button>';
                            }
                        });
                    }

                    var table = $('#clientUserTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/client_based_user/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                            }
                        },
                        columns: columns,
                        order: [[0, 'desc']],
                        pageLength: 25
                    });

                    $('#filterBtn').on('click', function () { table.ajax.reload(); });
                    $('#resetBtn').on('click', function () {
                        $('#statusFilter').val('');
                        table.ajax.reload();
                    });

                    // Open edit modal
                    $('#clientUserTable').on('click', '.btn-edit', function () {
                        var row = $(this).data('row');
                        $('#edit_id').val(row.id);
                        $('#edit_username').val(row.username);
                        $('#edit_password').val('');
                        $('#edit_role_id').val(row.role_id).trigger('change');
                        $('#edit_status').val(row.status || 'active');

                        // Set branch_ids
                        var bIds = row.branch_ids ? row.branch_ids.split(',').map(function(v){ return v.trim(); }) : [];
                        $('#edit_branch_ids').val(bIds).trigger('change');

                        // Set client_ids
                        var cIds = row.client_ids ? row.client_ids.split(',').map(function(v){ return v.trim(); }) : [];
                        $('#edit_client_ids').val(cIds).trigger('change');

                        $('#editUserModal').modal('show');
                    });

                    // Submit edit form
                    $('#editUserForm').on('submit', function (e) {
                        e.preventDefault();
                        var $btn = $('#editSaveBtn').prop('disabled', true);
                        var formData = new FormData(this);
                        $.ajax({
                            url: 'api/client_based_user/update.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success') {
                                    showtoastt(res.message || 'User updated', 'success');
                                    $('#editUserModal').modal('hide');
                                    table.ajax.reload(null, false);
                                } else {
                                    showtoastt(res.message || 'Error', 'error');
                                }
                                $btn.prop('disabled', false);
                            },
                            error: function (xhr) {
                                var msg = 'Request failed';
                                try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (ex) {}
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
