<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission('company', 'is_view');

// Get permissions for JS
$can_edit = can_edit('company') ? 'true' : 'false';
$can_delete = can_delete('company') ? 'true' : 'false';
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
                            <h4 class="fs-xl fw-bold m-0">Company Profile</h4>
                        </div>
                        <div class="text-end">
                            <ol class="breadcrumb m-0 py-0">
                                <li class="breadcrumb-item"><a href="company-list.php">Company List</a></li>
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
                                        <h3 class="text-white mb-1 fst-italic" id="banner_company_name"></h3>
                                        <p class="text-white-50 mb-0" id="banner_address"></p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="px-3 mt-n4">
                        <div class="row">
                            <!-- Left Column - Company Info Card -->
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-body">
                                        <!-- Company Logo & Name -->
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="me-3 position-relative">
                                                <img id="profile_logo" src="" alt="Company Logo"
                                                    class="rounded-circle border border-3 border-white shadow-sm"
                                                    style="width: 72px; height: 72px; object-fit: cover;">
                                                <div id="profile_logo_placeholder"
                                                    class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                    style="width: 72px; height: 72px; display: none;">
                                                    <i class="ri-building-2-line fs-1 text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h5 class="mb-1 d-flex align-items-center">
                                                    <span id="profile_company_name" class="fw-bold"></span>
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
                                                                    class="ri-edit-line me-1"></i> Edit Company</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Info Rows -->
                                        <div>
                                            <!-- Phone -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-phone fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Phone: <span class="text-dark fw-semibold"
                                                        id="profile_phone"></span></p>
                                            </div>

                                            <!-- GST -->
                                            <div class="d-flex align-items-center gap-2 mb-2" id="profile_gst_row">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-file-text fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">GST No: <span class="text-dark fw-semibold"
                                                        id="profile_gst"></span></p>
                                            </div>

                                            <!-- Address -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-map-pin fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Address: <span class="text-dark fw-semibold"
                                                        id="profile_address"></span></p>
                                            </div>

                                            <!-- City -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-building fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">City: <span class="text-dark fw-semibold"
                                                        id="profile_city"></span></p>
                                            </div>

                                            <!-- State -->
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-map fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">State: <span class="text-dark fw-semibold"
                                                        id="profile_state"></span></p>
                                            </div>

                                            <!-- Pincode -->
                                            <div class="d-flex align-items-center gap-2 mb-2" id="profile_pincode_row">
                                                <div
                                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                                    <i class="ti ti-hash fs-xl"></i>
                                                </div>
                                                <p class="mb-0 fs-sm">Pincode: <span class="text-dark fw-semibold"
                                                        id="profile_pincode"></span></p>
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
                                            <h4 class="card-title">Company Details</h4>
                                        </div>
                                        <ul class="nav nav-tabs card-header-tabs nav-bordered">
                                            <li class="nav-item">
                                                <a href="#about-company" data-bs-toggle="tab" class="nav-link active">
                                                    <i class="ti ti-building d-md-none d-block"></i>
                                                    <span class="d-none d-md-block fw-bold">About</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#company-remarks" data-bs-toggle="tab" class="nav-link">
                                                    <i class="ti ti-notes d-md-none d-block"></i>
                                                    <span class="d-none d-md-block fw-bold">Remarks</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="card-body">
                                        <div class="tab-content">
                                            <!-- About Tab -->
                                            <div class="tab-pane active" id="about-company">
                                                <h4 class="card-title mb-3 text-uppercase fs-sm"><i
                                                        class="ti ti-info-circle"></i> Company Information</h4>

                                                <div class="table-responsive">
                                                    <table class="table table-borderless table-sm mb-0">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-muted" style="width: 180px;">Company
                                                                    Name</td>
                                                                <td class="fw-semibold" id="detail_company_name"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Phone Number</td>
                                                                <td class="fw-semibold" id="detail_phone"></td>
                                                            </tr>
                                                            <tr id="detail_gst_row">
                                                                <td class="text-muted">GST No</td>
                                                                <td class="fw-semibold" id="detail_gst"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">Address</td>
                                                                <td class="fw-semibold" id="detail_address"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">City</td>
                                                                <td class="fw-semibold" id="detail_city"></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-muted">State</td>
                                                                <td class="fw-semibold" id="detail_state"></td>
                                                            </tr>
                                                            <tr id="detail_pincode_row">
                                                                <td class="text-muted">Pincode</td>
                                                                <td class="fw-semibold" id="detail_pincode"></td>
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
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="mt-4 d-flex gap-2" id="action_buttons_section">
                                                    <a href="#" id="edit_company_btn" class="btn btn-info rounded-pill">
                                                        <i class="ri-edit-line"></i> Edit Company
                                                    </a>
                                                    <a href="company-list.php" class="btn btn-secondary rounded-pill">
                                                        <i class="ri-arrow-left-line"></i> Back to List
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- Remarks Tab -->
                                            <div class="tab-pane" id="company-remarks">
                                                <h4 class="card-title mb-3 text-uppercase fs-sm"><i
                                                        class="ti ti-notes"></i> Remarks / Notes</h4>
                                                <div id="detail_remarks_content">
                                                    <p id="detail_remarks" class="text-muted"></p>
                                                </div>
                                                <div id="detail_no_remarks" style="display: none;">
                                                    <p class="text-muted fst-italic">No remarks available for this
                                                        company.</p>
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
                    // Get company ID from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const companyId = urlParams.get('id');

                    if (!companyId) {
                        window.location.href = 'company-list.php';
                        return;
                    }

                    // Hide edit elements if no permission
                    if (!userPermissions.canEdit) {
                        document.getElementById('profile_actions_dropdown').style.display = 'none';
                        document.getElementById('edit_company_btn').style.display = 'none';
                    }

                    // Fetch company data
                    $.get(`api/company/read_single.php?id=${companyId}`, function (response) {
                        if (response.status === 'success') {
                            const c = response.data;

                            // Banner
                            $('#banner_company_name').text(c.company_name);
                            $('#banner_address').text((c.city || '') + (c.city && c.state ? ', ' : '') + (c.state || ''));

                            // Profile Card - Logo
                            if (c.company_logo) {
                                $('#profile_logo').attr('src', c.company_logo).show();
                                $('#profile_logo_placeholder').hide();
                            } else {
                                $('#profile_logo').hide();
                                $('#profile_logo_placeholder').css('display', 'flex');
                            }

                            // Profile Card - Name & Status
                            $('#profile_company_name').text(c.company_name);
                            if (c.status === 'active') {
                                $('#profile_status_badge, #detail_status_badge').text('Active').removeClass('bg-danger').addClass('bg-success');
                            } else {
                                $('#profile_status_badge, #detail_status_badge').text('Inactive').removeClass('bg-success').addClass('bg-danger');
                            }

                            // Profile Card - Info Rows
                            $('#profile_phone').text(c.phone_number || '-');
                            $('#profile_address').text(c.address || '-');
                            $('#profile_city').text(c.city || '-');
                            $('#profile_state').text(c.state || '-');

                            if (c.gst_no) {
                                $('#profile_gst').text(c.gst_no);
                            } else {
                                $('#profile_gst_row').hide();
                            }

                            if (c.pincode) {
                                $('#profile_pincode').text(c.pincode);
                            } else {
                                $('#profile_pincode_row').hide();
                            }

                            if (c.created_at) {
                                let date = new Date(c.created_at);
                                let formatted = date.toLocaleDateString('en-IN', {
                                    day: '2-digit', month: 'short', year: 'numeric',
                                    hour: '2-digit', minute: '2-digit'
                                });
                                $('#profile_created_at, #detail_created_at').text(formatted);
                            } else {
                                $('#profile_created_row, #detail_created_row').hide();
                            }

                            // Detail Table (Right Column)
                            $('#detail_company_name').text(c.company_name);
                            $('#detail_phone').text(c.phone_number || '-');
                            $('#detail_address').text(c.address || '-');
                            $('#detail_city').text(c.city || '-');
                            $('#detail_state').text(c.state || '-');

                            if (c.gst_no) {
                                $('#detail_gst').text(c.gst_no);
                            } else {
                                $('#detail_gst_row').hide();
                            }

                            if (c.pincode) {
                                $('#detail_pincode').text(c.pincode);
                            } else {
                                $('#detail_pincode_row').hide();
                            }

                            // Remarks
                            if (c.remarks) {
                                $('#detail_remarks').text(c.remarks);
                                $('#detail_remarks_content').show();
                                $('#detail_no_remarks').hide();
                            } else {
                                $('#detail_remarks_content').hide();
                                $('#detail_no_remarks').show();
                            }

                            // Edit links
                            $('#edit_company_btn, #dropdown_edit_link').attr('href', `company-add.php?id=${c.id}`);

                        } else {
                            alert('Company not found');
                            window.location.href = 'company-list.php';
                        }
                    }).fail(function () {
                        alert('Error loading company data');
                        window.location.href = 'company-list.php';
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