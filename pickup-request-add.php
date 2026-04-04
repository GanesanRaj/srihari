<?php include 'header.php'; ?>
<?php require_once 'config/middleware.php'; require_permission('pickup_request', 'is_add'); ?>

<div class="wrapper">
    <?php require_once 'sidebar.php'; ?>
    <?php require_once 'topbar.php'; ?>

    <div class="content-page">
        <div class="" style="padding: 0 10px;">

            <div class="card" style="margin: 10px 0 5px;">
                <div class="row" style="padding: 5px;">
                    <div class="col-md-8">
                        <h5 class="mb-0 mt-1 ms-2">Create Pickup Request</h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="pickup-request-list.php">
                            <button type="button" class="btn btn-xs rounded-pill btn-primary">
                                <i class="ri-arrow-left-circle-fill"></i> &nbsp;Back to List
                            </button>
                        </a>
                    </div>
                </div>

                <div class="card-body" style="padding: 10px 20px;">
                    <form id="pickupRequestForm" class="row g-3">

                        <!-- Pickup Location -->
                        <div class="col-md-6">
                            <label class="form-label">Pickup Location (Warehouse) <span class="text-danger">*</span></label>
                            <select class="form-select" id="pickup_point_id" name="pickup_point_id" required>
                                <option value="">— Select Pickup Location —</option>
                            </select>
                            <div class="invalid-feedback">Pickup location is required.</div>
                        </div>

                        <!-- Courier (auto-filled) -->
                        <div class="col-md-6">
                            <label class="form-label">Courier Partner</label>
                            <input type="text" class="form-control" id="courier_name" readonly placeholder="Auto-filled on location select">
                        </div>

                        <!-- Pickup Date -->
                        <div class="col-md-4">
                            <label class="form-label">Pickup Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="pickup_date" name="pickup_date"
                                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback">Pickup date is required.</div>
                        </div>

                        <!-- Pickup Time -->
                        <div class="col-md-4">
                            <label class="form-label">Pickup Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="pickup_time" name="pickup_time"
                                   value="11:00" required>
                            <div class="invalid-feedback">Pickup time is required.</div>
                        </div>

                        <!-- Expected Package Count -->
                        <div class="col-md-4">
                            <label class="form-label">Expected Package Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="expected_package_count"
                                   name="expected_package_count" min="1" value="1" required>
                            <div class="invalid-feedback">Package count must be at least 1.</div>
                        </div>

                        <!-- Pickup Point Info Card -->
                        <div class="col-12" id="pickupInfoCard" style="display:none;">
                            <div class="alert alert-info py-2 mb-0">
                                <small>
                                    <strong id="ppName"></strong><br>
                                    <span id="ppAddress" class="text-muted"></span>
                                </small>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12 text-center mt-3">
                            <button type="submit" id="btnSubmit" class="btn btn-primary rounded-pill px-4">
                                <i class="ri-send-plane-fill"></i> Create Pickup Request
                            </button>
                            <a href="pickup-request-list.php" class="btn btn-secondary rounded-pill px-4 ms-2">
                                <i class="ri-close-line"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
        <?php require_once 'footer.php'; ?>

        <script src="assets/plugins/jquery/jquery.min.js"></script>
        <script>
        $(function () {

            // Load Delhivery pickup points
            $.get('api/pickuppoint/read.php?length=-1', function (res) {
                if (!res.data) return;
                res.data.forEach(function (pp) {
                    // Only show Delhivery pickup points (courier_id not 2 = not own courier)
                    if (parseInt(pp.courier_id) === 2) return;
                    $('#pickup_point_id').append(
                        $('<option>').val(pp.id)
                            .text(pp.name + (pp.city ? ' — ' + pp.city : ''))
                            .data('pp', pp)
                    );
                });
            }, 'json');

            // On location change — show info
            $('#pickup_point_id').on('change', function () {
                const opt = $(this).find(':selected');
                const pp  = opt.data('pp');
                if (pp) {
                    $('#courier_name').val(pp.courier_name || pp.partner_name || '');
                    $('#ppName').text(pp.name || '');
                    $('#ppAddress').text([pp.address, pp.city, pp.pin].filter(Boolean).join(', '));
                    $('#pickupInfoCard').show();
                } else {
                    $('#courier_name').val('');
                    $('#pickupInfoCard').hide();
                }
            });

            // Submit
            $('#pickupRequestForm').on('submit', function (e) {
                e.preventDefault();

                if (!$(this)[0].checkValidity()) {
                    $(this).addClass('was-validated');
                    return;
                }

                const btn = $('#btnSubmit');
                btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Creating...');

                $.post('api/pickup_request/create.php', $(this).serialize(), function (res) {
                    if (res.status === 'success') {
                        showtoastt(res.message, 'success');
                        setTimeout(function () {
                            window.location.href = 'pickup-request-list.php';
                        }, 1500);
                    } else {
                        showtoastt(res.message || 'Failed to create pickup request', 'error');
                        btn.prop('disabled', false).html('<i class="ri-send-plane-fill"></i> Create Pickup Request');
                    }
                }, 'json').fail(function () {
                    showtoastt('Request failed. Please try again.', 'error');
                    btn.prop('disabled', false).html('<i class="ri-send-plane-fill"></i> Create Pickup Request');
                });
            });

        });
        </script>
    </div>
</div>
</body>
</html>
