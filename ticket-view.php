<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('ticket', 'is_view');

$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<style>
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 13px;
    }
    .info-value {
        font-size: 14px;
        color: #212529;
    }
    .ticket-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 3px;
    }
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
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Ticket Details</h4>
                    </div>
                    <div class="text-end">
                        <a href="ticket-add.php?id=<?= $ticketId ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="tickets.php" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div id="ticketDetails">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading ticket details...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <script>
                $(document).ready(function () {
                    const ticketId = <?= $ticketId ?>;

                    $.get(`api/ticket/get_single.php?id=${ticketId}`, function (response) {
                        if (response.status === 'success' && response.data) {
                            const ticket = response.data;
                            displayTicketDetails(ticket);
                        } else {
                            $('#ticketDetails').html(`
                                <div class="alert alert-danger">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    ${response.message || 'Failed to load ticket details'}
                                </div>
                            `);
                        }
                    }, 'json').fail(function () {
                        $('#ticketDetails').html(`
                            <div class="alert alert-danger">
                                <i class="ti ti-alert-circle me-2"></i>
                                Error loading ticket details. Please try again.
                            </div>
                        `);
                    });

                    function displayTicketDetails(ticket) {
                        const statusClass = 'status-' + ticket.status.toLowerCase().replace(' ', '-');
                        const priorityClass = 'priority-' + ticket.priority.toLowerCase();
                        const createdDate = new Date(ticket.created_at).toLocaleString();
                        const updatedDate = new Date(ticket.updated_at).toLocaleString();

                        const html = `
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h3 class="mb-3">${ticket.title}</h3>
                                    <div class="d-flex gap-2 mb-3">
                                        <span class="ticket-badge ${statusClass}">${ticket.status}</span>
                                        <span class="${priorityClass}"><i class="ti ti-flag-filled"></i> ${ticket.priority} Priority</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="info-label">Ticket Number</div>
                                    <div class="info-value fw-bold">${ticket.ticket_number}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Branch</div>
                                    <div class="info-value">${ticket.branch_name || 'N/A'}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Client</div>
                                    <div class="info-value">${ticket.client_name || 'N/A'}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Assigned To</div>
                                    <div class="info-value">${ticket.employee_name || 'Unassigned'}</div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-label">Created At</div>
                                    <div class="info-value">${createdDate}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Last Updated</div>
                                    <div class="info-value">${updatedDate}</div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="info-label mb-2">Description</div>
                                    <div class="info-value" style="white-space: pre-wrap;">
                                        ${ticket.description || '<em class="text-muted">No description provided</em>'}
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#ticketDetails').html(html);
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>
