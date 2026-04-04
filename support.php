<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('support', 'is_view');

$can_add = can_add('support');
$can_edit = can_edit('support');
$can_delete = can_delete('support');
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
    .priority-urgent { color: #6f42c1; font-weight: 600; }
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
                            <h4 class="fs-18 fw-semibold m-0">
                                <i class="ti ti-headset me-1"></i> Support Center
                            </h4>
                        </div>
                        <div class="text-end">
                            <?php if ($can_add): ?>
                                <a href="support-add.php" class="btn btn-sm btn-soft-primary">
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
                                            <select id="categoryFilter" class="form-select form-select-sm">
                                                <option value="">All Categories</option>
                                                <option value="technical">Technical Issue</option>
                                                <option value="billing">Billing</option>
                                                <option value="feature">Feature Request</option>
                                                <option value="account">Account</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="priorityFilter" class="form-select form-select-sm">
                                                <option value="">All Priorities</option>
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select id="statusFilter" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="open">Open</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="closed">Closed</option>
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
                                            <div id="support-range" class="btn btn-sm btn-white border d-flex align-items-center gap-2 px-3 py-1 cursor-pointer w-100">
                                                <i class="ti ti-calendar fs-14"></i>
                                                <span class="fs-12 fw-medium"></span>
                                                <i class="ti ti-chevron-down fs-10 ms-auto"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <table id="supportTable" class="table table-hover dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Ticket #</th>
                                                <th>Subject</th>
                                                <th>Category</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th style="width: 120px;">Actions</th>
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

                    function cb(start, end) {
                        $('#support-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                        startDate = start.format('YYYY-MM-DD');
                        endDate = end.format('YYYY-MM-DD');
                        if (table) table.ajax.reload();
                    }

                    $('#support-range').daterangepicker({
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

                    table = $('#supportTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'api/support/read.php',
                            type: 'GET',
                            data: function (d) {
                                d.category = $('#categoryFilter').val();
                                d.priority = $('#priorityFilter').val();
                                d.status   = $('#statusFilter').val();
                                d.from_date = startDate;
                                d.to_date   = endDate;
                            }
                        },
                        columns: [
                            { data: 'id' },
                            { data: 'ticket_number', render: d => `<span class="fw-bold">${d}</span>` },
                            { data: 'subject', render: (d, t, r) => `<a href="support-view.php?id=${r.id}" class="fw-medium text-primary">${d}</a>` },
                            { data: 'category', render: d => d ? d.charAt(0).toUpperCase() + d.slice(1).replace('_', ' ') : '' },
                            { data: 'priority', render: d => `<span class="priority-${d}">${d.charAt(0).toUpperCase() + d.slice(1)}</span>` },
                            {
                                data: 'status',
                                render: d => `<span class="ticket-badge status-${d}">${d.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</span>`
                            },
                            { data: 'created_at', render: d => d ? new Date(d).toLocaleDateString() : '' },
                            {
                                data: null,
                                orderable: false,
                                render: (d, t, r) => {
                                    let html = `<a href="support-view.php?id=${r.id}" class="btn btn-sm btn-outline-dark"><i class="ti ti-eye"></i></a>`;
                                    if (<?= $can_edit ? 'true' : 'false' ?>) html += ` <a href="support-add.php?id=${r.id}" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i></a>`;
                                    if (<?= $can_delete ? 'true' : 'false' ?>) html += ` <button class="btn btn-sm btn-soft-danger btn-delete" data-id="${r.id}"><i class="ti ti-trash"></i></button>`;
                                    return html;
                                }
                            }
                        ],
                        order: [[6, 'desc']],
                        dom: 'Bfrtip',
                        buttons: ['csv', 'excel', 'pdf', 'print'],
                        language: {
                            paginate: {
                                first: '<i class="ti ti-chevrons-left"></i>',
                                previous: '<i class="ti ti-chevron-left"></i>',
                                next: '<i class="ti ti-chevron-right"></i>',
                                last: '<i class="ti ti-chevrons-right"></i>'
                            }
                        }
                    });

                    $('#filterBtn').click(() => table.ajax.reload());
                    $('#resetBtn').click(function () {
                        $('#categoryFilter, #priorityFilter, #statusFilter').val('').trigger('change');
                        startDate = moment().startOf('month').format('YYYY-MM-DD');
                        endDate = moment().endOf('month').format('YYYY-MM-DD');
                        table.ajax.reload();
                    });
                    $('#categoryFilter, #priorityFilter, #statusFilter').change(() => table.ajax.reload());

                    $(document).on('click', '.btn-delete', function () {
                        const id = $(this).data('id');
                        if (confirm('Delete this support ticket?')) {
                            $.post('api/support/delete.php', { id: id }, function (response) {
                                if (response.status === 'success') {
                                    showtoastt('Ticket deleted', 'success');
                                    table.ajax.reload();
                                } else {
                                    showtoastt('Error: ' + response.message, 'error');
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
