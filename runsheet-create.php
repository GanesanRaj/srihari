<?php include 'header.php'; ?>

<style>
    .scan-input-wrap input {
        font-size: 13px !important;
        font-weight: 700;
        letter-spacing: 1px;
        text-align: center;
    }

    .scan-pulse {
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, .4) }
        50%       { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0) }
    }

    #awbTable td, #awbTable th {
        padding: 4px 6px !important;
        vertical-align: middle;
    }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">

                    <!-- Header Bar -->
                    <div class="row mb-1">
                        <div class="col-12">
                            <div class="card mb-0">
                                <div class="card-body py-1 d-flex align-items-center gap-3 flex-wrap">

                                    <!-- Scan input -->
                                    <div class="scan-input-wrap position-relative">
                                        <input type="text" id="scanInput"
                                            class="form-control form-control-sm text-center scan-pulse text-dark"
                                            style="width:240px; font-weight:700;"
                                            placeholder="Scan AWB No... (Enter)"
                                            autocomplete="off" autofocus>
                                        <div id="lastScanInfo"
                                            class="position-absolute w-100 text-center fw-bold"
                                            style="font-size:10px; top:100%; color:#666;">
                                            Waiting for scan...
                                        </div>
                                    </div>

                                    <!-- RS No (editable) -->
                                    <div class="input-group input-group-sm" style="width:220px;">
                                        <span class="input-group-text fs-11 fw-bold text-muted"
                                            style="letter-spacing:0.5px;">RS NO</span>
                                        <input type="text" id="rsNoDisplay"
                                            class="form-control form-control-sm text-center fw-bold"
                                            style="font-size:13px; letter-spacing:1px;"
                                            placeholder="Auto-generated" title="Click to change RS number">
                                    </div>

                                    <!-- Counters -->
                                    <div class="ms-auto d-flex align-items-center gap-3 fs-15 font-monospace">
                                        <span class="text-primary">
                                            <span class="fs-20 fw-bolder" id="cntTotal">0</span>
                                            <span class="fs-12"> Shipments</span>
                                        </span>
                                        <span class="badge bg-warning text-dark" id="rsStatusBadge">Draft</span>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="ms-2 d-flex gap-1">
                                        <a href="whms-runsheet-list.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-list"></i> All Run Sheets
                                        </a>
                                        <button id="btnDispatch" class="btn btn-sm btn-success" disabled>
                                            <i class="ti ti-send"></i> Dispatch
                                        </button>
                                        <button id="btnPrint" class="btn btn-sm btn-dark" style="display:none;">
                                            <i class="ti ti-printer"></i> Print
                                        </button>
                                        <button id="btnExport" class="btn btn-sm btn-info" style="display:none;">
                                            <i class="ti ti-file-spreadsheet"></i> Export Excel
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="row">

                        <!-- Left: Details Panel -->
                        <div class="col-lg-3">
                            <div class="card mb-0">
                                <div class="card-header py-1 d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fs-13">Run Sheet Details</h6>
                                    <span class="badge bg-primary fs-12" id="rsNoBadge"
                                        style="letter-spacing:1px;">—</span>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Date</label>
                                        <input type="date" id="runsheetDate" class="form-control form-control-sm">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Driver Name <span class="text-danger">*</span></label>
                                        <input type="text" id="driverName"
                                            class="form-control form-control-sm"
                                            placeholder="Full name">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fs-12 mb-0">Mobile Number</label>
                                        <input type="text" id="mobileNumber"
                                            class="form-control form-control-sm"
                                            placeholder="10-digit mobile">
                                    </div>
                                    <button id="btnSaveDetails" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="ti ti-check me-1"></i> Save Details
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Scanned Shipments -->
                        <div class="col-lg-9 ps-0">
                            <div class="card mb-0">
                                <div class="card-header py-1 d-flex align-items-center px-2">
                                    <h6 class="mb-0 fs-13 flex-grow-1">Scanned Shipments</h6>
                                    <span class="badge bg-secondary" id="tableCount">0 items</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive m-0 p-0">
                                        <table class="table table-sm table-hover m-0 p-0" id="awbTable"
                                            style="font-size:12px;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:36px">#</th>
                                                    <th>AWB No</th>
                                                    <th>Consignee</th>
                                                    <th>City</th>
                                                    <th>Address</th>
                                                    <th>Phone</th>
                                                    <th>Scanned At</th>
                                                    <th>Status</th>
                                                    <th style="width:60px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="awbBody">
                                                <tr id="emptyRow">
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="ti ti-scan me-1" style="font-size:1.3rem;"></i><br>
                                                        Scan AWB numbers to add shipments
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

            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(function () {
                    let runsheetId  = null;
                    let runsheetNo  = null;
                    const scanned   = {};

                    // ── Audio ──────────────────────────────────────────────────────
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    let audioCtx = null;
                    function getCtx() {
                        if (!audioCtx) audioCtx = new AudioCtx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        return audioCtx;
                    }
                    function playTone(freq, type, delay, dur, vol) {
                        try {
                            const ctx = getCtx(), t = ctx.currentTime + delay;
                            const osc = ctx.createOscillator(), g = ctx.createGain();
                            osc.connect(g); g.connect(ctx.destination);
                            osc.type = type; osc.frequency.setValueAtTime(freq, t);
                            g.gain.setValueAtTime(vol || 0.4, t);
                            g.gain.linearRampToValueAtTime(0, t + dur);
                            osc.start(t); osc.stop(t + dur + 0.01);
                        } catch (e) { }
                    }
                    function beepSuccess()   { playTone(1400, 'sine',   0,    0.12, 0.5); }
                    function beepError()     { playTone(500,  'square', 0,    0.15, 0.5); playTone(300, 'square', 0.18, 0.20, 0.5); }
                    function beepDuplicate() { playTone(900,  'sine',   0,    0.08, 0.35); playTone(900, 'sine', 0.12, 0.08, 0.35); }
                    $(document).one('keydown', () => getCtx());

                    // ── Init ───────────────────────────────────────────────────────
                    const today = new Date().toISOString().split('T')[0];
                    $('#runsheetDate').val(today);

                    const urlId = new URLSearchParams(location.search).get('id');
                    urlId ? loadExisting(urlId) : createRunsheet();

                    function createRunsheet() {
                        console.log('Gaesan...');
                        $('#rsNoBadge').text('Creating...');
                        $.post('api/runsheet/create.php', {
                            driver_name:   '',
                            mobile_number: '',
                            runsheet_date: today
                        }, function (res) {
                            if (res.status === 'success') {
                                runsheetId = res.runsheet_id;
                                runsheetNo = res.runsheet_no;
                                $('#rsNoBadge').text(runsheetNo);
                                $('#rsNoDisplay').val(runsheetNo);
                                if (res.runsheet_date) $('#runsheetDate').val(res.runsheet_date);
                                history.replaceState(null, '', '?id=' + runsheetId);
                                $('#scanInput').focus();
                            } else {
                                $('#rsNoBadge').text('Error: ' + res.message);
                                showtoastt('Could not create run sheet', 'error');
                            }
                        });
                    }

                    function loadExisting(id) {
                        $.get('api/runsheet/readone.php?id=' + id, function (res) {
                            if (res.status === 'success') {
                                const d = res.data;
                                runsheetId = d.id;
                                runsheetNo = d.runsheet_no;
                                $('#rsNoBadge').text(runsheetNo);
                                $('#rsNoDisplay').val(runsheetNo);
                                $('#btnPrint, #btnExport').show();
                                $('#runsheetDate').val(d.runsheet_date || today);
                                $('#driverName').val(d.driver_name || '');
                                $('#mobileNumber').val(d.mobile_number || '');
                                updateStatusBadge(d.status);
                                if (Array.isArray(d.details) && d.details.length > 0) {
                                    d.details.forEach(e => appendRow(e));
                                    updateCounters();
                                }
                                $('#scanInput').focus();
                            } else {
                                createRunsheet();
                            }
                        });
                    }

                    // ── Scan ───────────────────────────────────────────────────────
                    $('#scanInput').on('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); doScan(); }
                    });

                    function doScan() {
                        if (!runsheetId) { showtoastt('Run sheet not ready', 'warning'); return; }
                        const val = $('#scanInput').val().trim();
                        if (!val) return;

                        if (scanned[val]) {
                            beepDuplicate();
                            flashScan('⚠ Already scanned: ' + val, 'warning');
                            $('#scanInput').val('').focus();
                            return;
                        }

                        $.post('api/runsheet/scan.php', { runsheet_id: runsheetId, scan_value: val }, function (res) {
                            if (res.status === 'success') {
                                appendRow(res.entry);
                                updateCounters();
                                beepSuccess();
                                flashScan('✓ ' + val, 'success');
                            } else {
                                beepError();
                                flashScan('✗ ' + res.message, 'danger');
                            }
                            $('#scanInput').val('').focus();
                        });
                    }

                    // ── Append Row ─────────────────────────────────────────────────
                    function appendRow(entry) {
                        scanned[entry.awb_no] = entry;
                        $('#emptyRow').remove();
                        const idx  = Object.keys(scanned).length;
                        const time = entry.scanned_at
                            ? new Date(entry.scanned_at).toLocaleTimeString('en-IN')
                            : '';
                        const addr = entry.address || entry.consignee_address || '—';

                        const st = entry.status || 'Pending';
                        let bClass = 'bg-secondary';
                        const ls = st.toLowerCase();
                        if (ls === 'delivered') bClass = 'bg-success';
                        else if (ls === 'pending') bClass = 'bg-warning text-dark';
                        else if (ls.includes('attempt')) bClass = 'bg-primary';
                        else if (ls.includes('return') || ls.includes('rto')) bClass = 'bg-danger';
                        else bClass = 'bg-info';

                        const tr = `<tr data-awb="${entry.awb_no}">
                            <td class="text-muted">${idx}</td>
                            <td><strong>${entry.awb_no}</strong></td>
                            <td>${entry.consignee_name || ''}</td>
                            <td>${entry.consignee_city || ''}</td>
                            <td style="max-width:160px;white-space:normal;font-size:11px;">${addr}</td>
                            <td style="font-size:11px;">${entry.consignee_phone || '—'}</td>
                            <td class="text-muted" style="font-size:11px;">${time}</td>
                            <td><span class="badge ${bClass}" style="font-size:10px;">${st}</span></td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger btn-delete-scan"
                                    data-awb="${entry.awb_no}"
                                    style="font-size:11px;padding:2px 8px;">Del</button>
                            </td>
                        </tr>`;
                        $('#awbBody').prepend(tr);
                        $('#tableCount').text(Object.keys(scanned).length + ' items');
                    }

                    // ── Delete Row ─────────────────────────────────────────────────
                    $('#awbBody').on('click', '.btn-delete-scan', function () {
                        const awb = $(this).data('awb');
                        if (!confirm('Remove AWB ' + awb + ' from this run sheet?')) return;
                        $.post('api/runsheet/delete_scan.php', { runsheet_id: runsheetId, awb_no: awb }, function (res) {
                            if (res.status === 'success') {
                                $(`tr[data-awb="${awb}"]`).remove();
                                delete scanned[awb];
                                updateCounters();
                                if (Object.keys(scanned).length === 0) {
                                    $('#awbBody').append(
                                        `<tr id="emptyRow"><td colspan="9" class="text-center text-muted py-4">
                                            <i class="ti ti-scan me-1" style="font-size:1.3rem;"></i><br>
                                            Scan AWB numbers to add shipments
                                        </td></tr>`
                                    );
                                    $('#tableCount').text('0 items');
                                } else {
                                    $('#tableCount').text(Object.keys(scanned).length + ' items');
                                    $('#awbBody tr[data-awb]').each(function (i) {
                                        $(this).find('td:first').text(i + 1);
                                    });
                                }
                            } else {
                                showtoastt('Error: ' + res.message, 'error');
                            }
                        });
                        $('#scanInput').focus();
                    });

                    // ── RS No blur-to-save ─────────────────────────────────────────
                    $('#rsNoDisplay').on('blur', function () {
                        if (!runsheetId) return;
                        const val = $(this).val().trim();
                        if (!val || val === runsheetNo) return;
                        $.post('api/runsheet/update_details.php',
                            { runsheet_id: runsheetId, runsheet_no: val },
                            function (res) {
                                if (res.status === 'success') {
                                    runsheetNo = val;
                                    $('#rsNoBadge').text(val);
                                    showtoastt('RS No updated', 'success');
                                } else {
                                    showtoastt('Error: ' + res.message, 'error');
                                    $('#rsNoDisplay').val(runsheetNo); // revert
                                }
                            }
                        );
                    });

                    // ── Save Details ───────────────────────────────────────────────
                    $('#btnSaveDetails').on('click', function () {
                        if (!runsheetId) return;
                        const $btn = $(this);
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
                        $.post('api/runsheet/update_details.php', buildPayload(), function (res) {
                            showtoastt(
                                res.status === 'success' ? 'Details saved' : 'Error: ' + res.message,
                                res.status === 'success' ? 'success' : 'error'
                            );
                        }).always(() => {
                            $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Save Details');
                            $('#scanInput').focus();
                        });
                    });

                    // ── Dispatch ───────────────────────────────────────────────────
                    $('#btnDispatch').on('click', function () {
                        const total = Object.keys(scanned).length;
                        if (!confirm(`Dispatch Run Sheet: ${runsheetNo}\nTotal Shipments: ${total}\n\nConfirm?`)) return;
                        const payload = Object.assign(buildPayload(), { status: 'dispatched' });
                        $.post('api/runsheet/update_details.php', payload, function (res) {
                            if (res.status === 'success') {
                                updateStatusBadge('dispatched');
                                $('#btnPrint, #btnExport').show();
                                showtoastt('Run Sheet dispatched!', 'success');
                            } else {
                                showtoastt('Error: ' + res.message, 'error');
                            }
                        });
                    });

                    // ── Print ──────────────────────────────────────────────────────
                    $('#btnPrint').on('click', function () {
                        if (runsheetId) window.open('runsheet-print.php?id=' + runsheetId, '_blank');
                    });
                    
                    // ── Export Excel ──────────────────────────────────────────────────
                    $('#btnExport').on('click', function () {
                        if (runsheetId) {
                            window.location.href = 'api/runsheet/export.php?id=' + runsheetId;
                        }
                    });

                    // ── Helpers ────────────────────────────────────────────────────
                    function buildPayload() {
                        return {
                            runsheet_id:   runsheetId,
                            runsheet_date: $('#runsheetDate').val(),
                            driver_name:   $('#driverName').val(),
                            mobile_number: $('#mobileNumber').val()
                        };
                    }

                    function updateCounters() {
                        const total = Object.keys(scanned).length;
                        $('#cntTotal').text(total);
                        $('#btnDispatch').prop('disabled', total === 0);
                    }

                    function updateStatusBadge(status) {
                        const labels = { draft: 'Draft', dispatched: 'Dispatched', completed: 'Completed' };
                        const colors = { draft: 'warning text-dark', dispatched: 'primary', completed: 'success' };
                        $('#rsStatusBadge')
                            .attr('class', 'badge bg-' + (colors[status] || 'secondary'))
                            .text(labels[status] || status);
                    }

                    function flashScan(msg, type) {
                        const tc = { success: '#065f46', danger: '#991b1b', warning: '#92400e' };
                        const bc = { success: '#d1fae5', danger: '#fee2e2', warning: '#fef9c3' };
                        $('#lastScanInfo').text(msg).css('color', tc[type] || '#666')
                            .animate({ backgroundColor: bc[type] || '#fff' }, 200)
                            .delay(1800).animate({ backgroundColor: '#fff' }, 600);
                    }
                });
            </script>
        </div>
    </div>
</body>

</html>
