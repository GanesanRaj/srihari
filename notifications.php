<?php
require_once 'header.php';
?>
<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />

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
                                <i class="ti ti-bell-ringing me-1"></i> Notifications Center
                            </h4>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-soft-primary" id="markAllReadBtn">
                                <i class="ti ti-checks me-1"></i> Mark All as Read
                            </button>
                            <button class="btn btn-sm btn-soft-danger" id="clearAllBtn">
                                <i class="ti ti-trash me-1"></i> Clear All
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select id="filter_type" class="form-select form-select-sm">
                                                <option value="">All Types</option>
                                                <option value="system">System</option>
                                                <option value="booking">Booking</option>
                                                <option value="payment">Payment</option>
                                                <option value="user">User</option>
                                                <option value="alert">Alert</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="filter_status" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="unread">Unread</option>
                                                <option value="read">Read</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-sm btn-soft-primary" id="filterBtn">
                                                <i class="ti ti-search me-1"></i> Search
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light" id="resetBtn">
                                                <i class="ti ti-rotate"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <table id="notificationsTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th width="50">Status</th>
                                                <th>Type</th>
                                                <th>Message</th>
                                                <th>Date & Time</th>
                                                <th width="100">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <!-- Datatables js -->
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>

            <script>
                $(document).ready(function() {
                    const table = $('#notificationsTable').DataTable({
                        processing: true,
                        serverSide: true,
                        fixedHeader: {
                            header: true,
                            headerOffset: 65
                        },
                        ajax: {
                            url: 'api/notifications/read.php',
                            type: 'GET',
                            data: function(d) {
                                d.type = $('#filter_type').val();
                                d.status = $('#filter_status').val();
                            }
                        },
                        columns: [
                            {
                                data: 'is_read',
                                orderable: false,
                                render: function(data) {
                                    return data == 0 ?
                                        '<span class="badge bg-primary">New</span>' :
                                        '<span class="badge bg-secondary">Read</span>';
                                }
                            },
                            {
                                data: 'type',
                                render: function(data) {
                                    var badges = {
                                        'system': 'bg-info',
                                        'booking': 'bg-success',
                                        'payment': 'bg-warning',
                                        'user': 'bg-primary',
                                        'alert': 'bg-danger'
                                    };
                                    return '<span class="badge ' + (badges[data] || 'bg-secondary') + '">' + data.toUpperCase() + '</span>';
                                }
                            },
                            { data: 'message' },
                            { data: 'created_at' },
                            {
                                data: null,
                                orderable: false,
                                render: function(data, type, row) {
                                    var buttons = '<div class="d-flex gap-1">';
                                    if (row.is_read == 0) {
                                        buttons += '<button class="btn btn-sm btn-soft-primary mark-read-btn" data-id="' + row.id + '"><i class="ti ti-check"></i></button>';
                                    }
                                    buttons += '<button class="btn btn-sm btn-soft-danger delete-btn" data-id="' + row.id + '"><i class="ti ti-trash"></i></button>';
                                    buttons += '</div>';
                                    return buttons;
                                }
                            }
                        ],
                        pageLength: 25,
                        order: [[3, 'desc']],
                        language: {
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        }
                    });

                    // Filter button
                    $('#filterBtn').on('click', function() {
                        table.ajax.reload();
                    });

                    // Reset button
                    $('#resetBtn').on('click', function() {
                        $('#filter_type').val('');
                        $('#filter_status').val('');
                        table.ajax.reload();
                    });

                    // Mark as read
                    $(document).on('click', '.mark-read-btn', function() {
                        var id = $(this).data('id');
                        $.ajax({
                            url: 'api/notifications/mark-read.php',
                            type: 'POST',
                            data: { id: id },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    table.ajax.reload(null, false);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error marking notification as read', 'error');
                            }
                        });
                    });

                    // Delete notification
                    $(document).on('click', '.delete-btn', function() {
                        var id = $(this).data('id');
                        confirmDelete('Are you sure you want to delete this notification?', function() {
                            $.ajax({
                                url: 'api/notifications/delete.php',
                                type: 'POST',
                                data: { id: id },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === 'success') {
                                        showtoastt(response.message, 'success');
                                        table.ajax.reload(null, false);
                                    } else {
                                        showtoastt(response.message, 'error');
                                    }
                                },
                                error: function() {
                                    showtoastt('Error deleting notification', 'error');
                                }
                            });
                        });
                    });

                    // Mark all as read
                    $('#markAllReadBtn').on('click', function() {
                        $.ajax({
                            url: 'api/notifications/mark-all-read.php',
                            type: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    table.ajax.reload();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error marking all notifications as read', 'error');
                            }
                        });
                    });

                    // Clear all notifications
                    $('#clearAllBtn').on('click', function() {
                        confirmDelete('Are you sure you want to clear all notifications? This action cannot be undone.', function() {
                            $.ajax({
                                url: 'api/notifications/clear-all.php',
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === 'success') {
                                        showtoastt(response.message, 'success');
                                        table.ajax.reload();
                                    } else {
                                        showtoastt(response.message, 'error');
                                    }
                                },
                                error: function() {
                                    showtoastt('Error clearing notifications', 'error');
                                }
                            });
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

<style>
    .table-sm th,
    .table-sm td {
        padding: 5px !important;
        font-size: 13px;
    }

    #notificationsTable,
    #notificationsTable * {
        color: #000000 !important;
    }

    .form-control-sm,
    .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }
</style>

</html>
