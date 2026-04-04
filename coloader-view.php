<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('coloader', 'is_view');

$can_edit = can_edit('coloader');
$can_delete = can_delete('coloader');
?>
<style>
    .profile-info-label { font-weight: 600; color: #6c757d; font-size: 13px; }
    .profile-info-value { font-size: 13px; color: #343a40; }
    .profile-card { border-radius: 10px; }
    .profile-header { background: linear-gradient(135deg, #405189 0%, #0ab39c 100%); border-radius: 10px 10px 0 0; padding: 25px; color: #fff; text-align: center; }
    .info-row { padding: 8px 0; border-bottom: 1px solid #f1f1f1; }
    .info-row:last-child { border-bottom: none; }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">
                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8"></div>
                        <div class="col-md-4 text-end">
                            <a href="coloader-list.php" class="btn btn-sm rounded-pill btn-primary waves-effect waves-light">
                                <i class="ri-arrow-left-circle-fill"></i> Back to Coloader List
                            </a>
                            <?php if ($can_edit): ?>
                                <a href="#" id="editBtn" class="btn btn-sm rounded-pill btn-warning waves-effect waves-light"><i class="ri-edit-line"></i> Edit</a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                                <button type="button" id="deleteBtn" class="btn btn-sm rounded-pill btn-danger waves-effect waves-light"><i class="ri-delete-bin-line"></i> Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-4">
                        <div class="card profile-card">
                            <div class="profile-header">
                                <div class="mb-2"><i class="ri-user-line" style="font-size: 48px;"></i></div>
                                <h4 class="text-white mb-1" id="view_name">-</h4>
                                <span class="badge bg-light text-dark" id="view_status_badge">-</span>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="profile-info-label">Mobile Number</span>
                                    <p class="profile-info-value mb-0" id="view_mobile_number">-</p>
                                </div>
                                <div class="info-row">
                                    <span class="profile-info-label">Email</span>
                                    <p class="profile-info-value mb-0" id="view_email">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details" role="tab"><i class="ri-information-line me-1"></i> Details</a></li>
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#audit" role="tab"><i class="ri-history-line me-1"></i> Audit Info</a></li>
                                </ul>
                                <div class="tab-content pt-3">
                                    <div class="tab-pane active" id="details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row"><span class="profile-info-label">Name</span><p class="profile-info-value mb-0" id="detail_name">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Mobile Number</span><p class="profile-info-value mb-0" id="detail_mobile_number">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Email</span><p class="profile-info-value mb-0" id="detail_email">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Status</span><p class="profile-info-value mb-0" id="detail_status">-</p></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row"><span class="profile-info-label">Address</span><p class="profile-info-value mb-0" id="detail_address">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Remarks</span><p class="profile-info-value mb-0" id="detail_remarks">-</p></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="audit" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-row"><span class="profile-info-label">Created By</span><p class="profile-info-value mb-0" id="detail_created_by">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Created At</span><p class="profile-info-value mb-0" id="detail_created_at">-</p></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-row"><span class="profile-info-label">Updated By</span><p class="profile-info-value mb-0" id="detail_updated_by">-</p></div>
                                                <div class="info-row"><span class="profile-info-label">Updated At</span><p class="profile-info-value mb-0" id="detail_updated_at">-</p></div>
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

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    var id = new URLSearchParams(window.location.search).get("id");
                    if (!id) {
                        showtoastt('Coloader ID is required', 'error');
                        setTimeout(function () { window.location.href = 'coloader-list.php'; }, 1500);
                        return;
                    }

                    function setText(sel, val) {
                        var v = val != null && val !== '' ? val : '-';
                        $(sel).text(v);
                    }

                    $.get('api/coloader/read_single.php?id=' + id, function (response) {
                        if (response.status !== 'success') {
                            showtoastt('Coloader not found', 'error');
                            setTimeout(function () { window.location.href = 'coloader-list.php'; }, 1500);
                            return;
                        }
                        var d = response.data;
                        setText('#view_name', d.name);
                        setText('#view_mobile_number', d.mobile_number);
                        setText('#view_email', d.email);
                        $('#view_status_badge').text(d.status === 'active' ? 'Active' : 'Inactive').removeClass('bg-light').addClass(d.status === 'active' ? 'bg-success' : 'bg-danger');

                        setText('#detail_name', d.name);
                        setText('#detail_mobile_number', d.mobile_number);
                        setText('#detail_email', d.email);
                        setText('#detail_address', d.address);
                        setText('#detail_status', d.status);
                        setText('#detail_remarks', d.remarks);
                        setText('#detail_created_by', d.created_by_name);
                        setText('#detail_created_at', d.created_at);
                        setText('#detail_updated_by', d.updated_by_name);
                        setText('#detail_updated_at', d.updated_at);
                    }).fail(function () {
                        showtoastt('Error loading coloader', 'error');
                    });

                    $('#editBtn').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = 'coloader-add.php?id=' + id;
                    });

                    $('#deleteBtn').on('click', function () {
                        confirmDelete('Are you sure you want to delete this coloader?', function () {
                            $.get('api/coloader/delete.php?id=' + id, function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(function () { window.location.href = 'coloader-list.php'; }, 1500);
                                } else {
                                    showtoastt(response.message || 'Error', 'error');
                                }
                            }).fail(function () { showtoastt('Error deleting', 'error'); });
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
