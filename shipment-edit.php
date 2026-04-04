<?php include 'header.php'; ?>
<!-- Start Main Content -->
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">
<div class="wrapper">
    <?php require_once 'sidebar.php'; ?>
    <?php require_once 'topbar.php'; ?>

    <!-- Custom CSS for compact form -->
    <style>
        .col-form-label {
            padding-bottom: 2px !important;
            padding-top: 2px !important;
            margin-bottom: 2px !important;
            font-size: 0.9rem;
        }

        .mb-3 {
            margin-bottom: 5px !important;
        }

        .form-control,
        .form-select {
            padding: 5px !important;
            font-size: 0.9rem;
        }

        .select2-container .select2-selection--single {
            height: 33px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 33px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 31px !important;
        }

        .select2-container .select2-selection.is-invalid {
            border-color: #dc3545 !important;
        }

        .tat-widget {
            min-width: 300px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            padding: 8px 10px;
        }

        .tat-result {
            min-height: 26px;
        }
    </style>

    <div class="content-page">
        <div class="content">
            <div class="px-0">
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Edit Shipment</h4>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Update Shipment Details</h4>
                            </div>
                            <div class="card-body">
                                <form id="shipmentForm">
                                    <input type="hidden" name="id" id="booking_id"
                                        value="<?php echo $_GET['id'] ?? ''; ?>">

                                    <div class="ins-wizard" data-wizard>
                                        <ul class="nav nav-tabs wizard-tabs" data-wizard-nav role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" href="#originInfo">
                                                    <span class="d-flex align-items-center">
                                                        <i class="ti ti-building-warehouse fs-32"></i>
                                                        <span class="flex-grow-1 ms-2 text-truncate">
                                                            <span
                                                                class="mb-0 lh-base d-block fw-semibold text-body">Origin</span>
                                                            <span class="fs-xxs mb-0">Pickup Point</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#consignorInfo">
                                                    <span class="d-flex align-items-center">
                                                        <i class="ti ti-user-check fs-32"></i>
                                                        <span class="flex-grow-1 ms-2 text-truncate">
                                                            <span
                                                                class="mb-0 lh-base d-block fw-semibold text-body">Consignor</span>
                                                            <span class="fs-xxs mb-0">Sender Details</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#consigneeInfo">
                                                    <span class="d-flex align-items-center">
                                                        <i class="ti ti-user fs-32"></i>
                                                        <span class="flex-grow-1 ms-2 text-truncate">
                                                            <span
                                                                class="mb-0 lh-base d-block fw-semibold text-body">Consignee</span>
                                                            <span class="fs-xxs mb-0">Customer Details</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#packageInfo">
                                                    <span class="d-flex align-items-center">
                                                        <i class="ti ti-package fs-32"></i>
                                                        <span class="flex-grow-1 ms-2 text-truncate">
                                                            <span
                                                                class="mb-0 lh-base d-block fw-semibold text-body">Package</span>
                                                            <span class="fs-xxs mb-0">Weight & Dimensions</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#confirmInfo">
                                                    <span class="d-flex align-items-center">
                                                        <i class="ti ti-check fs-32"></i>
                                                        <span class="flex-grow-1 ms-2 text-truncate">
                                                            <span
                                                                class="mb-0 lh-base d-block fw-semibold text-body">Confirm</span>
                                                            <span class="fs-xxs mb-0">Review & Submit</span>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content pt-3" data-wizard-content>
                                            <!-- Step 1: Origin -->
                                            <div class="tab-pane fade show active" id="originInfo">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Branch <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select select2" id="branch_id"
                                                                    data-toggle="select2" name="branch_id" required>
                                                                    <option value="">Select Branch</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Booking Type <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select" name="booking_type">
                                                                    <option value="Forward">Forward</option>
                                                                    <option value="Reverse">Reverse</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Courier <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select select2" id="courier_id"
                                                                    data-toggle="select2" name="courier_id" required>
                                                                    <option value="">Select Courier</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Pickup Point <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select select2" id="pickup_point_id"
                                                                    data-toggle="select2" name="pickup_point_id"
                                                                    required>
                                                                    <option value="">Select Pickup Point</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Date <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="date" class="form-control"
                                                                    name="booking_date" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Reference No</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="booking_ref_id" readonly>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button type="button" class="btn btn-primary" data-wizard-next>Next:
                                                        Consignor →</button>
                                                </div>
                                            </div>

                                            <!-- Step 2: Consignor -->
                                            <div class="tab-pane fade" id="consignorInfo">
                                                <h5 class="mb-3 text-primary"><i class="ti ti-user-check"></i> Consignor
                                                    Details</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Name <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="shipper_name" name="shipper_name" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Phone <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="shipper_phone" name="shipper_phone"
                                                                    pattern="\d{10}" maxlength="10" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Pincode <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control" id="shipper_pin"
                                                                    name="shipper_pin" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Address <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <textarea class="form-control" id="shipper_address"
                                                                    name="shipper_address" rows="2" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">City <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="shipper_city" name="shipper_city" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">State <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="shipper_state" name="shipper_state" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-3">
                                                    <button type="button" class="btn btn-secondary" data-wizard-prev>←
                                                        Back</button>
                                                    <button type="button" class="btn btn-primary" data-wizard-next>Next:
                                                        Consignee →</button>
                                                </div>
                                            </div>

                                            <!-- Step 3: Consignee -->
                                            <div class="tab-pane fade" id="consigneeInfo">
                                                <h5 class="text-primary mb-3"><i class="ti ti-user"></i> Consignee
                                                    Details</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Full Name <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="consignee_name" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Phone <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="consignee_phone" pattern="\d{10}"
                                                                    maxlength="10" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Email</label>
                                                            <div class="col-sm-8"><input type="email"
                                                                    class="form-control" name="consignee_email"></div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">GST Number</label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="consignee_gst"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Address <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <textarea class="form-control" name="consignee_address"
                                                                    rows="2" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">PIN Code <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="consignee_pin" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">City <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="consignee_city" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">State <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="consignee_state"
                                                                    required></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-3">
                                                    <button type="button" class="btn btn-secondary" data-wizard-prev>←
                                                        Back</button>
                                                    <button type="button" class="btn btn-primary" data-wizard-next>Next:
                                                        Package →</button>
                                                </div>
                                            </div>

                                            <!-- Step 4: Package -->
                                            <div class="tab-pane fade" id="packageInfo">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Shipping Mode</label>
                                                        <select class="form-select" name="shipping_mode">
                                                            <option value="Surface">Surface</option>
                                                            <option value="Express">Express</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Product Description</label>
                                                        <input type="text" class="form-control" name="product_desc">
                                                    </div>
                                                </div>

                                                <div class="table-responsive mb-2">
                                                    <table class="table table-bordered table-sm" id="pkgTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 48px">#</th>
                                                                <th>Length (cm)</th>
                                                                <th>Width (cm)</th>
                                                                <th>Height (cm)</th>
                                                                <th>Boxes</th>
                                                                <th>Actual Wt (kg/box)</th>
                                                                <th>Vol. Wt (total)</th>
                                                                <th>Chg. Wt (total)</th>
                                                                <th style="width:58px"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                    <button type="button" class="btn btn-sm btn-info" id="btnAddRow"><i
                                                            class="ti ti-plus"></i> Add Box Row</button>
                                                </div>

                                                <div class="row mb-3 g-2">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Total Boxes</label>
                                                        <input type="text" class="form-control fw-bold" id="total_boxes"
                                                            value="0" readonly>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Total Actual Wt (Kg)</label>
                                                        <input type="text" class="form-control fw-bold"
                                                            id="total_actual_weight" value="0.00" readonly>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Total Charged Wt (Kg)</label>
                                                        <input type="text" class="form-control fw-bold"
                                                            id="total_weight" name="total_weight" value="0.00" readonly>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between mt-3">
                                                    <button type="button" class="btn btn-secondary" data-wizard-prev>←
                                                        Back</button>
                                                    <button type="button" class="btn btn-primary" data-wizard-next>Next:
                                                        Confirm →</button>
                                                </div>
                                            </div>

                                            <!-- Step 5: Confirm -->
                                            <div class="tab-pane fade" id="confirmInfo">
                                                <h5 class="mb-3 text-primary"><i class="ti ti-check"></i> Review &
                                                    Update</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="mb-3 border-bottom pb-2">Invoice / E-Way Bill</h6>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Invoice No</label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="invoice_no"></div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Invoice Value</label>
                                                            <div class="col-sm-8"><input type="number" step="0.01"
                                                                    class="form-control" name="invoice_value"></div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">E-Way Bill No</label>
                                                            <div class="col-sm-8"><input type="text"
                                                                    class="form-control" name="ewaybill_no"></div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <h6 class="mb-3 border-bottom pb-2">Payment Details</h6>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Mode <span
                                                                        class="text-danger">*</span></label>
                                                                <div class="col-sm-8">
                                                                    <select class="form-select" name="payment_mode"
                                                                        required>
                                                                        <option value="Prepaid">Prepaid</option>
                                                                        <option value="COD">COD</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">COD
                                                                    Amount</label>
                                                                <div class="col-sm-8"><input type="number" step="0.01"
                                                                        class="form-control" name="cod_amount"
                                                                        value="0"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                                            <h6 class="mb-0">RTO Details (Return Address)</h6>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="sameAsConsignor" checked>
                                                                <label class="form-check-label fs-xxs"
                                                                    for="sameAsConsignor">Same as Consignor</label>
                                                            </div>
                                                        </div>
                                                        <div id="rtoFields" style="display:none">
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">RTO Name</label>
                                                                <div class="col-sm-8"><input type="text"
                                                                        class="form-control" name="rto_name"></div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Phone</label>
                                                                <div class="col-sm-8"><input type="text"
                                                                        class="form-control" name="rto_phone"></div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Address</label>
                                                                <div class="col-sm-8"><textarea class="form-control"
                                                                        name="rto_address" rows="2"></textarea></div>
                                                            </div>
                                                        </div>
                                                        <div id="rtoMessage" class="alert alert-secondary fs-xxs">
                                                            Running RTO details same as Consignor. Uncheck to edit.
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-3">
                                                    <button type="button" class="btn btn-secondary" data-wizard-prev>←
                                                        Back</button>
                                                    <button type="button" class="btn btn-primary"
                                                        id="btnUpdateShipment"><i class="ti ti-check"></i> Update
                                                        Shipment</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendors JS -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/select2/select2.min.js"></script>
