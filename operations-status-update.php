<?php include 'header.php'; ?>
<?php // require_permission('operation', 'is_update'); ?>

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
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">

                    <!-- Page Title -->
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">Operations Status Update</h4>
                        </div>
                        <div class="text-end">
                            <span id="scannedCount" class="badge bg-success me-2"
                                style="font-size: 12px; display: none;">0 Scanned</span>
                            <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal"
                                data-bs-target="#bulkUpdateModal">
                                <i class="ti ti-upload me-1"></i>Bulk Upload
                            </button>
                            <button type="button" id="btnClearAll" class="btn btn-sm btn-outline-dark me-2"
                                style="display: none;">
                                <i class="ti ti-trash me-1"></i>Clear All
                            </button>
                            <a href="manifest-list.php" class="btn btn-sm btn-outline-dark">
                                <i class="ri-arrow-left-circle-fill me-1"></i> Back to Operations
                            </a>
                        </div>
                    </div>


                    <!-- Scan AWB Input -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card" style="margin-bottom: 5px;">
                                <div class="card-body" style="padding: 10px 20px;">
                                    <div class="row align-items-center">
                                        <div class="col-md-10">
                                            <input type="text" class="form-control" id="searchInput"
                                                placeholder="Scan or type Operation ID / Manifest No and press Enter" autofocus>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-dark w-100" id="btnSearch">
                                                <i class="ti ti-search me-1"></i> Add Operation
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scanned Operations Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-semibold">Scanned Operations</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="scannedTable" class="table table-striped table-bordered mb-0"
                                            style="font-size: 12px;">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>Operation ID / Manifest</th>
                                                    <th>Courier</th>
                                                    <th>Route (Origin - Dest)</th>
                                                    <th>Items Count</th>
                                                    <th>Created Date</th>
                                                    <th>Current Status</th>
                                                    <th style="width: 100px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="shipmentsContainer">
                                                <!-- Dynamic rows appended here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Section -->
                    <div id="updateStatusSection" style="display: none;">
                        <div class="row">
                            <div class="col-12">
                                <div class="card" style="margin-bottom: 5px;">
                                    <div class="card-body" style="padding: 10px 20px;">
                                        <div class="row mb-2">
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Update Status <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="updateStatus" required>
                                                    <option value="">Choose Status</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Status Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="updateDate" required>
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Location</label>
                                                <input type="text" class="form-control" id="updateLocation"
                                                    placeholder="e.g., Delhi Hub">
                                            </div>
                                            <div class="col-sm-3 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-dark w-100" id="btnUpdate">
                                                    <i class="ti ti-check me-1"></i> Update All (<span
                                                        id="updateCount">0</span>)
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="col-form-label fw-semibold">Remarks / Instructions</label>
                                                <textarea class="form-control" id="updateRemarks" rows="2"
                                                    placeholder="Add any additional notes..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                Format: 1. Operation ID/Manifest, 2. Status, 3. Date, 4. Location, 5. Remarks
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

            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <script>
                $(document).ready(function () {
                    var scannedBookingIds = [];

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
                            }
                        });
                    }
                    loadStatuses();

                    // Update scanned count badge and controls visibility
                    function updateScannedUI() {
                        var count = scannedBookingIds.length;
                        if (count > 0) {
                            $('#scannedCount').text(count + ' Scanned').show();
                            $('#btnClearAll').show();
                            $('#updateStatusSection').show();
                            $('#updateCount').text(count);
                        } else {
                            $('#scannedCount').hide();
                            $('#btnClearAll').hide();
                            $('#updateStatusSection').hide();
                            $('#updateCount').text('0');
                        }
                    }

                    // Re-number table rows
                    function renumberRows() {
                        $('#shipmentsTableBody tr').not('.empty-row').each(function (i) {
                            $(this).find('td:first').text(i + 1);
                        });
                    }

                    // Search and append Operation to table
                    function performSearch() {
                        var searchValue = $('#searchInput').val().trim();
                        if (!searchValue) {
                            $('#searchInput').focus();
                            return;
                        }

                        $('#searchInput').prop('disabled', true);
                        $('#btnSearch').prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> ...');

                        $.get('api/shipment/read.php?length=-1', function (response) {
                            if (response.data) {
                                var found = response.data.find(function (s) {
                                    return s.waybill_no === searchValue ||
                                        s.booking_ref_id === searchValue ||
                                        s.id == searchValue;
                                });

                                if (found) {
                                    if (scannedBookingIds.indexOf(parseInt(found.id)) !== -1) {
                                        showtoastt('Operation already scanned!', 'warning');
                                    } else {
                                        appendShipmentRow(found);
                                    }
                                } else {
                                    showtoastt('Operation not found', 'error');
                                }
                            } else {
                                showtoastt('No operations found', 'error');
                            }
                        }).fail(function () {
                            showtoastt('Error searching operations', 'error');
                        }).always(function () {
                            $('#searchInput').prop('disabled', false).val('').focus();
                            $('#btnSearch').prop('disabled', false).html('<i class="ti ti-search me-1"></i> Add Operation');
                        });
                    }

                    // Append an operation row to the table
                    function appendShipmentRow(shipment) {
                        scannedBookingIds.push(parseInt(shipment.id));
                        var idx = scannedBookingIds.length;

                        // Clear empty state if first item
                        if (idx === 1) $('#shipmentsContainer').empty();

                        var statusText = shipment.last_status || 'PENDING';
                        var statusClass = getStatusClass(statusText);

                        var awb = shipment.waybill_no || '<span class="text-warning">Pending</span>';
                        var refId = shipment.booking_ref_id || '-';
                        var courier = shipment.courier_name || '-';
                        var createdDate = shipment.created_at ? new Date(shipment.created_at).toLocaleDateString() : '-';
                        var itemsCount = shipment.total_items || '1';
                        var origin = shipment.shipper_city || shipment.pickup_city || '-';
                        var destination = shipment.consignee_city || '-';

                        var row = `
                        <tr class="shipment-row" data-booking-id="${shipment.id}" style="animation: fadeInRow 0.3s ease">
                            <td class="text-center fw-bold">${idx}</td>
                            <td>
                                <div><strong>${awb}</strong></div>
                                <div class="text-muted small">${refId}</div>
                            </td>
                            <td>${courier}</td>
                            <td>
                                <div>${origin} <i class="ti ti-arrow-narrow-right mx-1"></i> ${destination}</div>
                            </td>
                            <td>${itemsCount}</td>
                            <td>${createdDate}</td>
                            <td>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-info btn-toggle-history" data-id="${shipment.id}" title="View History">
                                    <i class="ti ti-history"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete-row" data-id="${shipment.id}" title="Remove">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="history-row-${shipment.id}" class="history-row" style="display: none; background-color: #fbfbfb;">
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

                    // Load and display tracking history for an operation
                    function loadTrackingHistory(bookingId) {
                        $.get('api/tracking/read.php?id=' + bookingId, function (response) {
                            if (response.status === 'success') {
                                var scans = response.data.Scans || response.data.scans || [];
                                var currentStatus = response.current_status || 'Unknown';

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

                                var $historyRow = $('#history-row-' + bookingId);
                                $historyRow.find('.tracking-history-container').html(historyHtml);
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
                        var $historyRow = $('#history-row-' + bookingId);
                        var $btn = $(this);

                        if ($historyRow.is(':visible')) {
                            $historyRow.fadeOut(200);
                            $btn.removeClass('btn-info').addClass('btn-soft-info');
                        } else {
                            if ($historyRow.find('.tracking-history-container').html().trim() === '') {
                                loadTrackingHistory(bookingId);
                            }
                            $historyRow.fadeIn(200);
                            $btn.removeClass('btn-soft-info').addClass('btn-info');
                        }
                    });

                    // Delete single row
                    $(document).on('click', '.btn-delete-row', function () {
                        var bookingId = parseInt($(this).data('id'));
                        var $row = $('tr[data-booking-id="' + bookingId + '"]');
                        var $historyRow = $('#history-row-' + bookingId);

                        $row.add($historyRow).fadeOut(300, function () {
                            $(this).remove();
                            if ($(this).hasClass('shipment-row')) {
                                var idx = scannedBookingIds.indexOf(bookingId);
                                if (idx !== -1) scannedBookingIds.splice(idx, 1);
                                updateScannedUI();
                                renumberRows();

                                if (scannedBookingIds.length === 0) {
                                    $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan Operation IDs above to add operations</td></tr>');
                                }
                            }
                        });
                        $('#searchInput').focus();
                    });

                    // Clear All
                    $('#btnClearAll').click(function () {
                        if (!confirm('Remove all ' + scannedBookingIds.length + ' scanned operations?')) return;
                        scannedBookingIds = [];
                        $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan Operation IDs above to add operations</td></tr>');
                        updateScannedUI();
                        $('#searchInput').focus();
                    });

                    // Bulk Update status
                    $('#btnUpdate').click(function () {
                        if (scannedBookingIds.length === 0) {
                            showtoastt('Please scan at least one Operation first', 'warning');
                            return;
                        }

                        var status = $('#updateStatus').val();
                        if (!status) {
                            showtoastt('Please select a status', 'warning');
                            $('#updateStatus').focus();
                            return;
                        }

                        var count = scannedBookingIds.length;
                        if (!confirm('Update status to "' + status + '" for ' + count + ' operation(s)?')) return;

                        var now = new Date();
                        var timeString = String(now.getHours()).padStart(2, '0') + ':' +
                            String(now.getMinutes()).padStart(2, '0') + ':' +
                            String(now.getSeconds()).padStart(2, '0');

                        var $btn = $(this);
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Updating ' + count + '...');

                        var completed = 0;
                        var failed = 0;
                        var total = scannedBookingIds.length;

                        scannedBookingIds.forEach(function (bookingId) {
                            var formData = {
                                booking_id: bookingId,
                                status: status,
                                status_date: $('#updateDate').val() + 'T' + timeString,
                                location: $('#updateLocation').val(),
                                remarks: $('#updateRemarks').val()
                            };

                            $.post('api/statusupdate/create.php', formData, function (response) {
                                if (response.status === 'success') {
                                    completed++;
                                    var $row = $('tr[data-booking-id="' + bookingId + '"]');
                                    var badgeClass = getStatusClass(status);
                                    $row.find('.status-badge').attr('class', 'status-badge ' + badgeClass).text(status);
                                    $row.css('background-color', '#e8f5e9');

                                    // Refresh history if visible
                                    loadTrackingHistory(bookingId);

                                    setTimeout(function () { $row.css('background-color', ''); }, 2000);
                                } else {
                                    failed++;
                                }
                            }).fail(function () {
                                failed++;
                            }).always(function () {
                                if (completed + failed === total) {
                                    var msg = completed + ' of ' + total + ' updated successfully';
                                    if (failed > 0) msg += ' (' + failed + ' failed)';
                                    showtoastt(msg, failed > 0 ? 'warning' : 'success');

                                    $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update All (<span id="updateCount">' + total + '</span>)');
                                    $('#updateStatus').val('');
                                    $('#updateLocation').val('');
                                    $('#updateRemarks').val('');
                                    $('#searchInput').focus();
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
                        var csvContent = "Operation ID/Manifest,Status,StatusDate(YYYY-MM-DD),Location,Remarks\n";
                        csvContent += "MNF123456,Dispatched,2024-05-14,Mumbai,Ready for pickup\n";

                        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement("a");
                        var url = URL.createObjectURL(blob);
                        link.setAttribute("href", url);
                        link.setAttribute("download", "operations_status_update_template.csv");
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });

                    // Initial empty state
                    $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan Operation IDs above to add operations</td></tr>');
                });
            </script>
        </div>
    </div>
</body>

</html>
