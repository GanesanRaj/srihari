<?php include 'header.php'; ?>
<?php // require_permission('pickup', 'is_update'); ?>

<style>
    .col-form-label {
        padding-bottom: 2px !important;
        padding-top: 2px !important;
        margin-bottom: 2px !important;
    }

    .form-control,
    .form-select {
        padding: 5px !important;
    }

    #statusTable,
    #statusTable * {
        color: #000000 !important;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.pending {
        background-color: #fff3e0;
        color: #F57C00 !important;
    }

    .status-badge.not-picked {
        background-color: #fce4ec;
        color: #C2185B !important;
    }

    .status-badge.manifested {
        background-color: #f3e5f5;
        color: #7B1FA2 !important;
    }

    .status-badge.in-transit {
        background-color: #e3f2fd;
        color: #1976D2 !important;
    }

    .status-badge.out-for-delivery {
        background-color: #e0f2f1;
        color: #00796B !important;
    }

    .status-badge.delivered {
        background-color: #e8f5e9;
        color: #388E3C !important;
    }

    .status-badge.lost {
        background-color: #ffebee;
        color: #C62828 !important;
    }

    .status-badge.rto {
        background-color: #fff8e1;
        color: #F9A825 !important;
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

    #scannedTable td,
    #scannedTable th {
        padding: 0 !important;
        vertical-align: middle;
    }

    @keyframes fadeInRow {
        from {
            background-color: #c8e6c9;
            opacity: 0.5;
        }

        to {
            background-color: transparent;
            opacity: 1;
        }
    }

    .shipment-detail-card {
        border-left: 4px solid #007bff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #666;
        width: 30%;
    }

    .detail-value {
        color: #333;
        font-weight: 500;
    }

    .tracking-history-item {
        padding: 15px;
        border-left: 3px solid #ddd;
        margin-bottom: 10px;
        background-color: #f9f9f9;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .tracking-history-item:hover {
        border-left-color: #007bff;
        background-color: #f0f7ff;
    }

    .tracking-time {
        font-size: 12px;
        color: #999;
        display: block;
        margin-bottom: 5px;
    }

    .tracking-status {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .tracking-location {
        font-size: 12px;
        color: #555;
        margin-bottom: 3px;
    }

    .tracking-remarks {
        font-size: 12px;
        color: #777;
        margin-top: 5px;
        font-style: italic;
    }

    .empty-tracking {
        text-align: center;
        padding: 30px;
        color: #999;
        font-style: italic;
    }

    .pod-preview-container {
        max-height: 300px;
        overflow-y: auto;
        border: 2px dashed #ddd;
        border-radius: 4px;
        padding: 15px;
        background-color: #fafafa;
    }

    .pod-image-item {
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 10px;
        background-color: white;
    }

    .pod-image-preview {
        max-width: 100%;
        max-height: 200px;
        border-radius: 3px;
        margin-bottom: 8px;
    }

    .pod-image-info {
        font-size: 11px;
        color: #666;
        margin-top: 5px;
    }

    .pod-image-remove {
        font-size: 10px;
        color: #d32f2f;
        cursor: pointer;
        text-decoration: underline;
    }

    /* Select2 dropdown fixes */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: #888 transparent transparent transparent;
        border-width: 5px 4px 0 4px;
    }

    .select2-dropdown {
        max-height: none !important;
        min-width: 100% !important;
    }

    .select2-results {
        max-height: 300px;
        overflow-y: auto;
    }

    .select2-results__option {
        padding: 8px 12px;
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
                                        <input type="text" id="searchInput"
                                            class="form-control form-control-sm text-center scan-pulse text-dark"
                                            style="width:280px; font-weight:700;"
                                            placeholder="Scan Pickup ID or AWB No... (Enter)" autocomplete="off"
                                            autofocus>
                                        <div id="lastScanInfo" class="position-absolute w-100 text-center fw-bold"
                                            style="font-size:10px; top:100%; color:#666;">
                                            Waiting for scan...
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-dark d-none"
                                        id="btnSearch">Add</button>

                                    <!-- Counters + status -->
                                    <div class="ms-auto d-flex align-items-center gap-3 fs-15 font-monospace">
                                        <span class="text-primary">
                                            <span class="fs-20 fw-bolder" id="updateCountLabel">0</span>
                                            <span class="fs-12"> Pickups</span>
                                        </span>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="ms-2 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#bulkUpdateModal">
                                            <i class="ti ti-upload"></i> Bulk Upload
                                        </button>
                                        <button type="button" id="btnClearAll" class="btn btn-sm btn-outline-dark"
                                            style="display:none;">
                                            <i class="ti ti-trash"></i> Clear All
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
                                    <h6 class="mb-0 fs-13">Update Details</h6>
                                </div>
                                <div class="card-body py-4 text-center text-muted" id="updateStatusSectionEmpty">
                                    <i class="ti ti-info-circle fs-3" style="margin-bottom: 5px;"></i><br>
                                    <small>Scan shipments to enable update form</small>
                                </div>
                                <div class="card-body py-2 px-2" id="updateStatusSection" style="display: none;">
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Update Status <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm select2" id="updateStatus"
                                            data-toggle="select2" required>
                                            <option value="">Choose Status</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Status Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="updateDate"
                                            required>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Location</label>
                                        <input type="text" class="form-control form-control-sm" id="updateLocation"
                                            placeholder="e.g., Delhi Branch">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">Remarks / Instructions</label>
                                        <textarea class="form-control form-control-sm" id="updateRemarks" rows="2"
                                            placeholder="Add any additional notes..."></textarea>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label fs-12 mb-0">POD Images (Optional)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="file" class="form-control" id="podImageInput" accept="image/*"
                                                multiple>
                                            <button type="button" class="btn btn-outline-dark" id="btnAddImages">
                                                <i class="ti ti-photo"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-2" id="podPreviewSection" style="display: none;">
                                        <div class="pod-preview-container" id="podPreviewContainer"
                                            style="padding: 10px;"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary w-100 mt-1" id="btnUpdate">
                                        <i class="ti ti-check me-1"></i> Update All (<span id="updateCount">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Scanned Pickups -->
                        <div class="col-lg-9 ps-0">
                            <div class="card mb-0">
                                <div class="card-header py-1 d-flex align-items-center px-2">
                                    <h6 class="mb-0 fs-13 flex-grow-1">Scanned Pickups</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive m-0 p-0">
                                        <table class="table table-sm table-hover m-0 p-0" id="scannedTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:36px">#</th>
                                                    <th>Pickup / AWB</th>
                                                    <th>Location</th>
                                                    <th>Courier</th>
                                                    <th>Consignor</th>
                                                    <th>Scanned At</th>
                                                    <th>Current Status</th>
                                                    <th style="width:70px">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="shipmentsContainer">
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4"><i
                                                            class="ti ti-scan me-1"
                                                            style="font-size:1.3rem;"></i><br>Scan Pickup ID or AWB
                                                        number to add</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /row -->

                    <!-- Job Result Modal -->
                    <div class="modal fade" id="jobResultModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Bulk Upload Result</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div id="jobSummary" class="p-3 border-bottom bg-light"></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0" id="jobResultTable"
                                            style="font-size: 11px;">
                                            <thead class="bg-dark text-white">
                                                <!-- Headers injected here -->
                                            </thead>
                                            <tbody>
                                                <!-- Results injected here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Update Modal -->
                    <div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content border-0">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title text-white">Bulk Status Update</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form id="bulkUpdateForm">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Select Excel File</label>
                                            <input type="file" name="bulk_file" class="form-control" accept=".xls,.xlsx"
                                                required>
                                            <div class="form-text mt-2">
                                                Format: 1. Pickup Point ID/AWB, 2. Status, 3. Date, 4. Location, 5.
                                                Remarks
                                            </div>
                                        </div>
                                        <div class="alert alert-info py-2">
                                            <i class="ti ti-info-circle me-1"></i>
                                            <a href="#" id="downloadTemplate" class="fw-bold">Download Template</a>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="btnSubmitBulk">
                                            <i class="ti ti-upload me-1"></i>Upload & Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>



                </div>
            </div>

            <?php include 'footer.php'; ?>

            <link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                $(document).ready(function () {
                    var scannedKeys = [];       // ordered unique keys: "bookingId_packageId" (packageId=0 for parent)
                    var scannedItemData = {};   // key -> {bookingId, packageId, isChild, childAwbNo}
                    var podImages = {};         // key -> [imageData]

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

                    // Set default date to today
                    var today = new Date().toISOString().split('T')[0];
                    $('#updateDate').val(today);

                    // Fetch Statuses from Master
                    function loadStatuses() {
                        $.get('api/master_status/read.php?length=-1&status=active', function (response) {
                            if (response.data) {
                                var $select = $('#updateStatus');
                                response.data.forEach(function (s) {
                                    $select.append(`<option value="${s.name}">${s.name}</option>`);
                                });
                                // Initialize or refresh Select2
                                setTimeout(function () {
                                    if ($select.hasClass('select2-hidden-accessible')) {
                                        $select.select2('destroy');
                                    }
                                    $select.select2({
                                        width: '100%',
                                        allowClear: false,
                                        dropdownParent: $select.parent()
                                    });
                                }, 100);
                            }
                        });
                    }
                    loadStatuses();

                    // Update scanned count badge and controls visibility
                    function updateScannedUI() {
                        var count = scannedKeys.length;
                        if (count > 0) {
                            $('#updateCountLabel').text(count);
                            $('#btnClearAll').show();
                            $('#updateStatusSection').show();
                            $('#updateStatusSectionEmpty').hide();
                            $('#updateCount').text(count);
                        } else {
                            $('#updateCountLabel').text('0');
                            $('#btnClearAll').hide();
                            $('#updateStatusSection').hide();
                            $('#updateStatusSectionEmpty').show();
                            $('#updateCount').text('0');
                        }
                    }

                    // Handle POD image selection
                    $('#btnAddImages').click(function () {
                        $('#podImageInput').click();
                    });

                    $('#podImageInput').change(function () {
                        var files = this.files;
                        if (files.length === 0) return;

                        $('#podPreviewContainer').empty();
                        var firstKey = scannedKeys[0];

                        $.each(files, function (i, file) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                var img = new Image();
                                img.onload = function () {
                                    var canvas = document.createElement('canvas');
                                    canvas.width = img.width;
                                    canvas.height = img.height;
                                    var ctx = canvas.getContext('2d');
                                    ctx.drawImage(img, 0, 0);

                                    // Store image data
                                    var imgData = {
                                        id: 'pod_' + Date.now() + '_' + i,
                                        data: canvas.toDataURL('image/jpeg', 0.8),
                                        name: file.name
                                    };

                                    if (!podImages[firstKey]) {
                                        podImages[firstKey] = [];
                                    }
                                    podImages[firstKey].push(imgData);

                                    // Preview
                                    var previewHtml = `
                                    <div class="pod-image-item" data-img-id="${imgData.id}">
                                        <img src="${imgData.data}" class="pod-image-preview" alt="POD Preview">
                                        <small class="pod-image-info">
                                            <strong>${file.name}</strong> | Size: ${(file.size / 1024).toFixed(1)} KB
                                        </small>
                                        <div class="pod-image-remove cursor-pointer mt-2" onclick="removeImage('${imgData.id}')">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </div>
                                    </div>`;
                                    $('#podPreviewContainer').append(previewHtml);
                                    $('#podPreviewSection').show();
                                };
                                img.src = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        });

                        // Reset input
                        this.value = '';
                    });

                    window.removeImage = function (imgId) {
                        var firstKey = scannedKeys[0];
                        if (podImages[firstKey]) {
                            podImages[firstKey] = podImages[firstKey].filter(function (img) {
                                return img.id !== imgId;
                            });
                        }
                        $('[data-img-id="' + imgId + '"]').remove();
                        if ((podImages[firstKey] || []).length === 0) {
                            $('#podPreviewSection').hide();
                        }
                    };

                    // Re-number table rows
                    function renumberRows() {
                        $('#shipmentsContainer tr.shipment-row').each(function (i) {
                            $(this).find('td:first').text(i + 1);
                        });
                    }

                    // Search and append Pickup to table
                    function performSearch() {
                        var searchValue = $('#searchInput').val().trim();
                        if (!searchValue) {
                            $('#searchInput').focus();
                            return;
                        }

                        // Client-side duplicate check for single AWB/Pickup Point (not TAG)
                        if (!searchValue.match(/^TAG-/i)) {
                            // Find if string exists in the table already
                            var isDuplicate = false;
                            $('#shipmentsContainer tr').each(function () {
                                var textContent = $(this).text();
                                if (textContent.indexOf(searchValue) !== -1) {
                                    isDuplicate = true;
                                }
                            });

                            // Let the backend handle true deduplication but do a quick UI-level block if we know we already have this exact AWB scanned!
                        }

                        $('#searchInput').prop('disabled', true);
                        $('#btnSearch').prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> ...');

                        $.post('api/statusupdate/scan_pickup.php', { scan_value: searchValue }, function (response) {
                            if (response.status === 'success' && response.data) {
                                var addedCount = 0;
                                var alreadyScanned = 0;
                                response.data.forEach(function (s) {
                                    var itemKey = s.id + '_' + (s.package_id || 0);
                                    if (scannedKeys.indexOf(itemKey) !== -1) {
                                        alreadyScanned++;
                                    } else {
                                        appendShipmentRow(s);
                                        addedCount++;
                                    }
                                });

                                if (addedCount > 0) {
                                    beepSuccess();
                                    if (alreadyScanned > 0) {
                                        showtoastt(addedCount + ' Pickup(s) added, ' + alreadyScanned + ' already scanned', 'success');
                                    } else {
                                        showtoastt(addedCount + ' Pickup(s) added!', 'success');
                                    }
                                } else if (alreadyScanned > 0) {
                                    beepDuplicate();
                                    showtoastt('All fetched Pickups are already scanned!', 'warning');
                                }

                            } else {
                                beepError();
                                showtoastt('Pickup/TAG not found', 'error');
                            }
                        }).fail(function () {
                            beepError();
                            showtoastt('Error searching pickups', 'error');
                        }).always(function () {
                            $('#searchInput').prop('disabled', false).val('').focus();
                            $('#btnSearch').prop('disabled', false).html('<i class="ti ti-search me-1"></i> Add Pickup');
                        });
                    }

                    // Append a pickup row to the table
                    function appendShipmentRow(shipment) {
                        var isChild  = parseInt(shipment.is_child_package) === 1;
                        var pkgId    = shipment.package_id ? parseInt(shipment.package_id) : 0;
                        var itemKey  = shipment.id + '_' + pkgId;

                        scannedKeys.push(itemKey);
                        scannedItemData[itemKey] = { bookingId: parseInt(shipment.id), packageId: pkgId, isChild: isChild, childAwbNo: shipment.child_awb_no || '' };
                        podImages[itemKey] = [];
                        var idx = scannedKeys.length;

                        // Clear empty state if first item
                        if (idx === 1) $('#shipmentsContainer').empty();

                        var statusText = shipment.last_status || 'PENDING';
                        var statusClass = getStatusClass(statusText);

                        var awb = shipment.waybill_no || '<span class="text-warning">Pending</span>';
                        var refId = shipment.booking_ref_id || '-';
                        var courier = shipment.courier_name || '-';
                        var createdDate = shipment.created_at ? new Date(shipment.created_at).toLocaleDateString() : '-';
                        var consignor = shipment.consignor_name || '-';
                        var location = shipment.pickup_city || shipment.shipper_city || '-';

                        // Child package indicator
                        var childBadge = isChild
                            ? `<div class="mt-1"><span class="badge bg-warning text-dark" style="font-size:10px;">Child Box: ${shipment.child_awb_no}</span></div>`
                            : '';

                        var row = `
                        <tr class="shipment-row" data-item-key="${itemKey}" data-booking-id="${shipment.id}" style="animation: fadeInRow 0.3s ease">
                            <td class="text-center fw-bold">${idx}</td>
                            <td>
                                <div><strong>${awb}</strong></div>
                                <div class="text-muted small">${refId}</div>
                                ${childBadge}
                            </td>
                            <td>${location}</td>
                            <td>${courier}</td>
                            <td>${consignor}</td>
                            <td>${createdDate}</td>
                            <td>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-info btn-toggle-history" data-id="${shipment.id}" title="View History">
                                    <i class="ti ti-history"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete-row" data-item-key="${itemKey}" title="Remove">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="history-row-${itemKey}" class="history-row" style="display: none; background-color: #fbfbfb;">
                            <td colspan="8">
                                <div class="tracking-history-container p-3"></div>
                            </td>
                        </tr>`;

                        $('#shipmentsContainer').append(row);
                        updateScannedUI();
                    }

                    // Get status CSS class
                    function getStatusClass(status) {
                        var statusMap = {
                            'Pending': 'pending',
                            'Not Picked': 'not-picked',
                            'Manifested': 'manifested',
                            'In Transit': 'in-transit',
                            'Out For Delivery': 'out-for-delivery',
                            'Delivered': 'delivered',
                            'LOST': 'lost',
                            'RTO': 'rto'
                        };
                        return statusMap[status] || 'pending';
                    }

                    // Load and display tracking history for a pickup
                    function loadTrackingHistory(bookingId, itemKey) {
                        $.get('api/tracking/read.php?id=' + bookingId, function (response) {
                            if (response.status === 'success') {
                                var scans = response.data.Scans || response.data.scans || [];

                                var historyHtml = '<h6 class="fw-semibold mb-3 py-1 border-bottom" style="font-size: 13px;"><i class="ti ti-history me-2"></i>Tracking History Logs</h6>';
                                historyHtml += '<div class="row">';

                                if (scans.length === 0) {
                                    historyHtml += '<div class="col-12 empty-tracking">No tracking records found yet</div>';
                                } else {
                                    scans.forEach(function (scan) {
                                        var detail = scan.ScanDetail || scan;
                                        var scanType = detail.ScanType || detail.Scan || 'Unknown';
                                        var scanTime = detail.ScanDateTime || '-';
                                        var location = detail.ScannedLocation || detail.ScanLocation || '-';
                                        var remarks = detail.Instructions || '-';

                                        historyHtml += `
                                        <div class="col-md-4 mb-2">
                                            <div class="tracking-history-item p-2 border rounded shadow-sm">
                                                <span class="tracking-time fw-bold">${new Date(scanTime).toLocaleString()}</span>
                                                <div class="tracking-status my-1">
                                                    <span class="badge ${getStatusClass(scanType)}" style="font-size: 10px;">${scanType}</span>
                                                </div>
                                                <div class="tracking-location small font-bold"><i class="ti ti-map-pin me-1"></i>${location}</div>
                                                ${remarks !== '-' ? '<div class="tracking-remarks small text-italic">' + remarks + '</div>' : ''}
                                            </div>
                                        </div>`;
                                    });
                                }
                                historyHtml += '</div>';

                                $('#history-row-' + itemKey).find('.tracking-history-container').html(historyHtml);
                            }
                        });
                    }

                    // Search triggers
                    $('#btnSearch').click(function () {
                        performSearch();
                    });

                    $('#searchInput').keypress(function (e) {
                        if (e.which == 13) {
                            performSearch();
                        }
                    });

                    // Toggle tracking history
                    $(document).on('click', '.btn-toggle-history', function () {
                        var bookingId = $(this).data('id');
                        var $row = $(this).closest('tr.shipment-row');
                        var itemKey = $row.data('item-key') || (bookingId + '_0');
                        var $historyRow = $('#history-row-' + itemKey);
                        var $btn = $(this);

                        if ($historyRow.is(':visible')) {
                            $historyRow.fadeOut(200);
                            $btn.removeClass('btn-info').addClass('btn-soft-info');
                        } else {
                            if ($historyRow.find('.tracking-history-container').html().trim() === '') {
                                loadTrackingHistory(bookingId, itemKey);
                            }
                            $historyRow.fadeIn(200);
                            $btn.removeClass('btn-soft-info').addClass('btn-info');
                        }
                    });

                    // Delete single row
                    $(document).on('click', '.btn-delete-row', function () {
                        var itemKey = $(this).data('item-key');
                        var $row = $('tr[data-item-key="' + itemKey + '"]');
                        var $historyRow = $('#history-row-' + itemKey);

                        $row.add($historyRow).fadeOut(300, function () {
                            $(this).remove();
                            if ($(this).hasClass('shipment-row')) {
                                var idx = scannedKeys.indexOf(itemKey);
                                if (idx !== -1) scannedKeys.splice(idx, 1);
                                delete scannedItemData[itemKey];
                                delete podImages[itemKey];
                                updateScannedUI();
                                renumberRows();

                                if (scannedKeys.length === 0) {
                                    $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-scan me-1" style="font-size: 1.3rem;"></i><br>Scan Pickup ID or AWB number to add</td></tr>');
                                    $('#podPreviewSection').hide();
                                }
                            }
                        });
                        $('#searchInput').focus();
                    });

                    // Clear All
                    $('#btnClearAll').click(function () {
                        if (!confirm('Remove all ' + scannedKeys.length + ' scanned pickups?')) return;
                        scannedKeys = [];
                        scannedItemData = {};
                        podImages = {};
                        $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-scan me-1" style="font-size: 1.3rem;"></i><br>Scan Pickup ID or AWB number to add</td></tr>');
                        updateScannedUI();
                        $('#podPreviewSection').hide();
                        $('#searchInput').focus();
                    });

                    // Bulk Update status
                    $('#btnUpdate').click(function () {
                        if (scannedKeys.length === 0) {
                            showtoastt('Please scan at least one pickup first', 'warning');
                            return;
                        }

                        var status = $('#updateStatus').val();
                        if (!status) {
                            showtoastt('Please select a status', 'warning');
                            $('#updateStatus').focus();
                            return;
                        }

                        var count = scannedKeys.length;
                        if (!confirm('Update status to "' + status + '" for ' + count + ' pickup(s)?')) return;

                        var now = new Date();
                        var timeString = String(now.getHours()).padStart(2, '0') + ':' +
                            String(now.getMinutes()).padStart(2, '0') + ':' +
                            String(now.getSeconds()).padStart(2, '0');

                        var $btn = $(this);
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Updating ' + count + '...');

                        var completed = 0;
                        var failed = 0;
                        var total = scannedKeys.length;

                        scannedKeys.forEach(function (itemKey) {
                            var item = scannedItemData[itemKey];
                            var bookingId = item.bookingId;

                            var formData = new FormData();
                            formData.append('booking_id', bookingId);
                            formData.append('is_child', item.isChild ? 1 : 0);
                            formData.append('package_id', item.packageId);
                            formData.append('status', status);
                            formData.append('status_date', $('#updateDate').val() + 'T' + timeString);
                            formData.append('location', $('#updateLocation').val());
                            formData.append('remarks', $('#updateRemarks').val());
                            formData.append('images_folder', 'pickup/' + today.replace(/-/g, '/'));

                            // Add images if available
                            var imageList = podImages[itemKey] || [];
                            if (imageList.length > 0) {
                                imageList.forEach(function (imgData, idx) {
                                    // Convert base64 to blob
                                    var blobData = atob(imgData.data.split(',')[1]);
                                    var array = [];
                                    for (var i = 0; i < blobData.length; i++) {
                                        array.push(blobData.charCodeAt(i));
                                    }
                                    var blob = new Blob([new Uint8Array(array)], { type: 'image/jpeg' });
                                    formData.append('pod_images[]', blob, 'pod_' + bookingId + '_' + idx + '.jpg');
                                });
                            }

                            $.ajax({
                                url: 'api/statusupdate/pod_upload.php',
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function (response) {
                                    if (response.status === 'success') {
                                        completed++;
                                        var $row = $('tr[data-item-key="' + itemKey + '"]');
                                        var displayStatus = response.booking_status || status;
                                        var badgeClass = response.fully_updated ? getStatusClass(status) : 'not-picked';
                                        $row.find('.status-badge').attr('class', 'status-badge ' + badgeClass).text(displayStatus);
                                        $row.css('background-color', response.fully_updated ? '#e8f5e9' : '#fff8e1');

                                        // Refresh history only when fully updated
                                        if (response.fully_updated) {
                                            loadTrackingHistory(bookingId, itemKey);
                                        }

                                        setTimeout(function () { $row.css('background-color', ''); }, 2000);
                                    } else {
                                        failed++;
                                    }
                                },
                                error: function () {
                                    failed++;
                                },
                                complete: function () {
                                    if (completed + failed === total) {
                                        var msg = completed + ' of ' + total + ' updated successfully';
                                        if (failed > 0) msg += ' (' + failed + ' failed)';
                                        showtoastt(msg, failed > 0 ? 'warning' : 'success');

                                        $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update All (<span id="updateCount">' + total + '</span>)');
                                        $('#updateStatus').val('');
                                        $('#updateLocation').val('');
                                        $('#updateRemarks').val('');
                                        $('#podImageInput').val('');
                                        $('#podPreviewContainer').empty();
                                        $('#podPreviewSection').hide();
                                        podImages = {};
                                        scannedKeys.forEach(function (k) {
                                            podImages[k] = [];
                                        });
                                        $('#searchInput').focus();
                                    }
                                }
                            });
                        });
                    });

                    // Bulk Upload Handling
                    $('#bulkUpdateForm').submit(function (e) {
                        e.preventDefault();
                        var formData = new FormData(this);
                        var $btn = $('#btnSubmitBulk');

                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Processing...');

                        $.ajax({
                            url: 'api/statusupdate/bulk_update.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    $('#bulkUpdateModal').modal('hide');
                                    showBulkResults(response.job_id);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('Server error during bulk upload', 'error');
                            },
                            complete: function () {
                                $btn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i>Upload & Update');
                                $('#bulkUpdateForm')[0].reset();
                            }
                        });
                    });

                    function showBulkResults(jobId) {
                        $.get('api/shipment/bulk_jobs_list.php?job_id=' + jobId, function (response) {
                            if (response.status === 'success' && response.data) {
                                var job = response.data;
                                var results = JSON.parse(job.result_file);

                                var header = results.shift();
                                var $thead = $('#jobResultTable thead').empty();
                                var headerHtml = '<tr>';
                                header.forEach(function (h) {
                                    headerHtml += `<th>${h}</th>`;
                                });
                                headerHtml += '</tr>';
                                $thead.html(headerHtml);

                                var $tbody = $('#jobResultTable tbody').empty();
                                results.forEach(function (row) {
                                    var errColIdx = row[row.length - 1]; // Last element is errCol
                                    var status = row[row.length - 3];
                                    var rowClass = status === 'Failed' ? 'table-danger' : '';

                                    var rowHtml = `<tr class="${rowClass}">`;
                                    for (var i = 0; i < row.length - 1; i++) { // Skip the errCol
                                        var cellStyle = (status === 'Failed' && i == errColIdx) ? 'background-color: #ffcccc; font-weight: bold; border: 2px solid red;' : '';
                                        rowHtml += `<td style="${cellStyle}">${row[i] || '-'}</td>`;
                                    }
                                    rowHtml += '</tr>';
                                    $tbody.append(rowHtml);
                                });

                                $('#jobSummary').html(`
                                    <div class="row">
                                        <div class="col-md-3"><strong>Total:</strong> ${job.total_records}</div>
                                        <div class="col-md-3 text-success"><strong>Success:</strong> ${job.success_count}</div>
                                        <div class="col-md-3 text-danger"><strong>Failed:</strong> ${job.failure_count}</div>
                                        <div class="col-md-3 text-primary"><strong>Status:</strong> ${job.status}</div>
                                    </div>
                                `);

                                $('#jobResultModal').modal('show');
                            }
                        });
                    }

                    $('#downloadTemplate').click(function (e) {
                        e.preventDefault();
                        // Create a simple CSV template
                        var csvContent = "Pickup Point ID/AWB,Status,StatusDate(YYYY-MM-DD),Location,Remarks\n";
                        csvContent += "PU123456,Scheduled,2024-05-14,Delhi Branch,Morning Pickup\n";

                        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement("a");
                        var url = URL.createObjectURL(blob);
                        link.setAttribute("href", url);
                        link.setAttribute("download", "pickup_status_update_template.csv");
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });

                    // Initial empty state
                    $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan Pickup Point IDs above to add pickups</td></tr>');
                });
            </script>
        </div>
    </div>
</body>

</html>