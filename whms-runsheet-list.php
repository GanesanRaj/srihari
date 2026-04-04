<?php include 'header.php'; ?>
<?php if ( ! defined ( 'MIDDLEWARE_INCLUDED' )) {
    require_once __DIR__ . '/config/middleware.php';
    }
require_permission ( 'whms_runsheet', 'is_view' ); ?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<style>
    .date-range-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        border: 1.5px solid #e0e0e0;
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 12px;
        font-weight: 600;
        color: #344054;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        white-space: nowrap;
    }

    .date-range-chip:hover {
        border-color: #da7d41;
        box-shadow: 0 0 0 3px rgba(218, 125, 65, .12);
    }

    .date-range-chip i {
        color: #da7d41;
        font-size: 14px;
    }

    /* Breakdown cell clickable */
    .breakdown-cell {
        cursor: pointer;
        border-radius: 4px;
        padding: 2px 6px;
        transition: background .15s;
    }

    .breakdown-cell:hover {
        background: #f0f4ff;
    }

    /* Breakdown Modal styles */
    #breakdownModal .modal-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5f8a 100%);
        color: #fff;
        padding: 12px 20px;
    }

    #breakdownModal .modal-title {
        color: #fff;
        font-size: 15px;
    }

    #breakdownModal .btn-close {
        filter: invert(1);
    }

    #breakdownShipmentsTable {
        font-size: 12px;
    }

    #breakdownShipmentsTable th {
        background: #f8f9fa;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 8px 10px;
        white-space: nowrap;
    }

    #breakdownShipmentsTable td {
        padding: 6px 10px;
        vertical-align: middle;
    }

    #breakdownShipmentsTable .form-select {
        font-size: 11px;
        padding: 3px 8px;
        min-width: 140px;
    }

    .bd-status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .bd-status-badge.delivered {
        background: #d4edda;
        color: #155724;
    }

    .bd-status-badge.attempted {
        background: #fff3cd;
        color: #856404;
    }

    .bd-status-badge.pending {
        background: #e2e3e5;
        color: #383d41;
    }

    .bd-status-badge.ofd {
        background: #cce5ff;
        color: #004085;
    }

    .bd-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
    }

    .bd-summary-chip.del {
        background: #d4edda;
        color: #155724;
    }

    .bd-summary-chip.att {
        background: #fff3cd;
        color: #856404;
    }

    .bd-summary-chip.pen {
        background: #e2e3e5;
        color: #383d41;
    }

    .bd-save-btn {
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 4px;
    }

    .bd-row-saving {
        opacity: 0.5;
        pointer-events: none;
    }

    @keyframes bdFlashGreen {
        0% {
            background-color: #c8e6c9;
        }

        100% {
            background-color: transparent;
        }
    }

    .bd-row-saved {
        animation: bdFlashGreen 1s ease;
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
                            <div class="card">
                                <div class="card-body">

                                    <!-- Title + New button -->
                                    <div class="py-1 d-flex align-items-center flex-row mb-2 gap-2 flex-wrap">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0">
                                                <i data-lucide="clipboard-list"></i> Run Sheet Management
                                            </h6>
                                        </div>

                                        <!-- Date Range Chip -->
                                        <span class="date-range-chip" id="dateRangeChip">
                                            <i class="ti ti-calendar"></i>
                                            <span id="dateChipLabel"></span>
                                        </span>

                                        <div class="text-end">
                                            <a href="runsheet-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Run Sheet
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Status Filter Pills -->
                                    <div class="mb-3 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-outline-secondary status-filter active"
                                            data-status="">All</button>
                                        <button class="btn btn-sm btn-outline-warning status-filter"
                                            data-status="draft">Draft</button>
                                        <button class="btn btn-sm btn-outline-primary status-filter"
                                            data-status="dispatched">Dispatched</button>
                                        <button class="btn btn-sm btn-outline-success status-filter"
                                            data-status="completed">Completed</button>
                                    </div>

                                    <table id="runsheetTable" class="table table-hover dt-responsive nowrap w-100"
                                        style="font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Runsheet No</th>
                                                <th>Driver</th>
                                                <th>Mobile</th>
                                                <th>Shipments</th>
                                                <th>Breakdown</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created At</th>
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

            <!-- ═══════════════ Breakdown Modal ═══════════════ -->
            <div class="modal fade" id="breakdownModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header align-items-center">
                            <h5 class="modal-title mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span><i class="ti ti-list-details me-1"></i> Breakdown — <span
                                        id="bdRunsheetNo"></span></span>
                                <span id="bdRunsheetAttachments" class="fs-6 fw-normal"></span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <!-- Summary chips -->
                            <div class="px-3 py-2 border-bottom bg-light d-flex gap-2 flex-wrap align-items-center">
                                <span class="bd-summary-chip del">
                                    <i class="ti ti-circle-check"></i> Delivered: <strong id="bdCntDel">0</strong>
                                </span>
                                <span class="bd-summary-chip att">
                                    <i class="ti ti-alert-triangle"></i> Attempted: <strong id="bdCntAtt">0</strong>
                                </span>
                                <span class="bd-summary-chip pen">
                                    <i class="ti ti-clock"></i> Pending: <strong id="bdCntPen">0</strong>
                                </span>
                                <span class="ms-auto text-muted" style="font-size:11px;" id="bdTotalLabel">Total:
                                    0</span>
                            </div>

                            <!-- Bulk Action Bar -->
                            <div class="px-3 py-2 border-bottom d-flex gap-2 align-items-center flex-wrap"
                                id="bdBulkBar">
                                <span class="fw-semibold" style="font-size:12px;">
                                    <i class="ti ti-checks me-1"></i> Selected: <strong id="bdSelectedCount">0</strong>
                                </span>
                                <select class="form-select form-select-sm" id="bdBulkStatus"
                                    style="width:160px; font-size:12px;">
                                    <option value="">-- Select Status --</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" id="bdBulkRemarks"
                                    placeholder="Remarks (optional)" style="width:180px; font-size:12px;">
                                <button class="btn btn-sm btn-primary" id="bdBulkUpdateBtn" disabled>
                                    <i class="ti ti-check me-1"></i> Update Selected
                                </button>
                            </div>

                            <!-- Shipments table -->
                            <div class="table-responsive" style="max-height:50vh; overflow-y:auto;">
                                <table class="table table-sm table-hover mb-0" id="breakdownShipmentsTable">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th style="width:36px;"><input type="checkbox" id="bdSelectAll"
                                                    title="Select All"></th>
                                            <th>#</th>
                                            <th>AWB No</th>
                                            <th>Consignee</th>
                                            <th>City</th>
                                            <th>Phone</th>
                                            <th>Current Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bdShipmentsTbody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="ti ti-loader ti-spin me-1"></i> Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer py-2 border-top-0 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center gap-1">
                                    <label class="btn btn-sm btn-outline-secondary mb-0" for="bdRsFiles"
                                        title="Attach files (optional)">
                                        <i class="ti ti-paperclip me-1"></i>Attach
                                        <span class="badge bg-primary rounded-pill ms-1" id="bdRsFileCount"
                                            style="display:none;">0</span>
                                    </label>
                                    <input type="file" id="bdRsFiles" multiple
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.xls,.xlsx,.csv,.doc,.docx"
                                        style="display:none;">
                                    <button class="btn btn-sm btn-outline-danger" id="bdRsClearFiles"
                                        title="Clear files" style="display:none; padding: 2px 6px;">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-sm btn-success" id="bdMarkCompleted">
                                    <i class="ti ti-circle-check me-1"></i> Mark as Completed
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                $(function () {
                    const statusColors = { draft: 'warning', dispatched: 'primary', completed: 'success' };
                    const statusLabels = { draft: 'Draft', dispatched: 'Dispatched', completed: 'Completed' };

                    function getFileBadgeStyle(ext) {
                        if (!ext) return 'background:#e8f4fd;color:#0c7cd5;';
                        ext = ext.toLowerCase();
                        if (['pdf'].includes(ext)) return 'background:#fde8e8;color:#d32f2f;';
                        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'background:#e6f4ea;color:#1b7a2e;';
                        if (['doc', 'docx'].includes(ext)) return 'background:#e3eefa;color:#1565c0;';
                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'background:#fff8e1;color:#e67c00;';
                        return 'background:#e8f4fd;color:#0c7cd5;';
                    }

                    let currentStatus = '';
                    let fromDate = '';
                    let toDate = '';

                    // ── Master statuses cache ────────────────────────────────────────
                    let masterStatuses = [];
                    $.get('api/master_status/read.php?length=-1&status=active', function (res) {
                        if (res.data) masterStatuses = res.data;
                    });

                    // ── Date Range Picker (default = current week Mon–Sun) ────────
                    const today = moment();
                    const weekStart = moment().startOf('isoWeek');   // Monday
                    const weekEnd = moment().endOf('isoWeek');     // Sunday

                    function setDateRange(start, end) {
                        fromDate = start.format('YYYY-MM-DD');
                        toDate = end.format('YYYY-MM-DD');
                        $('#dateChipLabel').text(start.format('DD MMM') + ' – ' + end.format('DD MMM YYYY'));
                    }

                    $('#dateRangeChip').daterangepicker({
                        startDate: weekStart,
                        endDate: weekEnd,
                        showDropdowns: true,
                        linkedCalendars: false,
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'This Week': [moment().startOf('isoWeek'), moment().endOf('isoWeek')],
                            'Last Week': [moment().subtract(1, 'week').startOf('isoWeek'), moment().subtract(1, 'week').endOf('isoWeek')],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                            'All Time': [moment('2020-01-01'), moment()]
                        },
                        locale: { format: 'DD MMM YYYY', cancelLabel: 'Clear' }
                    }, function (start, end) {
                        setDateRange(start, end);
                        if (table) table.ajax.reload();
                    });

                    // Init label with current week
                    setDateRange(weekStart, weekEnd);

                    const table = $('#runsheetTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/runsheet/read.php',
                            data: function (d) {
                                d.status = currentStatus;
                                d.from_date = fromDate;
                                d.to_date = toDate;
                            }
                        },
                        columns: [
                            {
                                data: 'runsheet_no',
                                render: d => `<strong class="text-primary">${d}</strong>`
                            },
                            { data: 'driver_name', defaultContent: '—' },
                            { data: 'mobile_number', defaultContent: '—' },
                            {
                                data: 'shipment_count',
                                className: 'text-center',
                                render: d => `<span class="badge bg-secondary">${d ?? 0}</span>`
                            },
                            {
                                data: null,
                                orderable: false,
                                render: function (d, t, row) {
                                    let html = `
                                    <div class="breakdown-cell" data-runsheet-id="${row.id}" data-runsheet-no="${row.runsheet_no}" title="Click to view breakdown details">
                                        <span class="text-success fw-bold">Del:</span> ${row.cnt_delivered ?? 0} | 
                                        <span class="text-warning text-dark fw-bold">Att:</span> ${row.cnt_attempted ?? 0} | 
                                        <span class="text-secondary fw-bold">Pend:</span> ${row.cnt_pending ?? 0}`;

                                    if (row.attachments) {
                                        try {
                                            const atts = JSON.parse(row.attachments);
                                            if (Array.isArray(atts) && atts.length > 0) {
                                                atts.forEach(a => {
                                                    const bStyle = getFileBadgeStyle(a.ext);
                                                    html += ` | <a href="${a.path}" download="${a.name}" class="badge text-decoration-none" style="${bStyle}" title="Download ${a.name}" onclick="event.stopPropagation();">
                                                        <i class="ti ti-download"></i> ${a.ext.toUpperCase()}
                                                    </a>`;
                                                });
                                            }
                                        } catch (e) { }
                                    }
                                    html += `</div>`;
                                    return html;
                                }
                            },
                            {
                                data: 'runsheet_date',
                                render: d => d ? d : '—'
                            },
                            {
                                data: 'status',
                                render: d => {
                                    const c = statusColors[d] || 'secondary';
                                    const l = statusLabels[d] || d;
                                    return `<span class="badge bg-${c} ${c === 'warning' ? 'text-dark' : ''}">${l}</span>`;
                                }
                            },
                            { data: 'created_by_name', defaultContent: '—' },
                            {
                                data: 'created_at',
                                render: d => d ? new Date(d).toLocaleString('en-IN') : '—'
                            },
                            {
                                data: null,
                                orderable: false,
                                render: (d, t, row) => `
                                    <div class="d-flex gap-1">
                     <a href="runsheet-create.php?id=${row.id}"
                                            class="btn btn-sm btn-soft-primary" title="Open / Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-dark btn-print"
                                            data-id="${row.id}" title="Print">
                                            <i class="ti ti-printer"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete"
                                            data-id="${row.id}" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>`
                            }
                        ]
                    });

                    // Status filter pills
                    $('.status-filter').on('click', function () {
                        $('.status-filter').removeClass('active');
                        $(this).addClass('active');
                        currentStatus = $(this).data('status');
                        table.ajax.reload();
                    });

                    // Print
                    $('#runsheetTable').on('click', '.btn-print', function () {
                        const id = $(this).data('id');
                        window.open('runsheet-print.php?id=' + id, '_blank');
                    });

                    // Delete
                    $('#runsheetTable').on('click', '.btn-delete', function () {
                        const id = $(this).data('id');
                        if (!confirm('Permanently delete this run sheet?')) return;
                        $.post('api/runsheet/delete.php', { runsheet_id: id }, function (res) {
                            if (res.status === 'success') {
                                table.ajax.reload();
                            } else {
                                alert('Error: ' + res.message);
                            }
                        }, 'json');
                    });

                    // ═══════════════════════════════════════════════════════════════
                    //  BREAKDOWN POPUP
                    // ═══════════════════════════════════════════════════════════════

                    // Click breakdown cell → open modal
                    $('#runsheetTable').on('click', '.breakdown-cell', function (e) {
                        e.stopPropagation();
                        const rsId = $(this).data('runsheet-id');
                        const rsNo = $(this).data('runsheet-no');
                        openBreakdownModal(rsId, rsNo);
                    });

                    function classifyStatus(st) {
                        if (!st) return 'pending';
                        const s = st.trim().toLowerCase();
                        if (s === 'delivered') return 'delivered';
                        if (['attempted', 'undelivered', 'returned', 'rto', 'return'].includes(s)) return 'attempted';
                        return 'pending';
                    }

                    function statusBadgeClass(cat) {
                        if (cat === 'delivered') return 'delivered';
                        if (cat === 'attempted') return 'attempted';
                        if (cat === 'ofd') return 'ofd';
                        return 'pending';
                    }

                    let currentBreakdownRunsheetId = null;

                    // Populate bulk status dropdown from master statuses
                    function populateBulkStatusDropdown() {
                        let opts = '<option value="">-- Select Status --</option>';
                        masterStatuses.forEach(ms => {
                            opts += `<option value="${ms.name}">${ms.name}</option>`;
                        });
                        $('#bdBulkStatus').html(opts);
                    }

                    function openBreakdownModal(runsheetId, runsheetNo) {
                        currentBreakdownRunsheetId = runsheetId;
                        $('#bdRunsheetNo').text(runsheetNo);
                        $('#bdRunsheetAttachments').html('');
                        $('#bdShipmentsTbody').html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="ti ti-loader ti-spin me-1"></i> Loading shipments...</td></tr>');
                        $('#bdCntDel').text('0');
                        $('#bdCntAtt').text('0');
                        $('#bdCntPen').text('0');
                        $('#bdTotalLabel').text('Total: 0');
                        $('#bdSelectedCount').text('0');
                        $('#bdSelectAll').prop('checked', false);
                        $('#bdBulkUpdateBtn').prop('disabled', true);
                        $('#bdBulkStatus').val('');
                        $('#bdBulkRemarks').val('');
                        $('#bdBulkFiles').val('');
                        $('#bdFileCount').hide();
                        $('#bdClearFiles').hide();
                        $('#bdMarkCompleted').prop('disabled', false).html('<i class="ti ti-circle-check me-1"></i> Mark as Completed');
                        populateBulkStatusDropdown();

                        var bdModal = new bootstrap.Modal(document.getElementById('breakdownModal'));
                        bdModal.show();

                        $.get('api/runsheet/get_shipments.php', { id: runsheetId }, function (res) {
                            if (res.status !== 'success') {
                                $('#bdShipmentsTbody').html('<tr><td colspan="7" class="text-center text-danger py-3">Error: ' + (res.message || 'Failed to load') + '</td></tr>');
                                return;
                            }

                            const shipments = res.shipments || [];

                            if (shipments.length === 0) {
                                $('#bdShipmentsTbody').html('<tr><td colspan="7" class="text-center text-muted py-4">No shipments in this runsheet</td></tr>');
                                return;
                            }

                            // Count categories
                            let cntDel = 0, cntAtt = 0, cntPen = 0;
                            shipments.forEach(s => {
                                const cat = classifyStatus(s.booking_status || s.rd_status);
                                if (cat === 'delivered') cntDel++;
                                else if (cat === 'attempted') cntAtt++;
                                else cntPen++;
                            });
                            $('#bdCntDel').text(cntDel);
                            $('#bdCntAtt').text(cntAtt);
                            $('#bdCntPen').text(cntPen);
                            $('#bdTotalLabel').text('Total: ' + shipments.length);

                            // Show Runsheet Attachments if any
                            if (res.runsheet && res.runsheet.attachments) {
                                try {
                                    const att = JSON.parse(res.runsheet.attachments);
                                    if (Array.isArray(att) && att.length > 0) {
                                        let attHtml = '';
                                        att.forEach(a => {
                                            const bStyle = getFileBadgeStyle(a.ext);
                                            attHtml += `<a href="${a.path}" download="${a.name}" class="badge ms-1 text-decoration-none" style="${bStyle}" title="Download ${a.name}">
                                                <i class="ti ti-download me-1"></i> ${a.ext.toUpperCase()} File
                                            </a>`;
                                        });
                                        $('#bdRunsheetAttachments').html(attHtml);
                                    }
                                } catch (e) { }
                            }

                            // Build rows with checkboxes
                            let rows = '';
                            shipments.forEach((s, idx) => {
                                const currentSt = s.booking_status || s.rd_status || 'Pending';
                                const cat = classifyStatus(currentSt);
                                const badgeCls = statusBadgeClass(cat);

                                rows += `
                                <tr data-detail-id="${s.detail_id}" id="bd-row-${s.detail_id}">
                                    <td class="text-center">
                                        <input type="checkbox" class="bd-row-check" data-detail-id="${s.detail_id}">
                                    </td>
                                    <td class="text-center fw-bold">${idx + 1}</td>
                                    <td><strong class="text-primary">${s.awb_no || '—'}</strong></td>
                                    <td>${s.consignee_name || '—'}</td>
                                    <td>${s.consignee_city || '—'}</td>
                                    <td>${s.consignee_phone || '—'}</td>
                                    <td><span class="bd-status-badge ${badgeCls}" id="bd-badge-${s.detail_id}">${currentSt}</span></td>
                                </tr>`;
                            });

                            $('#bdShipmentsTbody').html(rows);
                        }).fail(function () {
                            $('#bdShipmentsTbody').html('<tr><td colspan="7" class="text-center text-danger py-3">Network error loading shipments</td></tr>');
                        });
                    }

                    // Select All checkbox
                    $(document).on('change', '#bdSelectAll', function () {
                        const checked = $(this).is(':checked');
                        $('.bd-row-check').prop('checked', checked);
                        updateSelectedCount();
                    });

                    // Individual checkbox change
                    $(document).on('change', '.bd-row-check', function () {
                        const total = $('.bd-row-check').length;
                        const checked = $('.bd-row-check:checked').length;
                        $('#bdSelectAll').prop('checked', total === checked && total > 0);
                        updateSelectedCount();
                    });

                    function updateSelectedCount() {
                        const cnt = $('.bd-row-check:checked').length;
                        $('#bdSelectedCount').text(cnt);
                        $('#bdBulkUpdateBtn').prop('disabled', cnt === 0);
                    }

                    // File attachment handlers for Mark Completed
                    $('#bdRsFiles').on('change', function () {
                        const cnt = this.files.length;
                        if (cnt > 0) {
                            $('#bdRsFileCount').text(cnt).show();
                            $('#bdRsClearFiles').show();
                        } else {
                            $('#bdRsFileCount').hide();
                            $('#bdRsClearFiles').hide();
                        }
                    });
                    $('#bdRsClearFiles').on('click', function () {
                        $('#bdRsFiles').val('');
                        $('#bdRsFileCount').hide();
                        $(this).hide();
                    });

                    // Bulk Update Selected
                    $('#bdBulkUpdateBtn').on('click', function () {
                        const newStatus = $('#bdBulkStatus').val();
                        const remarks = $('#bdBulkRemarks').val();
                        if (!newStatus) {
                            alert('Please select a status first');
                            return;
                        }

                        const checkedIds = [];
                        $('.bd-row-check:checked').each(function () {
                            checkedIds.push($(this).data('detail-id'));
                        });

                        if (checkedIds.length === 0) {
                            alert('Please select at least one shipment');
                            return;
                        }

                        if (!confirm('Update ' + checkedIds.length + ' shipment(s) to "' + newStatus + '"?')) return;

                        const btn = $('#bdBulkUpdateBtn');
                        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Updating...');

                        // Process each selected shipment sequentially
                        let completed = 0;
                        let errors = [];

                        function processNext(index) {
                            if (index >= checkedIds.length) {
                                // All done
                                btn.html('<i class="ti ti-check me-1"></i> Done!');
                                setTimeout(() => {
                                    btn.html('<i class="ti ti-check me-1"></i> Update Selected');
                                    btn.prop('disabled', true);
                                }, 1500);

                                // Uncheck all
                                $('.bd-row-check').prop('checked', false);
                                $('#bdSelectAll').prop('checked', false);
                                updateSelectedCount();

                                // Recalc counts
                                recalcBreakdownCounts();

                                // Refresh main table
                                table.ajax.reload(null, false);

                                // Auto-complete check: if all shipments are delivered, mark runsheet completed
                                autoCompleteCheck();

                                if (errors.length > 0) {
                                    alert(errors.length + ' error(s):\n' + errors.join('\n'));
                                }
                                return;
                            }

                            const detailId = checkedIds[index];
                            const row = $(`#bd-row-${detailId}`);
                            row.addClass('bd-row-saving');

                            // Build FormData for shipment status update
                            const fd = new FormData();
                            fd.append('detail_id', detailId);
                            fd.append('new_status', newStatus);
                            fd.append('remarks', remarks);

                            $.ajax({
                                url: 'api/runsheet/update_shipment_status.php',
                                type: 'POST',
                                data: fd,
                                processData: false,
                                contentType: false,
                                dataType: 'json',
                                success: function (res) {
                                    row.removeClass('bd-row-saving');
                                    if (res.status === 'success') {
                                        const cat = classifyStatus(newStatus);
                                        const badgeCls = statusBadgeClass(cat);
                                        $(`#bd-badge-${detailId}`)
                                            .attr('class', 'bd-status-badge ' + badgeCls)
                                            .text(newStatus);
                                        row.addClass('bd-row-saved');
                                        setTimeout(() => row.removeClass('bd-row-saved'), 1200);
                                        if (res.runsheet_completed) {
                                            const rsFileInput = document.getElementById('bdRsFiles');
                                            if (rsFileInput && rsFileInput.files.length > 0) {
                                                // Files attached, trigger Mark Completed to upload them
                                                $('#bdMarkCompleted').click();
                                            } else {
                                                $('#bdMarkCompleted').html('<i class="ti ti-check me-1"></i> Completed!').prop('disabled', true);
                                            }
                                        }
                                    } else {
                                        errors.push((res.message || 'Failed') + ' (ID:' + detailId + ')');
                                    }
                                    processNext(index + 1);
                                },
                                error: function () {
                                    row.removeClass('bd-row-saving');
                                    errors.push('Network error (ID:' + detailId + ')');
                                    processNext(index + 1);
                                }
                            });
                        }

                        processNext(0);
                    });

                    // Recalc summary chips in modal after a status change
                    function recalcBreakdownCounts() {
                        let cntDel = 0, cntAtt = 0, cntPen = 0;
                        $('#bdShipmentsTbody tr').each(function () {
                            const badge = $(this).find('.bd-status-badge');
                            if (badge.length) {
                                const cat = classifyStatus(badge.text().trim());
                                if (cat === 'delivered') cntDel++;
                                else if (cat === 'attempted') cntAtt++;
                                else cntPen++;
                            }
                        });
                        $('#bdCntDel').text(cntDel);
                        $('#bdCntAtt').text(cntAtt);
                        $('#bdCntPen').text(cntPen);
                    }

                    // Auto-complete: update UI after backend auto-completes
                    function autoCompleteCheck() {
                        let allDone = true;
                        $('#bdShipmentsTbody tr').each(function () {
                            const badge = $(this).find('.bd-status-badge');
                            if (badge.length) {
                                const cat = classifyStatus(badge.text().trim());
                                if (cat === 'pending') {
                                    allDone = false;
                                    return false;
                                }
                            }
                        });
                        if (allDone) {
                            $('#bdMarkCompleted').html('<i class="ti ti-check me-1"></i> Completed!').prop('disabled', true);
                        }
                    }

                    // Mark Runsheet as Completed (manual button)
                    $('#bdMarkCompleted').on('click', function () {
                        if (!currentBreakdownRunsheetId) return;
                        const btn = $(this);
                        const rsNo = $('#bdRunsheetNo').text();

                        if (!confirm('Mark runsheet ' + rsNo + ' as Completed?')) return;

                        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Updating...');

                        const fd = new FormData();
                        fd.append('runsheet_id', currentBreakdownRunsheetId);
                        fd.append('status', 'completed');

                        const fileInput = document.getElementById('bdRsFiles');
                        if (fileInput && fileInput.files.length > 0) {
                            for (let i = 0; i < fileInput.files.length; i++) {
                                fd.append('attachments[]', fileInput.files[i]);
                            }
                        }

                        $.ajax({
                            url: 'api/runsheet/update_details.php',
                            type: 'POST',
                            data: fd,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success') {
                                    btn.html('<i class="ti ti-check me-1"></i> Completed!');
                                    table.ajax.reload(null, false);
                                    setTimeout(function () {
                                        bootstrap.Modal.getInstance(document.getElementById('breakdownModal')).hide();
                                    }, 800);
                                } else {
                                    alert('Error: ' + (res.message || 'Update failed'));
                                    btn.prop('disabled', false).html('<i class="ti ti-circle-check me-1"></i> Mark as Completed');
                                }
                            },
                            error: function () {
                                alert('Network error. Please try again.');
                                btn.prop('disabled', false).html('<i class="ti ti-circle-check me-1"></i> Mark as Completed');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>