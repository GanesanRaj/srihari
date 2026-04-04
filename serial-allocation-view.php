<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check View Permission
require_permission ( 'serial_allocation', 'is_view' );

if ( ! isset ($_GET[ 'id' ])) {
    header ( 'Location: serial-allocation-list.php' );
    exit;
    }

$allocation_id = sanitizeText ( $_GET[ 'id' ] );
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />

<style>
    .info-card {
        border: 1px solid #e3e3e3;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #555;
    }

    .info-value {
        color: #333;
    }

    .stats-card {
        text-align: center;
        padding: 20px;
        border-radius: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .stats-card.success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
    }

    .stats-card.warning {
        background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
    }

    .stats-card.danger {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    }

    .stats-card.info {
        background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
    }

    .stats-card h2 {
        font-size: 36px;
        margin: 10px 0;
        font-weight: bold;
    }

    .stats-card p {
        margin: 0;
        font-size: 14px;
        opacity: 0.9;
    }

    .active-filter {
        opacity: 1 !important;
        font-weight: 600;
        box-shadow: 0 0 0 2px currentColor;
    }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="px-0">
                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Serial Allocation Details</h4>
                    </div>
                    <div class="text-end">
                        <a href="serial-allocation-list.php" class="btn btn-sm btn-soft-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="row mb-3" id="statsSection">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <p>Total</p>
                            <h2 id="statTotal">0</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card success">
                            <p>Available</p>
                            <h2 id="statAvailable">0</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card warning">
                            <p>Used</p>
                            <h2 id="statUsed">0</h2>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Allocation Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Allocation Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-card" id="allocationInfo">
                                    <div class="info-row">
                                        <span class="info-label">Allocation Number:</span>
                                        <span class="info-value" id="infoAllocationNumber">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Branch:</span>
                                        <span class="info-value" id="infoBranch">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Branch Code:</span>
                                        <span class="info-value" id="infoBranchCode">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Serial From:</span>
                                        <span class="info-value" id="infoSerialFrom">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Serial To:</span>
                                        <span class="info-value" id="infoSerialTo">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Allocation Date:</span>
                                        <span class="info-value" id="infoDate">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value" id="infoStatus">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Usage Percentage:</span>
                                        <span class="info-value" id="infoPercentage">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Remarks:</span>
                                        <span class="info-value" id="infoRemarks">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Serial Numbers List -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="mb-0">Serial Numbers</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-success active-filter" id="showAvailable"
                                        data-filter="0">Available</button>
                                    <button class="btn btn-sm btn-outline-secondary" id="showAll"
                                        data-filter="">All</button>
                                    <button class="btn btn-sm btn-outline-warning" id="showUsed"
                                        data-filter="1">Used</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="serialsTable" class="table table-sm table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Serial Number</th>
                                            <th>Status</th>
                                            <th>Used Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>

            <script>
                $(document).ready(function () {
                    const allocationId = <?php echo $allocation_id; ?>;

                    // Load allocation data
                    $.get('api/serial_allocation/read_single.php?id=' + allocationId, function (response) {
                        if (response.status === 'success') {
                            const data = response.data;

                            // Update stats
                            $('#statTotal').text(data.total_serials);
                            $('#statAvailable').text(data.available_count);
                            $('#statUsed').text(data.used_count);

                            // Update info
                            $('#infoAllocationNumber').html('<span class="badge bg-primary">' + data.serial_number + '</span>');
                            $('#infoBranch').text(data.branch_name);
                            $('#infoBranchCode').text(data.branch_code);
                            $('#infoSerialFrom').html('<span class="badge bg-info">' + data.serial_from + '</span>');
                            $('#infoSerialTo').html('<span class="badge bg-info">' + data.serial_to + '</span>');
                            $('#infoDate').text(new Date(data.allocation_date).toLocaleDateString());

                            let statusBadge = data.status === 'active' ? 'bg-success' : (data.status === 'inactive' ? 'bg-secondary' : 'bg-danger');
                            $('#infoStatus').html('<span class="badge ' + statusBadge + '">' + data.status.toUpperCase() + '</span>');

                            let percentage = (data.used_count / data.total_serials * 100).toFixed(2);
                            $('#infoPercentage').html('<div class="progress" style="height: 20px;"><div class="progress-bar bg-success" role="progressbar" style="width: ' + percentage + '%;">' + percentage + '%</div></div>');

                            $('#infoRemarks').text(data.remarks || 'N/A');
                        } else {
                            showtoastt('Failed to load allocation data', 'error');
                            setTimeout(() => window.location.href = 'serial-allocation-list.php', 1500);
                        }
                    });

                    // Initialize DataTable for serials
                    var serialsTable = $('#serialsTable').DataTable({
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: 'api/serial_allocation/get_available_serials.php?branch_id=0',
                            type: 'GET',
                            dataSrc: function (json) {
                                // Filter by allocation_id
                                if (json.data) {
                                    return json.data.filter(function (item) {
                                        return item.allocation_id == allocationId;
                                    });
                                }
                                return [];
                            }
                        },
                        columns: [
                            {
                                data: 'serial_number',
                                render: function (data) {
                                    return '<span class="fw-bold">' + data + '</span>';
                                }
                            },
                            {
                                data: 'is_used',
                                render: function (data, type, row) {
                                    if (type === 'filter' || type === 'sort') { return data; }
                                    if (data == 1) {
                                        return '<span class="badge bg-warning text-dark">USED</span>';
                                    } else {
                                        return '<span class="badge bg-success">AVAILABLE</span>';
                                    }
                                }
                            },
                            {
                                data: 'used_date',
                                render: function (data) {
                                    return data ? new Date(data).toLocaleDateString() : '-';
                                }
                            }
                        ],
                        pageLength: 10,
                        order: [[0, 'asc']]
                    });

                    // Load all serials (including used ones)
                    function loadAllSerials() {
                        $.get('api/serial_numbers/read_by_allocation.php?allocation_id=' + allocationId, function (response) {
                            if (response.status === 'success') {
                                serialsTable.clear();
                                serialsTable.rows.add(response.data);
                                serialsTable.draw();
                            }
                        }).fail(function () {
                            // Fallback: use simpler query
                            serialsTable.ajax.reload();
                        });
                    }

                    // Filter buttons
                    $('#showAvailable, #showAll, #showUsed').on('click', function () {
                        var filter = $(this).data('filter');
                        // Use exact match search for '0' or '1', empty for 'All'
                        serialsTable.column(1).search(filter !== '' ? '^' + filter + '$' : '', true, false).draw();
                        $('#showAvailable, #showAll, #showUsed').removeClass('active-filter');
                        $(this).addClass('active-filter');
                    });

                    // Load all serials initially
                    loadAllSerials();
                });
            </script>
        </div>
    </div>
</body>

</html>