<?php include 'header.php'; ?>
<?php if (!defined('MIDDLEWARE_INCLUDED')) { require_once __DIR__ . '/config/middleware.php'; } require_permission('whms_manifest', 'is_view'); ?>

<!-- Select2 CSS -->
<link href="assets/plugins/select2/select2.min.css" rel="stylesheet" />

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

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(13, 110, 253, .4)
        }

        50% {
            box-shadow: 0 0 0 8px rgba(13, 110, 253, 0)
        }
    }

    #awbTable td,
    #awbTable th {
        padding: 0 !important;
        vertical-align: middle;
    }

    .tag-chip {
        font-size: 10px;
        font-family: monospace;
        padding: 1px 6px;
        border-radius: 10px;
        background: #e8f4fd;
        border: 1px solid #bee3f8;
        color: #1a6fa8;
        white-space: nowrap;
    }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">

                    <!-- Header Row -->
                    <div class="row mb-1">
                        <div class="col-12">
                            <div class="card mb-0">
                                <div class="card-body py-1 d-flex align-items-center gap-3 flex-wrap">

                                    <!-- Scan input -->
                                    <div class="scan-input-wrap position-relative">
                                        <input type="text" id="scanInput"
                                            class="form-control form-control-sm text-center scan-pulse text-dark"
                                            style="width:260px; font-weight:700;"
                                            placeholder="Scan AWB or TAG No... (Enter)"
                                            autocomplete="off" autofocus>
                                        <div id="lastScanInfo"
                                            class="position-absolute w-100 text-center fw-bold"
                                            style="font-size:10px; top:100%; color:#666;">
                                            Waiting for scan...
                                        </div>
                                    </div>

                                    <!-- Manifest No display -->
                                    <div class="input-group input-group-sm" style="width:230px;">
                                        <span class="input-group-text fs-11 fw-bold text-muted"
                                            style="letter-spacing:0.5px;">MANIFEST</span>
                                        <input type="text" id="manifestNoDisplay"
                                            class="form-control form-control-sm text-center fw-bold"
                                            style="font-size:13px; letter-spacing:1px;" readonly>
                                    </div>

                                    <!-- Counters + status -->
                                    <div class="ms-auto d-flex align-items-center gap-3 fs-15 font-monospace">
                                        <span class="text-primary">
                                            <span class="fs-20 fw-bolder" id="cntTotal">0</span>
                                            <span class="fs-12"> Shipments</span>
                                        </span>
                                        <span class="badge bg-warning text-dark" id="manifestStatusBadge">Draft</span>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="ms-2 d-flex gap-1">
                                        <a href="whms-manifest-list.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-list"></i> All Manifests
                                        </a>
                                        <button id="btnSaveManifest" class="btn btn-sm btn-success" disabled>
                                            <i class="ti ti-device-floppy"></i> Save
                                        </button>
                                        <button id="btnPrintManifest" class="btn btn-sm btn-dark" style="display:none;">
                                            <i class="ti ti-printer"></i> Print
                                        </button>
                                        <button id="btnExportManifest" class="btn btn-sm btn-info" style="display:none;">
                                            <i class="ti ti-file-spreadsheet"></i> Export Excel
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Row -->
                    <div class="row">

                        <!-- Left: Details Panel -->
                        <div class="col-lg-3">
                            <div class="card mb-0">
                                <div class="card-header py-1 d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fs-13">Manifest Details</h6>
                                    <span class="badge bg-primary fs-12" id="manifestNoBadge"
                                        style="letter-spacing:1px;">—</span>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">From Branch</label>
                                        <select id="fromBranch" class="form-select form-select-sm branch-select">
                                            <option value="">-- Select Branch --</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">To Branch</label>
                                        <select id="toBranch" class="form-select form-select-sm branch-select">
                                            <option value="">-- Select Branch --</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Status</label>
                                        <select id="manifestStatus" class="form-select form-select-sm">
                                            <option value="draft">Draft</option>
                                        </select>
                                        <small class="text-muted fs-11">From Status Description (master). One manifest, one status.</small>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Dispatch Mode</label>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 fw-bold btn-mode" data-val="Air">Air/Express</button>
                                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 fw-bold btn-mode" data-val="Surface">Surface</button>
                                        </div>
                                        <input type="hidden" id="dispatchMode" value="">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Coloader</label>
                                        <select id="coloader" class="form-select form-select-sm coloader-select">
                                            <option value="">-- Select Coloader --</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">CD No</label>
                                        <input type="text" id="cdNo" class="form-control form-control-sm"
                                            placeholder="CD number">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Vehicle No</label>
                                        <input type="text" id="vehicleNo" class="form-control form-control-sm"
                                            placeholder="e.g. MH12AB1234">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Driver Name</label>
                                        <input type="text" id="driverName" class="form-control form-control-sm"
                                            placeholder="Driver name">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Mobile No</label>
                                        <input type="text" id="mobileNo" class="form-control form-control-sm"
                                            placeholder="10-digit mobile">
                                    </div>
                                    <div class="row g-1 mb-2">
                                        <div class="col-4">
                                            <label class="form-label fs-12 mb-0">Bags</label>
                                            <input type="number" id="bagCount" class="form-control form-control-sm"
                                                value="0" min="0">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label fs-12 mb-0">Wt (kg)</label>
                                            <input type="number" id="weight" class="form-control form-control-sm"
                                                value="0" min="0" step="0.01">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label fs-12 mb-0">Boxes</label>
                                            <input type="number" id="totalBox" class="form-control form-control-sm"
                                                value="0" min="0">
                                        </div>
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
                                        <table class="table table-sm table-hover m-0 p-0" id="awbTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:36px">#</th>
                                                    <th>AWB No</th>
                                                    <th>Consignee</th>
                                                    <th>City</th>
                                                    <th>Tag No</th>
                                                    <th>Dispatch Mode</th>
                                                    <th>Scanned At</th>
                                                    <th style="width:70px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="awbBody">
                                                <tr id="emptyRow">
                                                    <td colspan="7"
                                                        class="text-center text-muted py-4">
                                                        <i class="ti ti-scan me-1" style="font-size:1.3rem;"></i><br>
                                                        Scan AWB or TAG number to add shipments
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /row -->

                </div>
            </div>

            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script>
                $(function () {
                    let manifestId = null;
                    let manifestNo = null;
                    const scanned = {}; // keyed by awb_no

                    // ── Audio (Web Audio API) ─────────────────────────────────────────
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    let audioCtx = null;
                    function getCtx() {
                        if (!audioCtx) audioCtx = new AudioCtx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        return audioCtx;
                    }
                    function playTone(freq, type, startDelay, dur, vol) {
                        try {
                            const ctx = getCtx();
                            const t = ctx.currentTime + startDelay;
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain); gain.connect(ctx.destination);
                            osc.type = type || 'sine';
                            osc.frequency.setValueAtTime(freq, t);
                            gain.gain.setValueAtTime(vol || 0.4, t);
                            gain.gain.linearRampToValueAtTime(0, t + dur);
                            osc.start(t); osc.stop(t + dur + 0.01);
                        } catch (e) { }
                    }
                    function beepSuccess() { playTone(1400, 'sine', 0, 0.12, 0.5); }
                    function beepError() {
                        playTone(500, 'square', 0, 0.15, 0.5);
                        playTone(300, 'square', 0.18, 0.20, 0.5);
                    }
                    function beepDuplicate() {
                        playTone(900, 'sine', 0, 0.08, 0.35);
                        playTone(900, 'sine', 0.12, 0.08, 0.35);
                    }
                    $(document).one('keydown', function () { getCtx(); });

                    // ── Load Branches ─────────────────────────────────────────────────
                    $.get('api/branch/read.php?length=-1', function (res) {
                        if (res.data) {
                            let opts = '<option value="">-- Select Branch --</option>';
                            res.data.forEach(b => {
                                opts += `<option value="${b.id}">${b.branch_name}</option>`;
                            });
                            $('.branch-select').html(opts);
                            $('.branch-select').select2({
                                width: '100%',
                                placeholder: '-- Select Branch --',
                                allowClear: true
                            });
                            if (window.initFromBranch)
                                $('#fromBranch').val(window.initFromBranch).trigger('change.select2');
                            if (window.initToBranch)
                                $('#toBranch').val(window.initToBranch).trigger('change.select2');
                        }
                    });

                    // ── Load Coloaders (dropdown) ──────────────────────────────────────
                    $.get('api/coloader/read.php?length=1000&status=active', function (res) {
                        if (res.data && res.data.length) {
                            let opts = '<option value="">-- Select Coloader --</option>';
                            res.data.forEach(c => {
                                opts += `<option value="${c.id}">${(c.name || '').replace(/"/g, '&quot;')}</option>`;
                            });
                            $('#coloader').html(opts);
                            $('#coloader').select2({ width: '100%', placeholder: '-- Select Coloader --', allowClear: true });
                            if (window.initColoaderId) $('#coloader').val(window.initColoaderId).trigger('change.select2');
                        }
                    });

                    // ── Load Status from master-status-list (Status Description) ───────
                    $.get('api/master_status/read.php?length=-1&status=active', function (res) {
                        var $sel = $('#manifestStatus');
                        $sel.html('<option value="draft">Draft</option>');
                        if (res.data && res.data.length) {
                            res.data.forEach(function (s) {
                                var name = (s.name || '').trim();
                                if (name && name.toLowerCase() !== 'draft')
                                    $sel.append('<option value="' + (name.replace(/"/g, '&quot;')) + '">' + name + '</option>');
                            });
                        }
                        if (window.initManifestStatus) $sel.val(window.initManifestStatus);
                    });

                    // ── Init: create new or load existing ─────────────────────────────
                    const urlId = new URLSearchParams(location.search).get('id');
                    if (urlId) {
                        loadExistingManifest(urlId);
                    } else {
                        createManifest();
                    }

                    function createManifest() {
                        $('#manifestNoBadge').text('Creating...');
                        $.post('api/manifest/create.php', {}, function (res) {
                            if (res.status === 'success') {
                                manifestId = res.manifest_id;
                                manifestNo = res.manifest_no;
                                $('#manifestNoBadge').text(manifestNo);
                                $('#manifestNoDisplay').val(manifestNo);
                                history.replaceState(null, '', '?id=' + manifestId);
                                $('#scanInput').focus();
                            } else {
                                $('#manifestNoBadge').text('Error');
                                showtoastt('Failed to create manifest: ' + res.message, 'error');
                            }
                        });
                    }

                    function loadExistingManifest(id) {
                        $.get('api/manifest/readone.php?id=' + id, function (res) {
                            if (res.status === 'success') {
                                const d = res.data;
                                manifestId = d.id;
                                manifestNo = d.manifest_no;
                                $('#manifestNoBadge').text(manifestNo);
                                $('#manifestNoDisplay').val(manifestNo);
                                $('#btnPrintManifest, #btnExportManifest').show();

                                // Populate details form
                                window.initFromBranch = d.from_branch;
                                window.initToBranch = d.to_branch;
                                window.initColoaderId = d.coloader_id ? String(d.coloader_id) : '';
                                window.initManifestStatus = (d.status || 'draft');
                                if ($('#coloader').find('option[value="' + (d.coloader_id || '') + '"]').length)
                                    $('#coloader').val(d.coloader_id || '').trigger('change.select2');
                                if ($('#manifestStatus').find('option[value="' + (d.status || '') + '"]').length)
                                    $('#manifestStatus').val(d.status || 'draft');
                                else
                                    $('#manifestStatus').val('draft');
                                $('#cdNo').val(d.cd_no || '');
                                $('#vehicleNo').val(d.vehicle_no || '');
                                $('#driverName').val(d.driver_name || '');
                                $('#mobileNo').val(d.mobile_no || '');
                                $('#bagCount').val(d.bag_count || 0);
                                $('#weight').val(d.weight || 0);
                                $('#totalBox').val(d.total_box || 0);
                                if (d.dispatch_mode) {
                                    $('#dispatchMode').val(d.dispatch_mode);
                                    $('.btn-mode[data-val="' + d.dispatch_mode + '"]').addClass('active');
                                }

                                updateStatusBadge(d.status);

                                if (Array.isArray(d.json_data)) {
                                    d.json_data.forEach(e => appendRow(e));
                                    updateCounters();
                                }
                                $('#scanInput').focus();
                            } else {
                                createManifest(); // fallback
                            }
                        });
                    }

                    // ── Scan on Enter ─────────────────────────────────────────────────
                    $('#scanInput').on('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); doScan(); }
                    });

                    function doScan() {
                        if (!manifestId) { showtoastt('Manifest not ready', 'warning'); return; }
                        const val = $('#scanInput').val().trim();
                        if (!val) return;

                        // Client-side duplicate check for single AWB (not TAG)
                        if (!val.match(/^TAG-/i) && scanned[val]) {
                            beepDuplicate();
                            flashLastScan('⚠ Already scanned: ' + val, 'warning');
                            $('#scanInput').val('').focus();
                            return;
                        }

                        $.post('api/manifest/scan.php', { manifest_id: manifestId, scan_value: val }, function (res) {
                            if (res.status === 'success') {
                                res.entries.forEach(e => appendRow(e));
                                updateCounters();
                                updateStatusBadge(res.manifest_status);
                                if (res.bag_count != null) $('#bagCount').val(res.bag_count);
                                if (res.total_box != null) $('#totalBox').val(res.total_box);
                                if (res.weight != null) $('#weight').val(res.weight);
                                beepSuccess();
                                const added = res.entries.length;
                                const msg = added > 1
                                    ? `✓ ${added} shipments added from TAG: ${val}`
                                    : `✓ ${val}`;
                                flashLastScan(msg, 'success');
                            } else {
                                beepError();
                                flashLastScan('✗ ' + res.message, 'danger');
                            }
                            $('#scanInput').val('').focus();
                        });
                    }

                    // ── Append row to table ───────────────────────────────────────────
                    function appendRow(entry) {
                        scanned[entry.awb_no] = entry;
                        $('#emptyRow').remove();
                        const idx = Object.keys(scanned).length;
                        const time = entry.scanned_at
                            ? new Date(entry.scanned_at).toLocaleTimeString('en-IN')
                            : '';
                        const tagCell = entry.tag_no
                            ? `<span class="tag-chip">${entry.tag_no}</span>`
                            : '<span class="text-muted fs-11">—</span>';
                        const currentMode = $('#dispatchMode').val() || '—';
                        const modeCell = currentMode !== '—' 
                            ? `<span class="badge bg-light text-dark border border-secondary" style="font-size:10px;">${currentMode}</span>` 
                            : `<span class="text-muted fs-11">—</span>`;

                        const tr = `<tr data-awb="${entry.awb_no}">
                            <td class="text-muted fs-12 ps-1">${idx}</td>
                            <td><strong style="font-size:12px;">${entry.awb_no}</strong></td>
                            <td style="font-size:12px;">${entry.consignee_name || ''}</td>
                            <td style="font-size:12px;">${entry.consignee_city || ''}</td>
                            <td>${tagCell}</td>
                            <td>${modeCell}</td>
                            <td class="fs-11 text-muted">${time}</td>
                            <td class="ps-1">
                                <button class="btn btn-xs btn-danger btn-delete-scan"
                                    data-awb="${entry.awb_no}"
                                    style="font-size:11px;padding:2px 8px;">
                                    Del
                                </button>
                            </td>
                        </tr>`;
                        $('#awbBody').prepend(tr);
                        $('#tableCount').text(Object.keys(scanned).length + ' items');
                    }

                    // ── Delete row ────────────────────────────────────────────────────
                    $('#awbBody').on('click', '.btn-delete-scan', function () {
                        const awb = $(this).data('awb');
                        if (!confirm('Remove AWB ' + awb + ' from this manifest?')) return;

                        $.post('api/manifest/delete_scan.php', { manifest_id: manifestId, awb_no: awb }, function (res) {
                            if (res.status === 'success') {
                                $(`tr[data-awb="${awb}"]`).remove();
                                delete scanned[awb];
                                updateCounters();
                                if (res.bag_count != null) $('#bagCount').val(res.bag_count);
                                if (res.total_box != null) $('#totalBox').val(res.total_box);
                                if (res.weight != null) $('#weight').val(res.weight);
                                if (Object.keys(scanned).length === 0) {
                                    $('#awbBody').append(
                                        `<tr id="emptyRow"><td colspan="7" class="text-center text-muted py-4">
                                            <i class="ti ti-scan me-1" style="font-size:1.3rem;"></i><br>
                                            Scan AWB or TAG number to add shipments
                                        </td></tr>`
                                    );
                                    $('#tableCount').text('0 items');
                                } else {
                                    $('#tableCount').text(Object.keys(scanned).length + ' items');
                                    // Re-number
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

                    // ── Save Details ──────────────────────────────────────────────────
                    $('#btnSaveDetails').on('click', function () {
                        if (!manifestId) return;
                        saveDetails($(this));
                    });

                    function saveDetails($btn) {
                        if ($btn) $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
                        $.post('api/manifest/update_details.php', buildDetailsPayload(), function (res) {
                            if (res.status === 'success') {
                                showtoastt('Details saved', 'success');
                            } else {
                                showtoastt('Error: ' + res.message, 'error');
                            }
                        }).always(function () {
                            if ($btn) $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Save Details');
                            $('#scanInput').focus();
                        });
                    }
                    
                    // Toggle Dispatch Mode Buttons
                    $('.btn-mode').on('click', function () {
                        if ($(this).hasClass('active')) {
                            // Release selection
                            $(this).removeClass('active');
                            $('#dispatchMode').val('');
                        } else {
                            // Lock new selection
                            $('.btn-mode').removeClass('active');
                            $(this).addClass('active');
                            $('#dispatchMode').val($(this).data('val'));
                        }
                        // Auto-save if manifest is created
                        if (manifestId) saveDetails();
                    });

                    // Auto-save branches on change
                    $(document).on('change', '.branch-select', function () {
                        if (!manifestId) return;
                        $.post('api/manifest/update_details.php', {
                            manifest_id: manifestId,
                            from_branch: $('#fromBranch').val(),
                            to_branch: $('#toBranch').val()
                        });
                    });

                    // ── Save Manifest (final) ─────────────────────────────────────────
                    $('#btnSaveManifest').on('click', function () {
                        const total = Object.keys(scanned).length;
                        if (!confirm(`Save Manifest: ${manifestNo}\nTotal Shipments: ${total}\n\nMark as Dispatched?`)) return;

                        const payload = Object.assign(buildDetailsPayload(), { status: 'Dispatched' });
                        $.post('api/manifest/update_details.php', payload, function (res) {
                            if (res.status === 'success') {
                                updateStatusBadge('Dispatched');
                                $('#btnPrintManifest, #btnExportManifest').show();
                                showtoastt('Manifest saved as Dispatched!', 'success');
                            } else {
                                showtoastt('Error: ' + res.message, 'error');
                            }
                        });
                    });

                    // ── Print ─────────────────────────────────────────────────────────
                    $('#btnPrintManifest').on('click', function () {
                        if (manifestId) window.open('manifest-print.php?id=' + manifestId, '_blank');
                    });

                    // ── Export Excel ──────────────────────────────────────────────────
                    $('#btnExportManifest').on('click', function () {
                        if (manifestId) {
                            window.location.href = 'api/manifest/export.php?id=' + manifestId;
                        }
                    });

                    // ── Helpers ───────────────────────────────────────────────────────
                    function buildDetailsPayload() {
                        return {
                            manifest_id: manifestId,
                            from_branch: $('#fromBranch').val(),
                            to_branch: $('#toBranch').val(),
                            status: $('#manifestStatus').val() || 'draft',
                            coloader_id: $('#coloader').val(),
                            cd_no: $('#cdNo').val(),
                            vehicle_no: $('#vehicleNo').val(),
                            driver_name: $('#driverName').val(),
                            mobile_no: $('#mobileNo').val(),
                            bag_count: $('#bagCount').val(),
                            weight: $('#weight').val(),
                            total_box: $('#totalBox').val(),
                            dispatch_mode: $('#dispatchMode').val()
                        };
                    }

                    function updateCounters() {
                        const total = Object.keys(scanned).length;
                        $('#cntTotal').text(total);
                        $('#btnSaveManifest').prop('disabled', total === 0);
                    }

                    function updateStatusBadge(status) {
                        const labels = { draft: 'Draft', dispatched: 'Dispatched', received: 'Received' };
                        const colors = { draft: 'warning text-dark', dispatched: 'primary', received: 'success', 'In Transit': 'info', 'Manifested': 'secondary' };
                        $('#manifestStatusBadge')
                            .attr('class', 'badge bg-' + (colors[status] || 'secondary'))
                            .text(labels[status] || status);
                        if ($('#manifestStatus').find('option[value="' + (status || '') + '"]').length)
                            $('#manifestStatus').val(status || 'draft');
                    }

                    function flashLastScan(msg, type) {
                        const colors = { success: '#d1fae5', danger: '#fee2e2', warning: '#fef9c3' };
                        const textColors = { success: '#065f46', danger: '#991b1b', warning: '#92400e' };
                        $('#lastScanInfo')
                            .text(msg)
                            .css('color', textColors[type] || '#666')
                            .animate({ backgroundColor: colors[type] || '#fff' }, 200)
                            .delay(1800)
                            .animate({ backgroundColor: '#fff' }, 600);
                    }
                });
            </script>
        </div>
    </div>
</body>

</html>