<?php include 'footer.php'; ?>
<script src="assets/js/pages/form-wizard.js"></script>

<script>
    $(document).ready(function () {
        if (jQuery().select2) { $('[data-toggle="select2"]').select2({ width: '100%' }); }

        function digitsOnly(value) { return String(value || '').replace(/\D/g, ''); }

        // Load Static Data
        const loadInitialData = () => {
            return Promise.all([
                $.get('api/branch/read.php?length=-1').then(res => res.data || []),
                $.get('api/pickuppoint/read.php?length=-1').then(res => { window.pickupPoints = res.data; return res.data || []; }),
                $.get('api/courier_partner/read.php?length=-1').then(res => res.data || [])
            ]);
        };

        loadInitialData().then(([branches, points, couriers]) => {
            branches.forEach(b => $('#branch_id').append(`<option value="${b.id}">${b.branch_name}</option>`));
            couriers.forEach(c => $('#courier_id').append(`<option value="${c.id}">${c.partner_name}</option>`));

            // Pickup point loading is now handled by courier selection, but we populate it for initial state if editing
            const bookingId = $('#booking_id').val();
            if (!bookingId) {
                // Not editing, just setup the filter
            }

            // Courier Change -> Filter Pickup Points
            $('#courier_id').change(function () {
                let cid = $(this).val();
                let $pickupSelect = $('#pickup_point_id');
                let currentVal = $pickupSelect.val(); // Remember if it was already set (for edit mode)

                $pickupSelect.empty().append('<option value="">Select Pickup Point</option>');

                if (cid && window.pickupPoints) {
                    let filtered = window.pickupPoints.filter(p => p.courier_id == cid);
                    filtered.forEach(p => {
                        $pickupSelect.append(`<option value="${p.id}">${p.name} (${p.city})</option>`);
                    });
                }

                if (currentVal) {
                    $pickupSelect.val(currentVal);
                }
                $pickupSelect.trigger('change');
            });

            // Now Load Single Booking to populate
            if (bookingId) {
                $.get('api/booking/readone.php?id=' + bookingId, function (res) {
                    if (res.status === 'success') {
                        const d = res.data;
                        // Populate Basic
                        $('#branch_id').val(d.branch_id).trigger('change');
                        $('select[name="booking_type"]').val(d.booking_type || 'Forward');
                        $('input[name="booking_date"]').val(d.created_at ? d.created_at.split(' ')[0] : '');
                        $('input[name="booking_ref_id"]').val(d.booking_ref_id);
                        $('#courier_id').val(d.courier_id).trigger('change');
                        $('#pickup_point_id').val(d.pickup_point_id).trigger('change');

                        // Consignor
                        $('#shipper_name').val(d.shipper_name);
                        $('#shipper_phone').val(d.shipper_phone);
                        $('#shipper_pin').val(d.shipper_pin);
                        $('#shipper_address').val(d.shipper_address);
                        $('#shipper_city').val(d.shipper_city);
                        $('#shipper_state').val(d.shipper_state);

                        // Consignee
                        $('input[name="consignee_name"]').val(d.consignee_name);
                        $('input[name="consignee_phone"]').val(d.consignee_phone);
                        $('input[name="consignee_email"]').val(d.consignee_email);
                        $('input[name="consignee_gst"]').val(d.consignee_gst);
                        $('textarea[name="consignee_address"]').val(d.consignee_address);
                        $('input[name="consignee_pin"]').val(d.consignee_pin);
                        $('input[name="consignee_city"]').val(d.consignee_city);
                        $('input[name="consignee_state"]').val(d.consignee_state);

                        // Package
                        $('select[name="shipping_mode"]').val(d.shipping_mode);
                        $('input[name="product_desc"]').val(d.product_desc);

                        let packages = [];
                        try { packages = typeof d.package_details === 'string' ? JSON.parse(d.package_details) : d.package_details; } catch (e) { }
                        if (packages && packages.length > 0) {
                            packages.forEach(pkg => addRow(pkg));
                        } else {
                            addRow();
                        }

                        // Confirmation
                        $('input[name="invoice_no"]').val(d.invoice_no);
                        $('input[name="invoice_value"]').val(d.invoice_value);
                        $('input[name="ewaybill_no"]').val(d.ewaybill_no);
                        $('select[name="payment_mode"]').val(d.payment_mode).trigger('change');
                        $('input[name="cod_amount"]').val(d.cod_amount);

                        // RTO
                        if (d.rto_name && (d.rto_name !== d.shipper_name)) {
                            $('#sameAsConsignor').prop('checked', false).trigger('change');
                            $('input[name="rto_name"]').val(d.rto_name);
                            $('input[name="rto_phone"]').val(d.rto_phone);
                            $('textarea[name="rto_address"]').val(d.rto_address);
                        }

                        // Courier & Status Based Editing Restriction
                        const courierId = parseInt(d.courier_id);
                        const lastStatus = (d.last_status || '').toUpperCase();
                        const paymentMode = d.payment_mode;

                        // Allowed statuses for Forward (Prepaid/COD): Manifested, In Transit, Pending
                        // Allowed for RVP (Pickup): Scheduled
                        // Allowed for REPL: Manifested, In Transit, Pending

                        let isEditable = true;
                        let allowedFields = []; // If contains items, only these are editable

                        if (courierId === 2) {
                            // Own Courier - All Fields Editable
                            isEditable = true;
                        } else {
                            // Check if status allows API update
                            let canEdit = false;
                            const forwardStatuses = ['MANIFESTED', 'IN TRANSIT', 'PENDING'];
                            if (paymentMode === 'Prepaid' || paymentMode === 'COD') {
                                if (forwardStatuses.includes(lastStatus)) canEdit = true;
                            } else if (paymentMode === 'Pickup') {
                                if (lastStatus === 'SCHEDULED') canEdit = true;
                            } else if (paymentMode === 'REPL') {
                                if (forwardStatuses.includes(lastStatus)) canEdit = true;
                            }

                            // If no status at all (Local only), allow edit
                            if (!lastStatus) canEdit = true;

                            if (canEdit) {
                                isEditable = true;
                                // Only these parameters can be updated for Delhivery as per API doc
                                allowedFields = [
                                    'consignee_name', 'consignee_phone', 'payment_mode', 'consignee_address',
                                    'product_desc', 'actual_weight[]', 'length[]', 'width[]', 'height[]', 'boxes[]', 'cod_amount'
                                ];
                            } else {
                                isEditable = false;
                            }
                        }

                        if (!isEditable) {
                            // Completely Readonly
                            $('#shipmentForm input, #shipmentForm textarea').prop('readonly', true).addClass('bg-light');
                            $('#shipmentForm select').prop('disabled', true).trigger('change');
                            $('#btnAddRow, .remove-row, #btnUpdateShipment').hide();
                            $('.card-title').parent().append(`<div class="alert alert-danger py-1 px-2 mt-2 fs-12 mb-0">Record is currently in <b>${lastStatus || 'Processing'}</b> status and cannot be edited.</div>`);
                        } else if (allowedFields.length > 0) {
                            // Partially Readonly (Only specific fields allowed)
                            $('#shipmentForm').find('input, select, textarea').each(function () {
                                const name = $(this).attr('name');
                                if (name && !allowedFields.includes(name) && name !== 'id') {
                                    $(this).prop('readonly', true).addClass('bg-light');
                                    if ($(this).is('select')) {
                                        $(this).prop('disabled', true);
                                        // Specific for Select2
                                        if ($(this).hasClass('select2-hidden-accessible')) {
                                            $(this).trigger('change');
                                        }
                                    }
                                }
                            });
                            // Special UI hint
                            $('.card-title').parent().append(`<div class="alert alert-info py-1 px-2 mt-2 fs-12 mb-0">Only Consigee, Product, and Package details can be updated for this shipment status.</div>`);
                        }
                    }
                });
            }
        });

        // Event Listeners
        $('select[name="payment_mode"]').change(function () {
            $('input[name="cod_amount"]').prop('readonly', $(this).val() !== 'COD');
            if ($(this).val() !== 'COD') $('input[name="cod_amount"]').val(0);
        });

        $('#sameAsConsignor').change(function () {
            $('#rtoFields').toggle(!$(this).is(':checked'));
            $('#rtoMessage').toggle($(this).is(':checked'));
        });

        // Dynamic Table
        function buildPackageRow(rowNo, rowData = {}) {
            return `<tr class="pkg-row">
                <td class="text-center align-middle fw-semibold pkg-row-no">${rowNo}</td>
                <td><input type="number" name="length[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" value="${rowData.length || ''}" required></td>
                <td><input type="number" name="width[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" value="${rowData.width || ''}" required></td>
                <td><input type="number" name="height[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" value="${rowData.height || ''}" required></td>
                <td><input type="number" name="boxes[]" class="form-control form-control-sm calc-trigger" min="1" step="1" value="${rowData.boxes || 1}" required></td>
                <td><input type="number" step="0.01" min="0.01" name="actual_weight[]" class="form-control form-control-sm calc-trigger" value="${rowData.actual_weight || ''}" required></td>
                <td><input type="text" name="vol_weight[]" class="form-control form-control-sm bg-light" readonly></td>
                <td><input type="text" name="charged_weight[]" class="form-control form-control-sm bg-light fw-bold" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ti ti-x"></i></button></td>
            </tr>`;
        }

        function addRow(rowData = {}) {
            const rowNo = $('#pkgTable tbody .pkg-row').length + 1;
            $('#pkgTable tbody').append(buildPackageRow(rowNo, rowData));
            recalculatePackageRow($('#pkgTable tbody .pkg-row:last'));
            calculateTotal();
        }

        $(document).on('click', '.remove-row', function () {
            if ($('#pkgTable tbody tr').length > 1) { $(this).closest('tr').remove(); calculateTotal(); }
        });

        function recalculatePackageRow(row) {
            let L = parseFloat(row.find('input[name="length[]"]').val()) || 0;
            let W = parseFloat(row.find('input[name="width[]"]').val()) || 0;
            let H = parseFloat(row.find('input[name="height[]"]').val()) || 0;
            let boxes = parseFloat(row.find('input[name="boxes[]"]').val()) || 0;
            let actWtPerBox = parseFloat(row.find('input[name="actual_weight[]"]').val()) || 0;
            let volWtPerBox = (L * W * H) / 5000;
            let chgWtPerBox = Math.max(actWtPerBox, volWtPerBox);
            row.find('input[name="vol_weight[]"]').val((volWtPerBox * boxes).toFixed(2));
            row.find('input[name="charged_weight[]"]').val((chgWtPerBox * boxes).toFixed(2));
        }

        $(document).on('input', '.calc-trigger', function () { recalculatePackageRow($(this).closest('tr')); calculateTotal(); });

        function calculateTotal() {
            let totalBoxes = 0, totalActual = 0, totalChg = 0;
            $('.pkg-row').each(function () {
                let b = parseFloat($(this).find('input[name="boxes[]"]').val()) || 0;
                totalBoxes += b;
                totalActual += (b * (parseFloat($(this).find('input[name="actual_weight[]"]').val()) || 0));
                totalChg += parseFloat($(this).find('input[name="charged_weight[]"]').val()) || 0;
            });
            $('#total_boxes').val(totalBoxes);
            $('#total_actual_weight').val(totalActual.toFixed(2));
            $('#total_weight').val(totalChg.toFixed(2));
        }

        $('#btnAddRow').click(() => addRow());

        // Update Submit
        $('#btnUpdateShipment').click(function () {
            // Temporarily enable disabled fields for serialization so they are sent to the API
            const disabledFields = $('#shipmentForm').find(':disabled').prop('disabled', false);
            const formData = $('#shipmentForm').serialize();
            disabledFields.prop('disabled', true); // Re-disable them

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Updating...');

            $.post('api/shipment/update.php', formData, function (res) {
                if (res.status === 'success') {
                    alert('Shipment Updated!');
                    window.location.href = 'shipment-list.php';
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html('<i class="ti ti-check"></i> Update Shipment');
                }
            }, 'json').fail(() => { alert('Request Failed'); btn.prop('disabled', false).html('<i class="ti ti-check"></i> Update Shipment'); });
        });
    });
</script>