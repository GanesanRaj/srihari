<?php include 'header.php'; ?>
<?php if ( ! defined ( 'MIDDLEWARE_INCLUDED' )) {
    require_once __DIR__ . '/config/middleware.php';
    }
require_permission ( 'whms_shipment', 'is_view' ); ?>
<?php
// Resolve scope for client-based users
$_shlUserType      = $_SESSION[ 'user_type' ] ?? 'both';
$_shlClientAccess  = false;
$_shlAllowedBranch = [];  // branch ids the user can see
$_shlAllowedClient = [];  // client ids the user can see

if ($_shlUserType === 'client') {
    $chkSHL = $pdo->prepare ( "SELECT clientaccess, branch_ids, client_ids FROM tbl_user WHERE username = ? LIMIT 1" );
    $chkSHL->execute ( [ $_SESSION[ 'username' ] ] );
    $chkSHLRow = $chkSHL->fetch ( PDO::FETCH_ASSOC );
    if ($chkSHLRow && $chkSHLRow[ 'clientaccess' ] == 1) {
        $_shlClientAccess  = true;
        $rawB              = $chkSHLRow[ 'branch_ids' ] ?? '';
        $_shlAllowedBranch = $rawB !== '' ? array_values ( array_filter ( array_map ( 'intval', explode ( ',', $rawB ) ) ) ) : [];
        $rawC              = $chkSHLRow[ 'client_ids' ] ?? '';
        $_shlAllowedClient = $rawC !== '' ? array_values ( array_filter ( array_map ( 'intval', explode ( ',', $rawC ) ) ) ) : [];
        }
    }

// Superadmin check (role_id = 1)
$isSuperAdmin = ((int) ($_SESSION[ 'role_id' ] ?? 0) === 1);
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedColumns.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<style>
    /* ══════════════════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════════════════ */
    .shl-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0 12px;
        border-bottom: 1px solid #eaecf0;
        margin-bottom: 14px;
    }

    .shl-header-title {
        font-size: 15px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .shl-header-title i[data-lucide] {
        color: #da7d41;
        width: 18px;
        height: 18px;
    }

    .shl-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-filter-toggle {
        height: 30px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0 10px;
        border: 1px solid #d0d5dd;
        background: #fff;
        color: #344054;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .btn-filter-toggle:hover {
        border-color: #da7d41;
        color: #da7d41;
    }

    .btn-filter-toggle.active {
        background: #fff3ec;
        border-color: #da7d41;
        color: #da7d41;
    }

    .btn-filter-toggle .filter-active-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #da7d41;
        display: none;
        flex-shrink: 0;
    }

    .btn-filter-toggle.has-filters .filter-active-dot {
        display: block;
    }

    .btn-new-shipment {
        height: 30px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0 12px;
        background: #da7d41;
        border: 1px solid #da7d41;
        color: #fff;
        text-decoration: none;
        transition: all .15s;
        white-space: nowrap;
    }

    .btn-new-shipment:hover {
        background: #b5612c;
        border-color: #b5612c;
        color: #fff;
    }

    /* ══════════════════════════════════════════════════════
   STAT CARDS
══════════════════════════════════════════════════════ */
    .stat-cards {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }

    @media (max-width: 992px) {
        .stat-cards {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stat-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .stat-card {
        background: #fff;
        border: 1.5px solid #eaecf0;
        border-radius: 10px;
        padding: 11px 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: box-shadow .15s, border-color .15s, transform .1s;
        user-select: none;
    }

    .stat-card:hover {
        box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
        border-color: #d0d5dd;
        transform: translateY(-1px);
    }

    .stat-card.active {
        border-color: var(--sc);
        background: color-mix(in srgb, var(--sc) 6%, #fff);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--sc) 18%, transparent);
    }

    .stat-card-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--sc) 13%, transparent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
        color: var(--sc);
    }

    .stat-card-value {
        font-size: 19px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
        transition: color .2s;
    }

    .stat-card.active .stat-card-value {
        color: var(--sc);
    }

    .stat-card-label {
        font-size: 9.5px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-top: 2px;
    }

    /* ══════════════════════════════════════════════════════
   FILTER BAR (collapsible)
══════════════════════════════════════════════════════ */
    .filter-bar-wrap {
        overflow: hidden;
        max-height: 0;
        transition: max-height .3s ease, opacity .3s ease, margin .3s ease;
        opacity: 0;
        margin-bottom: 0;
    }

    .filter-bar-wrap.open {
        max-height: 200px;
        opacity: 1;
        margin-bottom: 12px;
    }

    .filter-bar {
        background: #f8f9fb;
        border: 1px solid #eaecf0;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 1.5fr auto;
        gap: 8px;
        align-items: end;
    }

    @media (max-width: 992px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr 1fr;
        }
    }

    .filter-label {
        font-size: 9.5px;
        font-weight: 700;
        color: #667085;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 4px;
    }

    .filter-bar .form-select {
        font-size: 12px;
        border-color: #d0d5dd;
        border-radius: 6px;
        height: 30px;
        padding: 3px 8px;
    }

    .filter-bar .form-select:focus {
        border-color: #da7d41;
        box-shadow: 0 0 0 3px rgba(218, 125, 65, .15);
    }

    #shipment-range {
        height: 30px;
        font-size: 12px;
        border: 1px solid #d0d5dd;
        border-radius: 6px;
        padding: 3px 10px;
        background: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    #shipment-range:hover {
        border-color: #da7d41;
    }

    #shipment-range span {
        flex-grow: 1;
        color: #344054;
        font-size: 11.5px;
    }

    .btn-reset {
        height: 30px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0 12px;
        border: 1px solid #d0d5dd;
        background: #fff;
        color: #344054;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-reset:hover {
        border-color: #dc3545;
        color: #dc3545;
    }

    /* ══════════════════════════════════════════════════════
   TABLE CARD
══════════════════════════════════════════════════════ */
    .table-card {
        border: 1px solid #eaecf0;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    #shipmentTable td,
    #shipmentTable th {
        white-space: nowrap;
        vertical-align: middle;
        font-size: 12px;
        padding: 5px 9px !important;
    }

    /* Address cells wrap naturally */
    .address-wrap {
        white-space: normal !important;
        word-break: break-word;
    }

    #shipmentTable thead th {
        background: #f2f4f7 !important;
        color: #344054;
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .3px;
        border-bottom: 2px solid #d0d5dd !important;
        white-space: nowrap;
    }

    #shipmentTable tbody tr {
        transition: background .1s;
    }

    #shipmentTable tbody tr:hover td {
        background: #fffaf7 !important;
    }

    #shipmentTable tbody tr:nth-child(even) td {
        background: #fafafa;
    }

    #shipmentTable tbody tr:nth-child(even):hover td {
        background: #fffaf7 !important;
    }

    /* First column — waybill, content contained within column */
    #shipmentTable td:first-child,
    #shipmentTable th:first-child {
        min-width: 180px;
        max-width: 200px;
        white-space: normal;
        overflow: hidden;
    }

    #shipmentTable td:first-child {
        min-width: 0;
        word-wrap: break-word;
    }

    /* ══════════════════════════════════════════════════════
   WAYBILL COLUMN (first col — compact horizontal)
══════════════════════════════════════════════════════ */
    .wbl-cell {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
        overflow: hidden;
    }

    .wbl-top {
        display: flex;
        align-items: center;
    }

    .wbl-no {
        font-size: 12.5px;
        font-weight: 700;
        color: #da7d41;
        letter-spacing: .3px;
        line-height: 1;
    }

    /* ── POD thumbnails (contained within AWB column) ────────────────────────── */
    .pod-thumbs {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-start;
        gap: 6px;
        margin-top: 4px;
        min-width: 0;
        overflow: hidden;
    }

    .pod-thumb-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        min-width: 0;
        overflow: hidden;
        flex: 0 1 auto;
    }

    .pod-thumb-label {
        font-size: 8px;
        font-weight: 700;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: .4px;
        line-height: 1;
        flex-shrink: 0;
    }

    .pod-thumb-imgs {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        justify-content: center;
        min-width: 0;
        max-width: 100%;
    }

    .pod-thumb-img {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 4px;
        border: 1.5px solid #d0f0e0;
        cursor: pointer;
        transition: transform .15s, box-shadow .15s;
        display: block;
        flex-shrink: 0;
    }

    .pod-thumb-img:hover {
        transform: scale(1.12);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .22);
        border-color: #027a48;
    }

    .pod-thumb-empty {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        border: 1.5px dashed #e0e0e0;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d0d0d0;
        font-size: 12px;
        cursor: default;
    }

    .pod-thumb-divider {
        width: 1px;
        min-height: 32px;
        align-self: stretch;
        background: #eee;
        flex-shrink: 0;
    }

    .wbl-awbs {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        align-items: center;
        min-width: 0;
        max-width: 100%;
    }

    .awb-badge {
        display: inline-block;
        background: #fff3ec;
        color: #da7d41;
        border: 1px solid #f4c49a;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 600;
        padding: 1px 5px;
        text-decoration: none;
        letter-spacing: .2px;
        cursor: pointer;
        transition: all .1s;
    }

    .awb-badge:hover {
        background: #da7d41;
        color: #fff;
        border-color: #da7d41;
    }

    .awb-more-link {
        cursor: pointer;
        color: #da7d41;
        font-size: 9px;
        font-weight: 700;
        text-decoration: underline;
    }

    .awb-more-link:hover {
        color: #b5612c;
    }

    /* ══════════════════════════════════════════════════════
   STATUS PILL
══════════════════════════════════════════════════════ */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 20px;
        white-space: nowrap;
    }

    .status-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ══════════════════════════════════════════════════════
   CELL CHIPS
