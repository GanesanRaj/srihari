<?php
require_once 'header.php';
require_once 'config/middleware.php';

require_permission('support', 'is_view');

$can_edit = can_edit('support');
$supportId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($supportId <= 0) {
    header('Location: support.php');
    exit;
}
?>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="content">
                <div class="">
                    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-18 fw-semibold m-0">
                                <i class="ti ti-headset me-1"></i> View Support Ticket
                            </h4>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($can_edit): ?>
                                <a href="support-add.php?id=<?= $supportId ?>" class="btn btn-xs rounded-pill btn-soft-primary waves-effect waves-light">
                                    <i class="ti ti-edit me-1"></i> Edit
                                </a>
                            <?php endif; ?>
                            <a href="support.php" class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                <i class="ti ti-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8 col-md-10 col-12 mx-auto">
                            <div class="card" id="ticketCard" style="display:none;">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Ticket #</label>
                                        <div class="col-sm-8"><span id="vTicketNumber" class="fw-bold"></span></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Subject</label>
                                        <div class="col-sm-8"><span id="vSubject"></span></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Category</label>
                                        <div class="col-sm-8"><span id="vCategory"></span></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Priority</label>
                                        <div class="col-sm-8"><span id="vPriority"></span></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Status</label>
                                        <div class="col-sm-8"><span id="vStatus"></span></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Message</label>
                                        <div class="col-sm-8"><p id="vMessage" class="mb-0" style="white-space:pre-wrap;"></p></div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-4 fw-medium text-muted">Created</label>
                                        <div class="col-sm-8"><span id="vCreatedAt"></span></div>
                                    </div>
                                </div>
                            </div>
                            <div id="loadingMsg" class="text-center py-4 text-muted">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    fetch('api/support/get_single.php?id=<?= $supportId ?>')
                        .then(r => r.json())
                        .then(res => {
                            document.getElementById('loadingMsg').style.display = 'none';
                            if (res.status !== 'success') {
                                document.getElementById('loadingMsg').textContent = 'Ticket not found.';
                                document.getElementById('loadingMsg').style.display = '';
                                return;
                            }
                            const d = res.data;
                            const priorityColors = { low: '#28a745', medium: '#fd7e14', high: '#dc3545', urgent: '#6f42c1' };
                            const statusLabels   = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved', closed: 'Closed' };
                            const statusColors   = { open: '#0066cc', in_progress: '#856404', resolved: '#155724', closed: '#6c757d' };

                            document.getElementById('vTicketNumber').textContent = d.ticket_number;
                            document.getElementById('vSubject').textContent      = d.subject;
                            document.getElementById('vCategory').textContent     = d.category ? d.category.charAt(0).toUpperCase() + d.category.slice(1).replace('_', ' ') : '-';
                            document.getElementById('vPriority').innerHTML       = `<span style="color:${priorityColors[d.priority] || '#333'}; font-weight:600;">${d.priority ? d.priority.charAt(0).toUpperCase() + d.priority.slice(1) : '-'}</span>`;
                            document.getElementById('vStatus').innerHTML         = `<span class="badge" style="background:${statusColors[d.status] || '#6c757d'}20; color:${statusColors[d.status] || '#6c757d'}; padding:4px 8px;">${statusLabels[d.status] || d.status}</span>`;
                            document.getElementById('vMessage').textContent      = d.message;
                            document.getElementById('vCreatedAt').textContent    = d.created_at ? new Date(d.created_at).toLocaleString() : '-';
                            document.getElementById('ticketCard').style.display = '';
                        });
                });
            </script>
        </div>
    </div>
</body>
</html>
