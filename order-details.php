<?php include 'header.php'; ?>
<?php
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">
                    <div class="page-title-head d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="fs-xl fw-bold m-0">Order Details</h4>
                        </div>
                        <div class="text-end">
                            <ol class="breadcrumb m-0 py-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="shipment-list.php">Booking</a></li>
                                <li class="breadcrumb-item active">Order Details</li>
                            </ol>
                        </div>
                    </div>

                    <div id="orderDetailsContent">
                        <?php if ($bookingId <= 0): ?>
                            <div class="alert alert-info">Select a shipment from <a href="shipment-list.php"
                                    class="alert-link">Booking List</a> to view order details, or open with
                                <code>?id=</code>.
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Loading order...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script>
        (function () {
            var id = <?php echo json_encode($bookingId); ?>;
            if (id <= 0) return;

            $.get('api/booking/readone.php?id=' + id, function (res) {
                if (res.status !== 'success' || !res.data) {
                    $('#orderDetailsContent').html('<div class="alert alert-danger">' + (res.message || 'Failed to load order.') + '</div>');
                    return;
                }
                var d = res.data;
                var ship = (d.ShipmentData && d.ShipmentData.Shipment) ? d.ShipmentData.Shipment : null;

                // For Own Booking, scans might be in d.ShipmentData.data.Scans
                var scans = [];
                if (ship && ship.Scans) {
                    scans = ship.Scans;
                } else if (d.ShipmentData && d.ShipmentData.data && d.ShipmentData.data.Scans) {
                    scans = d.ShipmentData.data.Scans;
                }

                var status = d.last_status;
                if (!status && ship && ship.Status && ship.Status.Status) {
                    status = ship.Status.Status;
                }
                if (!status) status = 'Pending';

                var statusBadge = 'secondary';
                if (status === 'Delivered') statusBadge = 'success';
                else if (status === 'In Transit' || status === 'Dispatched') statusBadge = 'primary';
                else if (status === 'Cancelled' || status === 'RTO Delivered') statusBadge = 'danger';
                else if (status === 'Manifested') statusBadge = 'info';

                var html = '<div class="row"><div class="col-xl-9">';
                html += '<div class="card"><div class="card-header align-items-start p-4">';
                html += '<div><h3 class="mb-1 fs-xl">Order #' + (d.booking_ref_id || d.id) + '</h3>';
                html += '<p class="text-muted mb-3"><i class="ti ti-calendar"></i> ' + (d.created_at ? new Date(d.created_at).toLocaleString() : '') + '</p>';
                html += '<span class="badge bg-' + statusBadge + '-subtle text-' + statusBadge + ' me-1">' + status + '</span>';
                if (d.waybill_no) html += '<span class="badge bg-info-subtle text-info">AWB: ' + d.waybill_no + '</span>';
                html += '</div><div class="ms-auto"><a href="shipment-list.php" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i> Back to List</a></div></div>';
                html += '<div class="card-body px-4"><h4 class="fs-sm mb-3">Order Summary</h4>';
                html += '<table class="table table-bordered table-nowrap align-middle mb-1"><thead class="bg-light"><tr class="text-uppercase fs-xxs"><th>Item</th><th>Value</th></tr></thead><tbody>';
                html += '<tr><td>Booking Ref</td><td>' + (d.booking_ref_id || '-') + '</td></tr>';
                html += '<tr><td>Parent AWB</td><td>' + (d.waybill_no || '-') + '</td></tr>';
                html += '<tr><td>Ewaybill No</td><td>' + (d.ewaybill_no || '-') + '</td></tr>';
                html += '<tr><td>Courier</td><td>' + (d.courier_name || '-') + '</td></tr>';
                html += '<tr><td>Service</td><td><span class="badge bg-' + (d.shipping_mode === 'Express' ? 'warning' : 'info') + '-subtle text-' + (d.shipping_mode === 'Express' ? 'warning' : 'info') + '">' + (d.shipping_mode || '-') + '</span></td></tr>';
                html += '<tr><td>Payment</td><td>' + (d.payment_mode || '-') + (d.payment_mode === 'COD' && d.cod_amount ? ' ₹' + d.cod_amount : '') + '</td></tr>';
                if (ship) {
                    if (ship.Origin) html += '<tr><td>Origin</td><td>' + ship.Origin + '</td></tr>';
                    if (ship.Destination) html += '<tr><td>Destination</td><td>' + ship.Destination + '</td></tr>';
                    if (ship.PickUpDate) html += '<tr><td>Pickup Date</td><td>' + new Date(ship.PickUpDate).toLocaleString() + '</td></tr>';
                    if (ship.DeliveryDate) html += '<tr><td>Delivery Date</td><td>' + new Date(ship.DeliveryDate).toLocaleString() + '</td></tr>';
                }
                html += '</tbody></table></div></div>';

                // Package Details Section
                html += '<div class="card mt-3"><div class="card-header"><h4 class="card-title">Package Details</h4></div>';
                html += '<div class="card-body p-4">';
                html += '<div class="row mb-3">';
                html += '<div class="col-md-4"><strong>Total Quantity:</strong> ' + (d.quantity || '-') + ' Box(es)</div>';
                html += '<div class="col-md-4"><strong>Total Weight:</strong> ' + (d.weight ? (d.weight / 1000).toFixed(2) + ' Kg' : '-') + '</div>';
                html += '</div>';

                var pkgList = [];
                var bookingPkgs = d.booking_packages || [];
                try {
                    if (d.package_details) {
                        pkgList = typeof d.package_details === 'string' ? JSON.parse(d.package_details) : d.package_details;
                    }
                } catch (e) { console.error("Error parsing package_details", e); }

                if (bookingPkgs.length > 0) {
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">';
                    html += '<thead class="bg-light text-uppercase fs-xxs"><tr><th>#</th><th>Child AWB</th><th>Child Ewaybill No</th><th>Dimensions (LxWxH)</th><th>Weight/Box</th><th>Boxes</th><th>Total Vol. Wt</th></tr></thead><tbody>';
                    bookingPkgs.forEach(function (bp, idx) {
                        var volWt = (bp.length && bp.width && bp.height) ? ((bp.length * bp.width * bp.height) / 5000) * (bp.boxes || 1) : 0;
                        html += '<tr>';
                        html += '<td>' + (bp.row_no || (idx + 1)) + '</td>';
                        html += '<td>' + (bp.awb_no || '-') + '</td>';
                        html += '<td>' + (bp.child_ewaybill_no || '-') + '</td>';
                        html += '<td>' + (bp.length && bp.width && bp.height ? bp.length + ' x ' + bp.width + ' x ' + bp.height + ' cm' : '-') + '</td>';
                        html += '<td>' + (bp.actual_weight || '-') + ' Kg</td>';
                        html += '<td>' + (bp.boxes || 1) + '</td>';
                        html += '<td>' + volWt.toFixed(2) + ' Kg</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else if (pkgList && pkgList.length > 0) {
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">';
                    html += '<thead class="bg-light text-uppercase fs-xxs"><tr><th>#</th><th>Child AWB</th><th>Child Ewaybill No</th><th>Dimensions (LxWxH)</th><th>Weight/Box</th><th>Boxes</th><th>Total Vol. Wt</th></tr></thead><tbody>';
                    pkgList.forEach(function (pkg, idx) {
                        var volWt = ((pkg.length * pkg.width * pkg.height) / 5000) * (pkg.boxes || 1);
                        html += '<tr>';
                        html += '<td>' + (idx + 1) + '</td>';
                        html += '<td>' + (pkg.awb_no || '-') + '</td>';
                        html += '<td>' + (pkg.child_ewaybill_no || '-') + '</td>';
                        html += '<td>' + pkg.length + ' x ' + pkg.width + ' x ' + pkg.height + ' cm</td>';
                        html += '<td>' + (pkg.actual_weight || '-') + ' Kg</td>';
                        html += '<td>' + (pkg.boxes || 1) + '</td>';
                        html += '<td>' + volWt.toFixed(2) + ' Kg</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html += '<p class="text-muted mb-0">Dimensions: ' + (d.length || '-') + 'x' + (d.width || '-') + 'x' + (d.height || '-') + ' cm</p>';
                }
                html += '</div></div>';

                html += '<div class="card mt-3"><div class="card-header"><h4 class="card-title">Shipping Activity</h4></div><div class="card-body p-4">';
                if (scans.length === 0) {
                    html += '<p class="text-muted mb-0">No scan history yet.</p>';
                } else {
                    html += '<div class="timeline">';
                    scans.forEach(function (item, idx) {
                        var sd = item.ScanDetail || {};
                        var dt = sd.ScanDateTime || sd.StatusDateTime || '';
                        var loc = sd.ScannedLocation || sd.ScanLocation || '';
                        var scanStatus = sd.Scan || '';
                        var instructions = sd.Instructions || '';
                        var isLast = idx === scans.length - 1;
                        html += '<div class="timeline-item d-flex align-items-stretch">';
                        html += '<div class="timeline-time pe-3 text-muted">' + (dt ? new Date(dt).toLocaleString() : '') + '</div>';
                        html += '<div class="timeline-dot ' + (scanStatus === 'Delivered' ? 'bg-success' : 'bg-light') + '"></div>';
                        html += '<div class="timeline-content ps-3 ' + (isLast ? '' : 'pb-5') + '">';
                        html += '<h5 class="mb-1">' + (scanStatus || 'Update') + '</h5>';
                        if (instructions) html += '<p class="mb-1 text-muted">' + instructions + '</p>';
                        if (loc) html += '<p class="mb-1 text-muted fs-xxs"><i class="ti ti-map-pin"></i> ' + loc + '</p>';
                        html += '</div></div>';
                    });
                    html += '</div>';
                }
                html += '</div></div></div>';

                html += '<div class="col-xl-3"><div class="card"><div class="card-header border-dashed"><h4 class="card-title">Customer / Consignee</h4></div><div class="card-body">';
                html += '<p class="mb-1"><strong>' + (d.consignee_name || '-') + '</strong></p>';
                html += '<p class="text-muted mb-1"><i class="ti ti-phone"></i> ' + (d.consignee_phone || '-') + '</p>';
                html += '<p class="text-muted mb-0 small">' + (d.consignee_address || '-') + '<br>' + (d.consignee_city || '') + ' ' + (d.consignee_state || '') + ' - ' + (d.consignee_pin || '') + '</p>';
                html += '</div></div></div></div>';

                $('#orderDetailsContent').html(html);
            }).fail(function () {
                $('#orderDetailsContent').html('<div class="alert alert-danger">Failed to load order.</div>');
            });
        })();
    </script>
</body>

</html>