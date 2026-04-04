<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    // Edit Mode
    require_permission('serial_allocation', 'is_edit');
} else {
    // Add Mode
    require_permission('serial_allocation', 'is_add');
}
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<style>
    .col-form-label {
        padding-bottom: 2px !important;
        padding-top: 2px !important;
        margin-bottom: 2px !important;
    }

    .mb-4 {
        margin-bottom: 3px !important;
    }

    .form-control {
        padding: 5px !important;
    }

    .form-select {
        padding: 5px !important;
    }

    .info-box {
        background: #e7f3ff;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
    }

    .info-box h6 {
        margin-bottom: 10px;
        color: #0066cc;
    }

    .info-box p {
        margin-bottom: 5px;
        font-size: 13px;
    }
</style>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8">
                            <h5 class="mb-0">Serial Allocation Management</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="serial-allocation-list.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to List
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form id="serialAllocationForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="allocationId" name="id" value="">

                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-sm-6">

                                    <!-- Select Branch -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="branch_id">Select Branch <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="branch_id" name="branch_id"
                                                data-toggle="select2" required>
                                                <option value="">Select Branch</option>
                                            </select>
                                            <div class="invalid-feedback">Branch is required.</div>
                                        </div>
                                    </div>

                                    <!-- Service Type -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="service_type">Service Type <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control select2" id="service_type" name="service_type"
                                                data-toggle="select2" required>
                                                <option value="">Select Service Type</option>
                                                <option value="express">Air</option>
                                                <option value="surface">Surface</option>
                                            </select>
                                            <div class="invalid-feedback">Service type is required.</div>
                                        </div>
                                    </div>

                                    <!-- Serial From -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="serial_from">Serial From <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="serial_from" name="serial_from"
                                                placeholder="e.g., SN-001" required>
                                            <div class="invalid-feedback">Serial From is required.</div>
                                            <small class="text-muted" id="serial_from_hint">Next start number (auto-filled when branch &amp; service type are selected)</small>
                                        </div>
                                    </div>

                                    <!-- Serial To -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="serial_to">Serial To <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="serial_to" name="serial_to"
                                                placeholder="e.g., SN-100" required>
                                            <div class="invalid-feedback">Serial To is required.</div>
                                            <small class="text-muted">Format: Prefix-Number (e.g., SN-100)</small>
                                        </div>
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-sm-6">

                                    <!-- Allocation Date -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="allocation_date">Allocation Date <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="date" class="form-control" id="allocation_date" name="allocation_date"
                                                required>
                                            <div class="invalid-feedback">Allocation date is required.</div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="row mb-4">
                                        <label class="col-sm-4 col-form-label" for="remarks">Remarks</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="remarks" name="remarks" rows="4"
                                                placeholder="Additional notes"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="row mb-4" id="infoBox" style="display: none;">
                                <div class="col-12">
                                    <div class="info-box">
                                        <h6><i class="ri-information-line"></i> Allocation Summary</h6>
                                        <p><strong>Branch:</strong> <span id="infoBranch">-</span></p>
                                        <p><strong>Service Type:</strong> <span id="infoServiceType">-</span></p>
                                        <p><strong>Serial Range:</strong> <span id="infoRange">-</span></p>
                                        <p><strong>Total Serials:</strong> <span id="infoTotal">0</span></p>
                                        <p><strong>Allocation Date:</strong> <span id="infoDate">-</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Allocate Serials
                                    </button>
                                    <a href="serial-allocation-list.php" class="btn btn-secondary rounded-pill">
                                        <i class="ri-close-line"></i> Cancel
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <!-- Vendors JS -->
            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script src="assets/plugins/select2/select2.min.js"></script>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Initialize Select2
                    if ($('.select2').length) {
                        $('.select2').select2({
                            minimumResultsForSearch: Infinity
                        });
                    }

                    // Set today's date as default
                    $('#allocation_date').val(new Date().toISOString().split('T')[0]);

                    // Load branches
                    $.get('api/branch/read.php?length=1000&status=active', function (response) {
                        if (response.data) {
                            response.data.forEach(function (branch) {
                                $('#branch_id').append(`<option value="${branch.id}" data-name="${branch.branch_name}">${branch.branch_name} (${branch.branch_code})</option>`);
                            });
                        }
                    });

                    // Fetch next start number when branch and service type are selected; pre-fill Serial From
                    function fetchNextSerialFrom() {
                        let branchId = $('#branch_id').val();
                        let serviceType = $('#service_type').val();
                        if (!branchId || !serviceType) return;
                        $.get('api/serial_allocation/get_next_serial_from.php?branch_id=' + encodeURIComponent(branchId) + '&service_type=' + encodeURIComponent(serviceType), function (response) {
                            if (response.status === 'success' && response.next_serial_from) {
                                $('#serial_from').val(response.next_serial_from);
                                $('#serial_from_hint').text('Next start number: ' + response.next_serial_from).addClass('text-success');
                            }
                        });
                    }
                    $('#branch_id, #service_type').on('change', function () {
                        $('#serial_from_hint').removeClass('text-success').text('Next start number (auto-filled when branch & service type are selected)');
                        fetchNextSerialFrom();
                    });

                    // Calculate total serials on input
                    function calculateTotalSerials() {
                        let serialFrom = $('#serial_from').val();
                        let serialTo = $('#serial_to').val();

                        if (serialFrom && serialTo) {
                            let fromMatch = serialFrom.match(/(\d+)/);
                            let toMatch = serialTo.match(/(\d+)/);

                            if (fromMatch && toMatch) {
                                let fromNum = parseInt(fromMatch[0]);
                                let toNum = parseInt(toMatch[0]);

                                if (toNum > fromNum) {
                                    let total = (toNum - fromNum) + 1;
                                    $('#infoTotal').text(total);
                                    $('#infoRange').text(serialFrom + ' to ' + serialTo);
                                    return true;
                                } else {
                                    $('#infoTotal').text('Invalid range');
                                    return false;
                                }
                            }
                        }
                        return false;
                    }

                    // Show info box on change
                    $('#branch_id, #service_type, #serial_from, #serial_to, #allocation_date').on('change keyup', function () {
                        let branchName = $('#branch_id option:selected').data('name');
                        let serviceType = $('#service_type option:selected').text();
                        let serialFrom = $('#serial_from').val();
                        let serialTo = $('#serial_to').val();
                        let allocationDate = $('#allocation_date').val();

                        if (branchName && serviceType && serialFrom && serialTo && allocationDate) {
                            $('#infoBranch').text(branchName);
                            $('#infoServiceType').text(serviceType);
                            $('#infoDate').text(allocationDate);
                            calculateTotalSerials();
                            $('#infoBox').slideDown();
                        } else {
                            $('#infoBox').slideUp();
                        }
                    });

                    // Validation function
                    function validateForm() {
                        let isValid = true;
                        let errors = [];

                        // Clear previous validation errors
                        $('.is-invalid').removeClass('is-invalid');

                        // Check branch
                        if (!$('#branch_id').val()) {
                            $('#branch_id').addClass('is-invalid');
                            errors.push('Branch is required');
                            isValid = false;
                        }

                        // Check service type
                        if (!$('#service_type').val()) {
                            $('#service_type').addClass('is-invalid');
                            errors.push('Service type is required');
                            isValid = false;
                        }

                        // Check serial from
                        if (!$('#serial_from').val()) {
                            $('#serial_from').addClass('is-invalid');
                            errors.push('Serial From is required');
                            isValid = false;
                        }

                        // Check serial to
                        if (!$('#serial_to').val()) {
                            $('#serial_to').addClass('is-invalid');
                            errors.push('Serial To is required');
                            isValid = false;
                        }

                        // Check allocation date
                        if (!$('#allocation_date').val()) {
                            $('#allocation_date').addClass('is-invalid');
                            errors.push('Allocation date is required');
                            isValid = false;
                        }

                        // Validate serial range
                        if (!calculateTotalSerials()) {
                            errors.push('Invalid serial range. "Serial To" must be greater than "Serial From"');
                            isValid = false;
                        }

                        // Show first error if validation fails
                        if (!isValid) {
                            showtoastt(errors[0], 'error');
                        }

                        return isValid;
                    }

                    // Form submission handler
                    $('#serialAllocationForm').on('submit', function (e) {
                        e.preventDefault();

                        // Validate form
                        if (!validateForm()) {
                            return;
                        }

                        // Disable submit button to prevent double submission
                        let $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Allocating...');

                        let formData = new FormData(this);
                        let url = 'api/serial_allocation/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                    setTimeout(() => window.location.href = 'serial-allocation-list.php', 1500);
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function () {
                                showtoastt('An error occurred while saving', 'error');
                            },
                            complete: function () {
                                // Re-enable submit button
                                $submitBtn.prop('disabled', false).html('<i class="ri-save-line"></i> Allocate Serials');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
</body>

</html>
