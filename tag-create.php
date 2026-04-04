<?php include 'header.php'; ?>

<!-- Select2 CSS -->
<link href="assets/plugins/select2/select2.min.css" rel="stylesheet" />

<style>
    .scan-input-wrap {
        position: relative;
    }

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

    .awb-row-verified {
        background: #f0fff4;
        border-left: 4px solid #198754;
    }

    .awb-row-hold {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
    }

    .tag-badge {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: 2px;
        color: #da7d41;
        font-family: monospace;
    }

    .status-bar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .status-pill {
        padding: 4px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
    }

    #awbTable td,
    #awbTable th {
        padding: 0 !important;
        vertical-align: middle;
    }

    #scanSound {
        display: none;
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
                                    <div class="scan-input-wrap position-relative">
                                        <input type="text" id="awbInput"
                                            class="form-control form-control-sm text-center scan-pulse text-dark"
                                            style="width:240px; font-weight:700;" placeholder="Scan AWB No... (Enter)"
                                            autocomplete="off" autofocus>
                                        <div id="lastScanInfo"
                                            class="position-absolute w-100 text-center text-muted fw-bold"
                                            style="font-size: 10px; top: 100%;">
                                            Waiting for scan...
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm" style="width:260px;">
                                        <span class="input-group-text fs-11 fw-bold text-muted"
                                            style="letter-spacing:0.5px;">TAG NO</span>
                                        <input type="text" id="tagNoInput"
                                            class="form-control form-control-sm text-center fw-bold"
                                            style="font-size:13px; letter-spacing:1px;" placeholder="Auto-generated..."
                                            autocomplete="off">
                                        <button class="btn btn-outline-secondary btn-sm" id="btnSaveTagNo"
                                            title="Save Tag No" style="font-size:11px;padding:2px 8px;">✓</button>
                                    </div>
                                    <div class="ms-auto d-flex align-items-center gap-3 fs-15 font-monospace">
                                        <span class="text-primary"><span class="fs-20 fw-bolder" id="cntTotal">0</span>
                                            Total Scanned</span>
                                        <span class="status-pill bg-warning text-dark ms-2"
                                            id="tagStatusBadge">Creating...</span>
                                    </div>
                                    <div class="ms-2">
                                        <a href="tag-list.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-list"></i> All Tags
                                        </a>
                                        <button id="btnVerifyAll" class="btn btn-sm btn-success ms-1" disabled>
                                            <i class="ti ti-device-floppy"></i> Save
                                        </button>
                                        <button id="btnPrintTag" class="btn btn-sm btn-dark ms-1" style="display:none;"
                                            title="Print Tag">
                                            <i class="ti ti-printer"></i> Print
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left: Scanner -->
                        <div class="col-lg-3">
                            <div class="card mb-0">
                                <div class="card-header py-1 d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fs-13">Tag Routing</h6>
                                    <span class="badge bg-primary fs-12" id="tagNoDisplay"
                                        style="letter-spacing:1px;">—</span>
                                </div>
                                <div class="card-body py-2">
                                    <div class="mb-1">
                                        <label class="form-label fs-12">From Branch</label>
                                        <select id="fromBranch" class="form-select form-select-sm branch-select">
                                            <option value="">-- Select Branch --</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12">To Branch</label>
                                        <select id="toBranch" class="form-select form-select-sm branch-select">
                                            <option value="">-- Select Branch --</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12">Tag Status</label>
                                        <select id="tagStatusSelect" class="form-select form-select-sm">
                                            <option value="packed">Packed</option>
                                            <option value="partially_verified">Partially Verified</option>
                                            <option value="fully_verified">Verified</option>
                                            <option value="hold">Hold</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Scanned AWBs Table -->
                        <div class="col-lg-9 ps-0">
                            <div class="card mb-0">
                                <div class="card-header p-0 d-flex align-items-center">
                                    <h6 class="mb-0 fs-13 flex-grow-1">
                                        Scanned Shipments
                                    </h6>
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
                                                    <th>E-Waybill</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                    <th>Time</th>
                                                    <th style="width:70px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="awbBody" class="p-0 m-0">
                                                <tr id="emptyRow">
                                                    <td colspan="9" class="text-center text-muted p-0 m-0 py-1">
                                                        Scan AWBs to start
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

            <!-- E-Waybill Modal -->
            <div class="modal fade" id="ewaybillModal" tabindex="-1" aria-labelledby="ewaybillModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white py-2">
                            <h5 class="modal-title fs-15 text-white" id="ewaybillModalLabel">
                                <i class="ti ti-alert-triangle me-1"></i> E-Waybill Required
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="ewaybillModalMsg" class="text-danger fs-14 fw-medium mb-3"></p>
                            <label class="form-label fs-13">Enter E-Waybill Number</label>
                            <input type="text" id="modalEwaybillInput" class="form-control"
                                placeholder="E-Waybill No..." autofocus>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-danger" id="btnSubmitEwaybill">Submit &
                                Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script>
                $(function () {
                    let tagId = null;
                    let tagNo = null;
                    const scanned = {};
                    let pendingAwbForEwaybill = null;

                    // ── Scan Sounds (Web Audio API) ────────────────────────────────
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    let audioCtx = null;
                    function getCtx() {
                        if (!audioCtx) audioCtx = new AudioCtx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        return audioCtx;
                    }
                    function playTone(freq, type, startDelay, duration, volume) {
                        try {
                            const ctx = getCtx();
                            const t = ctx.currentTime + startDelay;
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = type || 'sine';
                            osc.frequency.setValueAtTime(freq, t);
                            gain.gain.setValueAtTime(volume || 0.4, t);
                            gain.gain.linearRampToValueAtTime(0, t + duration);
                            osc.start(t);
                            osc.stop(t + duration + 0.01);
                        } catch (e) { }
                    }
                    function beepSuccess() {
                        // Single clean high beep — like a barcode scanner
                        playTone(1400, 'sine', 0, 0.12, 0.5);
                    }
                    function beepError() {
                        // Two descending harsh tones — unmistakable error
                        playTone(500, 'square', 0, 0.15, 0.5);
                        playTone(300, 'square', 0.18, 0.20, 0.5);
                    }
                    function beepDuplicate() {
                        // Two short mid-tone beeps — "already done"
                        playTone(900, 'sine', 0, 0.08, 0.35);
                        playTone(900, 'sine', 0.12, 0.08, 0.35);
                    }
                    // Warm up audio on first keypress to avoid browser autoplay block
                    $(document).one('keydown', function () { getCtx(); });

                    const statusColors = {
                        packed: 'warning', hold: 'danger',
                        partially_verified: 'info', fully_verified: 'success'
                    };
                    const statusLabels = {
                        packed: 'Packed', hold: 'Hold',
                        partially_verified: 'Partially Verified', fully_verified: 'Verified'
                    };

                    // Load Branches
                    $.get('api/branch/read.php?length=-1', res => {
                        if (res.data) {
                            let opts = '<option value="">-- Select Branch --</option>';
                            res.data.forEach(b => {
                                opts += `<option value="${b.id}">${b.branch_name}</option>`;
                            });
                            $('.branch-select').html(opts);

                            // Initialize select2
                            $('.branch-select').select2({
                                width: '100%',
                                placeholder: "-- Select Branch --",
                                allowClear: true
                            });

                            // Apply initial values if tag was already loaded
                            if (window.initFromBranch) {
                                $('#fromBranch').val(window.initFromBranch).trigger('change.select2');
                            }
                            if (window.initToBranch) {
                                $('#toBranch').val(window.initToBranch).trigger('change.select2');
                            }
                        }
                    });

                    // ── 1. Auto-create tag on page load ──────────────────────────────
                    // If ?id= passed, load existing tag; else create new one
                    const urlId = new URLSearchParams(location.search).get('id');
                    if (urlId) {
                        loadExistingTag(urlId);
                    } else {
                        createTag();
                    }

                    function createTag() {
                        $('#tagNoDisplay').text('Creating...');
                        $.post('api/tag/create.php', {}, res => {
                            if (res.status === 'success') {
                                tagId = res.tag_id;
                                tagNo = res.tag_no;
                                $('#tagNoDisplay').text(tagNo);
                                $('#tagNoInput').val(tagNo);
                                $('#btnPrintTag').show();
                                history.replaceState(null, '', '?id=' + tagId);
                                $('#awbInput').focus();
                            } else {
                                $('#tagNoDisplay').text('Error: ' + res.message);
                            }
                        });
                    }

                    function loadExistingTag(id) {
                        $.get('api/tag/readone.php?id=' + id, res => {
                            if (res.status === 'success') {
                                tagId = res.data.id;
                                tagNo = res.data.tag_no;
                                $('#tagNoDisplay').text(tagNo);
                                $('#tagNoInput').val(tagNo);
                                $('#btnPrintTag').show();

                                // Set initial branches and cache them
                                window.initFromBranch = res.data.from_branch;
                                window.initToBranch = res.data.to_branch;
                                $('#fromBranch').val(res.data.from_branch || '').trigger('change.select2');
                                $('#toBranch').val(res.data.to_branch || '').trigger('change.select2');

                                updateTagStatusUI(res.data.status);
                                $('#tagStatusSelect').val(res.data.status);
                                if (Array.isArray(res.data.json_data)) {
                                    res.data.json_data.forEach(entry => appendRow(entry));
                                    updateCounters();
                                }
                                $('#awbInput').focus();
                            } else {
                                createTag(); // fallback
                            }
                        });
                    }

                    // ── Save Tag No manually ─────────────────────────────────────────
                    function saveTagNo() {
                        const newNo = $('#tagNoInput').val().trim();
                        if (!newNo || !tagId) return;
                        $.post('api/tag/update_tag_no.php', { tag_id: tagId, tag_no: newNo }, res => {
                            if (res.status === 'success') {
                                tagNo = newNo;
                                $('#tagNoDisplay').text(newNo);
                                $('#btnSaveTagNo').text('✓').removeClass('btn-primary').addClass('btn-outline-secondary');
                            } else {
                                alert('Failed: ' + res.message);
                            }
                        });
                    }
                    $('#btnSaveTagNo').on('click', saveTagNo);
                    $('#tagNoInput').on('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); saveTagNo(); $('#awbInput').focus(); }
                    });
                    $('#tagNoInput').on('input', function () {
                        $('#btnSaveTagNo').text('Save').removeClass('btn-outline-secondary').addClass('btn-primary');
                    });

                    // ── 2. Scan on Enter ──────────────────────────────────────────────
                    $('#awbInput').on('keydown', function (e) {
                        if (e.key === 'Enter') { e.preventDefault(); doScan(); }
                    });

                    function doScan() {
                        if (!tagId) { alert('Tag not ready yet'); return; }
                        const awb = $('#awbInput').val().trim();
                        if (!awb) return;
                        if (scanned[awb]) {
                            beepDuplicate();
                            flashLastScan('⚠ Already scanned: ' + awb, 'warning');
                            $('#awbInput').val('').focus();
                            return;
                        }
                        const remarks = ''; // Default empty

                        $.post('api/tag/scan.php', { tag_id: tagId, awb_no: awb, remarks: '' }, res => {
                            if (res.status === 'success') {
                                appendRow(res.entry);
                                updateCounters();
                                updateTagStatusUI(res.tag_status);
                                $('#tagStatusSelect').val(res.tag_status);
                                beepSuccess();
                                flashLastScan('✓ ' + awb, 'success');
                            } else if (res.status === 'require_ewaybill') {
                                beepError();
                                pendingAwbForEwaybill = awb;
                                $('#ewaybillModalMsg').text(res.message);
                                $('#modalEwaybillInput').val('');
                                const ewayModal = new bootstrap.Modal(document.getElementById('ewaybillModal'));
                                ewayModal.show();

                                // Focus input when modal is shown
                                document.getElementById('ewaybillModal').addEventListener('shown.bs.modal', function () {
                                    document.getElementById('modalEwaybillInput').focus();
                                }, { once: true });

                                // Handle modal close to show cancellation
                                document.getElementById('ewaybillModal').addEventListener('hidden.bs.modal', function () {
                                    if (pendingAwbForEwaybill !== null) {
                                        flashLastScan('✗ Cancelled: E-Waybill required for ' + pendingAwbForEwaybill, 'danger');
                                        pendingAwbForEwaybill = null;
                                        $('#awbInput').val('').focus();
                                    }
                                }, { once: true });
                            } else {
                                beepError();
                                flashLastScan('✗ ' + res.message, 'danger');
                            }
                            $('#awbInput').val('').focus();
                        });
                    }

                    // ── E-Waybill Modal Submit Handle ─────────────────────────────────
                    $('#btnSubmitEwaybill').click(function () {
                        submitEwaybillModal();
                    });

                    $('#modalEwaybillInput').keydown(function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            submitEwaybillModal();
                        }
                    });

                    function submitEwaybillModal() {
                        const eway = $('#modalEwaybillInput').val().trim();
                        if (!eway) {
                            alert('E-Waybill Number is required.');
                            $('#modalEwaybillInput').focus();
                            return;
                        }

                        if (!pendingAwbForEwaybill) return;
                        const awbToProcess = pendingAwbForEwaybill;

                        // Prevent modal close handler from flashing cancelled message
                        pendingAwbForEwaybill = null;

                        // Hide modal
                        bootstrap.Modal.getInstance(document.getElementById('ewaybillModal')).hide();

                        $.post('api/tag/scan.php', { tag_id: tagId, awb_no: awbToProcess, remarks: '', eway_bill_no: eway }, retryRes => {
                            if (retryRes.status === 'success') {
                                appendRow(retryRes.entry);
                                updateCounters();
                                updateTagStatusUI(retryRes.tag_status);
                                $('#tagStatusSelect').val(retryRes.tag_status);
                                beepSuccess();
                                flashLastScan('✓ ' + awbToProcess + ' (E-Waybill added)', 'success');
                            } else {
                                beepError();
                                flashLastScan('✗ ' + retryRes.message, 'danger');
                            }
                            $('#awbInput').val('').focus();
                        });
                    }

                    // ── 3. Append row to table ────────────────────────────────────────
                    function appendRow(entry) {
                        scanned[entry.awb_no] = entry;
                        $('#emptyRow').remove();
                        const rowClass = entry.status === 'hold' ? 'awb-row-hold' : 'awb-row-verified';
                        const idx = Object.keys(scanned).length;
                        const time = entry.timestamp ? new Date(entry.timestamp).toLocaleTimeString('en-IN') : '';
                        const uid = 'bc_' + entry.awb_no.replace(/[^a-zA-Z0-9]/g, '_');
                        const ewaybillDisplay = entry.ewaybill_no ? `<span class="badge bg-secondary text-white fs-10" style="letter-spacing:0.3px;">${entry.ewaybill_no}</span>` : '<span class="text-muted fs-11">-</span>';

                        const tr = `<tr class="${rowClass}" data-awb="${entry.awb_no}">
                    <td class="text-muted fs-12">${idx}</td>
                    <td><strong style="font-size:12px;">${entry.awb_no}</strong></td>
                    <td>${entry.consignee_name || ''}</td>
                    <td>${entry.consignee_city || ''}</td>
                    <td>${ewaybillDisplay}</td>
                    <td>
                        <span class="badge bg-success awb-status-badge">Packed</span>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm awb-remark-input" data-awb="${entry.awb_no}" value="${entry.remarks || ''}" placeholder="..." style="height: 24px; padding: 2px 6px; font-size: 11px;">
                    </td>
                    <td class="fs-11 text-muted">${time}</td>
                    <td>
                        <button class="btn btn-xs btn-danger btn-delete-scanned" data-awb="${entry.awb_no}" style="font-size:11px;padding:2px 8px;">
                            Delete
                        </button>
                    </td>
                </tr>`;
                        $('#awbBody').prepend(tr);
                        $('#tableCount').text(Object.keys(scanned).length + ' items');
                    }

                    // ── Delete scanned AWB ─────────────────────────────────────────
                    $('#awbBody').on('click', '.btn-delete-scanned', function () {
                        const awb = $(this).data('awb');
                        if (!confirm('Remove AWB ' + awb + ' from this tag?')) return;

                        $.post('api/tag/delete_scan.php', { tag_id: tagId, awb_no: awb }, res => {
                            if (res.status === 'success') {
                                $(`tr[data-awb="${awb}"]`).remove();
                                delete scanned[awb];
                                updateCounters();
                                updateTagStatusUI(res.tag_status);
                                $('#tagStatusSelect').val(res.tag_status);
                                if (Object.keys(scanned).length === 0) {
                                    $('#awbBody').append(`<tr id="emptyRow">
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        Scan AWBs to start
                                                    </td>
                                                </tr>`);
                                    $('#tableCount').text('0 items');
                                } else {
                                    $('#tableCount').text(Object.keys(scanned).length + ' items');
                                }
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });
                    // ── Auto-save AWB Remarks ─────────────────────────────────────────
                    $('#awbBody').on('change', '.awb-remark-input', function () {
                        const awb = $(this).data('awb');
                        const remark = $(this).val().trim();
                        $.post('api/tag/update_scan_remark.php', { tag_id: tagId, awb_no: awb, remarks: remark }, res => {
                            if (res.status === 'success') {
                                scanned[awb].remarks = remark;
                            } else {
                                alert('Failed to update remark: ' + res.message);
                            }
                        });
                    });

                    // ── 5. Verify All ─────────────────────────────────────────────────
                    $('#btnVerifyAll').click(function () {
                        const selectedStatus = $('#tagStatusSelect').val();
                        const statusLabel = $("#tagStatusSelect option:selected").text();
                        const total = Object.values(scanned).length;

                        const msg = `Save Tag: ${tagNo}\n\n` +
                            `Status: ${statusLabel}\n` +
                            `Total AWBs: ${total}\n\n` +
                            `Are you sure you want to save this tag with status '${statusLabel}'?`;

                        if (!confirm(msg)) return;

                        $.post('api/tag/update_status.php', { tag_id: tagId, status: selectedStatus }, res => {
                            if (res.status === 'success') {
                                updateTagStatusUI(selectedStatus);
                                alert('Tag successfully saved!');
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });

                    // ── Auto Save Branches ───────────────────────────────────────────
                    $('.branch-select').change(function () {
                        if (!tagId) return;
                        $.post('api/tag/update_branch.php', {
                            id: tagId,
                            from_branch: $('#fromBranch').val(),
                            to_branch: $('#toBranch').val()
                        });
                    });

                    // ── Print Tag ─────────────────────────────────────────────────────
                    $('#btnPrintTag').click(function () {
                        if (!tagId) return;
                        window.open('tag-print.php?id=' + tagId, '_blank');
                    });

                    // ── Helpers ───────────────────────────────────────────────────────
                    function updateCounters() {
                        const total = Object.keys(scanned).length;
                        $('#cntTotal').text(total);
                        $('#btnVerifyAll').prop('disabled', total === 0);
                    }

                    function updateTagStatusUI(status) {
                        const label = statusLabels[status] || status;
                        const color = statusColors[status] || 'secondary';
                        const badge = $('#tagStatusBadge');
                        badge.attr('class', `status-pill bg-${color} ${color === 'warning' ? 'text-dark' : 'text-white'}`).text(label);
                    }

                    function flashLastScan(msg, type) {
                        const colors = { success: '#d1fae5', danger: '#fee2e2', warning: '#fef9c3' };
                        $('#lastScanInfo')
                            .text(msg)
                            .css('color', type === 'success' ? '#065f46' : type === 'danger' ? '#991b1b' : '#92400e')
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