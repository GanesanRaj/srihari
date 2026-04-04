<?php include 'header.php'; ?>

<style>
    .row-hold {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
    }

    .row-pending {
        background: #fffdf0;
        border-left: 4px solid #ffc107;
    }

    .row-verified {
        background: #f0fff4;
        border-left: 4px solid #198754;
    }

    #shipTable td,
    #shipTable th {
        padding: 4px 8px !important;
        vertical-align: middle;
    }

    .scan-pulse {
        animation: pulse 1.2s infinite;
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, .4) }
        50%       { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0) }
    }

    .awb-scan-wrap {
        position: relative;
    }

    #awbScanInfo {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        margin-top: 1px;
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

                    <!-- Horizontal Nav Tabs -->
                    <div class="row mb-1">
                        <div class="col-12">
                            <div class="card mb-1">
                                <div class="card-body py-1 px-2">
                                    <ul class="nav nav-tabs nav-bordered mb-0">
                                        <li class="nav-item">
                                            <a href="tag-list.php" class="nav-link py-2 px-3">
                                                <i class="ti ti-list me-1"></i> Tag List
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="tag-create.php" class="nav-link py-2 px-3">
                                                <i class="ti ti-plus me-1"></i> New Tag
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="tag-verify.php" class="nav-link active py-2 px-3">
                                                <i class="ti ti-circle-check me-1"></i> Verify Tag
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Controls Bar -->
                    <div class="row mb-1">
                        <div class="col-12">
                            <div class="card mb-0">
                                <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">

                                    <!-- Tag Search Input -->
                                    <div class="input-group input-group-sm" id="tagSearchWrap" style="width: 280px;">
                                        <span class="input-group-text fw-bold fs-12">TAG NO</span>
                                        <input type="text" id="tagSearch"
                                            class="form-control text-center fw-bold scan-pulse"
                                            style="font-size:13px; letter-spacing:1px;"
                                            placeholder="Scan / Enter Tag No..."
                                            autocomplete="off" autofocus>
                                        <button class="btn btn-primary btn-sm" id="btnLoadTag">
                                            <i class="ti ti-search"></i>
                                        </button>
                                    </div>

                                    <!-- AWB Scan Input (shown after tag loads) -->
                                    <div id="awbScanWrap" class="d-none awb-scan-wrap">
                                        <div class="input-group input-group-sm scan-pulse" style="width: 280px;">
                                            <span class="input-group-text fw-bold fs-12 text-success">AWB SCAN</span>
                                            <input type="text" id="awbScanInput"
                                                class="form-control text-center fw-bold"
                                                style="font-size:13px; letter-spacing:1px;"
                                                placeholder="Scan AWB No... (Enter)"
                                                autocomplete="off">
                                        </div>
                                        <div id="awbScanInfo" class="text-muted">Waiting for scan...</div>
                                    </div>

                                    <!-- Counters (shown after tag loads) -->
                                    <div id="tagSummary" class="d-none d-flex gap-2 align-items-center flex-wrap fs-12">
                                        <span class="badge bg-warning text-dark px-3 py-2">Pending: <b id="cntPending">0</b></span>
                                        <span class="badge bg-success px-3 py-2">Verified: <b id="cntVerified">0</b></span>
                                        <span class="badge bg-danger px-3 py-2">Hold: <b id="cntHold">0</b></span>
                                        <span class="badge bg-secondary px-3 py-2">Total: <b id="cntTotal">0</b></span>
                                    </div>

                                    <!-- Overall Status + Save (shown after tag loads) -->
                                    <div id="tagActions" class="ms-auto d-none d-flex align-items-center gap-2">
                                        <label class="fw-semibold fs-12 mb-0 text-muted">Overall:</label>
                                        <select id="overallStatus" class="form-select form-select-sm" style="width: 185px;">
                                            <option value="packed">Packed</option>
                                            <option value="partially_verified">Partially Verified</option>
                                            <option value="fully_verified">Fully Verified</option>
                                            <option value="in_transit">In Transit</option>
                                            <option value="hold">Hold</option>
                                        </select>
                                        <button id="btnSaveStatus" class="btn btn-sm btn-success">
                                            <i class="ti ti-device-floppy me-1"></i> Save
                                        </button>
                                        <a id="btnPrintTag" href="#" target="_blank" class="btn btn-sm btn-dark">
                                            <i class="ti ti-printer"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tag Content (shown after load) -->
                    <div id="tagContent" class="d-none">
                        <div class="row">

                            <!-- Left: Tag Info -->
                            <div class="col-lg-3">
                                <div class="card mb-0">
                                    <div class="card-header py-1 d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fs-13">Tag Info</h6>
                                        <span id="infoStatusBadge" class="badge bg-secondary">—</span>
                                    </div>
                                    <div class="card-body py-2">
                                        <table class="table table-sm table-borderless m-0 fs-12">
                                            <tr>
                                                <td class="text-muted fw-semibold" style="width:90px;">Tag No</td>
                                                <td><strong id="infoTagNo" class="font-monospace fs-14" style="color:#da7d41;">—</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">From</td>
                                                <td id="infoFrom">—</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">To</td>
                                                <td id="infoTo">—</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Created By</td>
                                                <td id="infoCreatedBy">—</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Created At</td>
                                                <td id="infoCreatedAt" class="fs-11">—</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Verified By</td>
                                                <td id="infoVerifiedBy">—</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Shipments Table -->
                            <div class="col-lg-9 ps-0">
                                <div class="card mb-0">
                                    <div class="card-header py-1 px-2 d-flex align-items-center">
                                        <h6 class="mb-0 fs-13 flex-grow-1">Shipments</h6>
                                        <span class="badge bg-secondary" id="tableCount">0 items</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive m-0">
                                            <table class="table table-sm table-hover m-0" id="shipTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:36px">#</th>
                                                        <th>AWB No</th>
                                                        <th>Consignee</th>
                                                        <th>City</th>
                                                        <th>Status</th>
                                                        <th>Remarks</th>
                                                        <th style="width:130px; white-space:nowrap;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="shipBody">
                                                    <tr id="emptyRow">
                                                        <td colspan="7" class="text-center text-muted py-4">
                                                            Load a tag to see shipments
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
            </div>
            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(function () {
                    let tagId = null;
                    let tagData = {};
                    let branchMap = {};
                    const verifiedSet = new Set(); // AWBs confirmed by scan
                    const holdSet = new Set();     // AWBs marked hold

                    // ── Audio Beeps (Web Audio API) ───────────────────────────────
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    let audioCtx = null;
                    function getCtx() {
                        if (!audioCtx) audioCtx = new AudioCtx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        return audioCtx;
                    }
                    function playTone(freq, type, delay, dur, vol) {
                        try {
                            const ctx = getCtx();
                            const t = ctx.currentTime + delay;
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain); gain.connect(ctx.destination);
                            osc.type = type; osc.frequency.setValueAtTime(freq, t);
                            gain.gain.setValueAtTime(vol, t);
                            gain.gain.linearRampToValueAtTime(0, t + dur);
                            osc.start(t); osc.stop(t + dur + 0.01);
                        } catch (e) {}
                    }
                    function beepSuccess()   { playTone(1400, 'sine',   0,    0.12, 0.5); }
                    function beepError()     { playTone(500,  'square', 0,    0.15, 0.5); playTone(300, 'square', 0.18, 0.20, 0.5); }
                    function beepDuplicate() { playTone(900,  'sine',   0,    0.08, 0.35); playTone(900, 'sine', 0.12, 0.08, 0.35); }
                    $(document).one('keydown', () => getCtx());

                    const statusColors = { packed: 'warning', hold: 'danger', partially_verified: 'info', fully_verified: 'success', in_transit: 'primary' };
                    const statusLabels = { packed: 'Packed', hold: 'Hold', partially_verified: 'Partially Verified', fully_verified: 'Fully Verified', in_transit: 'In Transit' };

                    // ── Load Branches ─────────────────────────────────────────────
                    $.get('api/branch/read.php?length=-1', res => {
                        if (res.data) res.data.forEach(b => { branchMap[b.id] = b.branch_name; });
                    });

                    // ── Tag Search ────────────────────────────────────────────────
                    $('#tagSearch').on('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); loadTag(); } });
                    $('#btnLoadTag').on('click', loadTag);

                    function loadTag() {
                        const tagNo = $('#tagSearch').val().trim();
                        if (!tagNo) return;
                        $.get('api/tag/readone.php?tag_no=' + encodeURIComponent(tagNo), res => {
                            if (res.status === 'success') {
                                renderTag(res.data);
                            } else {
                                beepError();
                                alert('Tag not found: ' + res.message);
                                $('#tagSearch').val('').focus();
                            }
                        });
                    }

                    function renderTag(data) {
                        tagId = data.id;
                        tagData = data;
                        verifiedSet.clear();
                        holdSet.clear();

                        // Info panel
                        $('#infoTagNo').text(data.tag_no);
                        const sc = statusColors[data.status] || 'secondary';
                        const sl = statusLabels[data.status] || data.status;
                        $('#infoStatusBadge').attr('class', 'badge bg-' + sc).text(sl);
                        $('#infoFrom').text(branchMap[data.from_branch] || data.from_branch || '—');
                        $('#infoTo').text(branchMap[data.to_branch] || data.to_branch || '—');
                        $('#infoCreatedBy').text(data.created_by_name || '—');
                        $('#infoCreatedAt').text(data.created_at ? new Date(data.created_at).toLocaleString('en-IN') : '—');
                        $('#infoVerifiedBy').text(data.verified_by_name || '—');

                        $('#overallStatus').val(data.status);
                        $('#btnPrintTag').attr('href', 'tag-print.php?id=' + data.id);

                        // Build table
                        $('#shipBody').empty();
                        const entries = data.json_data || [];
                        if (entries.length === 0) {
                            $('#shipBody').html('<tr><td colspan="7" class="text-center text-muted py-3">No shipments in this tag</td></tr>');
                        } else {
                            // Pre-populate sets from stored per-shipment status
                            entries.forEach(e => {
                                if (e.status === 'hold') {
                                    holdSet.add(e.awb_no);
                                } else if (e.status === 'verified') {
                                    verifiedSet.add(e.awb_no);
                                }
                            });
                            entries.forEach((entry, idx) => appendShipRow(entry, idx + 1));
                        }

                        updateCounters();
                        $('#tableCount').text(entries.length + ' items');

                        // Show all panels
                        $('#tagContent').removeClass('d-none');
                        $('#tagSummary').removeClass('d-none');
                        $('#tagActions').removeClass('d-none');
                        $('#awbScanWrap').removeClass('d-none');
                        $('#tagSearch').removeClass('scan-pulse');
                        $('#awbScanInput').focus();
                    }

                    function appendShipRow(entry, idx) {
                        const isHold = verifiedSet.has(entry.awb_no) ? false : (entry.status === 'hold');
                        const isVerified = verifiedSet.has(entry.awb_no);
                        const rowCls  = isHold ? 'row-hold'  : (isVerified ? 'row-verified' : 'row-pending');
                        const badgeCls = isHold ? 'bg-danger' : (isVerified ? 'bg-success'   : 'bg-warning text-dark');
                        const badgeTxt = isHold ? 'Hold'      : (isVerified ? 'Verified'      : 'Pending');

                        const tr = `<tr class="${rowCls}" data-awb="${entry.awb_no}">
                            <td class="text-muted fs-12">${idx}</td>
                            <td><strong style="font-size:12px;">${entry.awb_no}</strong></td>
                            <td class="fs-12">${entry.consignee_name || ''}</td>
                            <td class="fs-12">${entry.consignee_city || ''}</td>
                            <td><span class="badge ${badgeCls} awb-status-badge">${badgeTxt}</span></td>
                            <td>
                                <input type="text" class="form-control form-control-sm awb-remark-input"
                                    data-awb="${entry.awb_no}"
                                    value="${(entry.remarks || '').replace(/"/g, '&quot;')}"
                                    placeholder="Remarks..."
                                    style="height:24px; padding:2px 6px; font-size:11px; min-width:120px;">
                            </td>
                            <td style="white-space:nowrap;">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success btn-ok-awb" data-awb="${entry.awb_no}" style="font-size:11px;padding:2px 8px;" title="Mark OK">
                                        <i class="ti ti-check"></i> OK
                                    </button>
                                    <button class="btn btn-danger btn-hold-awb" data-awb="${entry.awb_no}" style="font-size:11px;padding:2px 8px;" title="Mark Hold">
                                        <i class="ti ti-ban"></i> Hold
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                        $('#shipBody').append(tr);
                    }

                    // ── AWB Scan Input ────────────────────────────────────────────
                    $('#awbScanInput').on('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); doScanVerify(); }
                    });

                    function doScanVerify() {
                        const awb = $('#awbScanInput').val().trim();
                        if (!awb) return;
                        $('#awbScanInput').val('');

                        // Check AWB exists in this tag
                        const entries = tagData.json_data || [];
                        const found = entries.find(e => e.awb_no === awb);

                        if (!found) {
                            beepError();
                            flashScanInfo('✗ Not in this tag: ' + awb, 'danger');
                            return;
                        }

                        if (verifiedSet.has(awb)) {
                            beepDuplicate();
                            flashScanInfo('⚠ Already verified: ' + awb, 'warning');
                            // Scroll to row
                            scrollToRow(awb);
                            return;
                        }

                        // Mark verified — save to DB
                        verifiedSet.add(awb);
                        holdSet.delete(awb);
                        $.post('api/tag/update_scan.php', { tag_id: tagId, awb_no: awb, status: 'verified' }, res => {
                            if (res.status === 'success') {
                                const entry = (tagData.json_data || []).find(e => e.awb_no === awb);
                                if (entry) entry.status = 'verified';
                                syncOverallBadge(res.tag_status);
                            }
                        });
                        setRowVerified(awb);
                        beepSuccess();
                        flashScanInfo('✓ Verified: ' + awb, 'success');
                        scrollToRow(awb);
                        updateCounters();
                    }

                    function setRowVerified(awb) {
                        const row = $(`tr[data-awb="${awb}"]`);
                        row.removeClass('row-hold row-pending row-verified').addClass('row-verified');
                        row.find('.awb-status-badge')
                           .attr('class', 'badge bg-success awb-status-badge')
                           .text('Verified');
                    }

                    function setRowHold(awb) {
                        const row = $(`tr[data-awb="${awb}"]`);
                        row.removeClass('row-hold row-pending row-verified').addClass('row-hold');
                        row.find('.awb-status-badge')
                           .attr('class', 'badge bg-danger awb-status-badge')
                           .text('Hold');
                    }

                    function setRowPending(awb) {
                        const row = $(`tr[data-awb="${awb}"]`);
                        row.removeClass('row-hold row-pending row-verified').addClass('row-pending');
                        row.find('.awb-status-badge')
                           .attr('class', 'badge bg-warning text-dark awb-status-badge')
                           .text('Pending');
                    }

                    // ── Manual OK Button ──────────────────────────────────────────
                    $('#shipBody').on('click', '.btn-ok-awb', function () {
                        const awb = $(this).data('awb');
                        verifiedSet.add(awb);
                        holdSet.delete(awb);
                        $.post('api/tag/update_scan.php', { tag_id: tagId, awb_no: awb, status: 'verified' }, res => {
                            if (res.status === 'success') {
                                const entry = (tagData.json_data || []).find(e => e.awb_no === awb);
                                if (entry) entry.status = 'verified';
                                syncOverallBadge(res.tag_status);
                            }
                        });
                        setRowVerified(awb);
                        updateCounters();
                        $('#awbScanInput').focus();
                    });

                    // ── Manual Hold Button ────────────────────────────────────────
                    $('#shipBody').on('click', '.btn-hold-awb', function () {
                        const awb = $(this).data('awb');
                        holdSet.add(awb);
                        verifiedSet.delete(awb);
                        $.post('api/tag/update_scan.php', { tag_id: tagId, awb_no: awb, status: 'hold' }, res => {
                            if (res.status === 'success') {
                                const entry = (tagData.json_data || []).find(e => e.awb_no === awb);
                                if (entry) entry.status = 'hold';
                                setRowHold(awb);
                                syncOverallBadge(res.tag_status);
                                updateCounters();
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                        $('#awbScanInput').focus();
                    });

                    // ── Auto-save Remarks ─────────────────────────────────────────
                    $('#shipBody').on('change', '.awb-remark-input', function () {
                        const awb = $(this).data('awb');
                        const remarks = $(this).val().trim();
                        $.post('api/tag/update_scan_remark.php', { tag_id: tagId, awb_no: awb, remarks: remarks });
                    });

                    // ── Save Overall Status ───────────────────────────────────────
                    $('#btnSaveStatus').on('click', function () {
                        if (!tagId) return;
                        const status = $('#overallStatus').val();
                        const label = $('#overallStatus option:selected').text();
                        if (!confirm(`Set tag overall status to "${label}"?`)) return;

                        $.post('api/tag/update_status.php', { tag_id: tagId, status: status }, res => {
                            if (res.status === 'success') {
                                const sc = statusColors[status] || 'secondary';
                                const sl = statusLabels[status] || status;
                                $('#infoStatusBadge').attr('class', 'badge bg-' + sc).text(sl);
                                // Lock dropdown + button after save
                                $('#overallStatus').prop('disabled', true);
                                $('#btnSaveStatus').prop('disabled', true).html('<i class="ti ti-circle-check me-1"></i> Saved');
                                alert('Tag status saved!');
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });

                    // ── Helpers ───────────────────────────────────────────────────
                    function updateCounters() {
                        const total = (tagData.json_data || []).length;
                        const hold = holdSet.size;
                        const verified = verifiedSet.size;
                        const pending = total - verified - hold;
                        $('#cntPending').text(pending < 0 ? 0 : pending);
                        $('#cntVerified').text(verified);
                        $('#cntHold').text(hold);
                        $('#cntTotal').text(total);
                        autoSuggestOverallStatus(total, verified, hold, pending < 0 ? 0 : pending);
                    }

                    function autoSuggestOverallStatus(total, verified, hold, pending) {
                        if ($('#overallStatus').prop('disabled')) return; // already saved/locked
                        const sel = $('#overallStatus');
                        sel.empty();

                        if (total === 0) return;

                        if (pending === 0 && hold === 0) {
                            // All shipments confirmed — only Fully Verified makes sense
                            sel.append('<option value="fully_verified">Fully Verified</option>');
                            sel.val('fully_verified').prop('disabled', false);
                        } else {
                            // Some pending or hold — offer Partially Verified / Hold
                            sel.append('<option value="partially_verified">Partially Verified</option>');
                            sel.append('<option value="hold">Hold</option>');
                            sel.val(hold > 0 ? 'hold' : 'partially_verified');
                        }
                    }

                    function syncOverallBadge(tagStatus) {
                        const sc = statusColors[tagStatus] || 'secondary';
                        const sl = statusLabels[tagStatus] || tagStatus;
                        $('#infoStatusBadge').attr('class', 'badge bg-' + sc).text(sl);
                    }

                    function scrollToRow(awb) {
                        const row = $(`tr[data-awb="${awb}"]`);
                        if (row.length) {
                            row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }

                    function flashScanInfo(msg, type) {
                        const colors = { success: '#065f46', danger: '#991b1b', warning: '#92400e' };
                        $('#awbScanInfo').text(msg).css('color', colors[type] || '#555')
                            .delay(2000).queue(function (next) {
                                $(this).text('Waiting for scan...').css('color', '#888');
                                next();
                            });
                    }
                });
            </script>
        </div>
    </div>
</body>

</html>
