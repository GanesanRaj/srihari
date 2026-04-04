<?php
require_once 'config/db.php';
require_once 'config/middleware.php';

include 'header.php';
?>

<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<style>
    /* ═══════════════════════════════════════════
   PROFESSIONAL DASHBOARD — DESIGN SYSTEM
═══════════════════════════════════════════ */

    /* Tab Switcher */
    .dash-tabs {
        display: flex;
        background: #f1f5f9;
        border-radius: 10px;
        padding: 3px;
        gap: 2px;
    }

    .dash-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 20px;
        border-radius: 8px;
        border: none;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
    }

    .dash-tab.active {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .12);
    }

    .dash-tab-pane {
        display: none;
    }

    .dash-tab-pane.active {
        display: block;
    }

    /* KPI Hero Cards */
    .kpi-card {
        border-radius: 14px;
        padding: 20px 22px;
        color: #0f172a;
        position: relative;
        overflow: hidden;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid #e2e8f0;
    }

    .kpi-card::after {
        content: '';
        position: absolute;
        right: -18px;
        top: -18px;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .03);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        right: 20px;
        top: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .02);
    }

    .kpi-card .kpi-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        opacity: .7;
    }

    .kpi-card .kpi-value {
        font-size: 32px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.02em;
    }

    .kpi-card .kpi-delta {
        font-size: 11px;
        font-weight: 600;
        opacity: .7;
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .kpi-card .kpi-icon {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 36px;
        opacity: .15;
        z-index: 0;
    }

    .kpi-blue {
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .kpi-blue .kpi-value, .kpi-blue .kpi-icon { color: #3e60d5; }

    .kpi-green {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .kpi-green .kpi-value, .kpi-green .kpi-icon { color: #16a34a; }

    .kpi-amber {
        background: #fffbeb;
        border-color: #fde68a;
    }
    .kpi-amber .kpi-value, .kpi-amber .kpi-icon { color: #d97706; }

    .kpi-red {
        background: #fef2f2;
        border-color: #fecaca;
    }
    .kpi-red .kpi-value, .kpi-red .kpi-icon { color: #dc2626; }

    .kpi-indigo {
        background: #eef2ff;
        border-color: #c7d2fe;
    }
    .kpi-indigo .kpi-value, .kpi-indigo .kpi-icon { color: #4f46e5; }

    .kpi-teal {
        background: #ecfeff;
        border-color: #a5f3fc;
    }
    .kpi-teal .kpi-value, .kpi-teal .kpi-icon { color: #0891b2; }

    /* Mini Stat Cards */
    .mini-card {
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: box-shadow .2s, border-color .2s;
        cursor: pointer;
        height: 100%;
    }

    .mini-card:hover {
        box-shadow: 0 4px 16px rgba(15, 23, 42, .09);
        border-color: #c7d2fe;
    }

    .mini-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .mini-label {
        font-size: 11px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .mini-value {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }

    .mini-sub {
        font-size: 11px;
        color: #64748b;
    }

    /* Section header */
    .sec-hdr {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        margin-top: 28px;
    }

    .sec-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .sec-title i {
        color: #3e60d5;
        font-size: 15px;
    }

    /* Dashboard Card */
    .db-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        height: 100%;
    }

    .db-card .db-card-hdr {
        padding: 14px 18px 10px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .db-card .db-card-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .db-card .db-card-body {
        padding: 0;
    }

    /* Manifest Table */
    .manifest-table th {
        background: #f8fafc;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 10px 12px;
        white-space: nowrap;
    }

    .manifest-table td {
        padding: 10px 12px;
        font-size: 12.5px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .manifest-table tr:last-child td {
        border-bottom: none;
    }

    .manifest-table tr:hover td {
        background: #fafafa;
    }

    .badge-dispatched {
        background: #fef3c7;
        color: #92400e;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        letter-spacing: .03em;
    }

    /* Manifest filter buttons */
    .manifest-filter-btn {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
        transition: all .2s;
    }

    .manifest-filter-btn:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .manifest-filter-btn.active {
        background: #3e60d5;
        color: #fff;
        border-color: #3e60d5;
    }

    .manifest-filter-btn.active .badge {
        background: rgba(255, 255, 255, .25) !important;
        color: #fff !important;
    }

    /* Attendance row */
    .att-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .att-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    /* Client rank list */
    .client-rank {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid #f1f5f9;
        gap: 12px;
    }

    .client-rank:last-child {
        border-bottom: none;
    }

    .client-num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #eff6ff;
        color: #3e60d5;
        font-size: 10px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Bulk table */
    .bulk-table th {
        background: #f8fafc;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .04em;
        padding: 9px 12px;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .bulk-table td {
        font-size: 12px;
        padding: 9px 12px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .bulk-table tr:last-child td {
        border-bottom: none;
    }

    /* Coming Soon */
    .coming-soon-panel {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 420px;
        text-align: center;
        padding: 60px 20px;
    }

    .cs-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        color: #3e60d5;
        margin: 0 auto 20px;
        box-shadow: 0 0 0 12px rgba(62, 96, 213, .07);
    }

    /* Pickup sub-stats */
    .pickup-stat {
        text-align: center;
        padding: 10px 8px;
    }

    .pickup-stat .pval {
        font-size: 24px;
        font-weight: 800;
        line-height: 1;
    }

    .pickup-stat .plbl {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #94a3b8;
        margin-top: 3px;
    }

    /* Chart legend dot */
    .chart-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    /* Activity Feed */
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }
    .activity-item:hover { background: #fafcff; }
    .activity-item:last-child { border-bottom: none; }
    .act-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 5px;
    }
    .act-waybill {
        font-size: 12px; font-weight: 700; color: #3e60d5;
        line-height: 1.2;
    }
    .act-consignee {
        font-size: 11.5px; color: #334155; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis; max-width: 160px;
    }
    .act-meta {
        font-size: 10.5px; color: #94a3b8;
    }
    .act-status {
        font-size: 10px; font-weight: 700; padding: 2px 7px;
        border-radius: 20px; white-space: nowrap; flex-shrink: 0;
    }

    /* Date range button */
    .dr-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 14px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        transition: border-color .2s;
    }

    .dr-btn:hover {
        border-color: #3e60d5;
    }
</style>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- ── Page Header ── -->
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <h4 class="fs-18 fw-bold mb-1 text-dark">Dashboard</h4>
                        <?php if ($isClientUser):
                            // Read branch_ids / client_ids directly from tbl_user (handles NULL session values)
                            $duRow = $pdo->prepare("SELECT branch_ids, client_ids FROM tbl_user WHERE username = ? AND clientaccess = 1 LIMIT 1");
                            $duRow->execute([$_SESSION['username'] ?? '']);
                            $duData = $duRow->fetch(PDO::FETCH_ASSOC);

                            $duBIds = [];
                            $rawB = $duData['branch_ids'] ?? '';
                            if ($rawB !== '') $duBIds = array_filter(array_map('intval', explode(',', $rawB)));

                            $duCIds = [];
                            $rawC = $duData['client_ids'] ?? '';
                            if ($rawC !== '') $duCIds = array_filter(array_map('intval', explode(',', $rawC)));

                            $duBranches = [];
                            if (!empty($duBIds)) {
                                $phs = implode(',', array_fill(0, count($duBIds), '?'));
                                $s = $pdo->prepare("SELECT branch_name FROM tbl_branch WHERE id IN ($phs) ORDER BY branch_name");
                                $s->execute(array_values($duBIds));
                                $duBranches = $s->fetchAll(PDO::FETCH_COLUMN);
                            }

                            $duClients = [];
                            if (!empty($duCIds)) {
                                $phs = implode(',', array_fill(0, count($duCIds), '?'));
                                $s = $pdo->prepare("SELECT client_name FROM tbl_client WHERE id IN ($phs) ORDER BY client_name");
                                $s->execute(array_values($duCIds));
                                $duClients = $s->fetchAll(PDO::FETCH_COLUMN);
                            }

                            $branchLabel = !empty($duBranches) ? implode(', ', $duBranches) : 'All Branches';
                            $clientLabel = !empty($duClients)  ? implode(', ', $duClients)  : 'All Clients';
                        ?>
                        <p class="fs-12 mb-0">
                            Welcome back <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong>
                            &mdash; Allowed:
                            <span class="text-primary fw-semibold"><?php echo htmlspecialchars($branchLabel); ?></span>
                            &amp;
                            <span class="text-success fw-semibold"><?php echo htmlspecialchars($clientLabel); ?></span>
                        </p>
                        <?php else: ?>
                        <p class="text-muted fs-12 mb-0">Welcome back — here's what's happening today.</p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <?php $activeTab = $_GET['tab'] ?? 'own'; ?>
                        <div class="dash-tabs" id="dashTabBar">
                            <button class="dash-tab <?php echo $activeTab === 'own' ? 'active' : ''; ?>" onclick="location.href='index.php?tab=own'">
                                <i class="ti ti-truck fs-13"></i> Own Courier (WHMS)
                            </button>
                            <button class="dash-tab <?php echo $activeTab === 'shiprocket' ? 'active' : ''; ?>" onclick="location.href='index.php?tab=shiprocket'">
                                <i class="ti ti-package fs-13"></i> Shiprocket
                            </button>
                            <button class="dash-tab <?php echo $activeTab === 'delhivery' ? 'active' : ''; ?>" onclick="location.href='index.php?tab=delhivery'">
                                <i class="ti ti-world fs-13"></i> Delhivery
                            </button>
                        </div>
                        <div id="dashboard-range" class="dr-btn">
                            <i class="ti ti-calendar fs-14 text-primary"></i>
                            <span></span>
                            <i class="ti ti-chevron-down fs-12 text-muted"></i>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-start" style="gap: 2%;">
                    <div style="width: 68%;">
                        <!-- ══════════════ DASHBOARD CONTENT ══════════════ -->
                        <div class="dash-tab-pane active">

                            <!-- §1  Hero KPI Row -->
                            <div class="row g-3 mb-1">
                                <div class="col-xl-3 col-md-6">
                                    <div class="kpi-card kpi-blue stat-card cursor-pointer" data-status="">
                                        <i class="ti ti-package kpi-icon"></i>
                                        <div class="kpi-label">Total Shipments</div>
                                        <div class="kpi-value" id="total-shipments">0</div>
                                        <div class="kpi-delta"><i class="ti ti-trending-up"></i> <span
                                                id="remark-total">+0
                                                Today</span></div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="kpi-card kpi-green stat-card cursor-pointer" data-status="Delivered">
                                        <i class="ti ti-circle-check kpi-icon"></i>
                                        <div class="kpi-label">Delivered</div>
                                        <div class="kpi-value" id="delivered-shipments">0</div>
                                        <div class="kpi-delta"><i class="ti ti-trending-up"></i> <span
                                                id="remark-delivered">+0
                                                Today</span></div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="kpi-card kpi-indigo stat-card cursor-pointer" data-status="Manifested">
                                        <i class="ti ti-truck kpi-icon"></i>
                                        <div class="kpi-label">In Transit</div>
                                        <div class="kpi-value" id="transit-shipments">0</div>
                                        <div class="kpi-delta"><i class="ti ti-activity"></i> <span
                                                id="remark-transit">+0
                                                Today</span></div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="kpi-card kpi-red stat-card cursor-pointer" data-status="RTO">
                                        <i class="ti ti-arrow-back-up kpi-icon"></i>
                                        <div class="kpi-label">Total RTO</div>
                                        <div class="kpi-value" id="rto-shipments">0</div>
                                        <div class="kpi-delta"><i class="ti ti-trending-down"></i> <span
                                                id="remark-rto">+0
                                                Today</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- §2  Mini Stat Row -->
                            <div class="row g-3 mb-1">
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card stat-card" data-status="Created">
                                        <div class="mini-icon" style="background:#fef3c7; color:#d97706;">
                                            <i class="ti ti-clock"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">Booked</div>
                                            <div class="mini-value" id="pending-shipments">0</div>
                                            <div class="mini-sub" id="remark-booked">+0 Today</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card stat-card" data-status="Out For Delivery">
                                        <div class="mini-icon" style="background:#dbeafe; color:#3e60d5;">
                                            <i class="ti ti-map-pin"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">Out for Delivery</div>
                                            <div class="mini-value" id="ofd-shipments">0</div>
                                            <div class="mini-sub" id="remark-ofd">+0 Today</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card cursor-pointer" onclick="location.href='ndr-shipments.php'">
                                        <div class="mini-icon" style="background:#fee2e2; color:#dc2626;">
                                            <i class="ti ti-alert-triangle"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">NDR Actions</div>
                                            <div class="mini-value text-danger" id="ndr-count">0</div>
                                            <div class="mini-sub">Needs action</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#d1fae5; color:#16a34a;">
                                            <i class="ti ti-currency-rupee"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">COD Value</div>
                                            <div class="mini-value" id="cod-total" style="font-size:17px;">₹0</div>
                                            <div class="mini-sub">Pending remit</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#e0f2fe; color:#0891b2;">
                                            <i class="ti ti-truck-delivery"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">Today's Pickups</div>
                                            <div class="mini-value" id="today-pickups">0</div>
                                            <div class="mini-sub"><span class="text-success fw-bold"
                                                    id="today-picked">0</span>
                                                picked · <span class="text-warning fw-bold" id="today-pending">0</span>
                                                pending
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#ede9fe; color:#7c3aed;">
                                            <i class="ti ti-calendar-event"></i>
                                        </div>
                                        <div>
                                            <div class="mini-label">Upcoming Pickups</div>
                                            <div class="mini-value" id="upcoming-pickups">0</div>
                                            <div class="mini-sub">Next 3 days</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($activeTab === 'own'): ?>
                            <!-- §3  Manifest → Branch (Filtered) - ONLY FOR OWN COURIER -->
                            <div class="sec-hdr">
                                <div class="sec-title d-flex align-items-center gap-2">
                                    <span>
                                        <i class="ti ti-file-text"></i>
                                        Manifest to Branch
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm manifest-filter-btn active" data-filter=""
                                        style="font-size:11px;padding:4px 12px;border-radius:20px;font-weight:600;">
                                        All <span class="badge bg-white text-dark ms-1" id="mf-count-all"
                                            style="font-size:10px;">0</span>
                                    </button>
                                    <button class="btn btn-sm manifest-filter-btn" data-filter="draft"
                                        style="font-size:11px;padding:4px 12px;border-radius:20px;font-weight:600;">
                                        <i class="ti ti-file-text fs-13 me-1"></i>Draft <span
                                            class="badge bg-white text-dark ms-1" id="mf-count-draft"
                                            style="font-size:10px;">0</span>
                                    </button>
                                    <button class="btn btn-sm manifest-filter-btn" data-filter="dispatched"
                                        style="font-size:11px;padding:4px 12px;border-radius:20px;font-weight:600;">
                                        <i class="ti ti-truck fs-13 me-1"></i>Dispatched <span
                                            class="badge bg-white text-dark ms-1" id="mf-count-dispatched"
                                            style="font-size:10px;">0</span>
                                    </button>
                                    <button class="btn btn-sm manifest-filter-btn" data-filter="received"
                                        style="font-size:11px;padding:4px 12px;border-radius:20px;font-weight:600;">
                                        <i class="ti ti-circle-check fs-13 me-1"></i>Received <span
                                            class="badge bg-white text-dark ms-1" id="mf-count-received"
                                            style="font-size:10px;">0</span>
                                    </button>
                                    <a href="whms-manifest-list.php" class="btn btn-sm btn-outline-primary fs-11 py-1">
                                        <i class="ti ti-external-link me-1"></i>View All
                                    </a>
                                </div>
                            </div>
                            <div class="db-card mb-4" style="border-radius:14px;">
                                <div class="table-responsive">
                                    <table class="table manifest-table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Manifest No</th>
                                                <th>Route</th>
                                                <th>Coloader / Vehicle</th>
                                                <th>Driver</th>
                                                <th class="text-center">Bags</th>
                                                <th class="text-center">Wt (kg)</th>
                                                <th class="text-center">Shipments</th>
                                                <th>Dispatched At</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center pe-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="manifest-pending-list">
                                            <tr>
                                                <td colspan="10" class="text-center py-4 text-muted fs-12">Loading
                                                    manifests...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- §4a  Daily Shipments + Status Donut -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <div class="db-card">
                                        <div class="db-card-hdr">
                                            <span class="db-card-title"><i
                                                    class="ti ti-chart-area me-1 text-primary"></i> Daily
                                                Shipments Trend</span>
                                            <div class="d-flex gap-2 align-items-center">
                                                <span class="chart-legend-dot" style="background:#3e60d5;"></span><span
                                                    class="fs-11 text-muted me-2">Total</span>
                                                <span class="chart-legend-dot" style="background:#16a34a;"></span><span
                                                    class="fs-11 text-muted me-2">Delivered</span>
                                                <span class="chart-legend-dot" style="background:#dc2626;"></span><span
                                                    class="fs-11 text-muted">RTO</span>
                                            </div>
                                        </div>
                                        <div class="db-card-body" style="padding:10px 16px 16px;">
                                            <div id="shipment-timeline" style="height:290px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="db-card">
                                        <div class="db-card-hdr">
                                            <span class="db-card-title"><i
                                                    class="ti ti-chart-donut me-1 text-primary"></i>
                                                Status Distribution</span>
                                        </div>
                                        <div class="db-card-body" style="padding:8px;">
                                            <div id="status-distribution" style="height:290px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- §4b  Branch Performance + Top Clients -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="db-card">
                                        <div class="db-card-hdr">
                                            <span class="db-card-title"><i class="ti ti-building me-1"
                                                    style="color:#0891b2;"></i> Branch Performance</span>
                                        </div>
                                        <div class="db-card-body" style="padding:10px 16px 16px;">
                                            <div id="branch-performance" style="height:300px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="db-card">
                                        <div class="db-card-hdr">
                                            <span class="db-card-title"><i class="ti ti-star me-1"
                                                    style="color:#d97706;"></i>
                                                Top 5 Clients</span>
                                        </div>
                                        <div class="db-card-body" style="padding:10px 16px 16px;">
                                            <div id="top-clients-chart" style="height:300px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($activeTab === 'own'): ?>
                            <!-- §4c  Runsheet by Branch -->
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="db-card">
                                        <div class="db-card-hdr">
                                            <span class="db-card-title"><i class="ti ti-clipboard-list me-1" style="color:#7c3aed;"></i> Runsheet Delivery Status — By Branch</span>
                                            <div class="d-flex gap-3 align-items-center">
                                                <span class="chart-legend-dot" style="background:#10b981;"></span><span class="fs-11 text-muted me-1">Delivered</span>
                                                <span class="chart-legend-dot" style="background:#6366f1;"></span><span class="fs-11 text-muted me-1">Out for Delivery</span>
                                                <span class="chart-legend-dot" style="background:#f97316;"></span><span class="fs-11 text-muted">Picked Up</span>
                                            </div>
                                        </div>
                                        <div class="db-card-body" style="padding:10px 16px 16px;">
                                            <div id="runsheet-branch-chart" style="height:280px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- §5  Attendance + System Snapshot -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-3 col-sm-6">
                                    <div class="att-card">
                                        <div class="att-icon" style="background:#dcfce7;color:#16a34a;"><i
                                                class="ti ti-user-check fs-20"></i></div>
                                        <div>
                                            <div class="mini-label">Present Today</div>
                                            <div class="mini-value text-success" id="att-present">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="att-card">
                                        <div class="att-icon" style="background:#fee2e2;color:#dc2626;"><i
                                                class="ti ti-user-x fs-20"></i></div>
                                        <div>
                                            <div class="mini-label">Absent Today</div>
                                            <div class="mini-value text-danger" id="att-absent">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="att-card">
                                        <div class="att-icon" style="background:#fef3c7;color:#d97706;"><i
                                                class="ti ti-user-minus fs-20"></i></div>
                                        <div>
                                            <div class="mini-label">Half Day</div>
                                            <div class="mini-value text-warning" id="att-half-day">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="att-card">
                                        <div class="att-icon" style="background:#e0f2fe;color:#0891b2;"><i
                                                class="ti ti-plane-departure fs-20"></i></div>
                                        <div>
                                            <div class="mini-label">On Leave</div>
                                            <div class="mini-value text-info" id="att-leave">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Snapshot mini row -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4 col-sm-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#f1f5f9;color:#475569;"><i
                                                class="ti ti-building"></i></div>
                                        <div>
                                            <div class="mini-label">Active Branches</div>
                                            <div class="mini-value" id="active-branches">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#f1f5f9;color:#475569;"><i
                                                class="ti ti-building-community"></i></div>
                                        <div>
                                            <div class="mini-label">Companies</div>
                                            <div class="mini-value" id="active-companies">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="mini-card">
                                        <div class="mini-icon" style="background:#f1f5f9;color:#475569;"><i
                                                class="ti ti-users"></i></div>
                                        <div>
                                            <div class="mini-label">Employees</div>
                                            <div class="mini-value" id="active-employees">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- §6  Bulk Uploads -->
                            <div class="sec-hdr">
                                <div class="sec-title"><i class="ti ti-upload"></i> Recent Bulk Uploads</div>
                                <a href="shipment-bulk.php" class="btn btn-sm btn-primary fs-11 py-1">View All</a>
                            </div>
                            <div class="db-card mb-3">
                                <div class="table-responsive">
                                    <table class="table bulk-table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Date</th>
                                                <th>Filename</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Pass</th>
                                                <th class="text-center">Fail</th>
                                                <th>Created By</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center pe-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bulk-upload-list">
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted fs-12">Loading
                                                    uploads...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><!-- /tab-own -->

                        <!-- ══════════════ TAB 2: OTHER COURIERS ══════════════ -->
                        <div class="dash-tab-pane" id="tab-other">
                            <div class="db-card" style="border-radius:16px;">
                                <div class="coming-soon-panel">
                                    <div class="cs-icon"><i class="ti ti-world"></i></div>
                                    <h5 class="fw-bold text-dark mb-2">Other Couriers Dashboard</h5>
                                    <p class="text-muted fs-13" style="max-width:340px;line-height:1.7;">
                                        Integration with third-party courier partners is under development.<br>
                                        Shipments, tracking, and analytics for all non-WHMS couriers will appear here.
                                    </p>
                                    <span
                                        class="badge bg-primary-subtle text-primary px-3 py-2 fs-12 mt-2 rounded-pill">
                                        <i class="ti ti-clock me-1"></i> Coming Soon
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Recent Activity Column -->
                    <div class="anoncenment-div" style="width: 30%; position: static;">

                        <!-- Quick AWB Search -->
                        <div class="db-card mb-3" style="border-radius:14px;">
                            <div class="db-card-hdr" style="background:#fff;">
                                <span class="db-card-title">
                                    <i class="ti ti-search me-1 text-primary"></i> Quick AWB Lookup
                                </span>
                                <a href="tracking.php" class="fs-11 text-primary">Open Tracker &rarr;</a>
                            </div>
                            <div class="db-card-body" style="padding:12px 14px;">
                                <div style="position:relative;">
                                    <input type="text" id="dashAwbSearch"
                                        class="form-control form-control-sm rounded-pill"
                                        placeholder="Type AWB or Ref No..."
                                        autocomplete="off"
                                        style="padding-right:32px;">
                                    <i class="ti ti-search" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;"></i>
                                </div>
                                <div id="dashAwbResults" style="margin-top:8px;"></div>
                            </div>
                        </div>

                        <!-- Recent Activity Feed -->
                        <div class="db-card" style="border-radius:14px;">
                            <div class="db-card-hdr" style="background:#fff;">
                                <span class="db-card-title">
                                    <i class="ti ti-activity me-1 text-primary"></i> Recent Activity
                                </span>
                                <span class="badge bg-primary-subtle text-primary fs-10 px-2 py-1" id="activity-count">0 new</span>
                            </div>
                            <div id="activity-feed-wrap" style="height:460px; overflow:hidden; position:relative;">
                                <div id="activity-feed" style="padding:0;">
                                    <div class="text-center py-5 text-muted fs-12">Loading activity...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipment Calendar -->
                        <div class="db-card mt-3">
                            <div class="db-card-hdr">
                                <span class="db-card-title"><i class="ti ti-calendar me-1 text-primary"></i>
                                    Shipment Calendar</span>
                            </div>
                            <div class="db-card-body" style="padding:14px;">
                                <div id="shipment-calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /content -->
        </div><!-- /content-page -->

        <?php require_once 'footer.php'; ?>

        <!-- JS -->
        <script src="assets/plugins/jquery/jquery.min.js"></script>
        <script src="assets/plugins/daterangepicker/moment.min.js"></script>
        <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>
        <script src="assets/plugins/apexcharts/apexcharts.min.js"></script>
        <script src="assets/plugins/fullcalendar/index.global.min.js"></script>

        <script>
            /* ── Tab switcher ── */
            document.querySelectorAll('.dash-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.dash-tab').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.dash-tab-pane').forEach(p => p.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
                });
            });

            /* ── Manifest filter counts ── */
            function loadManifestCounts() {
                // Fetch manifest status counts — use recordsTotal so server-side branch filter is respected
                $.when(
                    $.get('api/manifest/read.php', { length: 1, start: 0 }),
                    $.get('api/manifest/read.php', { length: 1, start: 0, status: 'draft' }),
                    $.get('api/manifest/read.php', { length: 1, start: 0, status: 'dispatched' }),
                    $.get('api/manifest/read.php', { length: 1, start: 0, status: 'received' })
                ).done(function (rAll, rDraft, rDispatched, rReceived) {
                    $('#mf-count-all').text(rAll[0].recordsTotal || 0);
                    $('#mf-count-draft').text(rDraft[0].recordsTotal || 0);
                    $('#mf-count-dispatched').text(rDispatched[0].recordsTotal || 0);
                    $('#mf-count-received').text(rReceived[0].recordsTotal || 0);
                });
            }

            /* ── Recent manifests ── */
            var currentManifestFilter = '';
            function loadPendingManifests() {
                var reqData = { length: 20, start: 0 };
                if (currentManifestFilter) {
                    reqData.status = currentManifestFilter;
                }

                $.get('api/manifest/read.php', reqData, function (res) {
                    var rows = '';
                    if (res.data && res.data.length > 0) {
                        res.data.forEach(function (m) {
                            var from = m.from_branch_name || '—';
                            var to = m.to_branch_name || '—';
                            var mStatus = m.status || 'Pending';
                            var stLower = mStatus.toLowerCase();
                            var bdgClass = 'badge bg-secondary-subtle text-secondary';
                            if (stLower === 'received') bdgClass = 'badge bg-success-subtle text-success';
                            else if (stLower === 'dispatched') bdgClass = 'badge-dispatched';
                            else if (stLower === 'draft') bdgClass = 'badge bg-info-subtle text-info';

                            var driver_name = m.driver_name || '—';
                            var awbCount = m.awb_count || 0;

                            rows += '<tr>'
                                + '<td class="ps-3"><span class="fw-bold text-primary fs-12">' + (m.manifest_no || '—') + '</span></td>'
                                + '<td><span class="text-muted">' + from + '</span> <i class="ti ti-arrow-right fs-10 text-muted mx-1"></i> <span class="fw-semibold">' + to + '</span></td>'
                                + '<td>' + (m.coloader || m.vehicle_no || '—') + '</td>'
                                + '<td>' + driver_name + '</td>'
                                + '<td class="text-center fw-semibold">' + (m.total_bags || 0) + '</td>'
                                + '<td class="text-center">' + (m.total_weight || 0) + '</td>'
                                + '<td class="text-center fw-semibold">' + awbCount + '</td>'
                                + '<td class="text-muted">' + (m.created_at ? moment(m.created_at).format('D MMM, HH:mm') : '—') + '</td>'
                                + '<td class="text-center"><span class="' + bdgClass + '" style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;letter-spacing:.03em;">' + mStatus + '</span></td>'
                                + '<td class="text-center pe-3"><a href="whms-manifest-list.php" class="btn btn-sm btn-light py-0 px-2 fs-11 border">View</a></td>'
                                + '</tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="10" class="text-center py-4 fs-12 text-muted">'
                            + '<i class="ti ti-circle-check text-success me-2 fs-16"></i>No manifests found'
                            + '</td></tr>';
                    }
                    $('#manifest-pending-list').html(rows);
                });
            }

            // Filter button click handler
            $(document).on('click', '.manifest-filter-btn', function () {
                $('.manifest-filter-btn').removeClass('active');
                $(this).addClass('active');
                currentManifestFilter = $(this).data('filter');
                loadPendingManifests();
            });

            /* ── Main dashboard data ── */
            $(document).ready(function () {
                let timelineChart, distributionChart, branchChart, topClientsChart, runsheetBranchChart, calendar;
                let currentStartDate = moment().startOf('month');
                let start = moment().startOf('month');
                let end = moment().endOf('month');

                function cb(s, e) {
                    $('#dashboard-range span').html(s.format('D MMM YYYY') + ' — ' + e.format('D MMM YYYY'));
                    currentStartDate = s;
                    start = s; end = e;
                    loadDashboardData(s.format('YYYY-MM-DD'), e.format('YYYY-MM-DD'));
                }

                $('#dashboard-range').daterangepicker({
                    startDate: start, endDate: end,
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                }, cb);

                <?php
                $dashboardCourierId = 2; // Default OWN
                if ($activeTab === 'shiprocket') {
                    $cStmt = $pdo->prepare("SELECT id FROM tbl_courier_partner WHERE LOWER(partner_name) LIKE '%shiprocket%' LIMIT 1");
                    $cStmt->execute();
                    $dashboardCourierId = $cStmt->fetchColumn() ?: 3;
                } elseif ($activeTab === 'delhivery') {
                    $cStmt = $pdo->prepare("SELECT id FROM tbl_courier_partner WHERE LOWER(partner_name) LIKE '%delhivery%' LIMIT 1");
                    $cStmt->execute();
                    $dashboardCourierId = $cStmt->fetchColumn() ?: 1;
                }
                ?>
                var currentCourierId = <?php echo (int) $dashboardCourierId; ?>;

                function loadDashboardData(from, to) {
                    $.get('api/dashboard/read.php', { from: from, to: to, courier_id: currentCourierId }, function (d) {
                        if (d.status !== 'success') return;

                        $('#total-shipments').text(d.data.total_shipments);

                        let delivered = 0, transit = 0, pending = 0, ofd = 0, rto = 0;
                        if (d.data.status_counts) {
                            d.data.status_counts.forEach(s => {
                                let st = (s.last_status || '').toLowerCase().trim();
                                if (st.includes('delivered')) delivered += +s.count;
                                else if (st.includes('out for delivery') || st === 'ofd' || st.includes('delivery')) ofd += +s.count;
                                else if (st.includes('transit') || st.includes('manifest') || st.includes('shipped')) transit += +s.count;
                                else if (st.includes('pending') || st.includes('booked') || st.includes('created')) pending += +s.count;
                                else if (st.includes('rto') || st.includes('return') || st.includes('failed')) rto += +s.count;
                                else pending += +s.count;
                            });
                        }
                        $('#delivered-shipments').text(delivered);
                        $('#transit-shipments').text(transit);
                        $('#pending-shipments').text(pending);
                        $('#ofd-shipments').text(ofd);
                        $('#rto-shipments').text(rto);

                        if (d.data.today_status_counts) {
                            let t_del = 0, t_trans = 0, t_pend = 0, t_ofd = 0, t_rto = 0, tot = 0;
                            Object.keys(d.data.today_status_counts).forEach(k => {
                                let st = k.toLowerCase().trim(), c = +d.data.today_status_counts[k]; tot += c;
                                if (st.includes('delivered')) t_del += c;
                                else if (st.includes('out for delivery') || st === 'ofd') t_ofd += c;
                                else if (st.includes('transit') || st.includes('manifest') || st.includes('shipped')) t_trans += c;
                                else if (st.includes('pending') || st.includes('booked') || st.includes('created')) t_pend += c;
                                else if (st.includes('rto') || st.includes('return') || st.includes('failed')) t_rto += c;
                            });
                            $('#remark-total').text('+' + tot + ' Today');
                            $('#remark-booked').text('+' + t_pend + ' Today');
                            $('#remark-transit').text('+' + t_trans + ' Today');
                            $('#remark-ofd').text('+' + t_ofd + ' Today');
                            $('#remark-delivered').text('+' + t_del + ' Today');
                            $('#remark-rto').text('+' + t_rto + ' Today');
                        }

                        $('#cod-total').text('₹' + parseFloat(d.data.cod_total || 0).toLocaleString('en-IN'));
                        $('#active-branches').text(d.data.active_branches || 0);
                        $('#active-companies').text(d.data.active_companies || 0);
                        $('#active-employees').text(d.data.active_employees || 0);
                        $('#today-pickups').text(d.data.today_pickups || 0);
                        $('#today-picked').text(d.data.today_picked || 0);
                        $('#today-pending').text(d.data.today_pending || 0);
                        $('#upcoming-pickups').text(d.data.upcoming_pickups || 0);
                        $('#ndr-count').text(d.data.ndr_count || 0);

                        // Charts
                        updateTopClientsChart(d.data.top_clients);
                        updateBranchChart(d.data.branch_stats);
                        updateRunsheetBranchChart(d.data.runsheet_branch_stats);
                        updateActivityFeed(d.data.recent_activity);

                        // Bulk Uploads
                        let bHtml = '';
                        if (d.data.recent_bulk_jobs && d.data.recent_bulk_jobs.length) {
                            d.data.recent_bulk_jobs.forEach(j => {
                                let bc = j.status === 'Completed' ? 'bg-success' : j.status === 'Processing' ? 'bg-warning text-dark' : 'bg-secondary';
                                let createdBy = j.username || 'System';
                                let actions = '';
                                if (j.success_count > 0) {
                                    actions += `<a href="shipment-bulk-print.php?job_id=${j.id}" target="_blank" class="btn btn-sm btn-warning fs-10 py-0 px-2" title="Print Labels"><i class="ti ti-printer"></i> Print</a> `;
                                }
                                if (j.result_file) {
                                    actions += `<a href="api/shipment/export_result.php?id=${j.id}" class="btn btn-sm btn-info fs-10 py-0 px-2" download title="Download Result"><i class="ti ti-download"></i> Excel</a>`;
                                }
                                if (!actions) actions = '<span class="text-muted fs-11">—</span>';

                                bHtml += `<tr>
                            <td class="ps-3">${moment(j.created_at).format('D MMM, HH:mm')}</td>
                            <td><span class="text-truncate d-inline-block" style="max-width:140px;" title="${j.filename}">${j.filename}</span></td>
                            <td class="text-center fw-semibold">${j.total_records}</td>
                            <td class="text-center text-success fw-bold">${j.success_count}</td>
                            <td class="text-center text-danger fw-bold">${j.failure_count}</td>
                            <td><span class="fs-11">${createdBy}</span></td>
                            <td class="text-center"><span class="badge ${bc} fs-10">${j.status}</span></td>
                            <td class="text-center pe-3">${actions}</td>
                        </tr>`;
                            });
                        } else {
                            bHtml = '<tr><td colspan="8" class="text-center py-4 text-muted fs-12">No recent uploads</td></tr>';
                        }
                        $('#bulk-upload-list').html(bHtml);

                        // Attendance
                        if (d.data.attendance_summary) {
                            $('#att-present').text(d.data.attendance_summary.present || 0);
                            $('#att-absent').text(d.data.attendance_summary.absent || 0);
                            $('#att-half-day').text(d.data.attendance_summary.half_day || 0);
                            $('#att-leave').text(d.data.attendance_summary.leave || 0);
                        }

                        updateTimeline(d.data.daily_stats);
                        updateDistribution(d.data.status_counts);
                        updateCalendar(d.data.calendar_events);
                    });
                }

                /* ── Modern Area Chart: Daily Shipments (3-series) ── */
                function updateTimeline(stats) {
                    if (!stats || !stats.length) return;
                    const opts = {
                        series: [
                            { name: 'Total', data: stats.map(s => +s.count) },
                            { name: 'Delivered', data: stats.map(s => +(s.delivered || 0)) },
                            { name: 'RTO', data: stats.map(s => +(s.rto || 0)) }
                        ],
                        chart: {
                            height: 290, type: 'area', toolbar: { show: false },
                            fontFamily: 'inherit', zoom: { enabled: false },
                            animations: { enabled: true, easing: 'easeinout', speed: 600 }
                        },
                        stroke: { curve: 'smooth', width: [2.5, 2.5, 2] },
                        fill: {
                            type: 'gradient',
                            gradient: { shadeIntensity: 1, opacityFrom: 0.32, opacityTo: 0.02, stops: [0, 90, 100] }
                        },
                        colors: ['#3e60d5', '#16a34a', '#dc2626'],
                        xaxis: {
                            categories: stats.map(s => moment(s.date).format('D MMM')),
                            labels: { style: { fontSize: '11px', colors: '#94a3b8' }, rotate: -30, rotateAlways: false },
                            axisBorder: { show: false }, axisTicks: { show: false }
                        },
                        yaxis: { labels: { style: { fontSize: '11px', colors: '#94a3b8' } }, min: 0 },
                        grid: { borderColor: '#f1f5f9', strokeDashArray: 4, padding: { left: 0, right: 8 } },
                        legend: { show: false },
                        markers: { size: 0, hover: { size: 5 } },
                        tooltip: {
                            shared: true, intersect: false,
                            y: { formatter: v => v + ' shipments' }
                        }
                    };
                    if (timelineChart) timelineChart.destroy();
                    timelineChart = new ApexCharts(document.querySelector('#shipment-timeline'), opts);
                    timelineChart.render();
                }

                /* ── Modern Donut: Status Distribution ── */
                function updateDistribution(counts) {
                    if (!counts || !counts.length) return;
                    const opts = {
                        series: counts.map(s => +s.count),
                        chart: { type: 'donut', height: 290, fontFamily: 'inherit' },
                        labels: counts.map(s => s.last_status),
                        legend: { position: 'bottom', fontSize: '11px', itemMargin: { horizontal: 5, vertical: 3 } },
                        colors: ['#93b5f7', '#86efac', '#67e8f9', '#fcd34d', '#fca5a5', '#c4b5fd', '#a5f3fc', '#fde68a'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true, label: 'Total',
                                            fontSize: '12px', fontWeight: '700', color: '#0f172a',
                                            formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        },
                                        value: { fontSize: '22px', fontWeight: '800', color: '#0f172a' }
                                    }
                                }
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: { width: 2 },
                        tooltip: { y: { formatter: v => v + ' shipments' } }
                    };
                    if (distributionChart) distributionChart.destroy();
                    distributionChart = new ApexCharts(document.querySelector('#status-distribution'), opts);
                    distributionChart.render();
                }

                /* ── Horizontal Bar: Branch Performance ── */
                function updateBranchChart(branches) {
                    if (!branches || !branches.length) {
                        $('#branch-performance').html('<div class="d-flex align-items-center justify-content-center h-100 text-muted fs-12 py-5">No branch data for this period</div>');
                        return;
                    }
                    const branchColors = ['#3e60d5', '#0891b2', '#16a34a', '#4f46e5', '#d97706', '#dc2626', '#7c3aed', '#06b6d4', '#f59e0b', '#22c55e'];
                    const opts = {
                        series: [{ name: 'Shipments', data: branches.map(b => +b.count) }],
                        chart: {
                            height: 300, type: 'bar', toolbar: { show: false },
                            fontFamily: 'inherit', animations: { enabled: true, easing: 'easeinout', speed: 700 }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true, barHeight: '58%', borderRadius: 5,
                                distributed: true,
                                dataLabels: { position: 'bottom' }
                            }
                        },
                        dataLabels: {
                            enabled: true, textAnchor: 'start', offsetX: 8,
                            style: { fontSize: '11px', fontWeight: '700', colors: ['#fff'] },
                            formatter: v => v
                        },
                        xaxis: {
                            categories: branches.map(b => b.branch_name),
                            labels: { style: { fontSize: '11px', colors: '#94a3b8' } },
                            axisBorder: { show: false }, axisTicks: { show: false }
                        },
                        yaxis: { labels: { style: { fontSize: '11.5px', colors: '#334155', fontWeight: '600' }, maxWidth: 130 } },
                        grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                        colors: branchColors,
                        legend: { show: false },
                        tooltip: { y: { formatter: v => v + ' shipments' } }
                    };
                    if (branchChart) branchChart.destroy();
                    branchChart = new ApexCharts(document.querySelector('#branch-performance'), opts);
                    branchChart.render();
                }

                /* ── Horizontal Bar: Top Clients ── */
                function updateTopClientsChart(clients) {
                    if (!clients || !clients.length) {
                        $('#top-clients-chart').html('<div class="d-flex align-items-center justify-content-center h-100 text-muted fs-12 py-5">No client data for this period</div>');
                        return;
                    }
                    const clientColors = ['#f59e0b', '#3e60d5', '#16a34a', '#7c3aed', '#dc2626'];
                    const opts = {
                        series: [{ name: 'Shipments', data: clients.map(c => +c.count) }],
                        chart: {
                            height: 300, type: 'bar', toolbar: { show: false },
                            fontFamily: 'inherit', animations: { enabled: true, easing: 'easeinout', speed: 700 }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true, barHeight: '58%', borderRadius: 5,
                                distributed: true,
                                dataLabels: { position: 'bottom' }
                            }
                        },
                        dataLabels: {
                            enabled: true, textAnchor: 'start', offsetX: 8,
                            style: { fontSize: '11px', fontWeight: '700', colors: ['#fff'] },
                            formatter: v => v
                        },
                        xaxis: {
                            categories: clients.map(c => c.company_name),
                            labels: { style: { fontSize: '11px', colors: '#94a3b8' } },
                            axisBorder: { show: false }, axisTicks: { show: false }
                        },
                        yaxis: { labels: { style: { fontSize: '11.5px', colors: '#334155', fontWeight: '600' }, maxWidth: 150 } },
                        grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                        colors: clientColors,
                        legend: { show: false },
                        tooltip: { y: { formatter: v => v + ' shipments' } }
                    };
                    if (topClientsChart) topClientsChart.destroy();
                    topClientsChart = new ApexCharts(document.querySelector('#top-clients-chart'), opts);
                    topClientsChart.render();
                }

                /* ── Grouped Bar: Runsheet by Branch (Lit Gradient Colors) ── */
                function updateRunsheetBranchChart(data) {
                    if (!data || !data.length) {
                        $('#runsheet-branch-chart').html('<div class="d-flex align-items-center justify-content-center h-100 text-muted fs-12 py-5"><i class="ti ti-clipboard-list me-2 fs-18"></i>No runsheet data for this period</div>');
                        return;
                    }
                    const branches  = data.map(d => d.branch_name);
                    const delivered = data.map(d => +(d.delivered||0));
                    const ofd       = data.map(d => +(d.ofd||0));
                    const picked    = data.map(d => +(d.picked||0));
                    const opts = {
                        series: [
                            { name: 'Delivered',        data: delivered },
                            { name: 'Out for Delivery', data: ofd },
                            { name: 'Picked Up',        data: picked }
                        ],
                        chart: {
                            height: 300, type: 'bar', toolbar: { show: false },
                            fontFamily: 'inherit',
                            animations: { enabled: true, easing: 'easeinout', speed: 800 },
                            dropShadow: { enabled: true, top: 4, left: 0, blur: 6, color: ['#10b981','#6366f1','#f97316'], opacity: 0.18 }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '52%',
                                borderRadius: 7,
                                borderRadiusApplication: 'end',
                                dataLabels: { position: 'top' }
                            }
                        },
                        dataLabels: {
                            enabled: true, offsetY: -20,
                            style: { fontSize: '10.5px', fontWeight: '700', colors: ['#475569'] },
                            formatter: v => v > 0 ? v : ''
                        },
                        stroke: { show: false },
                        colors: ['#10b981', '#6366f1', '#f97316'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'light', type: 'vertical',
                                shadeIntensity: 0.35,
                                gradientToColors: ['#34d399', '#818cf8', '#fb923c'],
                                inverseColors: false,
                                opacityFrom: 1, opacityTo: 0.72,
                                stops: [0, 100]
                            }
                        },
                        xaxis: {
                            categories: branches,
                            labels: { style: { fontSize: '11.5px', colors: '#475569', fontWeight: '700' } },
                            axisBorder: { show: false }, axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: { style: { fontSize: '11px', colors: '#94a3b8' } },
                            min: 0
                        },
                        grid: {
                            borderColor: '#f1f5f9', strokeDashArray: 4,
                            yaxis: { lines: { show: true } },
                            xaxis: { lines: { show: false } },
                            padding: { top: 20, right: 10, bottom: 0, left: 10 }
                        },
                        legend: { show: false },
                        tooltip: {
                            shared: true, intersect: false,
                            y: { formatter: v => v + ' shipments' },
                            style: { fontSize: '12px' }
                        }
                    };
                    if (runsheetBranchChart) runsheetBranchChart.destroy();
                    runsheetBranchChart = new ApexCharts(document.querySelector('#runsheet-branch-chart'), opts);
                    runsheetBranchChart.render();
                }

                /* ── Auto-scroll Recent Activity Feed ── */
                let activityScrollTimer = null;
                function updateActivityFeed(activities) {
                    if (!activities || !activities.length) {
                        $('#activity-feed').html('<div class="text-center py-5 text-muted fs-12">No recent activity</div>');
                        return;
                    }

                    const statusColor = {
                        'delivered':        { bg:'#dcfce7', color:'#16a34a', dot:'#16a34a' },
                        'out for delivery': { bg:'#dbeafe', color:'#3e60d5', dot:'#3e60d5' },
                        'dispatched':       { bg:'#e0f2fe', color:'#0891b2', dot:'#0891b2' },
                        'rto':              { bg:'#fee2e2', color:'#dc2626', dot:'#dc2626' },
                        'failed':           { bg:'#fee2e2', color:'#dc2626', dot:'#dc2626' },
                        'created':          { bg:'#fef3c7', color:'#d97706', dot:'#d97706' },
                        'manifested':       { bg:'#ede9fe', color:'#7c3aed', dot:'#7c3aed' }
                    };

                    function getStatusStyle(status) {
                        const key = (status||'').toLowerCase().trim();
                        for (const k in statusColor) {
                            if (key.includes(k)) return statusColor[k];
                        }
                        return { bg:'#f1f5f9', color:'#64748b', dot:'#94a3b8' };
                    }

                    let html = '';
                    activities.forEach(a => {
                        const sc = getStatusStyle(a.last_status);
                        const timeAgo = moment(a.created_at).fromNow();
                        html += `<div class="activity-item">
                            <div class="act-dot" style="background:${sc.dot};"></div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="act-waybill">${a.waybill_no || '—'}</div>
                                <div class="act-consignee">${a.consignee_name || '—'} · ${a.consignee_city || '—'}</div>
                                <div class="act-meta"><i class="ti ti-building fs-10 me-1"></i>${a.branch_name || '—'} &nbsp;·&nbsp; ${timeAgo}</div>
                            </div>
                            <span class="act-status" style="background:${sc.bg};color:${sc.color};">${a.last_status || 'Unknown'}</span>
                        </div>`;
                    });
                    $('#activity-feed').html(html);
                    $('#activity-count').text(activities.length + ' entries');

                    // Auto-scroll: smooth continuous scroll, loop back to top
                    if (activityScrollTimer) clearInterval(activityScrollTimer);
                    const wrap = document.getElementById('activity-feed-wrap');
                    wrap.scrollTop = 0;

                    function startActivityScroll() {
                        activityScrollTimer = setInterval(function() {
                            const maxScroll = wrap.scrollHeight - wrap.clientHeight;
                            if (maxScroll <= 0) return;
                            wrap.scrollTop += 1;
                            if (wrap.scrollTop >= maxScroll) {
                                clearInterval(activityScrollTimer);
                                setTimeout(function() {
                                    wrap.scrollTop = 0;
                                    startActivityScroll();
                                }, 2000);
                            }
                        }, 30);
                    }
                    startActivityScroll();

                    // Pause on hover
                    wrap.addEventListener('mouseenter', function() { clearInterval(activityScrollTimer); });
                    wrap.addEventListener('mouseleave', startActivityScroll);
                }

                function updateCalendar(events) {
                    const el = document.getElementById('shipment-calendar');
                    if (calendar) calendar.destroy();
                    calendar = new FullCalendar.Calendar(el, {
                        contentHeight: 'auto',
                        aspectRatio: 1.2,
                        initialView: 'dayGridMonth',
                        initialDate: currentStartDate.toDate(),
                        headerToolbar: {
                            left: 'prev',
                            center: 'title',
                            right: 'next'
                        },
                        titleFormat: { year: 'numeric', month: 'short' },
                        events: events || [],
                        themeSystem: 'bootstrap5'
                    });
                    calendar.render();
                }

                cb(start, end);
                loadPendingManifests();
                loadManifestCounts();

                // Stat card clicks
                $(document).on('click', '.stat-card', function () {
                    const status = $(this).data('status');
                    window.location.href = 'shipment-list.php?status=' + encodeURIComponent(status || '') + '&from=' + start.format('YYYY-MM-DD') + '&to=' + end.format('YYYY-MM-DD');
                });
            });

            /* ── Quick AWB Search (dashboard) ── */
            (function () {
                const inp = document.getElementById('dashAwbSearch');
                const res = document.getElementById('dashAwbResults');
                const statusColors = {
                    'Delivered': 'success', 'In Transit': 'primary', 'Dispatched': 'primary',
                    'Created': 'secondary', 'Pending': 'secondary', 'Manifested': 'info',
                    'RTO': 'danger', 'Failed': 'danger', 'Returned': 'danger', 'Delivery Failed': 'danger',
                    'Out For Delivery': 'warning', 'Picked Up': 'warning'
                };
                function badgeCls(s) { return statusColors[s] || 'secondary'; }

                let timer = null;
                inp.addEventListener('input', function () {
                    clearTimeout(timer);
                    const q = this.value.trim();
                    if (q.length < 2) { res.innerHTML = ''; return; }
                    res.innerHTML = '<div class="text-muted fs-12 py-1 text-center">Searching...</div>';
                    timer = setTimeout(function () {
                        fetch('api/shipment/search_awb.php?q=' + encodeURIComponent(q))
                            .then(r => r.json())
                            .then(function (data) {
                                if (!data.length) {
                                    res.innerHTML = '<div class="text-muted fs-12 py-2 text-center">No results found</div>';
                                    return;
                                }
                                let html = '<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">';
                                data.forEach(function (r, i) {
                                    const url = 'tracking.php?waybill=' + encodeURIComponent(r.waybill);
                                    const cls = badgeCls(r.status);
                                    const border = i < data.length - 1 ? 'border-bottom:1px solid #f1f5f9;' : '';
                                    html += `<a href="${url}" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none dash-awb-item" style="color:inherit;${border}background:#fff;">`;
                                    html += `<div class="flex-grow-1 overflow-hidden">`;
                                    html += `<div class="fw-semibold fs-13">${r.waybill || '-'}`;
                                    if (r.is_child) html += ` <span class="badge badge-soft-info fs-10 ms-1">Child</span>`;
                                    html += `</div>`;
                                    html += `<div class="text-muted fs-11 text-truncate">${r.consignee || ''}${r.ref ? ' · ' + r.ref : ''}</div>`;
                                    html += `</div>`;
                                    html += `<span class="badge bg-${cls} flex-shrink-0 fs-10">${r.status || '-'}</span>`;
                                    html += `</a>`;
                                });
                                html += '</div>';
                                res.innerHTML = html;
                            })
                            .catch(function () { res.innerHTML = ''; });
                    }, 280);
                });

                inp.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const q = this.value.trim();
                        if (q) window.location.href = 'tracking.php?waybill=' + encodeURIComponent(q);
                    }
                });

                // Hover highlight
                document.addEventListener('mouseover', function (e) {
                    const item = e.target.closest('.dash-awb-item');
                    if (!item) return;
                    document.querySelectorAll('.dash-awb-item').forEach(el => el.style.background = '#fff');
                    item.style.background = '#f8faff';
                });
            })();
        </script>
    </div><!-- /wrapper -->
</body>

</html>