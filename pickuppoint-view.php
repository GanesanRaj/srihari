<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('pickuppoint', 'is_view');

// Get permissions for JS
$can_edit = can_edit('pickuppoint') ? 'true' : 'false';
$can_delete = can_delete('pickuppoint') ? 'true' : 'false';
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 10px;">
                        <div class="col-md-8">
                            <h5 class="fs-16 fw-semibold m-0 pt-1">Pickup Point Details</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="pickuppoint-list.php">
                                <button type="button" class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;Back to List
                                </button>
                            </a>
                        </div>
                    </div>
                </div>

                <div id="loadingCard" class="card">
                    <div class="card-body text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading...
                    </div>
                </div>

                <div id="mainContent" style="display:none;">

                    <!-- Top Action Bar -->
                    <div class="card mb-2" id="actionBar" style="display:none!important;">
                        <div class="card-body py-2 px-3 d-flex gap-2">
                            <a id="editBtn" href="#" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit me-1"></i>Edit</a>
                            <button id="deleteBtn" class="btn btn-sm btn-soft-danger"><i class="ti ti-trash me-1"></i>Delete</button>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">

                            <!-- Basic Info -->
                            <div class="card mb-2">
                                <div class="card-header py-2 px-3" style="background:#f8f9fa; border-left:3px solid #007bff;">
                                    <h6 class="m-0 fw-semibold fs-13">Basic Information</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0 info-table">
                                        <tbody>
                                            <tr>
                                                <th>Company</th>
                                                <td id="company_name">—</td>
                                            </tr>
                                            <tr>
                                                <th>Branch</th>
                                                <td id="branch_name">—</td>
                                            </tr>
                                            <tr>
                                                <th>Courier Partner</th>
                                                <td id="courier_name">—</td>
                                            </tr>
                                            <tr>
                                                <th>Pickup Point Code</th>
                                                <td id="pickup_point_code">—</td>
                                            </tr>
                                            <tr>
                                                <th>Warehouse Name</th>
                                                <td id="name">—</td>
                                            </tr>
                                            <tr>
                                                <th>Registered Name</th>
                                                <td id="registered_name">—</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td id="status_badge">—</td>
                                            </tr>
                                            <tr>
                                                <th>Courier Sync</th>
                                                <td id="sync_status">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="card mb-2">
                                <div class="card-header py-2 px-3" style="background:#f8f9fa; border-left:3px solid #28a745;">
                                    <h6 class="m-0 fw-semibold fs-13">Contact & Address</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0 info-table">
                                        <tbody>
                                            <tr>
                                                <th>Phone</th>
                                                <td id="phone">—</td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td id="email">—</td>
                                            </tr>
                                            <tr>
                                                <th>Address</th>
                                                <td id="address">—</td>
                                            </tr>
                                            <tr>
                                                <th>City</th>
                                                <td id="city">—</td>
                                            </tr>
                                            <tr>
                                                <th>PIN Code</th>
                                                <td id="pin">—</td>
                                            </tr>
                                            <tr>
                                                <th>Country</th>
                                                <td id="country">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">

                            <!-- Return Address -->
                            <div class="card mb-2">
                                <div class="card-header py-2 px-3" style="background:#f8f9fa; border-left:3px solid #fd7e14;">
                                    <h6 class="m-0 fw-semibold fs-13">Return Address</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0 info-table">
                                        <tbody>
                                            <tr>
                                                <th>Return Address</th>
                                                <td id="return_address">—</td>
                                            </tr>
                                            <tr>
                                                <th>Return City</th>
                                                <td id="return_city">—</td>
                                            </tr>
                                            <tr>
                                                <th>Return PIN</th>
                                                <td id="return_pin">—</td>
                                            </tr>
                                            <tr>
                                                <th>Return State</th>
                                                <td id="return_state">—</td>
                                            </tr>
                                            <tr>
                                                <th>Return Country</th>
                                                <td id="return_country">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Meta Info -->
                            <div class="card mb-2">
                                <div class="card-header py-2 px-3" style="background:#f8f9fa; border-left:3px solid #6c757d;">
                                    <h6 class="m-0 fw-semibold fs-13">Record Info</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0 info-table">
                                        <tbody>
                                            <tr>
                                                <th>Created At</th>
                                                <td id="created_at">—</td>
                                            </tr>
                                            <tr>
                                                <th>Updated At</th>
                                                <td id="updated_at">—</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /mainContent -->

            </div>
        </div>

        <?php require_once 'footer.php'; ?>

        <script src="assets/plugins/jquery/jquery.min.js"></script>

        <script>
            const userPermissions = {
                canEdit: <?php echo $can_edit; ?>,
                canDelete: <?php echo $can_delete; ?>
            };

            function getQueryParam(param) {
                return new URLSearchParams(window.location.search).get(param);
            }

            $(document).ready(function () {
                const id = getQueryParam('id');

                if (!id) {
                    window.location.href = 'pickuppoint-list.php';
                    return;
                }

                $.get('api/pickuppoint/readone.php?id=' + id, function (response) {
                    $('#loadingCard').hide();

                    if (response.status !== 'success') {
                        showtoastt('Pickup point not found', 'error');
                        setTimeout(() => window.location.href = 'pickuppoint-list.php', 1500);
                        return;
                    }

                    const d = response.data;

                    // Basic Info
                    $('#company_name').text(d.company_name || '—');
                    $('#branch_name').text(d.branch_name || '—');
                    $('#courier_name').text(d.courier_name || '—');
                    $('#pickup_point_code').text(d.pickup_point_code || '—');
                    $('#name').text(d.name || '—');
                    $('#registered_name').text(d.registered_name || '—');

                    // Status badge
                    $('#status_badge').html(
                        d.status === 'active'
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-danger">Inactive</span>'
                    );

                    // Sync status
                    if (d.courier_token && d.courier_api_url) {
                        $('#sync_status').html(
                            d.delhivery_synced == 1
                                ? '<span class="badge bg-success">Synced</span>'
                                : '<span class="badge bg-warning text-dark">Not Synced</span>'
                        );
                    } else {
                        $('#sync_status').html('<span class="text-muted">N/A (Own/Local)</span>');
                    }

                    // Contact
                    $('#phone').text(d.phone || '—');
                    $('#email').text(d.email || '—');
                    $('#address').text(d.address || '—');
                    $('#city').text(d.city || '—');
                    $('#pin').text(d.pin || '—');
                    $('#country').text(d.country || '—');

                    // Return address
                    $('#return_address').text(d.return_address || '—');
                    $('#return_city').text(d.return_city || '—');
                    $('#return_pin').text(d.return_pin || '—');
                    $('#return_state').text(d.return_state || '—');
                    $('#return_country').text(d.return_country || '—');

                    // Meta
                    $('#created_at').text(d.created_at || '—');
                    $('#updated_at').text(d.updated_at || '—');

                    // Action buttons
                    if (userPermissions.canEdit) {
                        $('#editBtn').attr('href', 'pickuppoint-add.php?id=' + d.id).show();
                        $('#actionBar').css('display', 'block');
                    }
                    if (userPermissions.canDelete) {
                        $('#deleteBtn').show();
                        $('#actionBar').css('display', 'block');
                    }

                    $('#mainContent').show();

                }).fail(function () {
                    $('#loadingCard').hide();
                    showtoastt('Error loading pickup point', 'error');
                });

                // Delete handler
                $(document).on('click', '#deleteBtn', function () {
                    confirmDelete('Are you sure you want to delete this pickup point?', function () {
                        $.ajax({
                            url: 'api/pickuppoint/delete.php?id=' + id,
                            type: 'GET',
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'pickuppoint-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('Error deleting pickup point', 'error');
                            }
                        });
                    });
                });
            });
        </script>

    </div>
</body>

<style>
    .info-table th {
        width: 40%;
        padding: 5px 10px !important;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        background: #fafafa;
    }

    .info-table td {
        padding: 5px 10px !important;
        font-size: 13px;
    }

    .info-table tr:last-child th,
    .info-table tr:last-child td {
        border-bottom: 0;
    }
</style>

</html>
