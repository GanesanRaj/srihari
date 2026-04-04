<?php include 'header.php'; ?>
<?php if ( ! defined ( 'MIDDLEWARE_INCLUDED' )) {
    require_once __DIR__ . '/config/middleware.php';
    }
require_permission ( 'whms_shipment', 'is_view' ); ?>
<?php
// Detect client-type user (handles NULL user_type with clientaccess=1)
$isClientUser   = false;
$accessBranches = [];
$accessClients  = [];
if (($_SESSION[ 'user_type' ] ?? '') === 'client') {
    $isClientUser = true;
    } elseif (isset ($_SESSION[ 'username' ])) {
    $chk = $pdo->prepare ( "SELECT clientaccess FROM tbl_user WHERE username = ? LIMIT 1" );
    $chk->execute ( [ $_SESSION[ 'username' ] ] );
    $chkRow = $chk->fetch ( PDO::FETCH_ASSOC );
    if ($chkRow && $chkRow[ 'clientaccess' ] == 1)
        $isClientUser = true;
    }
if ($isClientUser) {
    $uRow = $pdo->prepare ( "SELECT branch_ids, client_ids FROM tbl_user WHERE username = ? AND clientaccess = 1 LIMIT 1" );
    $uRow->execute ( [ $_SESSION[ 'username' ] ?? '' ] );
    $uData = $uRow->fetch ( PDO::FETCH_ASSOC );

    $rawB = $uData[ 'branch_ids' ] ?? '';
    $bIds = $rawB !== '' ? array_filter ( array_map ( 'intval', explode ( ',', $rawB ) ) ) : [];
    if ( ! empty ($bIds)) {
        $phs  = implode ( ',', array_fill ( 0, count ( $bIds ), '?' ) );
        $stmt = $pdo->prepare ( "SELECT branch_name FROM tbl_branch WHERE id IN ($phs) ORDER BY branch_name" );
        $stmt->execute ( array_values ( $bIds ) );
        $accessBranches = $stmt->fetchAll ( PDO::FETCH_COLUMN );
        }

    $rawC = $uData[ 'client_ids' ] ?? '';
    $cIds = $rawC !== '' ? array_filter ( array_map ( 'intval', explode ( ',', $rawC ) ) ) : [];
    if ( ! empty ($cIds)) {
        $phs  = implode ( ',', array_fill ( 0, count ( $cIds ), '?' ) );
        $stmt = $pdo->prepare ( "SELECT client_name FROM tbl_client WHERE id IN ($phs) ORDER BY client_name" );
        $stmt->execute ( array_values ( $cIds ) );
        $accessClients = $stmt->fetchAll ( PDO::FETCH_COLUMN );
        }
    }

// Superadmin check (role_id = 1)
$isSuperAdmin = ((int) ($_SESSION[ 'role_id' ] ?? 0) === 1);
?>
<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<style>
    .highlight-red {
        background-color: #ffcccc !important;
    }

    /* ── Delete toolbar ── */
    #bulkDeleteBar {
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

    #bulkDeleteBar.show {
        display: flex;
    }

    /* Checkbox column */
    #bulkJobsTable th:first-child,
    #bulkJobsTable td:first-child {
        width: 36px;
        text-align: center;
    }

    .row-cb {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #dc3545;
    }

    #selectAllCb {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #dc3545;
    }

    #liveUploadModal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    #liveUploadModal.show {
        display: flex;
    }

    #liveUploadModal .live-upload-card {
        width: min(520px, 92vw);
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 20px;
    }
