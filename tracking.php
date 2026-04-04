<?php
require_once 'config/middleware.php';
$trackId     = isset ($_GET[ 'id' ]) ? (int) $_GET[ 'id' ] : 0;
$waybill     = isset ($_GET[ 'waybill' ]) ? trim ( $_GET[ 'waybill' ] ) : '';
$liveRefresh = isset ($_GET[ 'live' ]) && ($_GET[ 'live' ] === '1' || $_GET[ 'live' ] === 'true');
$canAddScan  = can_add ( 'shipment' );
include 'header.php';
?>
<style>
    .timeline-item.current-status .timeline-dot {
        background: #198754 !important;
        box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.3);
        animation: blink-green 1.2s ease-in-out infinite;
    }

    .timeline-item.current-status .timeline-content {
        background: rgba(25, 135, 84, 0.08);
        border-left: 3px solid #198754;
        margin-left: -3px;
        padding-left: 1rem;
        border-radius: 0 6px 6px 0;
    }

    .timeline-item.current-status .timeline-content h5 {
        color: #198754;
        font-weight: 700;
    }

    .timeline-item.old-status .timeline-dot {
        background: #fd7e14 !important;
    }

    .timeline-item.old-status .timeline-content {
        background: rgba(253, 126, 20, 0.06);
        border-left: 2px solid rgba(253, 126, 20, 0.5);
        margin-left: -2px;
        padding-left: 0.75rem;
        border-radius: 0 4px 4px 0;
    }

    .timeline-item.old-status .timeline-content h5 {
        color: #b66200;
    }

    @keyframes blink-green {

        0%,
        100% {
            opacity: 1;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.4);
        }

        50% {
            opacity: 0.85;
            box-shadow: 0 0 0 8px rgba(25, 135, 84, 0.2);
        }
    }

    /* POD thumbnails */
    .pod-thumbs {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-start;
        gap: 6px;
        margin-top: 4px;
    }

    .pod-thumb-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
    }

    .pod-thumb-label {
        font-size: 9px;
        font-weight: 700;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: .4px;
        line-height: 1;
    }

    .pod-thumb-imgs {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        justify-content: center;
    }

    .pod-thumb-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1.5px solid #d0f0e0;
        cursor: pointer;
        transition: transform .15s, box-shadow .15s;
    }

    .pod-thumb-img:hover {
        transform: scale(1.12);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .22);
        border-color: #027a48;
    }

    .pod-thumb-empty {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        border: 1.5px dashed #e0e0e0;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d0d0d0;
        font-size: 13px;
    }

    .pod-thumb-divider {
        width: 1px;
        min-height: 40px;
        align-self: stretch;
        background: #eee;
        flex-shrink: 0;
    }

    /* POD fullscreen overlay */
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
        font-size: 32px;
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
                <div class="">
                    <div class="page-title-head d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="fs-xl fw-bold m-0">Tracking</h4>
                        </div>
                        <div class="text-end">
                            <ol class="breadcrumb m-0 py-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="shipment-list.php">Booking</a></li>
                                <li class="breadcrumb-item active">Tracking</li>
                            </ol>
                        </div>
                    </div>

                    <div id="trackingContent">
                        <?php if ($trackId <= 0 && $waybill === '') : ?>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body p-4">
                                            <div class="alert alert-info mb-0">
                                                <h5 class="alert-heading">Track Shipment</h5>
                                                <p class="mb-3">Enter Booking ID or AWB / Waybill number to view tracking.
                                                </p>
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <input type="number" id="trackById" class="form-control"
                                                            placeholder="Booking ID" min="1">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" id="trackByWaybill" class="form-control"
                                                            placeholder="AWB / Waybill No">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="button" id="btnTrack" class="btn btn-primary me-1"><i
                                                                class="ti ti-search me-1"></i> Track</button>
                                                        <a href="shipment-list.php" class="btn btn-light">Booking List</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Loading tracking...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- POD fullscreen overlay -->
    <div id="podOverlay" onclick="closePodOverlay(event)">
        <span class="pod-close" onclick="closePodOverlay(event)">&times;</span>
        <div id="podOverlayContent"></div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script>
        (function () {
            var trackId = <?php echo json_encode ( $trackId ); ?>;
            var waybill = <?php echo json_encode ( $waybill ); ?>;
            var liveRefresh = <?php echo json_encode ( $liveRefresh ); ?>;
            var canAddScan = <?php echo $canAddScan ? 'true' : 'false'; ?>;

            function buildTrackingPage(trackRes, bookingRes) {
                var status = trackRes.current_status || 'Pending';
                var ship = (trackRes.data && trackRes.data.Shipment) ? trackRes.data.Shipment : (trackRes.data || {});
                var scans = trackRes.data && trackRes.data.Scans ? trackRes.data.Scans : (ship.Scans || []);
                var d = (bookingRes && bookingRes.data) ? bookingRes.data : {};
                var childAwb = trackRes.child_awb || bookingRes.child_awb || null;

                var statusBadge = 'secondary';
                if (status === 'Delivered') statusBadge = 'success';
                else if (status === 'In Transit' || status === 'Dispatched') statusBadge = 'primary';
                else if (status === 'Cancelled' || status === 'RTO Delivered' || status === 'Lost') statusBadge = 'danger';
                else if (status === 'Manifested') statusBadge = 'info';

                var waybillNo = d.waybill_no || ship.AWB || '';
                var refNo = d.booking_ref_id || ship.ReferenceNo || '';
                var childAwbs = d.child_awbs || null;
                var shippingMode = d.shipping_mode || '';
                var pickupPods = (function (arr) {
                    if (!Array.isArray(arr)) return arr ? [{ url: arr, date: '' }] : [];
                    return arr.map(function (x) { return typeof x === 'object' && x && x.url ? x : { url: x, date: '' }; });
                })(d.pickup_pod || []);
                var deliveryPods = (function (arr) {
                    if (!Array.isArray(arr)) return arr ? [{ url: arr, date: '' }] : [];
                    return arr.map(function (x) { return typeof x === 'object' && x && x.url ? x : { url: x, date: '' }; });
                })(d.delivery_pod || []);

                var html = '<div class="row"><div class="col-xxl-12"><div class="row">';
                html += '<div class="col-xl-9">';

                // Card: Tracking header + Summary (ecommerce-order-details style)
                html += '<div class="card"><div class="card-header align-items-start p-4">';
                html += '<div><h3 class="mb-1 d-flex fs-xl align-items-center">AWB #' + (waybillNo || '-') + '</h3>';
                if (childAwb) {
                    html += '<div class="alert alert-info py-1 px-3 mb-2 d-inline-flex align-items-center gap-2" style="font-size:13px;">'
                        + '<i class="ti ti-link"></i>'
                        + ' Child AWB <strong>' + childAwb + '</strong> &nbsp;&rarr;&nbsp; tracking under Parent AWB <strong>' + (waybillNo || '-') + '</strong>'
                        + '</div><br>';
                }
                html += '<p class="text-muted mb-3"><i class="ti ti-calendar"></i> ' + (d.created_at ? new Date(d.created_at).toLocaleString() : '') + '</p>';
                html += '<span class="badge badge-soft-' + statusBadge + ' fs-xxs badge-label me-1"><i class="ti ti-truck align-middle fs-sm"></i> ' + status + '</span>';
                if (refNo) html += '<span class="badge badge-soft-info fs-xxs badge-label">Ref: ' + refNo + '</span>';
                html += '</div>';
                html += '<div class="ms-auto"><a href="tracking.php" class="btn btn-light me-1"><i class="ti ti-arrow-left me-1"></i> Track Another</a>';
                html += '<a href="tracking.php?id=' + (d.id || '') + '&waybill=' + encodeURIComponent(waybillNo) + '&live=1" class="btn btn-primary"><i class="ti ti-refresh me-1"></i> Refresh</a></div></div>';

                html += '<div class="card-body px-4"><h4 class="fs-sm mb-3">Shipment Summary</h4>';
                html += '<div class="table-responsive"><table class="table table-bordered table-custom table-nowrap align-middle mb-1">';
                html += '<thead class="bg-light align-middle bg-opacity-25 thead-sm"><tr class="text-uppercase fs-xxs"><th>Item</th><th>Value</th></tr></thead><tbody>';
                if (childAwb) {
                    html += '<tr><td>Child AWB</td><td><span class="badge badge-soft-info">' + childAwb + '</span></td></tr>';
                    html += '<tr><td>Parent AWB</td><td>' + (waybillNo || '-') + '</td></tr>';
                } else {
                    html += '<tr><td>AWB / Waybill</td><td>' + (waybillNo || '-') + '</td></tr>';
                }
                // Child AWBs (from packages)
                if (childAwbs) {
                    var awbArr = childAwbs.split(',');
                    var awbBadges = awbArr.map(function (a) { return '<span class="badge badge-soft-warning me-1">' + a.trim() + '</span>'; }).join('');
                    html += '<tr><td>Child AWBs</td><td>' + awbBadges + '</td></tr>';
                }
                html += '<tr><td>Booking Ref</td><td>' + (refNo || '-') + '</td></tr>';
                html += '<tr><td>No. of Boxes</td><td>' + (d.quantity || '-') + '</td></tr>';
                html += '<tr><td>Courier</td><td>' + (d.courier_name || '-') + '</td></tr>';
                // Mode of shipment
                if (shippingMode) {
                    var modeMap = { air: 'Air', surface: 'Surface', express: 'Express' };
                    var modeLabel = modeMap[shippingMode.toLowerCase()] || shippingMode;
                    var modeBadge = shippingMode.toLowerCase() === 'surface' ? 'badge-soft-secondary' : (shippingMode.toLowerCase() === 'air' ? 'badge-soft-primary' : 'badge-soft-info');
                    html += '<tr><td>Mode of Shipment</td><td><span class="badge ' + modeBadge + '">' + modeLabel + '</span></td></tr>';
                }
                html += '<tr><td>Payment</td><td>' + (d.payment_mode || '-') + (d.payment_mode === "COD" && d.cod_amount ? " ₹" + d.cod_amount : "") + '</td></tr>';
                if (ship.Origin) html += '<tr><td>Origin</td><td>' + ship.Origin + '</td></tr>';
                if (ship.Destination) html += '<tr><td>Destination</td><td>' + ship.Destination + '</td></tr>';
                if (ship.PickUpDate) html += '<tr><td>Pickup Date</td><td>' + new Date(ship.PickUpDate).toLocaleString() + '</td></tr>';
                if (ship.DeliveryDate) html += '<tr><td>Delivery Date</td><td>' + new Date(ship.DeliveryDate).toLocaleString() + '</td></tr>';
                // POD thumbnails row
                html += '<tr><td>POD</td><td>';
                html += '<div class="pod-thumbs">';
                html += '<div class="pod-thumb-item"><span class="pod-thumb-label">Pickup</span><div class="pod-thumb-imgs">';
                if (pickupPods.length) {
                    pickupPods.forEach(function (item, i) {
                        var tip = 'Pickup ' + (i + 1) + '/' + pickupPods.length + (item.date ? ' | ' + item.date : '');
                        html += '<img src="' + item.url + '" class="pod-thumb-img" onclick="openPodOverlay(\'' + item.url.replace(/'/g, "\\'") + '\')" title="' + tip.replace(/"/g, '&quot;') + '">';
                    });
                } else {
                    html += '<span class="pod-thumb-empty"><i class="ti ti-camera-off"></i></span>';
                }
                html += '</div></div>';
                html += '<div class="pod-thumb-divider"></div>';
                html += '<div class="pod-thumb-item"><span class="pod-thumb-label">Delivery</span><div class="pod-thumb-imgs">';
                if (deliveryPods.length) {
                    deliveryPods.forEach(function (item, i) {
                        var tip = 'POD ' + (i + 1) + '/' + deliveryPods.length + (item.date ? ' | ' + item.date : '');
                        html += '<img src="' + item.url + '" class="pod-thumb-img" onclick="openPodOverlay(\'' + item.url.replace(/'/g, "\\'") + '\')" title="' + tip.replace(/"/g, '&quot;') + '">';
                    });
                } else {
                    html += '<span class="pod-thumb-empty"><i class="ti ti-file-x"></i></span>';
                }
                html += '</div></div></div>';
                html += '</td></tr>';
                html += '</tbody></table></div></div></div>';

                // Shipping Activity: current = blinking green, old = orange, full detail
                html += '<div class="card mt-3"><div class="card-header"><h4 class="card-title">Shipping Activity</h4></div><div class="card-body p-4">';
                if (scans.length === 0) {
                    html += '<p class="text-muted mb-0">No scan history yet.</p>';
                } else {
                    // Current status = API's current_status (e.g. Manifested). Mark the matching scan (most recent one) as current.
                    var currentIdx = -1;
                    for (var i = scans.length - 1; i >= 0; i--) {
                        var s = scans[i].ScanDetail || scans[i];
                        if ((s.Scan || '') === status) { currentIdx = i; break; }
                    }
                    if (currentIdx < 0) currentIdx = scans.length - 1;
                    html += '<div class="timeline">';
                    scans.forEach(function (item, idx) {
                        var sd = item.ScanDetail || item;
                        var dt = sd.ScanDateTime || sd.StatusDateTime || '';
                        var loc = sd.ScannedLocation || sd.ScanLocation || '';
                        var scanStatus = (sd.Scan || sd.ScanType || sd.StatusCode || 'Update').trim() || 'Update';
                        var scanType = sd.ScanType || '';
                        var statusCode = sd.StatusCode || sd.Status || '';
                        var instructions = sd.Instructions || '';
                        var isLast = idx === scans.length - 1;
                        var isCurrent = (idx === currentIdx);
                        var itemClass = isCurrent ? ' current-status' : ' old-status';
                        html += '<div class="timeline-item d-flex align-items-stretch' + itemClass + '">';
                        html += '<div class="timeline-time pe-3 text-muted">' + (dt ? new Date(dt).toLocaleString() : '') + '</div>';
                        html += '<div class="timeline-dot"></div>';
                        html += '<div class="timeline-content ps-3 ' + (isLast ? '' : 'pb-5') + '">';
                        html += '<h5 class="mb-2">' + scanStatus + (isCurrent ? ' <span class="badge bg-success-subtle text-success ms-1">Current</span>' : '') + '</h5>';
                        if (instructions) html += '<p class="mb-1">' + instructions + '</p>';
                        if (loc) html += '<p class="mb-1 text-muted"><i class="ti ti-map-pin me-1"></i>' + loc + '</p>';
                        html += '<div class="small text-muted mb-1">';
                        if (scanType) html += '<span class="me-2">ScanType: <strong>' + scanType + '</strong></span>';
                        if (statusCode) html += '<span class="me-2">Code: <strong>' + statusCode + '</strong></span>';
                        html += '</div>';
                        if (waybillNo) html += '<p class="mb-0 fs-xxs">Tracking No: <span class="fw-semibold">' + waybillNo + '</span></p>';
                        html += '</div></div>';
                    });
                    html += '</div>';
                }
                html += '</div></div></div>';

                // Add tracking scan (status from master list; same status is updated in booking)
                var bookingId = d.id;
                if (bookingId && canAddScan) {
                    html += '<div class="card mt-3"><div class="card-header"><h4 class="card-title">Add tracking scan</h4></div><div class="card-body p-4">';
                    html += '<p class="text-muted small mb-3">Status list is from <strong>Status Description</strong> (master). The same status is saved in tracking and in the booking.</p>';
                    html += '<div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>';
                    html += '<select class="form-select form-select-sm" id="addScanStatus" required><option value="">Choose status...</option></select></div>';
                    html += '<div class="col-md-2"><label class="form-label fw-semibold">Date <span class="text-danger">*</span></label><input type="datetime-local" class="form-control form-control-sm" id="addScanDate" required></div>';
                    html += '<div class="col-md-2"><label class="form-label fw-semibold">Location</label><input type="text" class="form-control form-control-sm" id="addScanLocation" placeholder="e.g. Branch"></div>';
                    html += '<div class="col-md-2"><label class="form-label fw-semibold">Remarks</label><input type="text" class="form-control form-control-sm" id="addScanRemarks" placeholder="Optional"></div>';
                    html += '<div class="col-md-2"><button type="button" class="btn btn-primary btn-sm w-100" id="btnAddScan" data-booking-id="' + bookingId + '"><i class="ti ti-plus me-1"></i> Update status</button></div></div>';
                    html += '</div></div></div>';
                }

                // Sidebar: Customer Details (ecommerce-order-details style)
                html += '<div class="col-xl-3"><div class="card"><div class="card-header justify-content-between border-dashed"><h4 class="card-title">Customer Details</h4></div><div class="card-body">';
                html += '<p class="mb-1"><strong>' + (d.consignee_name || '-') + '</strong></p>';
                html += '<p class="text-muted mb-1"><i class="ti ti-phone"></i> ' + (d.consignee_phone || '-') + '</p>';
                html += '<p class="text-muted mb-0 small">' + (d.consignee_address || '-') + '<br>' + (d.consignee_city || '') + ' ' + (d.consignee_state || '') + ' - ' + (d.consignee_pin || '') + '</p>';
                html += '</div></div></div></div></div></div>';

                $('#trackingContent').html(html);

                // Populate status dropdown from master-status-list (api/master_status/read.php)
                if (bookingId && canAddScan) {
                    var $statusSelect = $('#addScanStatus');
                    $.get('api/master_status/read.php?length=-1&status=active', function (resp) {
                        if (resp.data && resp.data.length) {
                            $statusSelect.find('option:not(:first)').remove();
                            resp.data.forEach(function (s) {
                                $statusSelect.append('<option value="' + (s.name || '').replace(/"/g, '&quot;') + '">' + (s.name || '') + '</option>');
                            });
                        }
                    });
                    var now = new Date();
                    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
                    $('#addScanDate').val(now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':00');
                    $('#btnAddScan').on('click', function () {
                        var bid = $(this).data('booking-id');
                        var st = $('#addScanStatus').val();
                        var dt = $('#addScanDate').val();
                        if (!st || !dt) { alert('Please select status and date.'); return; }
                        var dtStr = (dt.indexOf('T') !== -1) ? (dt.replace('T', ' ') + (dt.length <= 16 ? ':00' : '')) : (dt + ' 00:00:00');
                        var $btn = $(this).prop('disabled', true).html('<i class="ti ti-loader spin me-1"></i> Saving...');
                        $.post('api/statusupdate/create.php', {
                            booking_id: bid,
                            status: st,
                            status_date: dtStr,
                            location: $('#addScanLocation').val() || '',
                            remarks: $('#addScanRemarks').val() || ''
                        }).done(function (res) {
                            if (res.status === 'success') {
                                loadTracking();
                            } else {
                                alert(res.message || 'Update failed.');
                                $btn.prop('disabled', false).html('<i class="ti ti-plus me-1"></i> Update status');
                            }
                        }).fail(function () {
                            alert('Request failed.');
                            $btn.prop('disabled', false).html('<i class="ti ti-plus me-1"></i> Update status');
                        });
                    });
                }
            }

            function loadTracking() {
                var id = trackId || $('#trackById').val() || '';
                var wb = waybill || $('#trackByWaybill').val() || '';
                if (!id && !wb) {
                    alert('Enter Booking ID or Waybill number.');
                    return;
                }
                var q = id ? ('id=' + id) : ('waybill=' + encodeURIComponent(wb));
                var live = liveRefresh ? '&live=1' : '';
                var trackUrl = 'api/tracking/read.php?' + q + live;
                var bookUrl = 'api/booking/readone.php?' + (id ? ('id=' + id) : ('waybill=' + encodeURIComponent(wb)));

                $('#trackingContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading tracking...</p></div>');

                $.when(
                    $.get(trackUrl),
                    $.get(bookUrl)
                ).done(function (trackRes, bookRes) {
                    trackRes = trackRes[0] || trackRes;
                    bookRes = bookRes[0] || bookRes;
                    if (trackRes.status !== 'success') {
                        $('#trackingContent').html('<div class="alert alert-danger">' + (trackRes.message || 'Failed to load tracking.') + ' <a href="tracking.php" class="alert-link">Track another</a></div>');
                        return;
                    }
                    buildTrackingPage(trackRes, bookRes);
                }).fail(function () {
                    $('#trackingContent').html('<div class="alert alert-danger">Request failed. <a href="tracking.php" class="alert-link">Try again</a></div>');
                });
            }

            if (trackId > 0 || waybill) {
                loadTracking();
            }

            $('#btnTrack').on('click', function () {
                trackId = 0;
                waybill = '';
                loadTracking();
            });
        })();

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
    </script>
</body>

</html>