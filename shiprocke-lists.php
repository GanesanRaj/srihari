<?php include 'header.php'; ?>
<?php // require_permission('shipment', 'is_view'); ?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"><i data-lucide="truck"></i> Shiprocket Booking List</h6>
                                        </div>
                                        <div class="text-end">
                                            <a href="shipment-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Shipment
                                            </a>
                                            <a href="shiprocket-manifest-list.php" class="btn btn-info btn-sm ms-1">
                                                <i class="ti ti-list-details me-1"></i> Manifest List
                                            </a>
                                        </div>
                                    </div>

                                    <div class="row mb-3 g-2">
                                        <div class="col-md-3">
                                            <select id="shiprocketCourierFilter" class="form-select form-select-sm">
                                                <option value="">Select Shiprocket Account</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="pickupPointFilter" class="form-select form-select-sm">
                                                <option value="">Pickup Point (Optional)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="branchFilter" class="form-select form-select-sm">
                                                <option value="">Branch (Optional)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="clientFilter" class="form-select form-select-sm">
                                                <option value="">Client (Optional)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div id="shipment-range"
                                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                            <button id="btnGenerateManifest" class="btn btn-sm btn-success me-1" disabled>
                                                <i class="ti ti-file-text me-1"></i> Generate Manifest
                                            </button>
                                            <button id="btnCancelSelected" class="btn btn-sm btn-danger me-1" disabled>
                                                <i class="ti ti-ban me-1"></i> Cancel Selected
                                            </button>
                                            <button id="btnPrintSelected" class="btn btn-sm btn-warning" disabled>
                                                <i class="ti ti-printer me-1"></i> Print Labels
                                            </button>
                                        </div>
                                    </div>

                                    <table id="shiprocketTable" class="table table-hover dt-responsive w-100">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;"><input type="checkbox" id="selectAllRows"></th>
                                                <th>Waybill</th>
                                                <th>Ref ID</th>
                                                <th>Shiprocket Service</th>
                                                <th>Sub Courier</th>
                                                <th>SR Order ID</th>
                                                <th>SR Shipment ID</th>
                                                <th>Consignee</th>
                                                <th>Status</th>
                                                <th>Manifest</th>
                                                <th>Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="shiprocketActionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="shiprocketActionModalTitle">Shiprocket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="shiprocketActionModalBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" id="shiprocketModalCancelBtn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="shiprocketModalOkBtn">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                $(document).ready(function () {
                    var table;
                    var startDate = moment().startOf('month').format('YYYY-MM-DD');
                    var endDate = moment().endOf('month').format('YYYY-MM-DD');
                    var selectedRows = {};
                    var actionModalEl = document.getElementById('shiprocketActionModal');
                    var actionModal = actionModalEl ? new bootstrap.Modal(actionModalEl) : null;
                    var pendingConfirmAction = null;

                    function openInfoModal(title, html, okLabel) {
                        if (!actionModal) return;
                        $('#shiprocketActionModalTitle').text(title || 'Info');
                        $('#shiprocketActionModalBody').html(html || '');
                        $('#shiprocketModalCancelBtn').hide();
                        $('#shiprocketModalOkBtn').text(okLabel || 'OK').removeClass('btn-danger').addClass('btn-primary');
                        pendingConfirmAction = null;
                        actionModal.show();
                    }

                    function openConfirmModal(title, html, okLabel, onConfirm) {
                        if (!actionModal) {
                            if (confirm($(html).text() || 'Confirm?')) onConfirm();
                            return;
                        }
                        $('#shiprocketActionModalTitle').text(title || 'Confirm');
                        $('#shiprocketActionModalBody').html(html || '');
                        $('#shiprocketModalCancelBtn').show();
                        $('#shiprocketModalOkBtn').text(okLabel || 'Confirm').removeClass('btn-primary').addClass('btn-danger');
                        pendingConfirmAction = onConfirm;
                        actionModal.show();
                    }

                    function openLoadingModal(title, html) {
                        if (!actionModal) return;
                        $('#shiprocketActionModalTitle').text(title || 'Processing');
                        $('#shiprocketActionModalBody').html(
                            '<div class="d-flex align-items-center gap-2">' +
                            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
                            '<div>' + (html || 'Please wait...') + '</div>' +
                            '</div>'
                        );
                        $('#shiprocketModalCancelBtn').hide();
                        $('#shiprocketModalOkBtn').hide();
                        pendingConfirmAction = null;
                        actionModal.show();
                    }

                    $('#shiprocketModalOkBtn').on('click', function () {
                        if (typeof pendingConfirmAction === 'function') {
                            var fn = pendingConfirmAction;
                            pendingConfirmAction = null;
                            actionModal.hide();
                            fn();
                            return;
                        }
                        actionModal.hide();
                    });

                    $('#shiprocketCourierFilter').select2({ width: '100%' });
                    $('#pickupPointFilter').select2({ width: '100%' });
                    $('#branchFilter').select2({ width: '100%' });
                    $('#clientFilter').select2({ width: '100%' });

                    function updateActionBtns() {
                        var count = Object.keys(selectedRows).length;
                        $('#btnPrintSelected')
                            .prop('disabled', count === 0)
                            .html('<i class="ti ti-printer me-1"></i> Print Labels' + (count > 0 ? ' (' + count + ')' : ''));
                        $('#btnGenerateManifest')
                            .prop('disabled', count === 0)
                            .html('<i class="ti ti-file-text me-1"></i> Generate Manifest' + (count > 0 ? ' (' + count + ')' : ''));
                        $('#btnCancelSelected')
                            .prop('disabled', count === 0)
                            .html('<i class="ti ti-ban me-1"></i> Cancel' + (count > 0 ? ' (' + count + ')' : ''));
                    }

                    function dateCb(start, end) {
                        $('#shipment-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#shipment-range').daterangepicker({
                        startDate: moment().startOf('month'),
                        endDate: moment().endOf('month'),
                        ranges: {
                            'Today': [moment(), moment()],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, dateCb);
                    dateCb(moment().startOf('month'), moment().endOf('month'));

                    $.get('api/shipment/get_unique_statuses.php', function (res) {
                        if (res.data) {
                            res.data.forEach(function (s) {
                                $('#statusFilter').append('<option value="' + s + '">' + s + '</option>');
                            });
                        }
                    });

                    // Load only shiprocket courier partners
                    $.get('api/courier_partner/read.php?length=1000', function (res) {
                        var firstId = '';
                        if (res.data) {
                            res.data.forEach(function (c) {
                                var name = (c.partner_name || '').toLowerCase();
                                var code = (c.partner_code || '').toLowerCase();
                                if (name.indexOf('shiprocket') !== -1 || code.indexOf('sr') === 0 || String(c.id) === '4') {
                                    $('#shiprocketCourierFilter').append('<option value="' + c.id + '">' + c.partner_name + '</option>');
                                    if (!firstId) firstId = String(c.id);
                                }
                            });
                        }
                        if (firstId) {
                            $('#shiprocketCourierFilter').val(firstId).trigger('change');
                        }
                    });

                    function loadBranchFilter() {
                        $.get('api/branch/read.php?length=1000', function (res) {
                            var rows = (res && res.data) ? res.data : [];
                            $('#branchFilter').empty().append('<option value="">Branch (Optional)</option>');
                            rows.forEach(function (b) {
                                var id = b.id || b.branch_id || '';
                                var nm = b.branch_name || b.name || '';
                                if (id && nm) $('#branchFilter').append('<option value="' + id + '">' + nm + '</option>');
                            });
                            $('#branchFilter').trigger('change.select2');
                        });
                    }

                    function loadClientFilter() {
                        $.get('api/client/read.php?length=1000', function (res) {
                            var rows = (res && res.data) ? res.data : [];
                            $('#clientFilter').empty().append('<option value="">Client (Optional)</option>');
                            rows.forEach(function (c) {
                                var id = c.id || c.client_id || '';
                                var nm = c.client_name || c.name || '';
                                if (id && nm) $('#clientFilter').append('<option value="' + id + '">' + nm + '</option>');
                            });
                            $('#clientFilter').trigger('change.select2');
                        });
                    }

                    function loadPickupPointFilter() {
                        var courierId = $('#shiprocketCourierFilter').val() || '';
                        var url = 'api/pickuppoint/read.php?length=-1';
                        if (courierId) url += '&courier_id=' + encodeURIComponent(courierId);
                        $.get(url, function (res) {
                            var rows = (res && res.data) ? res.data : [];
                            $('#pickupPointFilter').empty().append('<option value="">Pickup Point (Optional)</option>');
                            rows.forEach(function (p) {
                                var id = p.id || '';
                                var nm = p.name || p.pickup_point_code || '';
                                if (id && nm) $('#pickupPointFilter').append('<option value="' + id + '">' + nm + '</option>');
                            });
                            $('#pickupPointFilter').trigger('change.select2');
                        });
                    }

                    loadBranchFilter();
                    loadClientFilter();

                    table = $('#shiprocketTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/shipment/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.courier_id = $('#shiprocketCourierFilter').val();
                                d.pickup_point_id = $('#pickupPointFilter').val();
                                d.branch_id = $('#branchFilter').val();
                                d.client_id = $('#clientFilter').val();
                                d.status = $('#statusFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        columns: [
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                render: function (_, __, row) {
                                    var wb = row.waybill_no || '';
                                    var id = row.id || '';
                                    if (!wb) return '';
                                    var checked = selectedRows[id] ? 'checked' : '';
                                    return '<input type="checkbox" class="row-select" data-id="' + id + '" data-waybill="' + wb + '" ' + checked + '>';
                                }
                            },
                            { data: 'waybill_no', defaultContent: '-' },
                            { data: 'booking_ref_id', defaultContent: '-' },
                            {
                                data: 'shiprocket_courier_company_name',
                                render: function (v) { return v || '<span class="text-muted">-</span>'; }
                            },
                            {
                                data: 'shiprocket_child_courier_name',
                                render: function (v) { return v || '<span class="text-muted">-</span>'; }
                            },
                            { data: 'shiprocket_order_id', defaultContent: '-' },
                            { data: 'shiprocket_shipment_id', defaultContent: '-' },
                            {
                                data: null,
                                render: function (_, __, row) {
                                    var phone = row.consignee_phone ? '<br><small>' + row.consignee_phone + '</small>' : '';
                                    return (row.consignee_name || '-') + phone;
                                }
                            },
                            { data: 'last_status', defaultContent: 'Created' },
                            {
                                data: 'is_manifest',
                                render: function (v) {
                                    return String(v) === '1'
                                        ? '<span class="badge bg-success-subtle text-success">Manifested</span>'
                                        : '<span class="badge bg-secondary-subtle text-muted">Pending</span>';
                                }
                            },
                            { data: 'created_at', defaultContent: '-' },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                render: function (_, __, row) {
                                    var html = '<a href="order-details.php?id=' + row.id + '" class="btn btn-sm btn-outline-primary me-1">View</a>';
                                    if (row.waybill_no) {
                                        html += '<a href="shipment-label-print.php?waybill=' + encodeURIComponent(row.waybill_no) + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1">Label</a>';
                                    }
                                    html += '<button class="btn btn-sm btn-outline-danger btn-cancel-single" data-id="' + row.id + '" data-waybill="' + (row.waybill_no || '') + '">Cancel</button>';
                                    return html;
                                }
                            }
                        ],
                        order: [[10, 'desc']],
                        drawCallback: function () {
                            updateActionBtns();
                            var all = $('#shiprocketTable tbody .row-select').length;
                            var checked = $('#shiprocketTable tbody .row-select:checked').length;
                            $('#selectAllRows').prop('checked', all > 0 && all === checked);
                            $('#selectAllRows').prop('indeterminate', checked > 0 && checked < all);
                        }
                    });

                    $('#shiprocketCourierFilter, #pickupPointFilter, #branchFilter, #clientFilter, #statusFilter').on('change', function () {
                        selectedRows = {};
                        $('#selectAllRows').prop('checked', false).prop('indeterminate', false);
                        updateActionBtns();
                        if (this.id === 'shiprocketCourierFilter') {
                            loadPickupPointFilter();
                        }
                        table.ajax.reload();
                    });

                    $('#shiprocketTable').on('change', '.row-select', function () {
                        var id = String($(this).data('id') || '');
                        var wb = $(this).data('waybill');
                        if (!id || !wb) return;
                        if ($(this).is(':checked')) selectedRows[id] = wb;
                        else delete selectedRows[id];
                        updateActionBtns();
                    });

                    $('#selectAllRows').on('change', function () {
                        var checked = $(this).is(':checked');
                        $('#shiprocketTable tbody .row-select').each(function () {
                            var id = String($(this).data('id') || '');
                            var wb = $(this).data('waybill');
                            if (!id || !wb) return;
                            $(this).prop('checked', checked);
                            if (checked) selectedRows[id] = wb;
                            else delete selectedRows[id];
                        });
                        updateActionBtns();
                    });

                    $('#btnPrintSelected').on('click', function () {
                        var waybills = Object.keys(selectedRows).map(function (id) { return selectedRows[id]; });
                        if (!waybills.length) {
                            openInfoModal('Validation', 'Select at least one shipment.');
                            return;
                        }
                        waybills.forEach(function (wb, idx) {
                            setTimeout(function () {
                                window.open('shipment-label-print.php?waybill=' + encodeURIComponent(wb), '_blank');
                            }, idx * 120);
                        });
                    });

                    $('#btnGenerateManifest').on('click', function () {
                        var $btn = $(this);
                        var ids = Object.keys(selectedRows).map(function (x) { return parseInt(x, 10); }).filter(function (x) { return x > 0; });
                        if (!ids.length) {
                            openInfoModal('Validation', 'Select at least one shipment.');
                            return;
                        }
                        openConfirmModal('Generate Manifest', 'Generate manifest for selected shipments?', 'Generate', function () {
                            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');
                            openLoadingModal('Generate Manifest', 'Generating manifest for selected shipments...');
                            $.ajax({
                                url: 'api/shipment/shiprocket_manifest_create.php',
                                type: 'POST',
                                contentType: 'application/json',
                                dataType: 'json',
                                data: JSON.stringify({
                                    booking_ids: ids,
                                    pickup_point_id: $('#pickupPointFilter').val() || ''
                                }),
                                success: function (res) {
                                    if (actionModal) actionModal.hide();
                                    if (res && res.status === 'success') {
                                        var detailsHtml = 'Manifest ID: <b>' + (res.manifested_id || '-') + '</b><br>AWB Count: <b>' + (res.awb_count || 0) + '</b>';
                                        
                                        if (res.pickup_details && res.pickup_details.length > 0) {
                                            detailsHtml += '<hr><h6 class="mb-2">Pickup Details:</h6>';
                                            detailsHtml += '<div class="table-responsive"><table class="table table-sm table-bordered fs-12 mb-0">';
                                            detailsHtml += '<thead class="table-light"><tr><th>AWB</th><th>Reference No</th><th>Scheduled Date</th></tr></thead><tbody>';
                                            res.pickup_details.forEach(function(pd) {
                                                detailsHtml += '<tr>';
                                                detailsHtml += '<td>' + (pd.awb || '-') + '</td>';
                                                detailsHtml += '<td>' + (pd.pickup_token_number || '-') + '</td>';
                                                detailsHtml += '<td>' + (pd.pickup_scheduled_date || '-') + '</td>';
                                                detailsHtml += '</tr>';
                                            });
                                            detailsHtml += '</tbody></table></div>';
                                        }

                                        var invoiceUrl = (res.invoice_url || '').trim();
                                        if (invoiceUrl) {
                                            detailsHtml += '<div class="mt-3"><a href="' + invoiceUrl + '" target="_blank" class="btn btn-sm btn-info w-100"><i class="ti ti-printer me-1"></i> Print Invoice</a></div>';
                                        } else {
                                            var invErr = '';
                                            if (res.invoice_response && res.invoice_response.error) {
                                                invErr = typeof res.invoice_response.error === 'string' ? res.invoice_response.error : JSON.stringify(res.invoice_response.error);
                                            } else if (res.invoice_response && res.invoice_response.message) {
                                                invErr = res.invoice_response.message;
                                            }
                                            if (invErr) {
                                                detailsHtml += '<div class="mt-3 text-danger fs-13"><i class="ti ti-alert-circle"></i> Invoice Generation Failed: ' + invErr + '</div>';
                                            }
                                        }

                                        openInfoModal(
                                            'Manifest Created',
                                            detailsHtml,
                                            'Close'
                                        );
                                        var manifestUrl = (res.print_manifest_url || res.manifest_url || '').trim();
                                        if (manifestUrl) {
                                            window.open(manifestUrl, '_blank');
                                        }
                                        selectedRows = {};
                                        $('#selectAllRows').prop('checked', false).prop('indeterminate', false);
                                        updateActionBtns();
                                        table.ajax.reload(null, false);
                                    } else {
                                        var msg = (res && res.message) ? res.message : 'Manifest creation failed.';
                                        if (res && Array.isArray(res.failed_awbs) && res.failed_awbs.length) {
                                            msg += '<br><br><b>Failed AWB:</b> ' + res.failed_awbs.join(', ');
                                        }
                                        openInfoModal('Manifest Error', msg);
                                    }
                                },
                                error: function (xhr) {
                                    if (actionModal) actionModal.hide();
                                    var msg = 'Manifest creation failed.';
                                    try {
                                        var j = JSON.parse(xhr.responseText || '{}');
                                        if (j.message) msg = j.message;
                                    } catch (e) {}
                                    openInfoModal('Manifest Error', msg);
                                },
                                complete: function () {
                                    updateActionBtns();
                                }
                            });
                        });
                    });

                    // Single cancel - service based (like delete pattern)
                    $('#shiprocketTable').on('click', '.btn-cancel-single', function () {
                        var id = $(this).data('id');
                        var waybill = $(this).data('waybill');
                        if (!waybill) { alert('No AWB number found.'); return; }
                        var msg = 'Cancel shipment ' + waybill + '?\n\nThis will call the courier API and update local status to Cancelled.';
                        if (!confirm(msg)) return;
                        doCancelBookings([id]);
                    });

                    // Bulk cancel - service based
                    $('#btnCancelSelected').on('click', function () {
                        var ids = Object.keys(selectedRows).map(function (x) { return parseInt(x, 10); }).filter(function (x) { return x > 0; });
                        var awbs = Object.values(selectedRows).filter(function (x) { return x; });
                        if (!ids.length) return;
                        var msg = 'Cancel ' + ids.length + ' selected shipment(s)?\n\nAWBs: ' + awbs.join(', ') + '\n\nThis will call the courier API and update local status to Cancelled.';
                        if (!confirm(msg)) return;
                        doCancelBookings(ids);
                    });

                    function doCancelBookings(ids) {
                        $.ajax({
                            url: 'api/shipment/booking_cancel.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ ids: ids }),
                            success: function (res) {
                                if (res.status === 'success') {
                                    shipCancelToast(res.message || 'Cancelled successfully.');
                                    selectedRows = {};
                                    $('#selectAllRows').prop('checked', false).prop('indeterminate', false);
                                    updateActionBtns();
                                    table.ajax.reload(null, false);
                                } else {
                                    alert('Error: ' + (res.message || 'Cancel failed.'));
                                }
                            },
                            error: function (xhr) {
                                var r = {};
                                try { r = JSON.parse(xhr.responseText); } catch (e) { }
                                alert('Server error: ' + (r.message || xhr.statusText));
                            }
                        });
                    }

                    function shipCancelToast(msg) {
                        var $t = $('<div style="position:fixed;bottom:24px;right:24px;z-index:9999;background:#dc3545;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="ti ti-circle-x me-1"></i>' + msg + '</div>');
                        $('body').append($t);
                        setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 3500);
                    }
                });
            </script>
        </div>
    </div>
</body>

