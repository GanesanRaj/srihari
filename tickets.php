<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('ticket', 'is_view');

$can_add = can_add('ticket');
$can_edit = can_edit('ticket');
$can_delete = can_delete('ticket');
?>

<link rel="stylesheet" href="assets/plugins/datatables/responsive.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/fixedHeader.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/datatables/buttons.bootstrap5.min.css" />
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

<style>
    .ticket-badge { font-size: 11px; padding: 4px 8px; border-radius: 3px; }
    .status-open { background-color: #e7f3ff; color: #0066cc; }
    .status-in-progress { background-color: #fff3cd; color: #856404; }
    .status-resolved { background-color: #d4edda; color: #155724; }
    .status-closed { background-color: #f8f9fa; color: #6c757d; }
    .priority-high { color: #dc3545; font-weight: 600; }
    .priority-medium { color: #fd7e14; font-weight: 600; }
    .priority-low { color: #28a745; font-weight: 600; }
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
                            <h4 class="fs-18 fw-semibold m-0">Ticket Management</h4>
                        </div>
                        <div class="text-end">
                            <?php if ($can_add): ?>
                                <a href="ticket-add.php" class="btn btn-sm btn-soft-primary">
                                    <i class="ti ti-plus me-1"></i> New Ticket
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">

                                    <div class="row mb-3 g-2">
                                        <div class="col-md-2">
                                            <select id="branchFilter" class="form-select form-select-sm">
                                                <option value="">All Branches</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="clientFilter" class="form-select form-select-sm">
                                                <option value="">All Clients</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="employeeFilter" class="form-select form-select-sm">
                                                <option value="">All Employees</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="Open">Open</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Resolved">Resolved</option>
                                                <option value="Closed">Closed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="priorityFilter" class="form-select form-select-sm">
                                                <option value="">All Priorities</option>
                                                <option value="High">High</option>
                                                <option value="Medium">Medium</option>
                                                <option value="Low">Low</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-soft-primary flex-grow-1" id="filterBtn">
                                                    <i class="ti ti-search me-1"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light" id="resetBtn">
                                                    <i class="ti ti-rotate"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <div id="ticket-range" class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <table id="ticketTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Ticket #</th>
                                                <th>Title</th>
                                                <th>Branch</th>
                                                <th>Client</th>
                                                <th>Assigned To</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th style="width: 150px;">Actions</th>
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
            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
            <script src="assets/plugins/datatables/responsive.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.fixedHeader.min.js"></script>
            <script src="assets/plugins/datatables/fixedHeader.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
            <script src="assets/plugins/datatables/buttons.bootstrap5.min.js"></script>
            <script src="assets/plugins/datatables/jszip.min.js"></script>
            <script src="assets/plugins/datatables/pdfmake.min.js"></script>
            <script src="assets/plugins/datatables/vfs_fonts.js"></script>
            <script src="assets/plugins/datatables/buttons.html5.min.js"></script>
            <script src="assets/plugins/datatables/buttons.print.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>
            <script src="assets/plugins/daterangepicker/moment.min.js"></script>
            <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

            <script>
                $(document).ready(function () {
                    let table;
                    let startDate = moment().startOf('month').format('YYYY-MM-DD');
                    let endDate = moment().endOf('month').format('YYYY-MM-DD');

                    $('.form-select').select2({ minimumResultsForSearch: 5 });

                    $.get('api/branch/read.php?length=1000', function (r) {
                        if (r.data) r.data.forEach(b => $('#branchFilter').append(`<option value="${b.id}">${b.branch_name}</option>`));
                    });

                    $.get('api/client/read.php?length=1000', function (r) {
                        if (r.data) r.data.forEach(c => $('#clientFilter').append(`<option value="${c.id}">${c.client_name}</option>`));
                    });

                    $.get('api/employee/get_employees.php?length=1000', function (r) {
                        if (r.data) r.data.forEach(e => $('#employeeFilter').append(`<option value="${e.id}">${e.name}</option>`));
                    });

                    function cb(start, end) {
                        $('#ticket-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#ticket-range').daterangepicker({
                        startDate: moment().startOf('month'),
                        endDate: moment().endOf('month'),
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, cb);

                    cb(moment().startOf('month'), moment().endOf('month'));

                    table = $('#ticketTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/ticket/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.branch_id = $('#branchFilter').val();
                                d.client_id = $('#clientFilter').val();
                                d.employee_id = $('#employeeFilter').val();
                                d.status = $('#statusFilter').val();
                                d.priority = $('#priorityFilter').val();
                                d.from_date = startDate;
                                d.to_date = endDate;
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'ticket_number', render: d => `<span class="fw-bold">${d}</span>` },
                            { data: 'title', render: (d, t, r) => `<a href="ticket-view.php?id=${r.id}" class="fw-medium text-primary">${d}</a>` },
                            { data: 'branch_name' },
                            { data: 'client_name' },
                            { data: 'employee_name' },
                            { data: 'priority', render: d => `<span class="priority-${d.toLowerCase()}">${d}</span>` },
                            { data: 'status', render: d => `<span class="ticket-badge status-${d.toLowerCase().replace(' ', '-')}">${d}</span>` },
                            { data: 'created_at', render: d => d ? new Date(d).toLocaleDateString() : '' },
                            {
                                data: null,
                                orderable: false,
                                render: (d, t, r) => {
                                    let html = `<a href="ticket-view.php?id=${r.id}" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i></a>`;
                                    if (<?= $can_edit ? 'true' : 'false' ?>) html += ` <a href="ticket-add.php?id=${r.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i></a>`;
                                    if (<?= $can_delete ? 'true' : 'false' ?>) html += ` <button class="btn btn-sm btn-soft-danger btn-delete" data-id="${r.id}"><i class="ti ti-trash"></i></button>`;
                                    return html;
                                }
                            }
                        ],
                        order: [[8, 'desc']],
                        dom: 'Bfrtip',
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    });

                    $('#filterBtn').click(() => table.ajax.reload());
                    $('#resetBtn').click(function () {
                        $('#branchFilter, #clientFilter, #employeeFilter, #statusFilter, #priorityFilter').val('').trigger('change');
                        startDate = moment().startOf('month').format('YYYY-MM-DD');
                        endDate = moment().endOf('month').format('YYYY-MM-DD');
                        table.ajax.reload();
                    });
                    $('#branchFilter, #clientFilter, #employeeFilter, #statusFilter, #priorityFilter').change(() => table.ajax.reload());

                    $(document).on('click', '.btn-delete', function () {
                        const ticketId = $(this).data('id');
                        if (confirm('Delete this ticket?')) {
                            $.post('api/ticket/delete.php', { id: ticketId }, function (response) {
                                if (response.status === 'success') {
                                    alert('Ticket deleted');
                                    table.ajax.reload();
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            }, 'json');
                        }
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>
