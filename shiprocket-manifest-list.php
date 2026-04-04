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
                                    <div class="py-1 d-flex align-items-sm-center flex-sm-row flex-column">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 fw-semibold m-0"><i data-lucide="file-text"></i> Shiprocket Manifest List</h6>
                                        </div>
                                        <div class="text-end">
                                            <a href="shiprocke-lists.php" class="btn btn-sm btn-primary">Back to Shiprocket List</a>
                                        </div>
                                    </div>
                                    <table id="manifestTable" class="table table-hover dt-responsive w-100 mt-3">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Manifested ID</th>
                                                <th>Pickup Point</th>
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
                    $('#manifestTable').DataTable({
                        ajax: {
                            url: 'api/shipment/shiprocket_manifest_list.php',
                            dataSrc: function (json) { return (json && json.data) ? json.data : []; }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'manifest_date', defaultContent: '-' },
                            { data: 'manifested_id', defaultContent: '-' },
                            { data: 'pickuppoint', defaultContent: '-' },
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
                                    
                                    // Parse response json to get invoice URL
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
                        order: [[0, 'desc']]
                    });
                });
            </script>
        </div>
    </div>
</body>
