<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('courier_partner', 'is_view');

// Get permissions for JS
$can_edit = can_edit('courier_partner') ? 'true' : 'false';
$can_delete = can_delete('courier_partner') ? 'true' : 'false';
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">

                    <!-- Page Title / Breadcrumb -->
                    <div class="page-title-head d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="fs-xl fw-bold m-0">Courier Partner Profile</h4>
                        </div>
                        <div class="text-end">
                            <ol class="breadcrumb m-0 py-0">
                                <li class="breadcrumb-item"><a href="courier-partner-list.php">Courier Partners</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Banner / Cover Image -->
                    <div class="row">
                        <div class="col-12">
                            <article class="card overflow-hidden mb-0">
                                <div class="position-relative card-side-img overflow-hidden"
                                    style="min-height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div
                                        class="p-4 card-img-overlay d-flex align-items-center flex-column justify-content-center">
                                        <h3 class="text-white mb-1 fst-italic" id="banner_partner_name"></h3>
                                        <p class="text-white-50 mb-0" id="banner_partner_code"></p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="px-3 mt-n4">
                        <div class="row">
                            <!-- Left Column - Partner Info Card -->
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-body">
                                        <!-- Partner Name & Status -->
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="me-3 position-relative">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                    style="width: 72px; height: 72px;">
                                                    <i class="ri-truck-line fs-1 text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h5 class="mb-1 d-flex align-items-center">
                                                    <span id="profile_partner_name" class="fw-bold"></span>
                                                </h5>
                                                <span id="profile_status_badge" class="badge rounded-pill"></span>
                                            </div>
                                            <div class="ms-auto" id="profile_actions_dropdown">
                                                <div class="dropdown">
                                                    <a href="#" class="btn btn-icon btn-ghost-light text-muted"
                                                        data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical fs-xl"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="#" id="dropdown_edit_link"><i
                                                                    class="ri-edit-line me-1"></i> Edit Partner</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Info Rows -->
                                        <div>
                                            <!-- Partner Code -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-barcode fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Code: <span class="text-dark fw-semibold"
                                                        id="profile_partner_code"></span></p>
                                            </div>

                                            <!-- Username -->
                                            <div class="d-flex align-items-center gap-2 mb-2" id="profile_username_row">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-user fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Username: <span class="text-dark fw-semibold"
                                                        id="profile_username"></span></p>
                                            </div>

                                            <!-- API URL -->
                                            <div class="d-flex align-items-center gap-2 mb-2" id="profile_api_url_row">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-link fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm text-truncate">API URL: <span
                                                        class="text-dark fw-semibold" id="profile_api_url"></span></p>
                                            </div>

                                            <!-- Preference Order -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-sort-ascending fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Preference: <span class="text-dark fw-semibold"
                                                        id="profile_preference"></span></p>
                                            </div>

                                            <!-- Created At -->
                                            <div class="d-flex align-items-center gap-2" id="profile_created_row">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-calendar fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Created: <span class="text-dark fw-semibold"
                                                        id="profile_created_at"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Details Card with Tabs -->
                            <div class="col-xl-8">
                                <div class="card">
                                    <div class="card-header card-tabs d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h4 class="card-title">API Configuration</h4>
                                        </div>
                                        <ul class="nav nav-tabs card-header-tabs nav-bordered">
                                            <li class="nav-item">
                                                <a href="#api-credentials" data-bs-toggle="tab" class="nav-link active">
                                                    <i class="ti ti-key d-md-none d-block"></i>
                                                    <span class="d-none d-md-block fw-bold">Credentials</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#partner-remarks" data-bs-toggle="tab" class="nav-link">
                                                    <i class="ti ti-notes d-md-none d-block"></i>
                                                    <span class="d-none d-md-block fw-bold">Remarks</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="card-body">
                                        <div class="tab-content">
                                            <!-- API Credentials Tab -->
                                            <div class="tab-pane active" id="api-credentials">
                                                <h4 class="card-title mb-3 text-uppercase fs-sm"><i
                                                        class="ti ti-key"></i> API Credentials</h4>

                                                <div class="table-responsive">
                                                    <table class="table table-borderless table-sm mb-0">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-muted" style="width: 180px;">Partner
                                                                    Name</td>
                                                                <td class="fw-semibold" id="detail_partner_name"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Partner Code</td>
                                                                <td class="fw-semibold" id="detail_partner_code"></td>
                                                            </tr>
                                                            <tr id="detail_api_url_row">
                                                                <td class="text-muted">API URL</td>
                                                                <td class="fw-semibold" id="detail_api_url"></td>
                                                            </tr>
                                                            <tr id="detail_api_key_row">
                                                                <td class="text-muted">API Key</td>
                                                                <td class="fw-semibold font-monospace"
                                                                    id="detail_api_key"></td>
                                                            </tr>
                                                            <tr id="detail_username_row">
                                                                <td class="text-muted">Username</td>
                                                                <td class="fw-semibold" id="detail_username"></td>
                                                            </tr>
                                                            <tr id="detail_password_row">
                                                                <td class="text-muted">Password</td>
                                                                <td class="fw-semibold font-monospace"
                                                                    id="detail_password"></td>
                                                            </tr>
                                                            <tr id="detail_token_row">
                                                                <td class="text-muted">Token</td>
                                                                <td class="fw-semibold font-monospace text-break"
                                                                    id="detail_token"></td>
                                                            </tr>
                                                            <tr id="detail_client_id_row">
                                                                <td class="text-muted">Client ID</td>
                                                                <td class="fw-semibold" id="detail_client_id"></td>
                                                            </tr>
                                                            <tr id="detail_client_secret_row">
                                                                <td class="text-muted">Client Secret</td>
                                                                <td class="fw-semibold font-monospace text-break"
                                                                    id="detail_client_secret"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Preference Order</td>
                                                                <td class="fw-semibold" id="detail_preference"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Status</td>
                                                                <td><span id="detail_status_badge"
                                                                        class="badge rounded-pill"></span></td>
                                                            </tr>
                                                            <tr id="detail_created_row">
                                                                <td class="text-muted">Created At</td>
                                                                <td class="fw-semibold" id="detail_created_at"></td>
                                                            </tr>
                                                            <tr id="detail_updated_row">
                                                                <td class="text-muted">Updated At</td>
                                                                <td class="fw-semibold" id="detail_updated_at"></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="mt-4 d-flex gap-2" id="action_buttons_section">
                                                    <a href="#" id="edit_partner_btn" class="btn btn-info rounded-pill">
                                                        <i class="ri-edit-line"></i> Edit Partner
                                                    </a>
                                                    <a href="courier-partner-list.php"
                                                        class="btn btn-secondary rounded-pill">
                                                        <i class="ri-arrow-left-line"></i> Back to List
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- Remarks Tab -->
                                            <div class="tab-pane" id="partner-remarks">
                                                <h4 class="card-title mb-3 text-uppercase fs-sm"><i
                                                        class="ti ti-notes"></i> Remarks / Notes</h4>
                                                <div id="detail_remarks_content">
                                                    <p id="detail_remarks" class="text-muted"></p>
                                                </div>
                                                <div id="detail_no_remarks" style="display: none;">
                                                    <p class="text-muted fst-italic">No remarks available for this
                                                        courier partner.</p>
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
                const userPermissions = {
                    canEdit: <?php echo $can_edit; ?>,
                    canDelete: <?php echo $can_delete; ?>
                };

                document.addEventListener("DOMContentLoaded", function () {
                    // Get partner ID from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const partnerId = urlParams.get('id');

                    if (!partnerId) {
                        window.location.href = 'courier-partner-list.php';
                        return;
                    }

                    // Hide edit elements if no permission
                    if (!userPermissions.canEdit) {
                        document.getElementById('profile_actions_dropdown').style.display = 'none';
                        document.getElementById('edit_partner_btn').style.display = 'none';
                    }

                    // Fetch partner data
                    $.get(`api/courier_partner/read_single.php?id=${partnerId}`, function (response) {
                        if (response.status === 'success') {
                            const p = response.data;

                            // Banner
                            $('#banner_partner_name').text(p.partner_name);
                            $('#banner_partner_code').text(p.partner_code);

                            // Profile Card - Name & Status
                            $('#profile_partner_name').text(p.partner_name);
                            if (p.status === 'active') {
                                $('#profile_status_badge, #detail_status_badge').text('Active').removeClass('bg-danger').addClass('bg-success');
                            } else {
                                $('#profile_status_badge, #detail_status_badge').text('Inactive').removeClass('bg-success').addClass('bg-danger');
                            }

                            // Profile Card - Info Rows
                            $('#profile_partner_code').text(p.partner_code);
                            $('#profile_preference').text(p.preference_order);

                            if (p.username) {
                                $('#profile_username').text(p.username);
                            } else {
                                $('#profile_username_row').hide();
                            }

                            if (p.api_url) {
                                $('#profile_api_url').text(p.api_url);
                            } else {
                                $('#profile_api_url_row').hide();
                            }

                            if (p.created_at) {
                                let date = new Date(p.created_at);
                                let formatted = date.toLocaleDateString('en-IN', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                $('#profile_created_at, #detail_created_at').text(formatted);
                            } else {
                                $('#profile_created_row, #detail_created_row').hide();
                            }

                            // Detail Table (Right Column)
                            $('#detail_partner_name').text(p.partner_name);
                            $('#detail_partner_code').text(p.partner_code);
                            $('#detail_preference').text(p.preference_order);

                            if (p.api_url) {
                                $('#detail_api_url').text(p.api_url);
                            } else {
                                $('#detail_api_url_row').hide();
                            }

                            if (p.api_key) {
                                $('#detail_api_key').text(p.api_key);
                            } else {
                                $('#detail_api_key_row').hide();
                            }

                            if (p.username) {
                                $('#detail_username').text(p.username);
                            } else {
                                $('#detail_username_row').hide();
                            }

                            if (p.password) {
                                $('#detail_password').text(p.password);
                            } else {
                                $('#detail_password_row').hide();
                            }

                            if (p.token) {
                                $('#detail_token').text(p.token);
                            } else {
                                $('#detail_token_row').hide();
                            }

                            if (p.client_id) {
                                $('#detail_client_id').text(p.client_id);
                            } else {
                                $('#detail_client_id_row').hide();
                            }

                            if (p.client_secret) {
                                $('#detail_client_secret').text(p.client_secret);
                            } else {
                                $('#detail_client_secret_row').hide();
                            }

                            if (p.updated_at) {
                                let date = new Date(p.updated_at);
                                let formatted = date.toLocaleDateString('en-IN', {
                                    day: '2-digit', month: 'short', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                });
                                $('#detail_updated_at').text(formatted);
                            } else {
                                $('#detail_updated_row').hide();
                            }

                            // Remarks
                            if (p.remarks) {
                                $('#detail_remarks').text(p.remarks);
                                $('#detail_remarks_content').show();
                                $('#detail_no_remarks').hide();
                            } else {
                                $('#detail_remarks_content').hide();
                                $('#detail_no_remarks').show();
                            }

                            // Edit links
                            $('#edit_partner_btn, #dropdown_edit_link').attr('href', `courier-partner-add.php?id=${p.id}`);

                        } else {
                            alert('Courier partner not found');
                            window.location.href = 'courier-partner-list.php';
                        }
                    }).fail(function () {
                        alert('Error loading courier partner data');
                        window.location.href = 'courier-partner-list.php';
                    });
                });
            </script>
        </div>
    </div>
    </div>
</body>

<style>
    .avatar-sm {
        width: 36px;
        height: 36px;
        min-width: 36px;
    }
</style>

</html>