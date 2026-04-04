<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('status', 'is_view');

// Get permissions
$can_add = can_add('status');
$can_edit = can_edit('status');
$can_delete = can_delete('status');
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0 10px;">

                <div class="card" style="margin-bottom:5px; margin-top:10px;">
                    <div class="row" style="padding:5px 5px;">
                        <div class="col-md-8">
                            <h4 class="mb-0">Status Master</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($can_add): ?>
                                <a href="status-add.php">
                                    <button type="button"
                                        class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                        <i class="ri-add-circle-fill"></i>&nbsp;&nbsp;Add New Status
                                    </button>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body" style="padding:5px 20px;">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <table id="statusTable" class="table table-striped table-bordered dt-responsive nowrap"
                            style="width:100%; font-size:12px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
            <script src="assets/plugins/datatables/buttons.bootstrap5.min.js"></script>

            <script>
                $(document).ready(function () {
                    var table = $('#statusTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/status/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.status = $('#statusFilter').val();
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'name' },
                            { data: 'code' },
                            {
                                data: 'status',
                                render: function (data) {
                                    return data === 'active'
                                        ? '<span class="badge bg-success">Active</span>'
                                        : '<span class="badge bg-danger">Inactive</span>';
                                }
                            },
                            { data: 'remarks' },
                            {
                                data: null,
                                orderable: false,
                                render: function (data, type, row) {
                                    let actions = '';
                                    <?php if ($can_edit): ?>
                                        actions += `<a href="status-add.php?id=${row.id}" class="btn btn-xs btn-warning" title="Edit"><i class="ri-edit-line"></i> Edit</a> `;
                                    <?php endif; ?>
                                    <?php if ($can_delete): ?>
                                        actions += `<button class="btn btn-xs btn-danger delete-btn" data-id="${row.id}" title="Delete"><i class="ri-delete-bin-line"></i> Delete</button>`;
                                    <?php endif; ?>
                                    return actions || '-';
                                }
                            }
                        ],
                        order: [[0, 'desc']],
                        pageLength: 25
                    });

                    $('#statusFilter').on('change', function () {
                        table.ajax.reload();
                    });

                    $('#statusTable').on('click', '.delete-btn', function () {
                        var id = $(this).data('id');
                        if (!confirm('Are you sure you want to delete this status?')) {
                            return;
                        }
                        $.post('api/status/delete.php', { id: id }, function (res) {
                            if (res.status === 'success') {
                                if (typeof showtoastt === 'function') {
                                    showtoastt(res.message, 'success');
                                } else {
                                    alert(res.message);
                                }
                                table.ajax.reload();
                            } else {
                                if (typeof showtoastt === 'function') {
                                    showtoastt(res.message, 'error');
                                } else {
                                    alert(res.message || 'Delete failed');
                                }
                            }
                        }).fail(function () {
                            if (typeof showtoastt === 'function') {
                                showtoastt('Server error while deleting', 'error');
                            } else {
                                alert('Server error while deleting');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>