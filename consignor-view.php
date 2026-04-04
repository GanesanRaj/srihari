<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('consignor', 'is_view');

$can_edit = can_edit('consignor');
$can_delete = can_delete('consignor');
?>

<style>
    .profile-info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 13px;
    }

    .profile-info-value {
        font-size: 13px;
        color: #343a40;
    }

    .profile-card {
        border-radius: 10px;
    }

    .profile-header {
        background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
        border-radius: 10px 10px 0 0;
        padding: 25px;
        color: #fff;
        text-align: center;
    }

    .info-row {
        padding: 8px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .info-row:last-child {
        border-bottom: none;
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
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="consignor-list.php"><button type="button"
                                    class="btn btn-sm rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Consignor List
                                </button></a>
                            <?php if ($can_edit): ?>
                                <a href="#" id="editBtn"><button type="button"
                                        class="btn btn-sm rounded-pill btn-warning waves-effect waves-light">
                                        <i class="ri-edit-line"></i> &nbsp;&nbsp;Edit
                                    </button></a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                                <button type="button" id="deleteBtn"
                                    class="btn btn-sm rounded-pill btn-danger waves-effect waves-light">
                                    <i class="ri-delete-bin-line"></i> &nbsp;&nbsp;Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column - Consignor Info Card -->
                    <div class="col-xl-4">
                        <div class="card profile-card">
                            <div class="profile-header">
                                <div class="mb-2">
                                    <i class="ri-truck-line" style="font-size: 48px;"></i>
                                </div>
                                <h4 class="text-white mb-1" id="view_name">-</h4>
                                <p class="text-white-50 mb-1" id="view_city">-</p>
                                <span class="badge bg-light text-dark" id="view_status_badge">-</span>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="profile-info-label">Branch</span>
                                    <p class="profile-info-value mb-0" id="view_branch_name">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Client</span>
                                    <p class="profile-info-value mb-0" id="view_client_name">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Contact No</span>
                                    <p class="profile-info-value mb-0" id="view_contact_no">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Email</span>
                                    <p class="profile-info-value mb-0" id="view_email">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Details Tabs -->
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#details" role="tab">
                                            <i class="ri-information-line me-1"></i> Details
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#audit" role="tab">
                                            <i class="ri-history-line me-1"></i> Audit Info
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content pt-3">
                                    <!-- Details Tab -->
                                    <div class="tab-pane active" id="details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <span class="profile-info-label">Consignor Name</span>
                                                    <p class="profile-info-value mb-0" id="detail_name">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Branch</span>
                                                    <p class="profile-info-value mb-0" id="detail_branch_name">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Client</span>
                                                    <p class="profile-info-value mb-0" id="detail_client_name">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Contact No</span>
                                                    <p class="profile-info-value mb-0" id="detail_contact_no">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Alternate Contact No</span>
                                                    <p class="profile-info-value mb-0" id="detail_alt_contact_no">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Email</span>
                                                    <p class="profile-info-value mb-0" id="detail_email">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">GST Number</span>
                                                    <p class="profile-info-value mb-0" id="detail_gst_number">-</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <span class="profile-info-label">Address</span>
                                                    <p class="profile-info-value mb-0" id="detail_address">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Location</span>
                                                    <p class="profile-info-value mb-0" id="detail_location">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">City</span>
                                                    <p class="profile-info-value mb-0" id="detail_city">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">State</span>
                                                    <p class="profile-info-value mb-0" id="detail_state">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Pincode</span>
                                                    <p class="profile-info-value mb-0" id="detail_pincode">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Status</span>
                                                    <p class="profile-info-value mb-0" id="detail_status">-</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Audit Info Tab -->
                                    <div class="tab-pane" id="audit" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <span class="profile-info-label">Created By</span>
                                                    <p class="profile-info-value mb-0" id="detail_created_by">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Created At</span>
                                                    <p class="profile-info-value mb-0" id="detail_created_at">-</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <span class="profile-info-label">Updated By</span>
                                                    <p class="profile-info-value mb-0" id="detail_updated_by">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Updated At</span>
                                                    <p class="profile-info-value mb-0" id="detail_updated_at">-</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <script>
                document.addEventListener("DOMContentLoaded", function () {

                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    let consignorId = getQueryParam("id");

                    if (!consignorId) {
                        showtoastt('Consignor ID is required', 'error');
                        setTimeout(() => window.location.href = 'consignor-list.php', 1500);
                        return;
                    }

                    // Set edit link
                    $('#editBtn').attr('href', `consignor-add.php?id=${consignorId}`);

                    // Load consignor data
                    $.get(`api/consignor/read_single.php?id=${consignorId}`, function (response) {
                        if (response.status === 'success') {
                            const data = response.data;

                            // Profile Card
                            $('#view_name').text(data.name || '-');
                            $('#view_city').text(data.city || '-');
                            $('#view_branch_name').text(data.branch_name || '-');
                            $('#view_client_name').text(data.client_name || '-');
                            $('#view_contact_no').text(data.contact_no || '-');
                            $('#view_email').text(data.email || '-');

                            // Status badge
                            if (data.status === 'active') {
                                $('#view_status_badge').removeClass('bg-danger').addClass('bg-success').text('Active');
                            } else {
                                $('#view_status_badge').removeClass('bg-success').addClass('bg-danger').text('Inactive');
                            }

                            // Details Tab
                            $('#detail_name').text(data.name || '-');
                            $('#detail_branch_name').text(data.branch_name || '-');
                            $('#detail_client_name').text(data.client_name || '-');
                            $('#detail_contact_no').text(data.contact_no || '-');
                            $('#detail_alt_contact_no').text(data.alt_contact_no || '-');
                            $('#detail_email').text(data.email || '-');
                            $('#detail_gst_number').text(data.gst_number || '-');
                            $('#detail_address').text(data.address || '-');
                            $('#detail_location').text(data.location || '-');
                            $('#detail_city').text(data.city || '-');
                            $('#detail_state').text(data.state || '-');
                            $('#detail_pincode').text(data.pincode || '-');

                            if (data.status === 'active') {
                                $('#detail_status').html('<span class="badge bg-success">Active</span>');
                            } else {
                                $('#detail_status').html('<span class="badge bg-danger">Inactive</span>');
                            }

                            // Audit Tab
                            $('#detail_created_by').text(data.created_by_name || '-');
                            $('#detail_created_at').text(data.created_at || '-');
                            $('#detail_updated_by').text(data.updated_by_name || '-');
                            $('#detail_updated_at').text(data.updated_at || '-');

                        } else {
                            showtoastt('Consignor not found', 'error');
                            setTimeout(() => window.location.href = 'consignor-list.php', 1500);
                        }
                    }).fail(function () {
                        showtoastt('Error loading consignor details', 'error');
                    });

                    // Delete handler
                    $('#deleteBtn').on('click', function () {
                        if (confirm('Are you sure you want to delete this consignor?')) {
                            $.post('api/consignor/delete.php', { id: consignorId }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'consignor-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            });
                        }
                    });

                });
            </script>
        </div>
    </div>
</body>

</html>