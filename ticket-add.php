<?php
require_once 'header.php';
require_once 'config/middleware.php';

if (isset($_GET['id'])) {
    require_permission('ticket', 'is_edit');
} else {
    require_permission('ticket', 'is_add');
}
?>

<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<style>
    .col-form-label {
        padding-bottom: 2px !important;
        padding-top: 2px !important;
        margin-bottom: 2px !important;
    }
    .mb-4 { margin-bottom: 3px !important; }
    .form-control { padding: 5px !important; }
    .form-select { padding: 5px !important; }
</style>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8"></div>
                        <div class="col-md-4 text-end">
                            <a href="tickets.php">
                                <button type="button" class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Ticket List
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="ticketForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="ticketId" name="id" value="">

                            <div class="row mb-4">

                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Branch -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_id">Branch <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="branch_id" name="branch_id" data-toggle="select2" required>
                                                <option value="">Select Branch</option>
                                            </select>
                                            <div class="invalid-feedback">Branch is required.</div>
                                        </div>
                                    </div>

                                    <!-- Client -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="client_id">Client <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="client_id" name="client_id" data-toggle="select2" required>
                                                <option value="">Select Client</option>
                                            </select>
                                            <div class="invalid-feedback">Client is required.</div>
                                        </div>
                                    </div>

                                    <!-- Assign To Employee -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="employee_id">Assign To</label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="employee_id" name="employee_id" data-toggle="select2">
                                                <option value="">Select Employee (Optional)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Priority -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="priority">Priority <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="priority" name="priority" data-toggle="select2" required>
                                                <option value="Medium" selected>Medium</option>
                                                <option value="High">High</option>
                                                <option value="Low">Low</option>
                                            </select>
                                            <div class="invalid-feedback">Priority is required.</div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="status">Status <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="status" name="status" data-toggle="select2" required>
                                                <option value="Open" selected>Open</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Resolved">Resolved</option>
                                                <option value="Closed">Closed</option>
                                            </select>
                                            <div class="invalid-feedback">Status is required.</div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Ticket Number (Edit mode only) -->
                                    <div class="row mb-4" id="ticketNumberRow" style="display:none;">
                                        <label class="col-sm-4 col-form-label">Ticket #</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="ticket_number" readonly>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="title">Title <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter ticket title" required>
                                            <div class="invalid-feedback">Title is required.</div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="description">Description</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="description" name="description" rows="6" placeholder="Enter ticket description"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill" id="submitBtn">
                                        <i class="ri-save-line"></i> Save Ticket
                                    </button>
                                    <a href="tickets.php" class="btn btn-secondary rounded-pill">
                                        <i class="ri-close-line"></i> Cancel
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                $(document).ready(function () {

                    // Initialize Select2
                    if (jQuery().select2) {
                        $('.select2').each(function () {
                            $(this).select2({
                                dropdownParent: $(this).parent(),
                                minimumResultsForSearch: Infinity
                            });
                        });
                    }

                    function getQueryParam(param) {
                        return new URLSearchParams(window.location.search).get(param);
                    }

                    let selectedId = getQueryParam('id');

                    // Load branches
                    $.get('api/branch/read.php?length=1000', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branch_id').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                            });
                            $('#branch_id').trigger('change.select2');
                        }
                        // Load all clients initially
                        $.get('api/client/read.php?length=1000', function (r) {
                            if (r.data) {
                                r.data.forEach(function (client) {
                                    $('#client_id').append(`<option value="${client.id}">${client.client_name}</option>`);
                                });
                                $('#client_id').trigger('change.select2');
                            }
                            // Load ticket data after dropdowns are ready
                            if (selectedId) {
                                editTicket(selectedId);
                            }
                        });
                    });

                    // Load employees
                    $.get('api/employee/get_employees.php?length=1000', function (response) {
                        if (response.data) {
                            response.data.forEach(function (emp) {
                                $('#employee_id').append(`<option value="${emp.id}">${emp.name}</option>`);
                            });
                            $('#employee_id').trigger('change.select2');
                        }
                    });

                    // Branch change → reload clients
                    $('#branch_id').on('change', function () {
                        const branchId = $(this).val();
                        const currentClient = $('#client_id').val();
                        $('#client_id').html('<option value="">Select Client</option>');
                        if (branchId) {
                            $.get(`api/client/read.php?length=1000&branch_id=${branchId}`, function (r) {
                                if (r.data) {
                                    r.data.forEach(function (c) {
                                        $('#client_id').append(`<option value="${c.id}">${c.client_name}</option>`);
                                    });
                                    if (currentClient) {
                                        $('#client_id').val(currentClient).trigger('change.select2');
                                    }
                                }
                            });
                        }
                    });

                    // Load ticket for edit
                    function editTicket(id) {
                        $.get(`api/ticket/get_single.php?id=${id}`, function (response) {
                            if (response.status === 'success') {
                                const d = response.data;
                                $('#ticketId').val(d.id);
                                $('#ticket_number').val(d.ticket_number);
                                $('#ticketNumberRow').show();
                                $('#branch_id').val(d.branch_id).trigger('change.select2');
                                $('#client_id').val(d.client_id).trigger('change.select2');
                                $('#employee_id').val(d.employee_id).trigger('change.select2');
                                $('#priority').val(d.priority).trigger('change.select2');
                                $('#status').val(d.status).trigger('change.select2');
                                $('#title').val(d.title);
                                $('#description').val(d.description);
                            } else {
                                showtoastt('Ticket not found', 'error');
                                setTimeout(() => window.location.href = 'tickets.php', 1500);
                            }
                        }).fail(function () {
                            showtoastt('Error loading ticket data', 'error');
                        });
                    }

                    // Validation
                    function validateForm() {
                        let isValid = true;
                        $('.is-invalid').removeClass('is-invalid');

                        const fields = [
                            { id: 'branch_id', message: 'Branch is required.' },
                            { id: 'client_id', message: 'Client is required.' },
                            { id: 'priority', message: 'Priority is required.' },
                            { id: 'status', message: 'Status is required.' },
                            { id: 'title', message: 'Title is required.' }
                        ];

                        fields.forEach(function (field) {
                            const val = $('#' + field.id).val();
                            if (!val || val.trim() === '') {
                                $('#' + field.id).addClass('is-invalid');
                                if (isValid) showtoastt(field.message, 'error');
                                isValid = false;
                            }
                        });

                        return isValid;
                    }

                    // Form submit
                    $('#ticketForm').on('submit', function (e) {
                        e.preventDefault();

                        if (!validateForm()) return;

                        let $btn = $('#submitBtn');
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        const url = selectedId ? 'api/ticket/update.php' : 'api/ticket/create.php';
                        const formData = new FormData(this);

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'tickets.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                $btn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Ticket');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