</style>
<div class="wrapper">
    <?php require_once 'sidebar.php'; ?>
    <?php require_once 'topbar.php'; ?>

    <div class="content-page">
        <div class="content">

            <!-- Start Content-->
            <div class="px-0">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Bulk Shipment Upload</h4>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h4 class="card-title">Upload Excel/CSV</h4>
                                <div class="d-flex gap-1">
                                    <a href="api/shipment/download_template.php" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-download"></i> Download Template
                                    </a>
                                    <a href="api/branch/download_codes.php" class="btn btn-outline-secondary btn-sm"
                                        download>
                                        <i class="ti ti-download"></i> Branch Codes (Excel)
                                    </a>
                                </div>
                            </div>
                            <?php if ($isClientUser) : ?>
                                <div class="card-body pb-1 border-bottom">
                                    <p class="fs-12 text-muted mb-1">
                                        You are only allowed to upload shipments for:
                                    </p>
                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                        <span class="fs-11 fw-semibold text-muted me-1"><i class="ti ti-map-pin fs-11"></i>
                                            Branches:</span>
                                        <?php if (empty ($accessBranches)) : ?>
                                            <span class="badge badge-soft-secondary fs-11">All Branches</span>
                                        <?php else :
                                            foreach ($accessBranches as $bn) : ?>
                                                <span
                                                    class="badge badge-soft-primary fs-11"><?php echo htmlspecialchars ( $bn ); ?></span>
                                            <?php endforeach; endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="fs-11 fw-semibold text-muted me-1"><i class="ti ti-user fs-11"></i>
                                            Clients:</span>
                                        <?php if (empty ($accessClients)) : ?>
                                            <span class="badge badge-soft-secondary fs-11">All Clients</span>
                                        <?php else :
                                            foreach ($accessClients as $cn) : ?>
                                                <span
                                                    class="badge badge-soft-success fs-11"><?php echo htmlspecialchars ( $cn ); ?></span>
                                            <?php endforeach; endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <form id="uploadForm" enctype="multipart/form-data">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label class="form-label">Select File (CSV/Excel)</label>
                                            <input type="file" class="form-control" name="bulk_file" required
                                                accept=".csv, .xlsx, .xls">
                                            <small class="text-muted">Ensure required columns are filled as per
                                                template.</small>
                                            <div class="mt-1 fs-11 text-muted">
                                                Note: Template includes optional columns for Shiprocket/Delhivery flows
                                                (Pickup Point, Shiprocket Courier Company ID, Shiprocket Sub Courier).
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary w-100" id="btnUpload">
                                                <i class="ti ti-upload"></i> Upload
                                            </button>
                                        </div>
                                    </div>
                                    <div id="uploadProgress" class="mt-3 d-none">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" id="uploadProgressBar" style="width: 0%">0%</div>
                                        </div>
                                        <div class="mt-1 small text-muted" id="uploadProgressText">0 completed / 0 total</div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Upload History</h4>
                            </div>
                            <div class="card-body">

                                <?php if ($isSuperAdmin) : ?>
                                    <!-- Multi-delete toolbar (superadmin only) -->
                                    <div id="bulkDeleteBar">
                                        <i class="ti ti-trash text-danger fs-18"></i>
                                        <span id="selectedCount" class="fw-semibold">0</span> job(s) selected
                                        <button id="btnMultiDelete" class="btn btn-danger btn-sm ms-2">
                                            <i class="ti ti-trash me-1"></i> Delete Selected
                                        </button>
                                        <button id="btnCancelSelect" class="btn btn-secondary btn-sm">
                                            Cancel
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="bulkJobsTable" class="table table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <?php if ($isSuperAdmin) : ?>
                                                    <th><input type="checkbox" id="selectAllCb" title="Select All"></th>
                                                <?php endif; ?>
                                                <th>Job ID</th>
                                                <th>Date</th>
                                                <th>Original File</th>
                                                <th>Created By</th>
                                                <th>Branch &amp; Client</th>
                                                <th>Total</th>
                                                <th>Success</th>
                                                <th>Failed</th>
                                                <th>Status</th>
                                                <th>Result File</th>
                                                <?php if ($isSuperAdmin) : ?>
                                                    <th>Action</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Loaded via AJAX -->
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
</div>
<div id="liveUploadModal" aria-hidden="true">
    <div class="live-upload-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Bulk Upload Progress</h5>
            <span class="badge bg-warning text-dark" id="uploadModalStatus">Processing</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="uploadModalBar" style="width:0%">0%</div>
        </div>
        <div class="mt-2 fw-semibold" id="uploadModalCount">0 completed / 0 total</div>
        <div class="mt-1 text-muted small" id="uploadModalHint">Preparing upload...</div>
    </div>
</div>

<!-- Vendors JS -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/datatables/dataTables.min.js"></script>
<script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
<script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
<?php include 'footer.php'; ?>

