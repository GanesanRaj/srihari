<?php include 'header.php'; ?>
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
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
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column gap-2">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"><i data-lucide="file-text"></i> Shiprocket Manifest List</h6>
                                        </div>
                                        <div class="text-end d-flex flex-wrap gap-1 align-items-center justify-content-end">
                                            <button type="button" id="btnManifestSelectPage" class="btn btn-sm btn-outline-secondary">Select page</button>
                                            <button type="button" id="btnManifestClearSel" class="btn btn-sm btn-outline-secondary">Clear</button>
                                            <button type="button" id="btnPrintCustomManifest" class="btn btn-sm btn-success" disabled>
                                                <i class="ti ti-printer me-1"></i> Print layout (selected)
                                            </button>
                                            <a href="shiprocke-lists.php" class="btn btn-sm btn-primary">Back to Shiprocket List</a>
                                        </div>
                                    </div>
                                    <div id="manifestSelectHint" class="small text-muted mt-2">Select rows and open unified print layout (max 50).</div>

                                    <div class="row g-2 align-items-end mt-2 mb-2 p-2 bg-light rounded border">
                                        <div class="col-6 col-md-2">
                                            <label class="form-label fs-11 mb-0 text-muted">From date</label>
                                            <input type="date" id="mfFromDate" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label fs-11 mb-0 text-muted">To date</label>
                                            <input type="date" id="mfToDate" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label fs-11 mb-0 text-muted">Pickup point</label>
                                            <select id="mfPickup" class="form-select form-select-sm">
                                                <option value="">All pickup points</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label class="form-label fs-11 mb-0 text-muted">Sub courier (Shiprocket)</label>
                                            <select id="mfSubCourier" class="form-select form-select-sm">
                                                <option value="">All services</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-2 d-flex gap-1 flex-wrap">
                                            <button type="button" id="btnManifestApplyFilters" class="btn btn-sm btn-primary flex-grow-1">Apply</button>
                                            <button type="button" id="btnManifestResetFilters" class="btn btn-sm btn-outline-secondary flex-grow-1">Reset</button>
                                        </div>
                                    </div>

                                    <table id="manifestTable" class="table table-hover w-100 mt-2">
                                        <thead>
                                            <tr>
                                                <th style="width:36px;"><input type="checkbox" id="selectAllManifests" title="Select page"></th>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Manifested ID</th>
                                                <th>Pickup Point</th>
                                                <th>Sub Couriers</th>
                                                <th>AWB Count</th>
                                                <th>Created At</th>
                                                <th>Created By</th>
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
            <?php include 'footer.php'; ?>
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script>
                $(function () {
                    function pad2(n) {
                        n = String(n);
                        return n.length < 2 ? '0' + n : n;
                    }
                    function formatYmd(d) {
                        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
                    }
                    function setDefaultMonthRange() {
                        var now = new Date();
                        var first = new Date(now.getFullYear(), now.getMonth(), 1);
                        var last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        $('#mfFromDate').val(formatYmd(first));
                        $('#mfToDate').val(formatYmd(last));
                    }
                    setDefaultMonthRange();

                    var table = $('#manifestTable').DataTable({
                        ajax: {
                            url: 'api/shipment/shiprocket_manifest_list.php',
                            data: function (d) {
                                d.from_date = $('#mfFromDate').val() || '';
                                d.to_date = $('#mfToDate').val() || '';
                                d.pickup_point = $('#mfPickup').val() || '';
                                d.sub_courier = $('#mfSubCourier').val() || '';
                            },
                            dataSrc: function (json) { return (json && json.data) ? json.data : []; }
                        },
                        columns: [
                            {
                                data: 'id',
                                orderable: false,
                                searchable: false,
                                render: function (id) {
                                    return '<input type="checkbox" class="manifest-row-cb" value="' + id + '">';
                                }
                            },
                            { data: 'id' },
                            { data: 'manifest_date', defaultContent: '-' },
                            { data: 'manifested_id', defaultContent: '-' },
                            { data: 'pickuppoint', defaultContent: '-' },
                            {
                                data: 'sub_couriers',
                                defaultContent: '-',
                                render: function (data) {
                                    if (!data || !Array.isArray(data) || data.length === 0) {
                                        return '-';
                                    }
                                    return data.map(function (courier) {
                                        return '<span class="badge bg-secondary me-1">' + courier + '</span>';
                                    }).join('');
                                }
                            },
                            { data: 'awb_count', defaultContent: 0 },
                            { data: 'created_at', defaultContent: '-' },
                            { data: 'created_by_name', defaultContent: '-' },
                            {
                                data: null,
                                orderable: false,
                                render: function (_, __, row) {
                                    var btn = '<span class="text-muted">No URL</span>';
                                    if (row.manifest_url) {
                                        btn = '<a class="btn btn-sm btn-success me-1" target="_blank" href="' + row.manifest_url + '">Print Manifest</a>';
                                    }

                                    var invUrl = '';
                                    if (row.response) {
                                        try {
                                            var rJson = (typeof row.response === 'string') ? JSON.parse(row.response) : row.response;
                                            if (rJson && rJson.invoice_url) {
                                                invUrl = rJson.invoice_url;
                                            }
                                        } catch (e) {}
                                    }
                                    if (invUrl) {
                                        if (btn.indexOf('No URL') !== -1) btn = '';
                                        btn += '<a class="btn btn-sm btn-info" target="_blank" href="' + invUrl + '">Print Invoice</a>';
                                    }

                                    return btn;
                                }
                            }
                        ],
                        order: [[1, 'desc']],
                        drawCallback: function () {
                            syncManifestSelectAll();
                            updateManifestPrintBtn();
                        }
                    });

                    function loadFilterOptions() {
                        $.getJSON('api/shipment/shiprocket_manifest_list.php', { filter_options: 1 })
                            .done(function (res) {
                                if (!res || res.status !== 'success') return;
                                var pu = $('#mfPickup');
                                var curP = pu.val();
                                pu.find('option:not(:first)').remove();
                                (res.pickups || []).forEach(function (p) {
                                    pu.append($('<option></option>').val(p).text(p));
                                });
                                if (curP) pu.val(curP);

                                var sc = $('#mfSubCourier');
                                var curS = sc.val();
                                sc.find('option:not(:first)').remove();
                                (res.sub_couriers || []).forEach(function (s) {
                                    sc.append($('<option></option>').val(s).text(s));
                                });
                                if (curS) sc.val(curS);
                            });
                    }
                    loadFilterOptions();

                    $('#btnManifestApplyFilters').on('click', function () {
                        table.ajax.reload(null, false);
                    });

                    $('#btnManifestResetFilters').on('click', function () {
                        setDefaultMonthRange();
                        $('#mfPickup').val('');
                        $('#mfSubCourier').val('');
                        table.ajax.reload(null, false);
                    });

                    function visibleRowCheckboxes() {
                        return $('#manifestTable tbody tr').filter(function () {
                            return $(this).css('display') !== 'none';
                        }).find('.manifest-row-cb');
                    }

                    function selectedManifestIds() {
                        var ids = [];
                        $('#manifestTable tbody .manifest-row-cb:checked').each(function () {
                            ids.push(parseInt($(this).val(), 10));
                        });
                        return ids.filter(function (x) { return x > 0; });
                    }

                    function updateManifestPrintBtn() {
                        var n = selectedManifestIds().length;
                        $('#btnPrintCustomManifest').prop('disabled', n === 0);
                        $('#manifestSelectHint').text(n ? (n + ' row(s) selected — Print layout (max 50).') : 'Select rows and open unified print layout (max 50).');
                    }

                    function syncManifestSelectAll() {
                        var $boxes = visibleRowCheckboxes();
                        var n = $boxes.length;
                        var c = $boxes.filter(':checked').length;
                        $('#selectAllManifests').prop('checked', n > 0 && c === n);
                        $('#selectAllManifests').prop('indeterminate', c > 0 && c < n);
                    }

                    $('#manifestTable').on('change', '.manifest-row-cb', function () {
                        syncManifestSelectAll();
                        updateManifestPrintBtn();
                    });

                    $('#selectAllManifests').on('change', function () {
                        var checked = $(this).prop('checked');
                        visibleRowCheckboxes().prop('checked', checked);
                        updateManifestPrintBtn();
                    });

                    $('#btnManifestSelectPage').on('click', function () {
                        visibleRowCheckboxes().prop('checked', true);
                        syncManifestSelectAll();
                        updateManifestPrintBtn();
                    });

                    $('#btnManifestClearSel').on('click', function () {
                        $('#manifestTable tbody .manifest-row-cb').prop('checked', false);
                        $('#selectAllManifests').prop('checked', false).prop('indeterminate', false);
                        updateManifestPrintBtn();
                    });

                    $('#btnPrintCustomManifest').on('click', function () {
                        var ids = selectedManifestIds();
                        if (!ids.length) return;
                        if (ids.length > 50) {
                            ids = ids.slice(0, 50);
                        }
                        window.open('shiprocket-manifest-print.php?ids=' + ids.join(','), '_blank');
                    });
                });
            </script>
        </div>
    </div>
</body>
