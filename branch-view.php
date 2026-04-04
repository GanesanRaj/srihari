<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('branch', 'is_view');

$can_edit = can_edit('branch');
$can_delete = can_delete('branch');
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                            <a href="branch-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Branch List
                                </button></a>
                            <?php if ($can_edit): ?>
                                <a href="#" id="editBtn"><button type="button"
                                        class="btn btn-xs rounded-pill btn-warning waves-effect waves-light">
                                        <i class="ri-edit-line"></i> &nbsp;&nbsp;Edit
                                    </button></a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                                <button type="button" id="deleteBtn"
                                    class="btn btn-xs rounded-pill btn-danger waves-effect waves-light">
                                    <i class="ri-delete-bin-line"></i> &nbsp;&nbsp;Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column - Branch Info Card -->
                    <div class="col-xl-4">
                        <div class="card profile-card">
                            <div class="profile-header">
                                <div class="mb-2">
                                    <i class="ri-building-2-line" style="font-size: 48px;"></i>
                                </div>
                                <h4 class="text-white mb-1" id="view_branch_name">-</h4>
                                <p class="text-white-50 mb-1" id="view_branch_code">-</p>
                                <span class="badge bg-light text-dark" id="view_status_badge">-</span>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="profile-info-label">Company</span>
                                    <p class="profile-info-value mb-0" id="view_company_name">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Contact No</span>
                                    <p class="profile-info-value mb-0" id="view_contact_no">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Email</span>
                                    <p class="profile-info-value mb-0" id="view_email">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">State</span>
                                    <p class="profile-info-value mb-0" id="view_state">-</p>
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
                                        <a class="nav-link" data-bs-toggle="tab" href="#remarks" role="tab">
                                            <i class="ri-chat-3-line me-1"></i> Remarks
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
                                                    <span class="profile-info-label">Branch Name</span>
                                                    <p class="profile-info-value mb-0" id="detail_branch_name">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Branch Code</span>
                                                    <p class="profile-info-value mb-0" id="detail_branch_code">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Company</span>
                                                    <p class="profile-info-value mb-0" id="detail_company_name">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Contact No</span>
                                                    <p class="profile-info-value mb-0" id="detail_contact_no">-</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row">
                                                    <span class="profile-info-label">Email</span>
                                                    <p class="profile-info-value mb-0" id="detail_email">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Address</span>
                                                    <p class="profile-info-value mb-0" id="detail_address">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">State</span>
                                                    <p class="profile-info-value mb-0" id="detail_state">-</p>
                                                </div>
                                                <div class="info-row">
                                                    <span class="profile-info-label">Status</span>
                                                    <p class="profile-info-value mb-0" id="detail_status">-</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks Tab -->
                                    <div class="tab-pane" id="remarks" role="tabpanel">
                                        <div class="info-row">
                                            <span class="profile-info-label">Remarks</span>
                                            <p class="profile-info-value mb-0" id="detail_remarks">-</p>
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

                    let branchId = getQueryParam("id");

                    if (!branchId) {
                        showtoastt('Branch ID is required', 'error');
                        setTimeout(() => window.location.href = 'branch-list.php', 1500);
                        return;
                    }

                    // Set edit link
                    $('#editBtn').attr('href', `branch-add.php?id=${branchId}`);

                    // Load branch data
                    $.get(`api/branch/read_single.php?id=${branchId}`, function (response) {
                        if (response.status === 'success') {
                            const data = response.data;

                            // Profile Card
                            $('#view_branch_name').text(data.branch_name || '-');
                            $('#view_branch_code').text(data.branch_code || '-');
                            $('#view_company_name').text(data.company_name || '-');
                            $('#view_contact_no').text(data.contact_no || '-');
                            $('#view_email').text(data.email || '-');
                            $('#view_state').text(data.state || '-');

                            // Status badge
                            if (data.status === 'active') {
                                $('#view_status_badge').removeClass('bg-danger').addClass('bg-success').text('Active');
                            } else {
                                $('#view_status_badge').removeClass('bg-success').addClass('bg-danger').text('Inactive');
                            }

                            // Details Tab
                            $('#detail_branch_name').text(data.branch_name || '-');
                            $('#detail_branch_code').text(data.branch_code || '-');
                            $('#detail_company_name').text(data.company_name || '-');
                            $('#detail_contact_no').text(data.contact_no || '-');
                            $('#detail_email').text(data.email || '-');
                            $('#detail_address').text(data.address || '-');
                            $('#detail_state').text(data.state || '-');

                            if (data.status === 'active') {
                                $('#detail_status').html('<span class="badge bg-success">Active</span>');
                            } else {
                                $('#detail_status').html('<span class="badge bg-danger">Inactive</span>');
                            }

                            // Remarks Tab
                            $('#detail_remarks').text(data.remarks || 'No remarks');

                            // Audit Tab
                            $('#detail_created_by').text(data.created_by_name || '-');
                            $('#detail_created_at').text(data.created_at || '-');
                            $('#detail_updated_by').text(data.updated_by_name || '-');
                            $('#detail_updated_at').text(data.updated_at || '-');

                        } else {
                            showtoastt('Branch not found', 'error');
                            setTimeout(() => window.location.href = 'branch-list.php', 1500);
                        }
                    }).fail(function () {
                        showtoastt('Error loading branch details', 'error');
                    });

                    // Delete handler
                    $('#deleteBtn').on('click', function () {
                        if (confirm('Are you sure you want to delete this branch?')) {
                            $.post('api/branch/delete.php', { id: branchId }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'branch-list.php', 1500);
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