<script>
    const isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;

    $(document).ready(function () {

        // ── Column definitions ───────────────────────────────────────────────
        var columns = [];

        if (isSuperAdmin) {
            columns.push({
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `<input type="checkbox" class="row-cb" value="${id}">`;
                }
            });
        }

        // Common columns
        columns = columns.concat([
            { data: 'id' },
            { data: 'created_at', render: function (v) { return v ? v.replace(/^(\d{4}-\d{2}-\d{2})\s.*/, '$1') : '-'; } },
            { data: 'filename' },
            { data: 'created_by_name', defaultContent: '—', render: function (v) { return v || '—'; } },
            {
                data: 'branch_name',
                defaultContent: '—',
                render: function (d, type, row) {
                    var parts = [];
                    if (row.branch_name) parts.push('<span class="badge badge-soft-primary fs-11">' + row.branch_name + '</span>');
                    if (row.client_name) parts.push('<span class="badge badge-soft-success fs-11">' + row.client_name + '</span>');
                    return parts.length ? parts.join(' ') : '—';
                }
            },
            { data: 'total_records' },
            { data: 'success_count', render: function (d) { return `<span class="text-success fw-bold">${d}</span>`; } },
            { data: 'failure_count', render: function (d) { return `<span class="text-danger fw-bold">${d}</span>`; } },
            {
                data: 'status',
                render: function (d) {
                    let cls = 'bg-secondary';
                    if (d === 'Completed') cls = 'bg-success';
                    if (d === 'Processing') cls = 'bg-warning text-dark';
                    if (d === 'Completed with Errors') cls = 'bg-warning text-dark';
                    if (d === 'Failed') cls = 'bg-danger';
                    return `<span class="badge ${cls}">${d}</span>`;
                }
            },
            {
                data: 'id',
                render: function (id, type, row) {
                    let html = '';
                    var showLabels = (row.success_count > 0);
                    if (showLabels) {
                        html += `<a href="shipment-bulk-print.php?job_id=${id}" class="btn btn-sm btn-warning" target="_blank">
                            <i class="ti ti-printer"></i> Print Labels
                        </a> `;
                    }
                    if (row.result_file) html += `<a href="api/shipment/export_result.php?id=${id}" class="btn btn-sm btn-info" download>Download Result</a>`;
                    return html || '-';
                }
            }
        ]);

        if (isSuperAdmin) {
            // Action column with single delete button
            columns.push({
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `<button class="btn btn-sm btn-danger btn-single-delete" data-id="${id}" title="Delete this job">
                                <i class="ti ti-trash"></i> Delete
                            </button>`;
                }
            });
        }

        // ── DataTable init ───────────────────────────────────────────────────
        var table = $('#bulkJobsTable').DataTable({
            serverSide: true,
            ajax: 'api/shipment/bulk_jobs_list.php',
            columns: columns,
            order: [[isSuperAdmin ? 1 : 0, 'desc']],
            drawCallback: function () {
                updateToolbar();
            }
        });

        // ── Upload form ──────────────────────────────────────────────────────
        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);

            $('#btnUpload').prop('disabled', true);
            $('#uploadProgress').removeClass('d-none');
            $('#uploadProgressBar').css('width', '0%').text('0%');
            $('#uploadProgressText').text('0 completed / 0 total');
            $('#liveUploadModal').addClass('show').attr('aria-hidden', 'false');
            $('#uploadModalStatus').removeClass('bg-danger bg-success').addClass('bg-warning text-dark').text('Processing');
            $('#uploadModalHint').text('Uploading file...');
            $('#uploadModalBar').css('width', '0%').text('0%');
            $('#uploadModalCount').text('0 completed / 0 total');

            var xhr = new XMLHttpRequest();
            var lastHandledLength = 0;
            var streamBuffer = '';
            var finalPayload = null;
            var hasError = false;
            var liveJobId = null;
            var pollTimer = null;
            var discoverTimer = null;

            function renderProgress(completed, total) {
                var safeTotal = Math.max(0, parseInt(total || 0, 10));
                var safeCompleted = Math.max(0, parseInt(completed || 0, 10));
                var pct = safeTotal > 0 ? Math.round((safeCompleted / safeTotal) * 100) : 0;
                $('#uploadProgressBar').css('width', pct + '%').text(pct + '%');
                $('#uploadProgressText').text(safeCompleted + ' completed / ' + safeTotal + ' total');
                $('#uploadModalBar').css('width', pct + '%').text(pct + '%');
                $('#uploadModalCount').text(safeCompleted + ' completed / ' + safeTotal + ' total');
                $('#uploadModalHint').text('Processing rows...');
            }

            function startProgressPolling(jobId) {
                if (!jobId || pollTimer) return;
                liveJobId = jobId;
                pollTimer = setInterval(function () {
                    $.getJSON('api/shipment/bulk_jobs_list.php', { job_id: jobId })
                        .done(function (resp) {
                            var row = resp && resp.data ? resp.data : null;
                            if (!row) return;
                            var completed = parseInt(row.success_count || 0, 10) + parseInt(row.failure_count || 0, 10);
                            var total = parseInt(row.total_records || 0, 10);
                            renderProgress(completed, total);
                        });
                }, 1000);
            }

            function startJobDiscoveryPolling() {
                if (discoverTimer) return;
                discoverTimer = setInterval(function () {
                    if (liveJobId) return;
                    $.getJSON('api/shipment/bulk_jobs_list.php', { latest_processing: 1 })
                        .done(function (resp) {
                            var row = resp && resp.data ? resp.data : null;
                            if (row && row.id) {
                                liveJobId = parseInt(row.id, 10);
                                var completed = parseInt(row.success_count || 0, 10) + parseInt(row.failure_count || 0, 10);
                                var total = parseInt(row.total_records || 0, 10);
                                renderProgress(completed, total);
                                startProgressPolling(liveJobId);
                            }
                        });
                }, 1000);
            }

            function stopProgressPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
                if (discoverTimer) {
                    clearInterval(discoverTimer);
                    discoverTimer = null;
                }
            }

            function closeLiveModal(status, hint) {
                $('#uploadModalStatus')
                    .removeClass('bg-warning bg-danger text-dark')
                    .addClass(status === 'success' ? 'bg-success' : 'bg-danger')
                    .text(status === 'success' ? 'Completed' : 'Failed');
                if (hint) $('#uploadModalHint').text(hint);
                setTimeout(function () {
                    $('#liveUploadModal').removeClass('show').attr('aria-hidden', 'true');
                }, 800);
            }

            function handleChunk(chunkText) {
                streamBuffer += chunkText;
                var lines = streamBuffer.split(/\r?\n/);
                streamBuffer = lines.pop() || '';
                for (var i = 0; i < lines.length; i++) {
                    var line = lines[i].trim();
                    if (!line || line.indexOf('EVENT:') !== 0) continue;

                    var parts = line.split(' ');
                    var eventName = parts.shift().replace('EVENT:', '');
                    var payloadText = parts.join(' ').trim();
                    var payload = {};
                    if (payloadText) {
                        try { payload = JSON.parse(payloadText); } catch (e) { payload = {}; }
                    }

                    if (eventName === 'JOB_CREATED') {
                        liveJobId = payload.job_id || liveJobId;
                        startProgressPolling(liveJobId);
                        renderProgress(0, payload.total || 0);
                    } else if (eventName === 'PROGRESS') {
                        renderProgress(payload.completed || 0, payload.total || 0);
                    } else if (eventName === 'COMPLETE') {
                        finalPayload = payload;
                        renderProgress(payload.total || 0, payload.total || 0);
                    } else if (eventName === 'ERROR') {
                        hasError = true;
                        finalPayload = { status: 'error', message: payload.message || 'Upload failed.' };
                    }
                }
            }

            xhr.open('POST', 'api/shipment/bulk_upload.php?stream=1', true);
            startJobDiscoveryPolling();
            xhr.upload.onprogress = function (evt) {
                if (evt.lengthComputable && evt.total > 0) {
                    $('#uploadProgressText').text('Uploading file... ' + Math.round((evt.loaded / evt.total) * 100) + '%');
                }
            };
            xhr.onprogress = function () {
                var text = xhr.responseText || '';
                var chunk = text.slice(lastHandledLength);
                lastHandledLength = text.length;
                handleChunk(chunk);
            };
            xhr.onerror = function () {
                stopProgressPolling();
                $('#btnUpload').prop('disabled', false);
                $('#uploadProgress').addClass('d-none');
                closeLiveModal('error', 'Server error during upload.');
                toastError('Upload failed due to server error.');
            };
            xhr.onload = function () {
                var text = xhr.responseText || '';
                var chunk = text.slice(lastHandledLength);
                handleChunk(chunk);
                if (streamBuffer.trim()) {
                    handleChunk('\n');
                }
                stopProgressPolling();

                $('#btnUpload').prop('disabled', false);
                $('#uploadProgress').addClass('d-none');

                if (!hasError && finalPayload && finalPayload.status === 'success') {
                    closeLiveModal('success', 'Bulk upload completed successfully.');
                    toastSuccess('Upload Success! ' + finalPayload.message);
                    $('#uploadForm')[0].reset();
                    table.ajax.reload();
                } else {
                    var errMsg = (finalPayload && finalPayload.message) ? finalPayload.message : 'Upload failed.';
                    closeLiveModal('error', errMsg);
                    toastError(errMsg);
                }
            };
            xhr.send(formData);
        });

        // Auto-submit the form when a file is selected
        $('input[name="bulk_file"]').on('change', function () {
            if (this.files && this.files.length > 0) {
                $('#uploadForm').trigger('submit');
            }
        });

        if (!isSuperAdmin) return; // rest is admin-only

        // ── Select-all checkbox ──────────────────────────────────────────────
        $('#bulkJobsTable').on('change', '#selectAllCb', function () {
            var checked = $(this).prop('checked');
            $('#bulkJobsTable tbody .row-cb').prop('checked', checked);
            updateToolbar();
        });

        // ── Individual row checkbox ──────────────────────────────────────────
        $('#bulkJobsTable').on('change', '.row-cb', function () {
            var total = $('#bulkJobsTable tbody .row-cb').length;
            var checked = $('#bulkJobsTable tbody .row-cb:checked').length;
            $('#selectAllCb').prop('indeterminate', checked > 0 && checked < total);
            $('#selectAllCb').prop('checked', checked === total && total > 0);
            updateToolbar();
        });

        function updateToolbar() {
            var checked = $('#bulkJobsTable tbody .row-cb:checked').length;
            if (checked > 0) {
                $('#selectedCount').text(checked);
                $('#bulkDeleteBar').addClass('show');
            } else {
                $('#bulkDeleteBar').removeClass('show');
            }
        }

        // ── Cancel selection ─────────────────────────────────────────────────
        $('#btnCancelSelect').on('click', function () {
            $('#bulkJobsTable tbody .row-cb').prop('checked', false);
            $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
            updateToolbar();
        });

        // ── Single delete ────────────────────────────────────────────────────
        $('#bulkJobsTable').on('click', '.btn-single-delete', function () {
            var jobId = $(this).data('id');
            confirmAndDelete([jobId]);
        });

        // ── Multi delete ─────────────────────────────────────────────────────
        $('#btnMultiDelete').on('click', function () {
            var ids = [];
            $('#bulkJobsTable tbody .row-cb:checked').each(function () {
                ids.push($(this).val());
            });
            if (ids.length === 0) return;
            confirmAndDelete(ids);
        });

        // ── Delete logic ─────────────────────────────────────────────────────
        function confirmAndDelete(ids) {
            var count = ids.length;
            var msg = count === 1
                ? 'Are you sure you want to delete this job?\n\nThis will permanently delete the booking(s) created by this job and cancel their serial allocation.'
                : `Are you sure you want to delete ${count} jobs?\n\nThis will permanently delete all bookings and cancel serial allocations.`;

            if (!confirm(msg)) return;

            $.ajax({
                url: 'api/shipment/bulk_job_delete.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: ids }),
                success: function (res) {
                    if (res.status === 'success') {
                        toastSuccess(res.message || 'Deleted successfully.');
                        $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
                        updateToolbar();
                        table.ajax.reload(null, false);
                    } else {
                        toastError(res.message || 'Delete failed.');
                    }
                },
                error: function (xhr) {
                    var resp = {};
                    try { resp = JSON.parse(xhr.responseText); } catch (e) { }
                    toastError(resp.message || xhr.statusText || 'Server error');
                }
            });
        }

        // ── Simple toast helper ───────────────────────────────────────────────
        function toastSuccess(msg) {
            var $toast = $(`
                <div style="
                    position:fixed;bottom:24px;right:24px;z-index:9999;
                    background:#198754;color:#fff;padding:12px 20px;
                    border-radius:8px;font-size:13px;
                    box-shadow:0 4px 12px rgba(0,0,0,.2);
                    animation:fadeInUp .3s ease;
                ">
                    <i class="ti ti-circle-check me-1"></i>${msg}
                </div>
            `);
            $('body').append($toast);
            setTimeout(function () { $toast.fadeOut(400, function () { $(this).remove(); }); }, 3500);
        }

        function toastError(msg) {
            var $toast = $(`
                <div style="
                    position:fixed;bottom:24px;right:24px;z-index:9999;
                    background:#dc3545;color:#fff;padding:12px 20px;
                    border-radius:8px;font-size:13px;
                    box-shadow:0 4px 12px rgba(0,0,0,.2);
                    animation:fadeInUp .3s ease;
                ">
                    <i class="ti ti-alert-circle me-1"></i>${msg}
                </div>
            `);
            $('body').append($toast);
            setTimeout(function () { $toast.fadeOut(400, function () { $(this).remove(); }); }, 4000);
        }
    });
</script>