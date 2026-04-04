<?php include 'header.php'; ?>
<?php // require_permission('shipment', 'is_view'); ?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="px-0">



                    <!-- List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <!-- Page Title -->
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"> <i data-lucide="database"></i> Shipment
                                                Management</h4>
                                        </div>
                                        <div class="text-end">
                                            <a href="shipment-create.php" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i> New Shipment
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <select id="companyFilter" class="form-select form-select-sm">
                                                <option value="">All Companies</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="branchFilter" class="form-select form-select-sm">
                                                <option value="">All Branches</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="courierFilter" class="form-select form-select-sm">
                                                <option value="">All Couriers</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div id="shipment-range"
                                                class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <style>
                                        #shipmentTable,
                                        .text-primary,
                                        .text-info,
                                        .text-warning,
                                        .text-success,
                                        .text-danger {
                                            color: #000 !important;
                                        }

                                        #shipmentTable td {
                                            white-space: normal !important;
                                            vertical-align: top;
                                            min-width: 120px;
                                        }
                                    </style>

                                    <table id="shipmentTable" class="table table-hover dt-responsive w-100">
                                        <thead>
                                            <tr>
                                                <th>Shipment Info</th>
                                                <th style="width: 20%;">Status Info</th>
                                                <th style="width: 25%;">Sender Info</th>
                                                <th style="width: 25%;">Receiver Info</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>

                                    <div class="modal fade" id="ewbUpdateModal" tabindex="-1"
                                        aria-labelledby="ewbUpdateModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="ewbUpdateModalLabel">Manual EWB Update
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" id="ewbBookingId">
                                                    <div class="mb-3">
                                                        <label for="ewbInvoiceNo" class="form-label">Invoice Number
                                                            (DCN)</label>
                                                        <input type="text" class="form-control" id="ewbInvoiceNo"
                                                            placeholder="Enter invoice number">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="ewbNumber" class="form-label">E-Waybill Number
                                                            (EWBN)</label>
                                                        <input type="text" class="form-control" id="ewbNumber"
                                                            placeholder="Enter e-waybill number">
                                                    </div>
                                                    <small class="text-muted">This updates Delhivery EWB for the
                                                        selected shipment.</small>
                                                    <div id="ewbModalMsg" class="mt-2 small"></div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-warning"
                                                        id="btnSubmitEwbUpdate">
                                                        <i class="ti ti-refresh me-1"></i>Update EWB
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
            </div>


            <?php include 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <!-- Datatables js -->
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>

            <!-- Select2 js -->
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>
            <script src="assets/js/bootstrap.bundle.min.js"></script>

            <script>
                $(document).ready(function () {
                    let table;
                    const ewbModalEl = document.getElementById('ewbUpdateModal');
                    const ewbModal = ewbModalEl ? new bootstrap.Modal(ewbModalEl) : null;
                    const urlParams = new URLSearchParams(window.location.search);
                    const preStatus = urlParams.get('status');
                    const preFrom = urlParams.get('from');
                    const preTo = urlParams.get('to');

                    // Load Companies
                    $.get('api/company/read.php?length=1000', function (res) {
                        if (res.data) {
                            res.data.forEach(c => {
                                $('#companyFilter').append(`<option value="${c.id}">${c.company_name}</option>`);
                            });
                        }
                    });

                    // Load Used Statuses for Filter
                    $.get('api/shipment/get_unique_statuses.php', function (res) {
                        if (res.data) {
                            res.data.forEach(s => {
                                let selected = (preStatus && preStatus === s) ? 'selected' : '';
                                $('#statusFilter').append(`<option value="${s}" ${selected}>${s}</option>`);
                            });
                            // If table is already initialized, reload it
                            if (table) table.ajax.reload();
                        }
                    });

                    // Load Couriers for Filter
                    $.get('api/courier_partner/read.php?length=100', function (res) {
                        if (res.data) {
                            res.data.forEach(c => {
                                $('#courierFilter').append(`<option value="${c.id}">${c.partner_name}</option>`);
                            });
                        }
                    });

                    // Load Branches when Company changes
                    $('#companyFilter').change(function () {
                        var companyId = $(this).val();
                        $('#branchFilter').html('<option value="">All Branches</option>');
                        if (companyId) {
                            $.get('api/branch/read.php?length=1000&company_id=' + companyId, function (res) {
                                if (res.data) {
                                    res.data.forEach(b => {
                                        $('#branchFilter').append(`<option value="${b.id}">${b.branch_name}</option>`);
                                    });
                                }
                            });
                        }
                    });

                    // Date Range Picker
                    let initialStart = preFrom ? moment(preFrom) : moment().startOf('month');
                    let initialEnd = preTo ? moment(preTo) : moment().endOf('month');

                    let startDate = initialStart.format('YYYY-MM-DD');
                    let endDate = initialEnd.format('YYYY-MM-DD');

                    function cb(start, end) {
                        $('#shipment-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#shipment-range').daterangepicker({
                        startDate: initialStart,
                        endDate: initialEnd,
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);

                    cb(initialStart, initialEnd);


                    table = $('#shipmentTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "api/shipment/read.php",
                            type: "GET",
                            data: function (d) {
                                d.company_id = $('#companyFilter').val();
                                d.branch_id = $('#branchFilter').val();
                                d.courier_id = $('#courierFilter').val();
                                d.status = $('#statusFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        columns: [
                            // Shipment Info
                            {
                                data: null,
                                render: function (data, type, row) {
                                    let childAwbHtml = '';
                                    if (row.courier_id == 2 && row.child_awbs) {
                                        const awbs = row.child_awbs.split(',');
                                        childAwbHtml = `<br><span class="light">Child AWBs : </span>` +
                                            awbs.map(awb =>
                                                `<a href="shipment-label-print.php?waybill=${encodeURIComponent(row.waybill_no)}" target="_blank"
                                                    class="badge" style="background:#da7d41;color:#fff;font-size:10px;margin:1px;"
                                                    title="Print label for ${awb}">${awb}</a>`
                                            ).join(' ');
                                    }
                                    return `<i class="fa-solid fa-truck"></i> <span class="light">Waybill :</span> <strong style="color:#da7d41;">${row.waybill_no || 'N/A'}</strong>
                                        <br><span class="light">Ref No : </span> ${row.booking_ref_id || ''}
                                        <br><span class="light">Courier : </span> ${row.courier_name || ''}
                                        <br><span class="light">Branch : </span> ${row.branch_name || ''}
                                        <br><span class="light">Client : </span> <strong>${row.company_name || ''}</strong>
                                        <br><span class="light">Box Count : </span> <strong>${row.quantity || 0}</strong>
                                        <br><span class="light">Created By : </span> ${row.created_by_name || ''}
                                        <br><span class="light">Created At : </span> ${row.created_at ? new Date(row.created_at).toLocaleString('en-IN') : ''}
                                        ${childAwbHtml}`;
                                }
                            },
                            // Status Info
                            {
                                data: null,
                                render: function (data, type, row) {
                                    let statusColor = '#6c757d';
                                    const s = (row.last_status || '').toLowerCase();
                                    if (s.includes('deliver')) statusColor = '#198754';
                                    else if (s.includes('transit') || s.includes('pickup')) statusColor = '#0d6efd';
                                    else if (s.includes('rto') || s.includes('return')) statusColor = '#dc3545';
                                    else if (s.includes('created')) statusColor = '#fd7e14';
                                    const amountLabel = row.payment_mode === 'COD'
                                        ? `COD : <strong>&#8377;${parseFloat(row.cod_amount || 0).toFixed(2)}</strong>`
                                        : `Invoice : <strong>&#8377;${parseFloat(row.invoice_value || 0).toFixed(2)}</strong>`;
                                    return `<span class="badge" style="background:${statusColor};color:#fff;">${row.last_status || 'Created'}</span>
                                        <br><span class="light">Mode : </span> ${row.shipping_mode || ''}
                                        <br><span class="light">Payment : </span> ${row.payment_mode || ''}
                                        <br><span class="light">${amountLabel}</span>
                                        <br><span class="light">EWB : </span> ${row.ewaybill_no || '-'}
                                        <br><span class="light">EWB Update : </span> <span class="badge bg-${(row.ewb_update_status === 'success') ? 'success' : ((row.ewb_update_status === 'failed') ? 'danger' : 'secondary')}">${row.ewb_update_status || 'not_required'}</span>`;
                                }
                            },
                            // Sender Info
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<i class="fa-solid fa-user"></i> <strong>${row.shipper_name || ''}</strong>
                                        <br><i class="fa-solid fa-location-dot"></i> ${row.shipper_address || ''}
                                        <br><i class="fa-solid fa-map-pin"></i> ${row.shipper_city || ''} - ${row.shipper_pin || ''}
                                        <br><i class="fa-solid fa-mobile-screen-button"></i> <a href="tel:${row.shipper_phone}" style="color:inherit;">${row.shipper_phone || ''}</a>`;
                                }
                            },
                            // Receiver Info
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<i class="fa-solid fa-user"></i> <strong>${row.consignee_name || ''}</strong>
                                        <br><i class="fa-solid fa-location-dot"></i> ${row.consignee_address || ''}
                                        <br><i class="fa-solid fa-map-pin"></i> ${row.consignee_city || ''} - ${row.consignee_pin || ''}
                                        <br><i class="fa-solid fa-mobile-screen-button"></i> <a href="tel:${row.consignee_phone}" style="color:inherit;">${row.consignee_phone || ''}</a>`;
                                }
                            },
                            // Actions
                            {
                                data: null,
                                render: function (data, type, row) {
                                    return `<div class="d-flex gap-1 flex-wrap">
                                <a href="order-details.php?id=${row.id}" class="btn btn-sm btn-outline-dark">Details</a>
                                <a href="shipment-edit.php?id=${row.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i> Edit</a>
                                <button class="btn btn-sm btn-outline-dark btn-label-print" data-id="${row.id}" data-waybill="${row.waybill_no}" data-size="A4">
                                    <i class="ti ti-printer"></i> Print Label
                                </button>
                                ${row.courier_id == 1 ? `<button class="btn btn-sm btn-warning btn-ewb-update"
                                    data-id="${row.id}"
                                    data-invoice="${(row.invoice_no || '').replace(/"/g, '&quot;')}"
                                    data-ewb="${(row.ewaybill_no || '').replace(/"/g, '&quot;')}">
                                    <i class="ti ti-refresh"></i> EWB Update
                                </button>` : ''}
                            </div>`;
                                }
                            }
                        ]
                    });

                    // Refresh table on filter change
                    $('#companyFilter, #branchFilter, #courierFilter, #statusFilter').change(function () {
                        table.ajax.reload();
                    });

                    // Handle Custom Print Label
                    $('#shipmentTable').on('click', '.btn-label-print', function () {
                        var id = $(this).data('id');
                        var waybill = $(this).data('waybill');
                        var size = $(this).data('size') || 'A4';

                        if (!waybill) {
                            alert('No Waybill generated yet.');
                            return;
                        }

                        var url = 'shipment-label-print.php?id=' + encodeURIComponent(id) +
                            '&waybill=' + encodeURIComponent(waybill) +
                            '&pdf_size=' + encodeURIComponent(size);

                        window.open(url, '_blank');
                    });

                    $('#shipmentTable').on('click', '.btn-ewb-update', function () {
                        if (!ewbModal) return;
                        $('#ewbBookingId').val($(this).data('id'));
                        $('#ewbInvoiceNo').val($(this).data('invoice') || '');
                        $('#ewbNumber').val($(this).data('ewb') || '');
                        $('#ewbModalMsg').removeClass('text-success text-danger').text('');
                        $('#btnSubmitEwbUpdate').prop('disabled', false);
                        ewbModal.show();
                    });

                    $('#btnSubmitEwbUpdate').on('click', function () {
                        const id = parseInt($('#ewbBookingId').val(), 10);
                        const invoiceNo = ($('#ewbInvoiceNo').val() || '').trim();
                        const ewaybillNo = ($('#ewbNumber').val() || '').trim();

                        if (!id || !invoiceNo || !ewaybillNo) {
                            $('#ewbModalMsg').removeClass('text-success').addClass('text-danger')
                                .text('Invoice number and E-waybill number are required.');
                            return;
                        }

                        $('#btnSubmitEwbUpdate').prop('disabled', true);
                        $('#ewbModalMsg').removeClass('text-success text-danger').text('Updating...');

                        $.ajax({
                            url: 'api/shipment/manual_ewaybill_update.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                id: id,
                                invoice_no: invoiceNo,
                                ewaybill_no: ewaybillNo
                            }),
                            success: function (res) {
                                if (res.status === 'success') {
                                    const first = (res.results && res.results.length) ? res.results[0] : null;
                                    if (first && first.success) {
                                        $('#ewbModalMsg').removeClass('text-danger').addClass('text-success')
                                            .text('EWB updated successfully.');
                                        table.ajax.reload(null, false);
                                        setTimeout(function () { ewbModal.hide(); }, 700);
                                    } else {
                                        $('#ewbModalMsg').removeClass('text-success').addClass('text-danger')
                                            .text('EWB update failed: ' + ((first && first.message) || 'Unknown error'));
                                    }
                                } else {
                                    $('#ewbModalMsg').removeClass('text-success').addClass('text-danger')
                                        .text(res.message || 'Manual EWB update failed.');
                                }
                            },
                            error: function (xhr) {
                                $('#ewbModalMsg').removeClass('text-success').addClass('text-danger')
                                    .text('Server error while updating EWB: ' + (xhr.statusText || 'Unknown error'));
                            },
                            complete: function () {
                                $('#btnSubmitEwbUpdate').prop('disabled', false);
                            }
                        });
                    });
                });
            </script>