══════════════════════════════════════════════════════ */
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 10px;
        font-weight: 500;
        color: #475467;
        background: #f2f4f7;
        border-radius: 4px;
        padding: 2px 6px;
        white-space: nowrap;
    }

    .chip i {
        font-size: 10px;
        color: #98a2b3;
    }

    .chip-blue {
        background: #eef4ff;
        color: #3538cd;
        font-weight: 600;
    }

    .chip-amber {
        background: #fffaeb;
        color: #b54708;
        border: 1px solid #fedf89;
        font-weight: 700;
    }

    .chip-green {
        background: #ecfdf3;
        color: #027a48;
        font-weight: 600;
    }

    .amount-val {
        font-size: 12px;
        font-weight: 700;
        color: #027a48;
    }

    /* ══════════════════════════════════════════════════════
   CONTACT CELL
══════════════════════════════════════════════════════ */
    .contact-name {
        font-weight: 600;
        font-size: 12px;
        color: #1a1d23;
    }

    .contact-meta {
        font-size: 10px;
        color: #667085;
        margin-top: 1px;
    }

    .contact-phone a {
        font-size: 10px;
        color: #da7d41;
        text-decoration: none;
    }

    .contact-phone a:hover {
        text-decoration: underline;
    }

    .address-wrap {
        white-space: normal;
        min-width: 160px;
        max-width: 220px;
        font-size: 11px;
        color: #475467;
        line-height: 1.4;
    }

    /* ══════════════════════════════════════════════════════
   ACTION BUTTONS
══════════════════════════════════════════════════════ */
    .action-wrap {
        display: flex;
        gap: 3px;
        align-items: center;
    }

    .btn-act {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        font-size: 12px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .15s;
        text-decoration: none;
        flex-shrink: 0;
        background: none;
    }

    .btn-act-view {
        background: #f2f4f7;
        color: #344054;
        border-color: #d0d5dd;
    }

    .btn-act-view:hover {
        background: #344054;
        color: #fff;
        border-color: #344054;
    }

    .btn-act-edit {
        background: #eef4ff;
        color: #3538cd;
        border-color: #c7d7fe;
    }

    .btn-act-edit:hover {
        background: #3538cd;
        color: #fff;
        border-color: #3538cd;
    }

    .btn-act-print {
        background: #fff3ec;
        color: #da7d41;
        border-color: #f4c49a;
    }

    .btn-act-print:hover {
        background: #da7d41;
        color: #fff;
        border-color: #da7d41;
    }

    .btn-act-del {
        background: #fff1f3;
        color: #c01048;
        border-color: #fda4af;
    }

    .btn-act-del:hover {
        background: #c01048;
        color: #fff;
        border-color: #c01048;
    }

    /* ── Delete toolbar ── */
    #shipDeleteBar {
        display: none;
        align-items: center;
        gap: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 8px 16px;
        margin-bottom: 10px;
        font-size: 13px;
    }

    #shipDeleteBar.show {
        display: flex;
    }

    /* Checkbox column */
    #shipmentTable th.cb-col,
    #shipmentTable td.cb-col {
        width: 36px;
        text-align: center;
        white-space: nowrap;
    }

    .row-cb,
    #selectAllCb {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #dc3545;
    }

    /* ══════════════════════════════════════════════════════
   DT FOOTER CHROME
══════════════════════════════════════════════════════ */
    .dataTables_length select,
    .dataTables_filter input {
        font-size: 12px;
        border-radius: 6px;
        border-color: #d0d5dd;
        padding: 4px 8px;
    }

    .dataTables_filter input:focus {
        border-color: #da7d41;
        box-shadow: 0 0 0 3px rgba(218, 125, 65, .15);
        outline: none;
    }

    div.dataTables_info {
        font-size: 11px;
        color: #667085;
        padding-top: 8px;
    }

    div.dataTables_paginate .paginate_button {
        border-radius: 5px !important;
        padding: 3px 8px !important;
        font-size: 11px !important;
    }

    div.dataTables_paginate .paginate_button.current {
        background: #da7d41 !important;
        border-color: #da7d41 !important;
        color: #fff !important;
    }

    div.dataTables_paginate .paginate_button:hover:not(.current) {
        background: #f2f4f7 !important;
        border-color: #d0d5dd !important;
        color: #344054 !important;
    }

    /* ══════════════════════════════════════════════════════
   POD FULLSCREEN OVERLAY
══════════════════════════════════════════════════════ */
    #podOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .88);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    #podOverlay.show {
        display: flex;
    }

    #podOverlay img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 10px;
        box-shadow: 0 4px 40px rgba(0, 0, 0, .6);
    }

    #podOverlay .pod-close {
        position: absolute;
        top: 16px;
        right: 24px;
        color: #fff;
        font-size: 28px;
        cursor: pointer;
        opacity: .7;
        line-height: 1;
    }

    #podOverlay .pod-close:hover {
        opacity: 1;
    }

    #podOverlay .no-pod-msg {
        color: #ccc;
        font-size: 15px;
        text-align: center;
    }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-none" style="background:transparent;">
                                <div class="card-body p-0">

                                    <!-- ── Page Header ─────────────────────────────────── -->
                                    <div class="shl-header">
                                        <h5 class="shl-header-title">
                                            <i data-lucide="package"></i>
                                            Shipment Management
                                        </h5>
                                        <div class="shl-header-actions">
                                            <span id="dateRangeChip" class="chip">
                                                <i class="ti ti-calendar" style="color:#da7d41;"></i>
                                                <span id="dateChipLabel"></span>
                                            </span>
                                            <button class="btn-filter-toggle" id="filterToggleBtn"
                                                title="Toggle Filters">
                                                <i class="ti ti-adjustments-horizontal"></i>
                                                Filters
                                                <span class="filter-active-dot"></span>
                                            </button>
                                            <a href="shipment-create.php" class="btn-new-shipment">
                                                <i class="ti ti-plus"></i> New Shipment
                                            </a>
                                        </div>
                                    </div>

                                    <!-- ── Stat Cards ──────────────────────────────────── -->
                                    <div class="stat-cards" id="statCards">
                                        <div class="stat-card" style="--sc:#667085;" data-filter="">
                                            <div class="stat-card-icon"><i class="ti ti-packages"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-total">—</div>
                                                <div class="stat-card-label">Total</div>
                                            </div>
                                        </div>
                                        <div class="stat-card" style="--sc:#fd7e14;" data-filter="Created">
                                            <div class="stat-card-icon"><i class="ti ti-circle-plus"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-created">—</div>
                                                <div class="stat-card-label">Created</div>
                                            </div>
                                        </div>
                                        <div class="stat-card" style="--sc:#0d6efd;" data-filter="In Transit">
                                            <div class="stat-card-icon"><i class="ti ti-truck"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-transit">—</div>
                                                <div class="stat-card-label">In Transit</div>
                                            </div>
                                        </div>
                                        <div class="stat-card" style="--sc:#198754;" data-filter="Delivered">
                                            <div class="stat-card-icon"><i class="ti ti-circle-check"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-delivered">—</div>
                                                <div class="stat-card-label">Delivered</div>
                                            </div>
                                        </div>
                                        <div class="stat-card" style="--sc:#dc3545;" data-filter="RTO">
                                            <div class="stat-card-icon"><i class="ti ti-arrow-back-up"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-rto">—</div>
                                                <div class="stat-card-label">RTO</div>
                                            </div>
                                        </div>
                                        <div class="stat-card" style="--sc:#7c3aed;" data-filter="Out for Delivery">
                                            <div class="stat-card-icon"><i class="ti ti-map-pin-bolt"></i></div>
                                            <div>
                                                <div class="stat-card-value" id="sc-ofd">—</div>
                                                <div class="stat-card-label">Out for Delivery</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ── Filter Bar (collapsed by default) ──────────── -->
                                    <div class="filter-bar-wrap" id="filterBarWrap">
                                        <div class="filter-bar">
                                            <div class="filter-grid">
                                                <div>
                                                    <div class="filter-label">Branch</div>
                                                    <select id="branchFilter" class="form-select">
                                                        <option value="">All Branches</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <div class="filter-label">Client</div>
                                                    <select id="clientFilter" class="form-select">
                                                        <option value="">All Clients</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <div class="filter-label">Courier</div>
                                                    <select id="courierFilter" class="form-select">
                                                        <option value="">All Couriers</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <div class="filter-label">Status</div>
                                                    <select id="statusFilter" class="form-select">
                                                        <option value="">All Status</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <div class="filter-label">Date Range</div>
                                                    <div id="shipment-range">
                                                        <i class="ti ti-calendar"
                                                            style="font-size:12px;color:#da7d41;"></i>
                                                        <span></span>
                                                        <i class="ti ti-chevron-down"
                                                            style="font-size:10px;color:#aaa;margin-left:auto;"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="filter-label">&nbsp;</div>
                                                    <button class="btn-reset w-100" id="clearFiltersBtn">
                                                        <i class="ti ti-x"></i> Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ── Shipment Table ──────────────────────────────── -->
                                    <?php if ($isSuperAdmin) : ?>
                                        <!-- Multi-delete toolbar (superadmin only) -->
                                        <div id="shipDeleteBar">
                                            <i class="ti ti-trash text-danger fs-18"></i>
                                            <span id="selectedCount" class="fw-semibold">0</span> shipment(s) selected
                                            <button id="btnMultiDelete" class="btn btn-danger btn-sm ms-2">
                                                <i class="ti ti-trash me-1"></i> Delete Selected
                                            </button>
                                            <button id="btnCancelSelect" class="btn btn-secondary btn-sm">Cancel</button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-card">
                                        <table id="shipmentTable" class="table table-hover table-bordered w-100 mb-0">
                                            <thead>
                                                <tr>
                                                    <?php if ($isSuperAdmin) : ?>
                                                        <th class="cb-col"><input type="checkbox" id="selectAllCb"
                                                                title="Select All"></th>
                                                    <?php endif; ?>
                                                    <th>Waybill</th>
                                                    <th>Ref No</th>
                                                    <th>Courier</th>
                                                    <th>Branch</th>
                                                    <th>Client</th>
                                                    <th>Boxes</th>
                                                    <th>Created By</th>
                                                    <th>Created At</th>
                                                    <th>Status</th>
                                                    <th>Mode</th>
                                                    <th>Payment</th>
                                                    <th>Amount</th>
                                                    <th>Sender</th>
                                                    <th>Sender Address</th>
                                                    <th>Receiver</th>
                                                    <th>Receiver Address</th>
                                                    <th>Action</th>
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
            </div>

            <!-- ── Full-screen POD overlay ─────────────────────────── -->
            <div id="podOverlay" onclick="closePodOverlay(event)">
                <span class="pod-close" onclick="closePodOverlay(event)">&times;</span>
                <div id="podOverlayContent"></div>
            </div>

            <?php include 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedColumns.min.js"></script>
            <script src="assets/plugins/datatables/fixedColumns.bootstrap5.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                /* ── POD overlay ─────────────────────────────────── */
                function openPodOverlay(src) {
                    var c = document.getElementById('podOverlayContent');
                    c.innerHTML = src
                        ? '<img src="' + src + '" alt="POD">'
                        : '<div class="no-pod-msg"><i class="ti ti-photo-off" style="font-size:56px;display:block;margin-bottom:10px;"></i>No Image Available</div>';
                    document.getElementById('podOverlay').classList.add('show');
                }
                function closePodOverlay(e) {
                    if (e.target.id === 'podOverlay' || e.target.classList.contains('pod-close'))
                        document.getElementById('podOverlay').classList.remove('show');
                }

                /* ── Status styling ──────────────────────────────── */
                function statusStyle(s) {
                    s = (s || 'created').toLowerCase();
                    if (s.includes('deliver')) return { bg: '#ecfdf3', color: '#027a48', dot: '#12b76a' };
                    if (s.includes('rto') || s.includes('return')) return { bg: '#fff1f3', color: '#c01048', dot: '#f63d68' };
                    if (s.includes('created')) return { bg: '#fff6ed', color: '#b54708', dot: '#fd7e14' };
                    if (s.includes('transit') || s.includes('pickup') ||
                        s.includes('dispatch') || s.includes('out for') ||
                        s.includes('manifest') || s.includes('booked'))
                        return { bg: '#eff8ff', color: '#175cd3', dot: '#2e90fa' };
                    return { bg: '#f2f4f7', color: '#344054', dot: '#98a2b3' };
                }

                $(document).ready(function () {
                    let table;
                    const urlParams = new URLSearchParams(window.location.search);
                    const preStatus = urlParams.get('status');
                    const preFrom = urlParams.get('from');
                    const preTo = urlParams.get('to');

                    /* ── Filter toggle ───────────────────────────── */
                    var filterOpen = false;
                    $('#filterToggleBtn').on('click', function () {
                        filterOpen = !filterOpen;
                        $('#filterBarWrap').toggleClass('open', filterOpen);
                        $(this).toggleClass('active', filterOpen);
                    });

                    /* If URL has status/from/to pre-filter, open filters */
                    if (preStatus || preFrom || preTo) {
                        filterOpen = true;
                        $('#filterBarWrap').addClass('open');
                        $('#filterToggleBtn').addClass('active');
                    }

                    function updateFilterDot() {
                        var hasFilter = $('#branchFilter').val() || $('#clientFilter').val()
                            || $('#courierFilter').val() || $('#statusFilter').val();
                        $('#filterToggleBtn').toggleClass('has-filters', !!hasFilter);
                    }

                    /* ── Scope vars from PHP session ─────────────── */
                    var shlIsClient = <?= $_shlClientAccess ? 'true' : 'false' ?>;
                    var shlAllowedBranch = <?= json_encode ( $_shlAllowedBranch ) ?>;
                    var shlAllowedClient = <?= json_encode ( $_shlAllowedClient ) ?>;

                    /* ── Load clients for a given branch (or all allowed) ── */
                    function loadClientDropdown(branchId) {
                        var url = 'api/client/read.php?length=1000';
                        if (branchId) url += '&branch_id=' + branchId;
                        else if (shlIsClient && shlAllowedBranch.length)
                            url += '&branch_ids=' + shlAllowedBranch.join(',');

                        var current = $('#clientFilter').val();
                        $('#clientFilter').html('<option value="">All Clients</option>');
                        $.get(url, function (res) {
                            if (res.data) res.data.forEach(function (c) {
                                if (!shlIsClient || shlAllowedClient.length === 0 || shlAllowedClient.indexOf(parseInt(c.id)) !== -1) {
                                    $('#clientFilter').append(`<option value="${c.id}">${c.client_name}</option>`);
                                }
                            });
                            if (current) $('#clientFilter').val(current);
                        });
                    }

                    /* ── Load filter dropdowns ───────────────────── */
                    // Branch: load all, then filter to allowed list for client users
                    $.get('api/branch/read.php?length=1000', function (res) {
                        if (res.data) res.data.forEach(function (b) {
                            if (!shlIsClient || shlAllowedBranch.indexOf(parseInt(b.id)) !== -1) {
                                $('#branchFilter').append(`<option value="${b.id}">${b.branch_name}</option>`);
                            }
                        });
                    });

                    // Client: load on page load, then reload when branch changes
                    loadClientDropdown('');
                    $('#branchFilter').on('change', function () {
                        loadClientDropdown($(this).val());
                        $('#clientFilter').val('');
                    });

                    $.get('api/shipment/get_unique_statuses.php', function (res) {
                        if (res.data) {
                            res.data.forEach(s => {
                                var sel = (preStatus && preStatus === s) ? 'selected' : '';
                                $('#statusFilter').append(`<option value="${s}" ${sel}>${s}</option>`);
                            });
                            if (table) table.ajax.reload();
                        }
                    });

                    $.get('api/courier_partner/read.php?length=100', function (res) {
                        if (res.data) res.data.forEach(c =>
                            $('#courierFilter').append(`<option value="${c.id}">${c.partner_name}</option>`)
                        );
                    });

                    /* ── Date Range ──────────────────────────────── */
                    let initialStart = preFrom ? moment(preFrom) : moment().startOf('month');
                    let initialEnd = preTo ? moment(preTo) : moment().endOf('month');
                    let startDate = initialStart.format('YYYY-MM-DD');
                    let endDate = initialEnd.format('YYYY-MM-DD');

                    function cb(start, end) {
                        var label = start.format('DD MMM YY') + ' – ' + end.format('DD MMM YY');
                        $('#shipment-range span').text(label);
                        $('#dateChipLabel').text(label);
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#shipment-range').daterangepicker({
                        startDate: initialStart, endDate: initialEnd,
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);
                    cb(initialStart, initialEnd);

                    /* ── Stat card click → status filter ────────── */
                    $('#statCards .stat-card').on('click', function () {
                        var f = $(this).data('filter');
                        $('#statusFilter').val(f);
                        $('#statCards .stat-card').removeClass('active');
                        $(this).addClass('active');
                        updateFilterDot();
                        if (table) table.ajax.reload();
                    });

                    /* ── DataTable ───────────────────────────────── */
                    table = $('#shipmentTable').DataTable({
                        processing: true,
                        serverSide: true,
                        pageLength: 100,
                        scrollX: true,
                        language: {
                            processing: '<div class="d-flex align-items-center gap-2 text-muted" style="font-size:12px;padding:6px 0;">'
                                + '<div class="spinner-border spinner-border-sm" style="color:#da7d41;width:14px;height:14px;"></div>'
                                + ' Loading…</div>'
                        },
                        ajax: {
                            url: 'api/shipment/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.branch_id = $('#branchFilter').val();
                                d.client_id = $('#clientFilter').val();
                                d.courier_id = $('#courierFilter').val();
                                d.status = $('#statusFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        drawCallback: function (settings) {
                            /* ── Update stat cards from server response ── */
                            var json = settings.json;
                            if (json) {
                                /* Option A: if API returns stats object */
                                if (json.stats) {
                                    $('#sc-total').text(json.stats.total ?? json.recordsFiltered ?? '—');
                                                    $('#sc-created').text(json.stats.created ?? '—');
                                                    $('#sc-transit').text(json.stats.transit ?? '—');
                                                    $('#sc-delivered').text(json.stats.delivered ?? '—');
                                                    $('#sc-rto').text(json.stats.rto ?? '—');
                                                    $('#sc-ofd').text(json.stats.ofd ?? '—');
                                } else {
                                    /* Option B: tally the current page data by status */
                                                    var totals = { created: 0, transit: 0, delivered: 0, rto: 0, ofd: 0 };
                                                    (json.data || []).forEach(function (r) {
                                                        var s = (r.last_status || '').toLowerCase();
                                                        if (s.includes('out for delivery') || s === 'out for delivery') totals.ofd++;
                                                        else if (s.includes('created')) totals.created++;
                                                        else if (s.includes('deliver')) totals.delivered++;
                                                        else if (s.includes('rto') || s.includes('return')) totals.rto++;
                                                        else if (s.includes('transit') || s.includes('pickup') ||
                                                            s.includes('dispatch') || s.includes('out for') ||
                                                            s.includes('manifest') || s.includes('booked')) totals.transit++;
                                                    });
                                                    $('#sc-total').text(json.recordsFiltered ?? (json.data || []).length);
                                                    $('#sc-created').text(totals.created);
                                                    $('#sc-transit').text(totals.transit);
                                                    $('#sc-delivered').text(totals.delivered);
                                                    $('#sc-rto').text(totals.rto);
                                                    $('#sc-ofd').text(totals.ofd);
                                }
                            }
                        },
                        columns: [
                            <?php if ($isSuperAdmin) : ?>
                                /* checkbox column (superadmin only) */
                                {
                                    data: 'id', orderable: false, searchable: false,
                                    className: 'cb-col',
                                    render: function (id) {
                                        return '<input type="checkbox" class="row-cb" value="' + id + '">';
                                    }
                                },
                            <?php endif; ?>
                            /* 0 — Waybill (compact) */
                            {
                                data: null,
                                render: function (data, type, row) {
                                    if (type !== 'display') return row.waybill_no || '';
                                    var safe = function (s) { return (s || '').toString().replace(/'/g, "\\'"); };

                                    var h = '<div class="wbl-cell">';

                                    /* Waybill number */
                                    h += '<div class="wbl-top">';
                                    h += '<span class="wbl-no">' + (row.waybill_no || 'N/A') + '</span>';
                                    h += '</div>';

                                    /* Pickup + POD thumbnails (support multiple images each); tooltip: AWB + child AWBs + uploaded date */
                                    var toPodItems = function (arr) {
                                        if (!Array.isArray(arr)) return arr ? [{ url: arr, date: '' }] : [];
                                        return arr.map(function (x) {
                                            return typeof x === 'object' && x && x.url
                                                ? { url: x.url, date: x.date || '' }
                                                : { url: x, date: '' };
                                        });
                                    };
                                    var fmtDate = function (d) {
                                        if (!d) return '';
                                        var t = new Date(d);
                                        return isNaN(t.getTime()) ? d : t.toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                                    };
                                    var awbLine = (row.waybill_no || '') + (row.child_awbs ? ' (Child: ' + row.child_awbs + ')' : '');
                                    var pickupPods = toPodItems(row.pickup_pod);
                                    var deliveryPods = toPodItems(row.delivery_pod);

                                    h += '<div class="pod-thumbs">';

                                    /* Pickup — label above row of thumbnails */
                                    h += '<div class="pod-thumb-item">';
                                    h += '<span class="pod-thumb-label">Pickup</span>';
                                    h += '<div class="pod-thumb-imgs">';
                                    if (pickupPods.length) {
                                        pickupPods.forEach(function (item, i) {
                                            var url = item.url;
                                            var s = safe(url);
                                            var tip = 'AWB: ' + awbLine + (item.date ? ' | Uploaded: ' + fmtDate(item.date) : '');
                                            if (pickupPods.length > 1) tip = 'Pickup ' + (i + 1) + '/' + pickupPods.length + ' — ' + tip;
                                            h += '<img src="' + s + '" class="pod-thumb-img" onclick="openPodOverlay(\'' + s + '\')" title="' + tip.replace(/"/g, '&quot;') + '">';
                                        });
                                    } else {
                                        h += '<span class="pod-thumb-empty"><i class="ti ti-camera-off"></i></span>';
                                    }
                                    h += '</div></div>';

                                    h += '<div class="pod-thumb-divider"></div>';

                                    /* Delivery POD — label above row of thumbnails */
                                    h += '<div class="pod-thumb-item">';
                                    h += '<span class="pod-thumb-label">POD</span>';
                                    h += '<div class="pod-thumb-imgs">';
                                    if (deliveryPods.length) {
                                        deliveryPods.forEach(function (item, i) {
                                            var url = item.url;
                                            var s = safe(url);
                                            var tip = 'AWB: ' + awbLine + (item.date ? ' | Uploaded: ' + fmtDate(item.date) : '');
                                            if (deliveryPods.length > 1) tip = 'POD ' + (i + 1) + '/' + deliveryPods.length + ' — ' + tip;
                                            h += '<img src="' + s + '" class="pod-thumb-img" onclick="openPodOverlay(\'' + s + '\')" title="' + tip.replace(/"/g, '&quot;') + '">';
                                        });
                                    } else {
                                        h += '<span class="pod-thumb-empty"><i class="ti ti-file-x"></i></span>';
                                    }
                                    h += '</div></div>';

                                    h += '</div>'; /* end pod-thumbs */

                                    /* Child AWBs row */
                                    if (row.courier_id == 2 && row.child_awbs) {
                                        var awbs = row.child_awbs.split(',');
                                        var uid = 'awb_' + row.id;
                                        var visible = awbs.slice(0, 3);
                                        var hidden = awbs.slice(3);

                                        h += '<div class="wbl-awbs">';
                                        h += visible.map(function (a) {
                                            return '<a href="shipment-label-print.php?waybill=' + encodeURIComponent(row.waybill_no) + '" target="_blank" class="awb-badge" title="' + a + '">' + a + '</a>';
                                        }).join('');
                                        if (hidden.length) {
                                            h += '<span class="awb-more-link" onclick="document.getElementById(\'' + uid + '\').style.display=\'contents\';this.style.display=\'none\'">+' + hidden.length + '</span>';
                                            h += '<span id="' + uid + '" style="display:none;contents:normal;">'
                                                + hidden.map(function (a) {
                                                    return '<a href="shipment-label-print.php?waybill=' + encodeURIComponent(row.waybill_no) + '" target="_blank" class="awb-badge">' + a + '</a>';
                                                }).join('') + '</span>';
                                        }
                                        h += '</div>';
                                    }

                                    h += '</div>';
                                    return h;
                                }
                            },
                            /* 1 — Ref No */
                            { data: 'booking_ref_id', defaultContent: '<span class="text-muted">—</span>' },
                            /* 2 — Courier */
                            {
                                data: 'courier_name', defaultContent: '',
                                render: function (v) {
                                    return v ? '<span class="chip chip-blue">' + v + '</span>' : '<span class="text-muted">—</span>';
                                }
                            },
                            /* 3 — Branch */
                            {
                                data: 'branch_name', defaultContent: '',
                                render: function (v) {
                                    return v ? '<span class="chip"><i class="ti ti-building"></i>' + v + '</span>' : '<span class="text-muted">—</span>';
                                }
                            },
                            /* 4 — Client */
                            { data: 'company_name', defaultContent: '<span class="text-muted">—</span>' },
                            /* 5 — Boxes */
                            {
                                data: 'quantity', defaultContent: '0',
                                render: function (v) {
                                    return '<span class="chip"><i class="ti ti-box"></i>' + (v || 0) + '</span>';
                                }
                            },
                            /* 6 — Created By */
                            { data: 'created_by_name', defaultContent: '<span class="text-muted">—</span>' },
                            /* 7 — Created At */
                            {
                                data: 'created_at',
                                render: function (v) {
                                    if (!v) return '<span class="text-muted">—</span>';
                                    var d = new Date(v);
                                    return '<span style="font-size:11px;color:#344054;font-weight:500;">'
                                        + d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
                                        + '</span><br><span style="font-size:10px;color:#667085;">'
                                        + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) + '</span>';
                                }
                            },
                            /* 8 — Status */
                            {
                                data: 'last_status',
                                render: function (data, type, row) {
                                    var label = row.last_status || 'Created';
                                    var st = statusStyle(label);
                                    return '<span class="status-pill" style="background:' + st.bg + ';color:' + st.color + ';">'
                                        + '<span class="status-dot" style="background:' + st.dot + ';"></span>'
                                        + label + '</span>';
                                }
                            },
                            /* 9 — Shipping Mode */
                            {
                                data: 'shipping_mode', defaultContent: '',
                                render: function (v) {
                                    return v ? '<span class="chip">' + v + '</span>' : '<span class="text-muted">—</span>';
                                }
                            },
                            /* 10 — Payment */
                            {
                                data: 'payment_mode', defaultContent: '',
                                render: function (v) {
                                    if (!v) return '<span class="text-muted">—</span>';
                                    return v === 'COD'
                                        ? '<span class="chip chip-amber">' + v + '</span>'
                                        : '<span class="chip">' + v + '</span>';
                                }
                            },
                            /* 11 — Amount */
                            {
                                data: null,
                                render: function (data, type, row) {
                                    var amt = parseFloat(row.payment_mode === 'COD' ? (row.cod_amount || 0) : (row.invoice_value || 0));
                                    var cod = row.payment_mode === 'COD' ? '<br><span class="chip chip-amber" style="font-size:9px;padding:1px 4px;">COD</span>' : '';
                                    return '<span class="amount-val">₹' + amt.toFixed(2) + '</span>' + cod;
                                }
                            },
                            /* 12 — Sender */
                            {
                                data: null,
                                render: function (data, type, row) {
                                    var h = '<div class="contact-name">' + (row.shipper_name || '—') + '</div>';
                                    var meta = [row.shipper_city, row.shipper_pin].filter(Boolean).join(', ');
                                    if (meta) h += '<div class="contact-meta">' + meta + '</div>';
                                    if (row.shipper_phone)
                                        h += '<div class="contact-phone"><a href="tel:' + row.shipper_phone + '">' + row.shipper_phone + '</a></div>';
                                    return h;
                                }
                            },
                            /* 13 — Sender Address */
                            {
                                data: 'shipper_address',
                                render: function (v) {
                                    return v ? '<div class="address-wrap">' + v + '</div>' : '<span class="text-muted">—</span>';
                                }
                            },
                            /* 14 — Receiver */
                            {
                                data: null,
                                render: function (data, type, row) {
                                    var h = '<div class="contact-name">' + (row.consignee_name || '—') + '</div>';
                                    var meta = [row.consignee_city, row.consignee_pin].filter(Boolean).join(', ');
                                    if (meta) h += '<div class="contact-meta">' + meta + '</div>';
                                    if (row.consignee_phone)
                                        h += '<div class="contact-phone"><a href="tel:' + row.consignee_phone + '">' + row.consignee_phone + '</a></div>';
                                    return h;
                                }
                            },
                            /* 15 — Receiver Address */
                            {
                                data: 'consignee_address',
                                render: function (v) {
                                    return v ? '<div class="address-wrap">' + v + '</div>' : '<span class="text-muted">—</span>';
                                }
                            },
                            /* 16 — Action */
                            {
                                data: null, orderable: false,
                                render: function (data, type, row) {
                                    var h = '<div class="action-wrap">';
                                    h += '<a href="order-details.php?id=' + row.id + '" class="btn-act btn-act-view" title="View Details"><i class="ti ti-eye"></i></a>';
                                    <?php if (can_edit ( 'whms_shipment' )) : ?>
                                        h += '<a href="whms-ownbooking-create.php?id=' + row.id + '" class="btn-act btn-act-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                                    <?php endif; ?>
                                    h += '<button class="btn-act btn-act-print btn-label-print" data-id="' + row.id + '" data-waybill="' + row.waybill_no + '" data-size="A4" title="Print Label"><i class="ti ti-printer"></i></button>';
                                    <?php if ($isSuperAdmin) : ?>
                                        h += '<button class="btn-act btn-act-del btn-single-del" data-id="' + row.id + '" data-waybill="' + (row.waybill_no || '') + '" title="Delete Shipment"><i class="ti ti-trash"></i></button>';
                                        if (row.shiprocket_order_id) {
                                            h += '<button class="btn-act btn-act-cancel-shiprocket" data-id="' + row.id + '" data-orderid="' + row.shiprocket_order_id + '" title="Cancel Shiprocket Order"><i class="ti ti-circle-x"></i></button>';
                                        }
                                    <?php endif; ?>
                                    h += '</div>';
                                    return h;
                                }
                            }
                        ]
                    });

                    /* ── Filter change → reload ──────────────────── */
                    $('#branchFilter, #clientFilter, #courierFilter, #statusFilter').change(function () {
                        table.ajax.reload();
                        updateFilterDot();
                        /* sync active stat card */
                        var sv = $('#statusFilter').val();
                        $('#statCards .stat-card').each(function () {
                            $(this).toggleClass('active', $(this).data('filter') === sv);
                        });
                    });

                    /* ── Reset button ───────────────────────────── */
                    $('#clearFiltersBtn').on('click', function () {
                        $('#branchFilter').val('');
                        $('#clientFilter').val('');
                        $('#courierFilter').val('');
                        $('#statusFilter').val('');
                        $('#statCards .stat-card').removeClass('active');
                        updateFilterDot();
                        table.ajax.reload();
                    });

                    /* ── Print label ────────────────────────────── */
                    $('#shipmentTable').on('click', '.btn-label-print', function () {
                        var id = $(this).data('id');
                        var waybill = $(this).data('waybill');
                        var size = $(this).data('size') || 'A4';
                        if (!waybill) { alert('No Waybill generated yet.'); return; }
                        window.open('shipment-label-print.php?id=' + encodeURIComponent(id)
                            + '&waybill=' + encodeURIComponent(waybill)
                            + '&pdf_size=' + encodeURIComponent(size), '_blank');
                    });

                    <?php if ($isSuperAdmin) : ?>
                        /* ── Checkbox column — add dynamically to first column ── */
                        /* DataTables re-renders on draw; inject cb cells each time */
                        $('#shipmentTable').on('draw.dt', function () {
                            updateDelToolbar();
                        });

                        /* Select All */
                        $('#shipmentTable').on('change', '#selectAllCb', function () {
                            var checked = $(this).prop('checked');
                            $('#shipmentTable tbody .row-cb').prop('checked', checked);
                            updateDelToolbar();
                        });

                        /* Individual checkbox */
                        $('#shipmentTable').on('change', '.row-cb', function () {
                            var total = $('#shipmentTable tbody .row-cb').length;
                            var checked = $('#shipmentTable tbody .row-cb:checked').length;
                            $('#selectAllCb').prop('indeterminate', checked > 0 && checked < total);
                            $('#selectAllCb').prop('checked', checked === total && total > 0);
                            updateDelToolbar();
                        });

                        function updateDelToolbar() {
                            var n = $('#shipmentTable tbody .row-cb:checked').length;
                            if (n > 0) {
                                $('#selectedCount').text(n);
                                $('#shipDeleteBar').addClass('show');
                            } else {
                                $('#shipDeleteBar').removeClass('show');
                            }
                        }

                        /* Cancel selection */
                        $('#btnCancelSelect').on('click', function () {
                            $('#shipmentTable tbody .row-cb').prop('checked', false);
                            $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
                            updateDelToolbar();
                        });

                        /* Single delete */
                        $('#shipmentTable').on('click', '.btn-single-del', function () {
                            var id = $(this).data('id');
                            var waybill = $(this).data('waybill');
                            var msg = 'Delete shipment ' + (waybill ? '"' + waybill + '"' : '#' + id) + '?\n\nThis permanently removes the booking, packages, tracking records, and restores the serial allocation.';
                            if (!confirm(msg)) return;
                            doDeleteShipments([id]);
                        });

                        /* Multi delete */
                        $('#btnMultiDelete').on('click', function () {
                            var ids = [];
                            $('#shipmentTable tbody .row-cb:checked').each(function () { ids.push($(this).val()); });
                            if (!ids.length) return;
                            var msg = 'Delete ' + ids.length + ' selected shipment(s)?\n\nThis permanently removes the bookings, packages, tracking records, and restores serial allocations.';
                            if (!confirm(msg)) return;
                            doDeleteShipments(ids);
                        });

                        /* Shiprocket cancel order */
                        $('#shipmentTable').on('click', '.btn-act-cancel-shiprocket', function () {
                            var bookingId = $(this).data('id');
                            var orderId = $(this).data('orderid');
                            if (!orderId) { alert('Missing Shiprocket order id.'); return; }
                            var msg = 'Cancel Shiprocket order #' + orderId + '?';
                            if (!confirm(msg)) return;

                            $(this).prop('disabled', true).attr('title', 'Cancelling...');

                            $.ajax({
                                url: 'api/shipment/shiprocket_cancel_order.php',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ booking_id: bookingId, ids: [orderId] }),
                                success: function (res) {
                                    if (res.status === 'success') {
                                        shipCancelToast(res.message || 'Cancelled successfully.');
                                    } else {
                                        alert('Error: ' + (res.message || 'Cancel failed.'));
                                    }
                                    table.ajax.reload(null, false);
                                },
                                error: function (xhr) {
                                    var r = {};
                                    try { r = JSON.parse(xhr.responseText); } catch (e) { }
                                    alert('Server error: ' + (r.message || xhr.statusText));
                                },
                                complete: function () {
                                    $('.btn-act-cancel-shiprocket').prop('disabled', false).attr('title', 'Cancel Shiprocket Order');
                                }
                            });
                        });

                        function doDeleteShipments(ids) {
                            $.ajax({
                                url: 'api/shipment/shipment_delete.php',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ ids: ids }),
                                success: function (res) {
                                    if (res.status === 'success') {
                                        shipDelToast(res.message || 'Deleted successfully.');
                                        $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
                                        updateDelToolbar();
                                        table.ajax.reload(null, false);
                                    } else {
                                        alert('Error: ' + (res.message || 'Delete failed.'));
                                    }
                                },
                                error: function (xhr) {
                                    var r = {};
                                    try { r = JSON.parse(xhr.responseText); } catch (e) { }
                                    alert('Server error: ' + (r.message || xhr.statusText));
                                }
                            });
                        }

                        function shipDelToast(msg) {
                            var $t = $('<div style="position:fixed;bottom:24px;right:24px;z-index:9999;background:#198754;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="ti ti-circle-check me-1"></i>' + msg + '</div>');
                            $('body').append($t);
                            setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 3500);
                        }

                        function shipCancelToast(msg) {
                            var $t = $('<div style="position:fixed;bottom:24px;right:24px;z-index:9999;background:#da7d41;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="ti ti-circle-x me-1"></i>' + msg + '</div>');
                            $('body').append($t);
                            setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 3500);
                        }
                    <?php endif; ?>

                });
            </script>