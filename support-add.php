<?php
require_once 'header.php';
require_once 'config/middleware.php';

if (isset($_GET['id'])) {
    require_permission('support', 'is_edit');
} else {
    require_permission('support', 'is_add');
}
?>

<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

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
                                <i class="ti ti-headset me-1"></i>
                                <span id="pageTitle"><?= isset($_GET['id']) ? 'Edit Support Ticket' : 'New Support Ticket' ?></span>
                            </h4>
                        </div>
                        <div>
                            <a href="support.php" class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                <i class="ti ti-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8 col-md-10 col-12 mx-auto">
                            <div class="card">
                                <div class="card-body">
                                    <form id="supportForm">

                                        <!-- Ticket Number (edit mode only) -->
                                        <div class="row mb-4" id="ticketNumberRow" style="display:none;">
                                            <label class="col-sm-4 col-form-label fw-medium">Ticket #</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control form-control-sm" id="ticketNumber" readonly>
                                            </div>
                                        </div>

                                        <!-- Subject -->
                                        <div class="row mb-4">
                                            <label class="col-sm-4 col-form-label fw-medium">Subject <span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control form-control-sm" id="subject" name="subject" placeholder="Enter subject">
                                            </div>
                                        </div>

                                        <!-- Category -->
                                        <div class="row mb-4">
                                            <label class="col-sm-4 col-form-label fw-medium">Category</label>
                                            <div class="col-sm-8">
                                                <select class="form-select form-select-sm" id="category" name="category">
                                                    <option value="">Select Category</option>
                                                    <option value="technical">Technical Issue</option>
                                                    <option value="billing">Billing</option>
                                                    <option value="feature">Feature Request</option>
                                                    <option value="account">Account</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Priority -->
                                        <div class="row mb-4">
                                            <label class="col-sm-4 col-form-label fw-medium">Priority <span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select class="form-select form-select-sm" id="priority" name="priority">
                                                    <option value="">Select Priority</option>
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Status (edit mode only) -->
                                        <div class="row mb-4" id="statusRow" style="display:none;">
                                            <label class="col-sm-4 col-form-label fw-medium">Status</label>
                                            <div class="col-sm-8">
                                                <select class="form-select form-select-sm" id="status" name="status">
                                                    <option value="open">Open</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="resolved">Resolved</option>
                                                    <option value="closed">Closed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Message -->
                                        <div class="row mb-4">
                                            <label class="col-sm-4 col-form-label fw-medium">Message <span class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <textarea class="form-control form-control-sm" id="message" name="message" rows="5" placeholder="Describe your issue..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="row">
                                            <div class="col-sm-8 offset-sm-4 d-flex gap-2">
                                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                                                    <i class="ti ti-device-floppy me-1"></i> Save
                                                </button>
                                                <a href="support.php" class="btn btn-sm btn-secondary rounded-pill px-4">
                                                    <i class="ti ti-x me-1"></i> Cancel
                                                </a>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const selectedId = <?= isset($_GET['id']) ? intval($_GET['id']) : 'null' ?>;

                    // Init select2
                    $('#category, #priority, #status').select2({
                        dropdownParent: $('#supportForm'),
                        minimumResultsForSearch: Infinity
                    });

                    if (selectedId) {
                        document.getElementById('ticketNumberRow').style.display = '';
                        document.getElementById('statusRow').style.display = '';
                        editTicket(selectedId);
                    }

                    function editTicket(id) {
                        fetch('api/support/get_single.php?id=' + id)
                            .then(r => r.json())
                            .then(res => {
                                if (res.status !== 'success') {
                                    showtoastt('Failed to load ticket data', 'error');
                                    return;
                                }
                                const d = res.data;
                                document.getElementById('ticketNumber').value = d.ticket_number;
                                document.getElementById('subject').value      = d.subject;
                                document.getElementById('message').value      = d.message;
                                $('#category').val(d.category).trigger('change');
                                $('#priority').val(d.priority).trigger('change');
                                $('#status').val(d.status).trigger('change');
                            });
                    }

                    function validateForm() {
                        const subject = document.getElementById('subject').value.trim();
                        const priority = document.getElementById('priority').value;
                        const message = document.getElementById('message').value.trim();

                        if (!subject) { showtoastt('Subject is required', 'error'); return false; }
                        if (!priority) { showtoastt('Priority is required', 'error'); return false; }
                        if (!message) { showtoastt('Message is required', 'error'); return false; }
                        return true;
                    }

                    document.getElementById('supportForm').addEventListener('submit', function (e) {
                        e.preventDefault();
                        if (!validateForm()) return;

                        const formData = new FormData(this);
                        if (selectedId) formData.append('id', selectedId);

                        const url = selectedId ? 'api/support/update.php' : 'api/support/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success') {
                                    showtoastt(res.message, 'success');
                                    setTimeout(() => window.location.href = 'support.php', 1000);
                                } else {
                                    showtoastt(res.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('Server error. Please try again.', 'error');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>
