<?php
require_once 'header.php';
require_once 'config/config.php';

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest User';
$user_email = $_SESSION['user_email'] ?? '';
$user_image = isset($_SESSION['user_image']) && !empty($_SESSION['user_image'])
    ? $_SESSION['user_image']
    : 'assets/images/users/default-avatar.png';
$session_role_id = (int) ($_SESSION['role_id'] ?? 0);
$login_role_name = trim((string) ($_SESSION['designation'] ?? ''));

if ($login_role_name === '' && $session_role_id > 0) {
    try {
        $roleStmt = $pdo->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->execute([$session_role_id]);
        $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
        if ($roleRow && !empty($roleRow['name'])) {
            $login_role_name = trim((string) $roleRow['name']);
        }
    } catch (Exception $e) {
        // Keep graceful fallback if roles table lookup fails.
    }
}
if ($login_role_name === '') {
    $login_role_name = $session_role_id > 0 ? ('Role ID: ' . $session_role_id) : 'Not assigned';
}

// Access scope for client-type users
$isClientUser = ($_SESSION['user_type'] ?? '') === 'client';
if (!$isClientUser && isset($_SESSION['username'])) {
    $chk = $pdo->prepare("SELECT clientaccess FROM tbl_user WHERE username = ? LIMIT 1");
    $chk->execute([$_SESSION['username']]);
    $chkRow = $chk->fetch(PDO::FETCH_ASSOC);
    if ($chkRow && $chkRow['clientaccess'] == 1) $isClientUser = true;
}
$accessBranches = [];
$accessClients  = [];
if ($isClientUser) {
    // Read directly from tbl_user (handles NULL session values)
    $upRow = $pdo->prepare("SELECT branch_ids, client_ids FROM tbl_user WHERE username = ? AND clientaccess = 1 LIMIT 1");
    $upRow->execute([$_SESSION['username'] ?? '']);
    $upData = $upRow->fetch(PDO::FETCH_ASSOC);

    $rawB = $upData['branch_ids'] ?? '';
    $bIds = $rawB !== '' ? array_filter(array_map('intval', explode(',', $rawB))) : [];
    if (!empty($bIds)) {
        $phs  = implode(',', array_fill(0, count($bIds), '?'));
        $stmt = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id IN ($phs) ORDER BY branch_name");
        $stmt->execute(array_values($bIds));
        $accessBranches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $rawC = $upData['client_ids'] ?? '';
    $cIds = $rawC !== '' ? array_filter(array_map('intval', explode(',', $rawC))) : [];
    if (!empty($cIds)) {
        $phs  = implode(',', array_fill(0, count($cIds), '?'));
        $stmt = $pdo->prepare("SELECT client_name FROM tbl_client WHERE id IN ($phs) ORDER BY client_name");
        $stmt->execute(array_values($cIds));
        $accessClients = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>

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
                                <i class="ti ti-user-circle me-1"></i> User Profile
                            </h4>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Profile Card -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="position-relative d-inline-block mb-3">
                                        <img src="<?php echo htmlspecialchars($user_image); ?>"
                                             class="rounded-circle avatar-xl"
                                             alt="user-image"
                                             id="profileImagePreview"
                                             onerror="this.src='assets/images/users/default-avatar.png'">
                                        <button type="button" class="btn btn-sm btn-soft-primary position-absolute bottom-0 end-0 rounded-circle btn-icon" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                                            <i class="ti ti-camera"></i>
                                        </button>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user_email); ?></p>

                                    <div class="row text-start">
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-phone me-2 text-muted"></i>
                                                <span class="text-muted" id="display_phone">Not set</span>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-briefcase me-2 text-muted"></i>
                                                <span class="text-muted" id="display_role"><?php echo htmlspecialchars($login_role_name); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-building me-2 text-muted"></i>
                                                <span class="text-muted fs-12">
                                                    <?php echo $isClientUser ? 'Clients:' : 'Department:'; ?>
                                                </span>
                                                <span class="ms-1 text-dark fs-12 fw-semibold" id="display_department">—</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-map-pin me-2 text-muted"></i>
                                                <span class="text-muted fs-12">
                                                    <?php echo $isClientUser ? 'Branches:' : 'Branch:'; ?>
                                                </span>
                                                <span class="ms-1 text-dark fs-12 fw-semibold" id="display_branch">—</span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($isClientUser): ?>
                                    <hr class="my-3">
                                    <div class="text-start">
                                        <p class="fs-12 text-muted mb-2">
                                            Welcome <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> — you are only allowed in:
                                        </p>
                                        <p class="fs-11 fw-semibold text-muted mb-1"><i class="ti ti-building fs-11 me-1"></i> Branches</p>
                                        <div class="mb-2">
                                        <?php if (empty($accessBranches)): ?>
                                            <span class="badge badge-soft-secondary fs-11">All Branches</span>
                                        <?php else: foreach ($accessBranches as $bn): ?>
                                            <span class="badge badge-soft-primary fs-11 mb-1"><?php echo htmlspecialchars($bn); ?></span>
                                        <?php endforeach; endif; ?>
                                        </div>
                                        <p class="fs-11 fw-semibold text-muted mb-1"><i class="ti ti-user fs-11 me-1"></i> Clients</p>
                                        <div>
                                        <?php if (empty($accessClients)): ?>
                                            <span class="badge badge-soft-secondary fs-11">All Clients</span>
                                        <?php else: foreach ($accessClients as $cn): ?>
                                            <span class="badge badge-soft-success fs-11 mb-1"><?php echo htmlspecialchars($cn); ?></span>
                                        <?php endforeach; endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Profile Form -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Edit Profile Information</h5>
                                    <form id="profileForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="text" class="form-control form-control-sm" id="phone" name="phone">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Address</label>
                                                <input type="text" class="form-control form-control-sm" id="address" name="address">
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-sm btn-soft-primary">
                                                    <i class="ti ti-device-floppy me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Activity Log -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Recent Activity</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Activity</th>
                                                    <th>Date & Time</th>
                                                    <th>IP Address</th>
                                                </tr>
                                            </thead>
                                            <tbody id="activityLog">
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">
                                                        Loading activity log...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Upload Image Modal -->
            <div class="modal fade" id="uploadImageModal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Profile Picture</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="uploadImageForm">
                                <div class="mb-3">
                                    <label class="form-label">Choose Image</label>
                                    <input type="file" class="form-control form-control-sm" id="profileImage" name="profile_image" accept="image/*" required>
                                    <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                                </div>
                                <button type="submit" class="btn btn-sm btn-soft-primary w-100">
                                    <i class="ti ti-upload me-1"></i> Upload
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(document).ready(function() {
                    // Load user profile data
                    loadProfileData();
                    loadActivityLog();

                    // Profile form submission
                    $('#profileForm').on('submit', function(e) {
                        e.preventDefault();

                        $.ajax({
                            url: 'api/user/update-profile.php',
                            type: 'POST',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    loadProfileData();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error updating profile', 'error');
                            }
                        });
                    });

                    // Image upload form
                    $('#uploadImageForm').on('submit', function(e) {
                        e.preventDefault();

                        var formData = new FormData(this);

                        $.ajax({
                            url: 'api/user/upload-image.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    $('#profileImagePreview').attr('src', response.image_url);
                                    $('#uploadImageModal').modal('hide');
                                    $('#uploadImageForm')[0].reset();
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error uploading image', 'error');
                            }
                        });
                    });

                    function loadProfileData() {
                        $.ajax({
                            url: 'api/user/get-profile.php',
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    var data = response.data;
                                    $('#name').val(data.name || '');
                                    $('#email').val(data.email || '');
                                    $('#phone').val(data.phone || '');
                                    $('#address').val(data.address || '');

                                    $('#display_phone').text(data.phone || 'Not set');
                                    $('#display_role').text(data.role || 'Not set');
                                    $('#display_department').text(data.department || 'Not set');
                                    $('#display_branch').text(data.branch || 'Not set');
                                }
                            }
                        });
                    }

                    function loadActivityLog() {
                        $.ajax({
                            url: 'api/user/get-activity-log.php',
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    var html = '';
                                    if (response.data.length > 0) {
                                        response.data.forEach(function(item) {
                                            html += '<tr>';
                                            html += '<td>' + item.activity + '</td>';
                                            html += '<td>' + item.datetime + '</td>';
                                            html += '<td>' + item.ip_address + '</td>';
                                            html += '</tr>';
                                        });
                                    } else {
                                        html = '<tr><td colspan="3" class="text-center text-muted">No activity found</td></tr>';
                                    }
                                    $('#activityLog').html(html);
                                }
                            }
                        });
                    }
                });
            </script>
        </div>
    </div>
</body>

<style>
    .form-control-sm, .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }

    .table-sm th, .table-sm td {
        padding: 5px !important;
        font-size: 13px;
    }
</style>

</html>
