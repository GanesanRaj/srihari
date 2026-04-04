<?php
// Determine if logged-in user is a client-access user
// Covers: user_type='client' OR user_type=NULL with clientaccess=1
if (!isset($isClientUser)) {
    $isClientUser = false;
    if (($_SESSION['user_type'] ?? '') === 'client') {
        $isClientUser = true;
    } elseif (isset($_SESSION['username'])) {
        $chk = $pdo->prepare("SELECT clientaccess FROM tbl_user WHERE username = ? LIMIT 1");
        $chk->execute([$_SESSION['username']]);
        $chkRow = $chk->fetch(PDO::FETCH_ASSOC);
        if ($chkRow && $chkRow['clientaccess'] == 1) $isClientUser = true;
    }
}
?>
<!-- Topbar Start -->
<style>
    .app-topbar .logo-topbar {
        padding-left: 0 !important;
    }

    /* Ensure the last visible item is flush */
    .app-topbar .topbar-menu>div:last-child>.topbar-item:last-child {
        padding-right: 0 !important;
    }
</style>
<header class="app-topbar">

    <div class="topbar-menu px-0">
        <div class="d-flex align-items-center gap-2">
            <!-- Topbar Brand Logo -->
            <!--<div class="logo-topbar">-->
                <!-- Logo light -->
            <!--    <a href="index.php" class="logo-light">-->
            <!--        <span class="logo-lg">-->
            <!--            <img src="assets/images/logo-black.png" alt="logo">-->
            <!--        </span>-->
            <!--        <span class="logo-sm">-->
            <!--            <img src="assets/images/logo-sm.png" alt="small logo">-->
            <!--        </span>-->
            <!--    </a>-->

                <!-- Logo Dark -->
            <!--    <a href="index.php" class="logo-dark">-->
            <!--        <span class="logo-lg">-->
            <!--            <img src="assets/images/logo-black.png" alt="dark logo">-->
            <!--        </span>-->
            <!--        <span class="logo-sm">-->
            <!--            <img src="assets/images/logo-sm.png" alt="small logo">-->
            <!--        </span>-->
            <!--    </a>-->
            <!--</div>-->

            <!-- Horizontal Menu Toggle Button (For Mobile/Small Screens) -->
            <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <i class="ti ti-menu-4 fs-22"></i>
            </button>

            <!-- Shortcuts Dropdown -->
            <div class="topbar-item d-none d-md-flex">
                <div class="dropdown">
                    <button class="topbar-link btn fw-medium btn-link dropdown-toggle drop-arrow-none"
                        data-bs-toggle="dropdown" data-bs-offset="0,17" type="button" aria-haspopup="false"
                        aria-expanded="false">
                        Quick Actions <i class="ti ti-chevron-down ms-1 fs-16"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-xl p-0">
                        <div class="p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h5 class="mb-2 fw-semibold fs-sm dropdown-header text-uppercase">Shipments</h5>
                                    <div class="d-grid gap-2">
                                        <a href="shipment-create.php"
                                            class="dropdown-item p-2 rounded d-flex align-items-center">
                                            <span
                                                class="avatar-sm bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-plus fs-18"></i>
                                            </span>
                                            <div>
                                                <h6 class="m-0 fs-14">New Booking</h6>
                                                <span class="text-muted fs-12">Create domestic shipment</span>
                                            </div>
                                        </a>
                                        <a href="shipment-bulk.php"
                                            class="dropdown-item p-2 rounded d-flex align-items-center">
                                            <span
                                                class="avatar-sm bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-file-spreadsheet fs-18"></i>
                                            </span>
                                            <div>
                                                <h6 class="m-0 fs-14">Bulk Upload</h6>
                                                <span class="text-muted fs-12">Excel import orders</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-2 fw-semibold fs-sm dropdown-header text-uppercase">Tracking</h5>
                                    <div class="d-grid gap-2">
                                        <a href="tracking.php"
                                            class="dropdown-item p-2 rounded d-flex align-items-center">
                                            <span
                                                class="avatar-sm bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-truck-delivery fs-18"></i>
                                            </span>
                                            <div>
                                                <h6 class="m-0 fs-14">Track Order</h6>
                                                <span class="text-muted fs-12">Real-time status</span>
                                            </div>
                                        </a>
                                        <a href="branch-list.php"
                                            class="dropdown-item p-2 rounded d-flex align-items-center">
                                            <span
                                                class="avatar-sm bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="ti ti-building fs-18"></i>
                                            </span>
                                            <div>
                                                <h6 class="m-0 fs-14">Locations</h6>
                                                <span class="text-muted fs-12">Manage branch offices</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Title -->
            <div class="topbar-item d-none d-xl-flex align-items-center gap-2">
                <span class="badge badge-soft-primary px-3 py-2 fs-13 fw-semibold">Srihari Agencies Logistics</span>
                <?php
                if ($isClientUser):
                    // Read directly from tbl_user (handles NULL session values)
                    $tbRow = $pdo->prepare("SELECT branch_ids, client_ids FROM tbl_user WHERE username = ? AND clientaccess = 1 LIMIT 1");
                    $tbRow->execute([$_SESSION['username'] ?? '']);
                    $tbData = $tbRow->fetch(PDO::FETCH_ASSOC);

                    $accessBranches = [];
                    $bIds = [];
                    $rawB = $tbData['branch_ids'] ?? '';
                    if ($rawB !== '') $bIds = array_filter(array_map('intval', explode(',', $rawB)));
                    if (!empty($bIds)) {
                        $phs  = implode(',', array_fill(0, count($bIds), '?'));
                        $stmt = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id IN ($phs) ORDER BY branch_name");
                        $stmt->execute(array_values($bIds));
                        $accessBranches = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }

                    $accessClients = [];
                    $cIds = [];
                    $rawC = $tbData['client_ids'] ?? '';
                    if ($rawC !== '') $cIds = array_filter(array_map('intval', explode(',', $rawC)));
                    if (!empty($cIds)) {
                        $phs  = implode(',', array_fill(0, count($cIds), '?'));
                        $stmt = $pdo->prepare("SELECT client_name FROM tbl_client WHERE id IN ($phs) ORDER BY client_name");
                        $stmt->execute(array_values($cIds));
                        $accessClients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }

                    $loggedUser = $_SESSION['username'] ?? 'User';
                    $branchText = !empty($accessBranches) ? implode(', ', $accessBranches) : 'All Branches';
                    $clientText = !empty($accessClients)  ? implode(', ', $accessClients)  : 'All Clients';
                ?>
                <span class="text-muted">|</span>
                <span class="fs-12 text-dark">
                    Welcome <strong><?php echo htmlspecialchars($loggedUser); ?></strong>
                    &mdash; You are only allowed in:
                    <span class="text-primary fw-semibold"><?php echo htmlspecialchars($branchText); ?></span>
                    &amp;
                    <span class="text-success fw-semibold"><?php echo htmlspecialchars($clientText); ?></span>
                </span>
                <?php endif; ?>
            </div>

        </div> <!-- end left side -->

        <div class="d-flex align-items-center gap-2">

            <!-- AWB Search with live dropdown -->
            <div class="app-search d-none d-xl-flex me-2" style="position:relative;">
                <input type="text" id="topbarAwbSearch" class="form-control topbar-search rounded-pill"
                    placeholder="Search AWB / Ref No..." autocomplete="off" style="min-width:220px;">
                <i data-lucide="search" class="app-search-icon text-muted"></i>
                <div id="awbSearchDropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;
                    background:#fff;border:1px solid #dee2e6;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);
                    z-index:9999;max-height:380px;overflow-y:auto;min-width:320px;"></div>
            </div>
            <script>
            (function(){
                var inp = document.getElementById('topbarAwbSearch');
                var box = document.getElementById('awbSearchDropdown');
                var timer = null;
                var statusColors = {
                    'Delivered':'success','In Transit':'primary','Dispatched':'primary',
                    'Created':'secondary','Pending':'secondary','Manifested':'info',
                    'RTO':'danger','Failed':'danger','Returned':'danger','Delivery Failed':'danger',
                    'Out For Delivery':'warning','Picked Up':'warning'
                };
                function badgeCls(s){ return statusColors[s] || 'secondary'; }

                inp.addEventListener('input', function(){
                    clearTimeout(timer);
                    var q = this.value.trim();
                    if (q.length < 2) { box.style.display='none'; return; }
                    timer = setTimeout(function(){
                        fetch('api/shipment/search_awb.php?q=' + encodeURIComponent(q))
                            .then(function(r){ return r.json(); })
                            .then(function(data){
                                if (!data.length){ box.innerHTML='<div class="px-3 py-2 text-muted fs-13">No results found</div>'; box.style.display='block'; return; }
                                var html = '';
                                data.forEach(function(r){
                                    var url = 'tracking.php?waybill=' + encodeURIComponent(r.waybill);
                                    var cls = badgeCls(r.status);
                                    html += '<a href="' + url + '" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none border-bottom awb-result-item" style="color:inherit;">';
                                    html += '<div class="flex-grow-1 overflow-hidden">';
                                    html += '<div class="fw-semibold fs-13">' + (r.waybill || '-');
                                    if (r.is_child) html += ' <span class="badge badge-soft-info fs-10 ms-1">Child</span>';
                                    html += '</div>';
                                    html += '<div class="text-muted fs-11 text-truncate">' + (r.consignee || '') + (r.ref ? ' &middot; ' + r.ref : '') + '</div>';
                                    html += '</div>';
                                    html += '<span class="badge bg-' + cls + ' ms-auto flex-shrink-0 fs-10">' + (r.status || '-') + '</span>';
                                    html += '</a>';
                                });
                                html += '<div class="text-center py-2 border-top"><a href="tracking.php" class="fs-12 text-primary">Open Tracking Page &rarr;</a></div>';
                                box.innerHTML = html;
                                box.style.display = 'block';
                            })
                            .catch(function(){ box.style.display='none'; });
                    }, 280);
                });

                inp.addEventListener('keydown', function(e){
                    if (e.key === 'Enter'){
                        e.preventDefault();
                        var q = this.value.trim();
                        if (q) window.location.href = 'tracking.php?waybill=' + encodeURIComponent(q);
                    }
                });

                document.addEventListener('click', function(e){
                    if (!inp.contains(e.target) && !box.contains(e.target)) box.style.display='none';
                });

                document.addEventListener('mouseover', function(e){
                    var item = e.target.closest('.awb-result-item');
                    if (!item) return;
                    box.querySelectorAll('.awb-result-item').forEach(function(el){ el.style.background=''; });
                    item.style.background = '#f8f9fa';
                });
            })();
            </script>
            
            
            <!-- External Tracking Button -->
            <div class="topbar-item d-none d-sm-flex me-2">
                <a href="https://srihariagencies.com/old/tracking.php" target="_blank" class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 shadow-none" title="Open External Tracking">
                    <i class="ti ti-external-link fs-14"></i>
                    <span class="d-none d-md-block fw-medium">Shipment Tracker</span>
                </a>
            </div>

            <!-- Mail Dropdown (nbot) -->
            <div class="topbar-item">
                <div class="dropdown border-end pe-2 me-2">
                    <button class="topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown"
                        data-bs-offset="0,24" type="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-mail fs-22"></i>
                        <span class="badge text-bg-info badge-circle topbar-badge">2</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg p-0 shadow-lg">
                        <div
                            class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top">
                            <h6 class="m-0 fw-bold">Message Center</h6>
                            <span class="badge bg-info-subtle text-info">2 New</span>
                        </div>
                        <div style="max-height: 250px;" data-simplebar>
                            <a href="mail-logs.php" class="dropdown-item p-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div
                                        class="avatar-sm bg-soft-info text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                                        <i class="ti ti-robot fs-18"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="m-0 fs-14">Notification Bot (nbot)</h6>
                                            <small class="text-muted">Just now</small>
                                        </div>
                                        <small class="text-muted d-block text-truncate mt-1">New shipment status update
                                            available for AWB #1029</small>
                                    </div>
                                </div>
                            </a>
                            <a href="mail-logs.php" class="dropdown-item p-3 border-bottom text-wrap">
                                <div class="d-flex align-items-center">
                                    <div
                                        class="avatar-sm bg-soft-warning text-warning rounded-circle me-3 d-flex align-items-center justify-content-center">
                                        <i class="ti ti-mail fs-18"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="m-0 fs-14">System Reports</h6>
                                            <small class="text-muted">1 hour ago</small>
                                        </div>
                                        <small class="text-muted d-block text-truncate mt-1">Your daily performance
                                            summary is ready for review.</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <a href="mail-logs.php"
                            class="dropdown-item text-center text-primary fw-bold py-2 border-top rounded-bottom">
                            View All Messages
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notification Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <button class="topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown"
                        data-bs-offset="0,24" type="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-bell-ringing fs-22"></i>
                        <span class="badge text-bg-danger badge-circle topbar-badge">4</span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg p-0 shadow-lg">
                        <div
                            class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top">
                            <h6 class="m-0 fw-bold">Live Notifications</h6>
                            <span class="badge bg-danger-subtle text-danger">4 New</span>
                        </div>
                        <div style="max-height: 250px;" data-simplebar>
                            <div class="dropdown-item p-3 border-bottom">
                                <div class="d-flex">
                                    <div
                                        class="avatar-sm bg-soft-success text-success rounded-circle me-3 d-flex align-items-center justify-content-center text-shrink-0">
                                        <i class="ti ti-truck-check fs-18"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="m-0 fs-14">Shipment <b>#AWB-9921029</b> successfully delivered.</p>
                                        <small class="text-muted">5 minutes ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-item p-3 border-bottom">
                                <div class="d-flex">
                                    <div
                                        class="avatar-sm bg-soft-primary text-primary rounded-circle me-3 d-flex align-items-center justify-content-center text-shrink-0">
                                        <i class="ti ti-user-plus fs-18"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="m-0 fs-14">New client <b>Global Logico</b> account activated.</p>
                                        <small class="text-muted">2 hours ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="#!"
                            class="dropdown-item text-center text-primary fw-bold py-2 border-top rounded-bottom">
                            Dismiss All
                        </a>
                    </div>
                </div>
            </div>

            <!-- Language Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <button class="topbar-link fw-bold" data-bs-toggle="dropdown" data-bs-offset="0,24" type="button"
                        aria-haspopup="false" aria-expanded="false">
                        <img src="assets/images/flags/us.svg" alt="user-image" class="rounded" height="20"
                            id="selected-language-image">
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow">
                        <a href="javascript:void(0);" class="dropdown-item" data-translator-lang="en">
                            <img src="assets/images/flags/us.svg" alt="English" class="me-2 rounded" height="15">
                            <span class="align-middle">English</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item" data-translator-lang="hi">
                            <img src="assets/images/flags/in.svg" alt="Hindi" class="me-2 rounded" height="15">
                            <span class="align-middle">हिन्दी</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Theme Mode -->
            <div class="topbar-item">
                <div class="dropdown">
                    <button class="topbar-link" data-bs-toggle="dropdown" data-bs-offset="0,24" type="button"
                        aria-haspopup="false" aria-expanded="false">
                        <i data-lucide="sun" class="fs-xxl"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end thememode-dropdown shadow">
                        <li>
                            <label class="dropdown-item">
                                <i data-lucide="sun" class="align-middle me-2 fs-16"></i>
                                <span class="align-middle">Light Mode</span>
                                <input class="form-check-input ms-auto" type="radio" name="data-bs-theme" value="light">
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item">
                                <i data-lucide="moon" class="align-middle me-2 fs-16"></i>
                                <span class="align-middle">Dark Mode</span>
                                <input class="form-check-input ms-auto" type="radio" name="data-bs-theme" value="dark">
                            </label>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- FullScreen -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" type="button" data-toggle="fullscreen">
                    <i data-lucide="maximize" class="fs-xxl fullscreen-off"></i>
                    <i data-lucide="minimize" class="fs-xxl fullscreen-on"></i>
                </button>
            </div>

            <!-- User Dropdown -->
            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown"
                        data-bs-offset="0,19" href="#!" aria-haspopup="false" aria-expanded="false">
                        <?php
                        $user_image = isset ($_SESSION[ 'user_image' ]) && ! empty ($_SESSION[ 'user_image' ])
                            ? $_SESSION[ 'user_image' ]
                            : 'assets/images/users/user-1.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars ( $user_image ); ?>" width="32"
                            class="rounded-circle me-lg-2 d-flex" alt="user-image"
                            onerror="this.src='assets/images/users/user-1.jpg'">
                        <div class="d-lg-flex align-items-center gap-1 d-none">
                            <h5 class="my-0"><?php echo htmlspecialchars ( $_SESSION[ 'full_name' ] ?? 'Admin' ); ?></h5>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome back 👋!</h6>
                        </div>

                        <a href="user-profile.php" class="dropdown-item">
                            <i class="ti ti-user-circle me-2 fs-18 align-middle"></i>
                            <span class="align-middle">My Profile</span>
                        </a>

                        <a href="change-password.php" class="dropdown-item">
                            <i class="ti ti-key me-2 fs-18 align-middle"></i>
                            <span class="align-middle">Security Settings</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <a href="logout.php" class="dropdown-item fw-semibold text-danger"
                            onclick="return confirm('Are you sure you want to logout?');">
                            <i class="ti ti-logout-2 me-2 fs-18 align-middle"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Settings Button -->
            <div class="topbar-item">
                <button class="topbar-link" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas"
                    type="button">
                    <i class="ti ti-settings icon-spin fs-24"></i>
                </button>
            </div>
        </div>
    </div>
</header>
<!-- Topbar End -->

<?php include 'horizontal-menu.php'; ?>
