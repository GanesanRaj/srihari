<?php include 'header.php'; ?>
<?php // require_permission('pod', 'is_update'); ?>

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
                            <h4 class="fs-18 fw-semibold m-0">POD (Proof of Delivery) Status Update</h4>
                        </div>
                        <div class="text-end">
                            <span id="scannedCount" class="badge bg-success me-2"
                                style="font-size: 12px; display: none;">0 Scanned</span>
                            <button type="button" id="btnClearAll" class="btn btn-sm btn-outline-dark me-2"
                                style="display: none;">
                                <i class="ti ti-trash me-1"></i>Clear All
                            </button>
                            <a href="tracking.php" class="btn btn-sm btn-outline-dark">
                                <i class="ri-arrow-left-circle-fill me-1"></i> Back to Tracking
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
                                                placeholder="Scan or type AWB Number and press Enter" autofocus>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-dark w-100" id="btnSearch">
                                                <i class="ti ti-search me-1"></i> Add AWB
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scanned Deliveries Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-semibold">Scanned Deliveries</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="scannedTable" class="table table-striped table-bordered mb-0"
                                            style="font-size: 12px;">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>AWB / Ref ID</th>
                                                    <th>Courier</th>
                                                    <th>Consignee</th>
                                                    <th>Route</th>
                                                    <th>Booking Date</th>
                                                    <th>POD Status</th>
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

                    <!-- Update POD Status Section -->
                    <div id="updateStatusSection" style="display: none;">
                        <div class="row">
                            <div class="col-12">
                                <div class="card" style="margin-bottom: 5px;">
                                    <div class="card-body" style="padding: 10px 20px;">
                                        <div class="row mb-2">
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">POD Status <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="updateStatus" required>
                                                    <option value="">Choose Status</option>
                                                    <option value="Delivered">Delivered</option>
                                                    <option value="Attempted">Attempted Delivery</option>
                                                    <option value="RTO">RTO (Return to Origin)</option>
                                                    <option value="Lost">Lost in Transit</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Delivery Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="updateDate" required>
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Receiver Name</label>
                                                <input type="text" class="form-control" id="updateReceiverName"
                                                    placeholder="Delivery recipient">
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="col-form-label fw-semibold">Receiver Phone</label>
                                                <input type="text" class="form-control" id="updateReceiverPhone"
                                                    placeholder="Contact number">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <label class="col-form-label fw-semibold">Remarks / Instructions</label>
                                                <textarea class="form-control" id="updateRemarks" rows="2"
                                                    placeholder="Add any additional notes..."></textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <label class="col-form-label fw-semibold">POD Images (Optional - Max 200KB each)</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="podImageInput" accept="image/*" multiple>
                                                    <button type="button" class="btn btn-outline-dark" id="btnAddImages">
                                                        <i class="ti ti-photo me-1"></i> Add Images
                                                    </button>
                                                </div>
                                                <small class="d-block mt-1 text-muted">Supported: JPG, PNG, GIF. Images will be auto-compressed.</small>
                                            </div>
                                        </div>
                                        <div class="row mb-2" id="podPreviewSection" style="display: none;">
                                            <div class="col-12">
                                                <label class="col-form-label fw-semibold">Preview</label>
                                                <div class="pod-preview-container" id="podPreviewContainer"></div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-outline-dark me-2" id="btnUpdateReset">
                                                    <i class="ti ti-refresh me-1"></i> Reset
                                                </button>
                                                <button type="button" class="btn btn-primary" id="btnUpdate">
                                                    <i class="ti ti-check me-1"></i> Update All (<span
                                                        id="updateCount">0</span>)
                                                </button>
                                            </div>
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
                $(document).ready(function () {
                    var scannedBookingIds = [];
                    var podImages = {}; // Store images for each shipment

                    // Set default date to today
                    var today = new Date().toISOString().split('T')[0];
                    $('#updateDate').val(today);

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

                    // Handle POD image selection
                    $('#btnAddImages').click(function () {
                        $('#podImageInput').click();
                    });

                    $('#podImageInput').change(function () {
                        var files = this.files;
                        if (files.length === 0) return;

                        $('#podPreviewContainer').empty();
                        var firstBookingId = scannedBookingIds[0];

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

                                    if (!podImages[firstBookingId]) {
                                        podImages[firstBookingId] = [];
                                    }
                                    podImages[firstBookingId].push(imgData);

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
                        var firstBookingId = scannedBookingIds[0];
                        if (podImages[firstBookingId]) {
                            podImages[firstBookingId] = podImages[firstBookingId].filter(function (img) {
                                return img.id !== imgId;
                            });
                        }
                        $('[data-img-id="' + imgId + '"]').remove();
                        if (Object.keys(podImages[firstBookingId] || {}).length === 0) {
                            $('#podPreviewSection').hide();
                        }
                    };

                    // Search and append AWB to table
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
                                        showtoastt('AWB already scanned!', 'warning');
                                    } else {
                                        appendShipmentRow(found);
                                    }
                                } else {
                                    showtoastt('Shipment not found', 'error');
                                }
                            } else {
                                showtoastt('No shipments found', 'error');
                            }
                        }).fail(function () {
                            showtoastt('Error searching shipments', 'error');
                        }).always(function () {
                            $('#searchInput').prop('disabled', false).val('').focus();
                            $('#btnSearch').prop('disabled', false).html('<i class="ti ti-search me-1"></i> Add AWB');
                        });
                    }

                    // Append a shipment row to the table
                    function appendShipmentRow(shipment) {
                        scannedBookingIds.push(parseInt(shipment.id));
                        podImages[shipment.id] = [];
                        var idx = scannedBookingIds.length;

                        // Clear empty state if first item
                        if (idx === 1) $('#shipmentsContainer').empty();

                        var statusText = 'Pending POD';
                        var statusClass = 'pending';

                        var awb = shipment.waybill_no || '<span class="text-warning">Pending</span>';
                        var refId = shipment.booking_ref_id || '-';
                        var courier = shipment.courier_name || '-';
                        var createdDate = shipment.created_at ? new Date(shipment.created_at).toLocaleDateString() : '-';
                        var consignee = shipment.consignee_name || '-';
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
                            <td>${consignee}</td>
                            <td>
                                <div>${origin} <i class="ti ti-arrow-narrow-right mx-1"></i> ${destination}</div>
                            </td>
                            <td>${createdDate}</td>
                            <td>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete-row" data-id="${shipment.id}" title="Remove">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>`;

                        $('#shipmentsContainer').append(row);
                        updateScannedUI();
                    }

                    // Get status CSS class
                    function getStatusClass(status) {
                        var statusMap = {
                            'Delivered': 'delivered',
                            'Attempted': 'in-transit',
                            'RTO': 'rto',
                            'Lost': 'lost'
                        };
                        return statusMap[status] || 'pending';
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

                    // Delete single row
                    $(document).on('click', '.btn-delete-row', function () {
                        var bookingId = parseInt($(this).data('id'));
                        var $row = $('tr[data-booking-id="' + bookingId + '"]');

                        $row.fadeOut(300, function () {
                            $(this).remove();
                            var idx = scannedBookingIds.indexOf(bookingId);
                            if (idx !== -1) scannedBookingIds.splice(idx, 1);
                            delete podImages[bookingId];
                            updateScannedUI();

                            if (scannedBookingIds.length === 0) {
                                $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan AWB numbers above to add deliveries</td></tr>');
                                $('#podPreviewSection').hide();
                            }
                        });
                        $('#searchInput').focus();
                    });

                    // Clear All
                    $('#btnClearAll').click(function () {
                        if (!confirm('Remove all ' + scannedBookingIds.length + ' scanned deliveries?')) return;
                        scannedBookingIds = [];
                        podImages = {};
                        $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan AWB numbers above to add deliveries</td></tr>');
                        updateScannedUI();
                        $('#podPreviewSection').hide();
                        $('#searchInput').focus();
                    });

                    // Reset form
                    $('#btnUpdateReset').click(function () {
                        $('#updateStatus').val('');
                        $('#updateDate').val(today);
                        $('#updateReceiverName').val('');
                        $('#updateReceiverPhone').val('');
                        $('#updateRemarks').val('');
                        $('#podImageInput').val('');
                        $('#podPreviewContainer').empty();
                        $('#podPreviewSection').hide();
                        podImages = {};
                        scannedBookingIds.forEach(function (id) {
                            podImages[id] = [];
                        });
                    });

                    // Bulk Update POD status
                    $('#btnUpdate').click(function () {
                        if (scannedBookingIds.length === 0) {
                            showtoastt('Please scan at least one AWB first', 'warning');
                            return;
                        }

                        var status = $('#updateStatus').val();
                        if (!status) {
                            showtoastt('Please select a POD status', 'warning');
                            $('#updateStatus').focus();
                            return;
                        }

                        var count = scannedBookingIds.length;
                        if (!confirm('Update POD status to "' + status + '" for ' + count + ' delivery(ies)?')) return;

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
                            var formData = new FormData();
                            formData.append('booking_id', bookingId);
                            formData.append('status', status);
                            formData.append('status_date', $('#updateDate').val() + 'T' + timeString);
                            formData.append('receiver_name', $('#updateReceiverName').val());
                            formData.append('receiver_phone', $('#updateReceiverPhone').val());
                            formData.append('remarks', $('#updateRemarks').val());
                            formData.append('images_folder', 'pickup/' + today.replace(/-/g, '/'));

                            // Add images if available
                            var imageList = podImages[bookingId] || [];
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
                                        var $row = $('tr[data-booking-id="' + bookingId + '"]');
                                        var badgeClass = getStatusClass(status);
                                        $row.find('.status-badge').attr('class', 'status-badge ' + badgeClass).text(status);
                                        $row.css('background-color', '#e8f5e9');

                                        setTimeout(function () { $row.css('background-color', ''); }, 2000);
                                    } else {
                                        failed++;
                                    }
                                },
                                error: function (xhr) {
                                    failed++;
                                },
                                complete: function () {
                                    if (completed + failed === total) {
                                        var msg = completed + ' of ' + total + ' POD records updated successfully';
                                        if (failed > 0) msg += ' (' + failed + ' failed)';
                                        showtoastt(msg, failed > 0 ? 'warning' : 'success');

                                        $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update All (<span id="updateCount">' + total + '</span>)');
                                        $('#btnUpdateReset').click();
                                        $('#searchInput').focus();
                                    }
                                }
                            });
                        });
                    });

                    // Initial empty state
                    $('#shipmentsContainer').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="ti ti-inbox me-2" style="font-size: 2rem;"></i><br>Scan AWB numbers above to add deliveries</td></tr>');
                });
            </script>
        </div>
    </div>
</body>

</html>
