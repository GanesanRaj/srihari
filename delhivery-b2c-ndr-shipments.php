<?php
require_once 'header.php';
require_once 'config/middleware.php';
?>
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />

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
                            <h4 class="fs-18 fw-semibold m-0"><i data-lucide="zap" style="width:18px;height:18px;"></i> Delhivery B2C NDR Shipments</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-0">Manage Delhivery B2C NDR Actions</h5>
                                    <div id="ndrMessage" class="mt-2 small"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 text-end">
                            <button id="btnReloadNdrList" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-refresh me-1"></i>Reload NDR List
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3">Delhivery NDR Shipments (from Tracking Table)</h5>
                                    <div class="table-responsive">
                                        <table id="ndrTable" class="table table-striped table-bordered dt-responsive w-100">
                                            <thead>
                                                <tr>
                                                    <th>AWB</th>
                                                    <th>Ref ID</th>
                                                    <th>Consignee</th>
                                                    <th>NSL Code</th>
                                                    <th>Reason</th>
                                                    <th>Last Scan</th>
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card d-none" id="ndrResultCard">
                                <div class="card-body">
                                    <h5 class="mb-3">NDR Detail</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th style="width:25%;">AWB</th>
                                                    <td id="rAwb">-</td>
                                                </tr>
                                                <tr>
                                                    <th>Current Status</th>
                                                    <td id="rStatus">-</td>
                                                </tr>
                                                <tr>
                                                    <th>NDR Flag</th>
                                                    <td id="rNdrFlag">-</td>
                                                </tr>
                                                <tr>
                                                    <th>NDR Reason</th>
                                                    <td id="rReason">-</td>
                                                </tr>
                                                <tr>
                                                    <th>Last Scan Time</th>
                                                    <td id="rScanTime">-</td>
                                                </tr>
                                                <tr>
                                                    <th>Last Scan Location</th>
                                                    <td id="rScanLocation">-</td>
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
            <?php require_once 'footer.php'; ?>
        </div>
    </div>
    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnReloadNdrList = document.getElementById('btnReloadNdrList');
            const ndrMessage = document.getElementById('ndrMessage');
            const ndrTableEl = document.getElementById('ndrTable');
            let ndrTable = null;

            function showMsg(text, type) {
                const cls = type === 'error' ? 'text-danger' : (type === 'success' ? 'text-success' : 'text-muted');
                ndrMessage.classList.remove('text-danger', 'text-success', 'text-muted');
                ndrMessage.classList.add(cls);
                ndrMessage.textContent = text || '';
            }

            function loadNdrList() {
                if (ndrTable) {
                    ndrTable.ajax.reload();
                    return;
                }
                ndrTable = $(ndrTableEl).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: 'api/delhivery/ndr',
                        type: 'GET'
                    },
                    order: [[5, 'desc']],
                    columns: [
                        { data: 'awb', defaultContent: '-' },
                        { data: 'booking_ref_id', defaultContent: '-' },
                        {
                            data: null,
                            render: function (_, __, row) {
                                return `${row.consignee_name || '-'}<br><small>${row.consignee_phone || ''}</small>`;
                            }
                        },
                        {
                            data: 'nsl_code',
                            render: function (data) {
                                return `<span class="badge bg-warning text-dark">${data || '-'}</span>`;
                            }
                        },
                        { data: 'ndr_reason', defaultContent: '-' },
                        {
                            data: null,
                            render: function (_, __, row) {
                                return `${row.last_scan_datetime || '-'}<br><small>${row.last_scan_location || ''}</small>`;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function (_, __, row) {
                                const awb = row.awb || '';
                                return `<div class="d-flex gap-1 flex-wrap">
                                    <button class="btn btn-sm btn-danger btn-ndr-action" data-awb="${awb}" data-act="RE-ATTEMPT">RE-ATTEMPT</button>
                                    <button class="btn btn-sm btn-secondary btn-ndr-action" data-awb="${awb}" data-act="PICKUP_RESCHEDULE">PICKUP_RESCHEDULE</button>
                                </div>`;
                            }
                        }
                    ]
                });
            }

            async function applyNdrAction(awb, act) {
                if (!awb || !act) return;
                showMsg(`Submitting ${act} for ${awb}...`, 'info');
                try {
                    const response = await fetch('api/delhivery/ndr', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ waybill: awb, act: act })
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        const uplText = res.upl_id ? ` UPL ID: ${res.upl_id}` : '';
                        showMsg(`${act} submitted.${uplText}`, 'success');
                    } else {
                        showMsg(res.message || `Failed to submit ${act}.`, 'error');
                    }
                } catch (error) {
                    showMsg(`Server error while applying ${act}.`, 'error');
                }
            }

            btnReloadNdrList.addEventListener('click', loadNdrList);

            $(ndrTableEl).on('click', '.btn-ndr-action', function () {
                const awb = this.getAttribute('data-awb');
                const act = this.getAttribute('data-act');
                applyNdrAction(awb, act);
            });

            loadNdrList();
        });
    </script>
</body>
</html>
