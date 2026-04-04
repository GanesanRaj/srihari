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
            /* Adjust font size if needed */
        }

        .mb-3 {
            margin-bottom: 5px !important;
            /* Tighter spacing */
        }

        .form-control,
        .form-select {
            padding: 5px !important;
            font-size: 0.9rem;
        }

        /* Adjust select2 to match */
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

            <!-- Start Content-->
            <div class="px-0">

                <!-- Page Title -->
                <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Create New Shipment</h4>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Shipment Booking</h4>
                            </div>
                            <div class="card-body">

                                <form id="shipmentForm">
                                    <div class="ins-wizard" data-wizard>
                                        <!-- Navigation Tabs -->
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
                                            <!-- Step 1: Origin -->
                                            <div class="tab-pane fade show active" id="originInfo">
                                                <div class="row">
                                                    <!-- Left Column -->
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
                                                            <label class="col-sm-4 col-form-label">Client</label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select select2" id="client_id"
                                                                    data-toggle="select2" name="client_id">
                                                                    <option value="">Select Client</option>
                                                                </select>
                                                                <small class="text-muted">Clients for selected branch</small>
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

                                                        <div class="row mb-3" id="pickupPointRow">
                                                            <label class="col-sm-4 col-form-label">Pickup Point <span
                                                                    class="text-danger"
                                                                    id="pickupReqMark">*</span></label>
                                                            <div class="col-sm-8">
                                                                <select class="form-select select2" id="pickup_point_id"
                                                                    data-toggle="select2" name="pickup_point_id"
                                                                    required>
                                                                    <option value="">Select Pickup Point</option>
                                                                </select>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <!-- Right Column -->
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Date <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="date" class="form-control"
                                                                    name="booking_date"
                                                                    value="<?php echo date ( 'Y-m-d' ); ?>" required>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Reference No</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="booking_ref_id" placeholder="Auto if empty">
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
                                                <h5 class="mb-3 text-primary"><i class="ti ti-user-check"></i> Manual
                                                    Consignor Details</h5>

                                                <div class="alert alert-info py-2 mb-3">
                                                    <i class="ti ti-info-circle"></i> Details auto-filled from Pickup
                                                    Point selection, but can be edited.
                                                </div>

                                                <div class="row">
                                                    <!-- Left Column -->
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Phone <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                        id="shipper_phone" name="shipper_phone"
                                                                        pattern="\d{10}" maxlength="10" required>
                                                                    <button class="btn btn-outline-primary"
                                                                        type="button" id="btnGetShipper">Get</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Consignor Name <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="shipper_name" name="shipper_name" required>
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

                                                    <!-- Right Column -->
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
                                                <div
                                                    class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                                    <h5 class="text-primary mb-0"><i class="ti ti-user"></i> Consignee
                                                        Details</h5>
                                                    <div class="tat-widget">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span
                                                                class="fw-semibold fs-xxs text-uppercase text-muted">Expected
                                                                TAT</span>
                                                            <div class="form-check form-switch m-0">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="checkTAT">
                                                                <label class="form-check-label fs-xxs ms-1"
                                                                    for="checkTAT">Check</label>
                                                            </div>
                                                        </div>
                                                        <div id="tatResult" class="tat-result text-muted fs-xxs mt-1">
                                                            Enable check and enter destination PIN.
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <!-- Left Column -->
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Phone <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                        id="consignee_phone" name="consignee_phone"
                                                                        pattern="\d{10}" maxlength="10" required>
                                                                    <button class="btn btn-outline-primary"
                                                                        type="button" id="btnGetConsignee">Get</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Full Name <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="consignee_name" name="consignee_name" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Email</label>
                                                            <div class="col-sm-8">
                                                                <input type="email" class="form-control"
                                                                    id="consignee_email" name="consignee_email">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">GST Number</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="consignee_gst" name="consignee_gst"
                                                                    placeholder="Optional">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Right Column -->
                                                    <div class="col-md-6">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Address <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <textarea class="form-control" id="consignee_address"
                                                                    name="consignee_address" rows="2"
                                                                    required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">PIN Code <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="consignee_pin" name="consignee_pin" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">City <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="consignee_city" name="consignee_city" required>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">State <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    id="consignee_state" name="consignee_state"
                                                                    required>
                                                            </div>
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

                                            <!-- Step 3: Package -->
                                            <div class="tab-pane fade" id="packageInfo">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Shipping Mode</label>
                                                        <select class="form-select" name="shipping_mode">
                                                            <option value="Surface">Surface</option>
                                                            <option value="Express">Air</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Product Description</label>
                                                        <input type="text" class="form-control" name="product_desc">
                                                    </div>
                                                </div>

                                                <h5 class="mb-1">Delhivery Multi-Box Package Details</h5>
                                                <p class="text-muted fs-xxs mb-2">
                                                    Each row is one box type. <strong>Boxes</strong> means how many
                                                    identical boxes for that row.
                                                </p>
                                                <div class="alert alert-light border py-2 fs-xxs mb-2">
                                                    Volumetric weight per box = (Length x Width x Height) / 5000
                                                </div>
                                                <div id="awbHint" class="alert alert-info py-2 fs-xxs mb-2" style="display:none;">
                                                    <strong>Own Courier:</strong> Leave AWB empty to auto-assign from allocation. Or enter a valid available serial per box (each box must have a unique serial; same serial in two boxes is not allowed).
                                                </div>
                                                <div id="awbValidationMsg" class="small mb-1"></div>

                                                <div class="table-responsive mb-2">
                                                    <table class="table table-bordered table-sm" id="pkgTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 38px">#</th>
                                                                <th style="min-width:110px">AWB No</th>
                                                                <th>Length (cm)</th>
                                                                <th>Width (cm)</th>
                                                                <th>Height (cm)</th>
                                                                <th>Boxes</th>
                                                                <th>Actual Wt (kg/box)</th>
                                                                <th>Vol. Wt (total)</th>
                                                                <th>Chg. Wt (total)</th>
                                                                <th style="width:48px"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Rows dynamic -->
                                                            <tr class="pkg-row">
                                                                <td
                                                                    class="text-center align-middle fw-semibold pkg-row-no">
                                                                    1</td>
                                                                <td><input type="text" name="pkg_awb_no[]"
                                                                        class="form-control form-control-sm"
                                                                        placeholder="AWB / EWB No"></td>
                                                                <td><input type="number" name="length[]"
                                                                        class="form-control form-control-sm calc-trigger"
                                                                        min="0.01" step="0.01" placeholder="L" required>
                                                                </td>
                                                                <td><input type="number" name="width[]"
                                                                        class="form-control form-control-sm calc-trigger"
                                                                        min="0.01" step="0.01" placeholder="W" required>
                                                                </td>
                                                                <td><input type="number" name="height[]"
                                                                        class="form-control form-control-sm calc-trigger"
                                                                        min="0.01" step="0.01" placeholder="H" required>
                                                                </td>
                                                                <td><input type="number" name="boxes[]"
                                                                        class="form-control form-control-sm calc-trigger"
                                                                        min="1" step="1" value="1" required></td>
                                                                <td><input type="number" step="0.01"
                                                                        name="actual_weight[]"
                                                                        class="form-control form-control-sm calc-trigger"
                                                                        min="0.01" required></td>
                                                                <td><input type="text" name="vol_weight[]"
                                                                        class="form-control form-control-sm bg-light"
                                                                        readonly></td>
                                                                <td><input type="text" name="charged_weight[]"
                                                                        class="form-control form-control-sm bg-light fw-bold"
                                                                        readonly></td>
                                                                <td><button type="button"
                                                                        class="btn btn-sm btn-danger remove-row"><i
                                                                            class="ti ti-x"></i></button></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-sm btn-info"
                                                            id="btnAddRow">
                                                            <i class="ti ti-plus"></i> Add Box Row
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            id="btnCloneLastRow">
                                                            <i class="ti ti-copy"></i> Clone Last Row
                                                        </button>
                                                    </div>
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
                                                    Additional Details</h5>

                                                <div class="row">
                                                    <!-- Left Column: Invoice Details -->
                                                    <div class="col-md-6">
                                                        <h6 class="mb-3 border-bottom pb-2">Invoice / E-Way Bill
                                                        </h6>

                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Invoice
                                                                No</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="invoice_no">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">Invoice
                                                                Value</label>
                                                            <div class="col-sm-8">
                                                                <input type="number" step="0.01" class="form-control"
                                                                    name="invoice_value">
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-4 col-form-label">E-Way Bill
                                                                No</label>
                                                            <div class="col-sm-8">
                                                                <input type="text" class="form-control"
                                                                    name="ewaybill_no">
                                                            </div>
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
                                                                <div class="col-sm-8">
                                                                    <input type="number" step="0.01"
                                                                        class="form-control" name="cod_amount"
                                                                        value="0">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Right Column: RTO Details -->
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
                                                                <label class="col-sm-4 col-form-label">RTO
                                                                    Name</label>
                                                                <div class="col-sm-8">
                                                                    <input type="text" class="form-control"
                                                                        name="rto_name">
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Phone</label>
                                                                <div class="col-sm-8">
                                                                    <input type="text" class="form-control"
                                                                        name="rto_phone">
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label class="col-sm-4 col-form-label">Address</label>
                                                                <div class="col-sm-8">
                                                                    <textarea class="form-control" name="rto_address"
                                                                        rows="2"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="rtoMessage" class="alert alert-secondary fs-xxs">
                                                            Running RTO details same as Consignor (Shipper). Uncheck
                                                            to edit.
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between mt-3">
                                                    <button type="button" class="btn btn-secondary" data-wizard-prev>←
                                                        Back</button>
                                                    <button type="button" class="btn btn-success"
                                                        id="btnSubmitShipment">
                                                        <i class="ti ti-check"></i> Create Shipment
                                                    </button>
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
<!-- END wrapper -->

<!-- Vendors JS -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/select2/select2.min.js"></script>
<?php include 'footer.php'; ?>
<script src="assets/js/pages/form-wizard.js"></script>

<script>
    $(document).ready(function () {
        if (jQuery().select2) {
            $('[data-toggle="select2"]').select2({
                width: '100%'
            });
        }

        // Load Branches
        $.get('api/branch/read.php', function (res) {
            if (res.data) {
                res.data.forEach(b => {
                    $('#branch_id').append(`<option value="${b.id}">${b.branch_name}</option>`);
                });
            }
        });

        // Load clients by branch (branch-based client)
        $('#branch_id').change(function () {
            var branchId = $(this).val();
            var $client = $('#client_id');
            $client.empty().append('<option value="">Select Client</option>');
            if (branchId) {
                $.get('api/client/read.php?length=1000&branch_id=' + encodeURIComponent(branchId), function (res) {
                    if (res.data && res.data.length) {
                        res.data.forEach(function (c) {
                            $client.append(`<option value="${c.id}">${c.client_name || c.contact_no || 'Client #' + c.id}</option>`);
                        });
                    }
                });
            }
            if ($client.data('select2')) $client.trigger('change');
        });

        // Load Pickup Points (and auto-fill consignor on change)
        $.get('api/pickuppoint/read.php?length=-1', function (res) {
            if (res.data) {
                window.pickupPoints = res.data; // Store for filtering
            }
        });

        // Auto-fill Consignor details
        $('#pickup_point_id').change(function () {
            let pid = $(this).val();
            let point = window.pickupPoints ? window.pickupPoints.find(p => p.id == pid) : null;
            if (point) {
                $('#shipper_name').val(point.name);
                $('#shipper_phone').val(point.phone);
                $('#shipper_address').val(point.address);
                $('#shipper_pin').val(point.pin);
                $('#shipper_city').val(point.city);
                $('#shipper_state').val(point.state || 'State');
                if ($('#checkTAT').is(':checked')) {
                    scheduleTatFetch();
                }
            }
        });

        // Load Couriers
        $.get('api/courier_partner/read.php?length=-1', function (res) {
            if (res.data) {
                res.data.forEach(c => {
                    $('#courier_id').append(`<option value="${c.id}">${c.partner_name}</option>`);
                });
                // Set default to Own Courier (ID=2)
                $('#courier_id').val(2).trigger('change');
            }
        });

        // Courier Change -> Filter Pickup Points + Toggle Pickup Point Required/Hidden
        $('#courier_id').change(function () {
            let cid = $(this).val();
            let $pickupSelect = $('#pickup_point_id');
            $pickupSelect.empty().append('<option value="">Select Pickup Point</option>');

            // Toggle Pickup Point visibility and requirement based on courier selection
            // If Own Courier (ID=2), hide and make it optional; otherwise show and require
            if (cid == 2) {
                $pickupSelect.removeAttr('required');
                $('#pickupReqMark').hide();
                $('#pickupPointRow').hide();
                $('#awbHint').show();
            } else {
                $pickupSelect.attr('required', 'required');
                $('#pickupReqMark').show();
                $('#pickupPointRow').show();
                $('#awbHint').hide();
                $('#awbValidationMsg').empty().removeClass('text-success text-danger');
            }
            if (cid && window.pickupPoints) {
                let filtered = window.pickupPoints.filter(p => p.courier_id == cid);
                filtered.forEach(p => {
                    $pickupSelect.append(`<option value="${p.id}">${p.name} (${p.city})</option>`);
                });
            }
            $pickupSelect.trigger('change'); // Trigger change to clear consignor if needed
        });
        if ($('#courier_id').val() == 2) $('#awbHint').show();

        // AWB validation for Own Courier: every row (multi-box); also check duplicate AWB across rows
        function checkDuplicateAwbInTable() {
            let seen = {};
            let dup = null;
            $('#pkgTable tbody tr').each(function (idx) {
                let awb = ($(this).find('input[name="pkg_awb_no[]"]').val() || '').trim().toLowerCase();
                if (awb === '') return;
                if (seen[awb] !== undefined) {
                    dup = { awb: $(this).find('input[name="pkg_awb_no[]"]').val().trim(), boxes: [seen[awb] + 1, idx + 1] };
                    return false;
                }
                seen[awb] = idx;
            });
            return dup;
        }

        $(document).on('blur', '#pkgTable input[name="pkg_awb_no[]"]', function () {
            let $input = $(this);
            if ($('#courier_id').val() != 2) return;
            let rowIndex = $('#pkgTable tbody tr').index($input.closest('tr'));
            let boxNum = rowIndex + 1;
            let awb = ($input.val() || '').trim();
            $('#awbValidationMsg').empty().removeClass('text-success text-danger text-muted');

            let dup = checkDuplicateAwbInTable();
            if (dup) {
                $('#awbValidationMsg').html('<span class="text-danger">Same AWB cannot be used in more than one box (e.g. box ' + dup.boxes[0] + ' and ' + dup.boxes[1] + '). Use a unique serial per box.</span>').addClass('text-danger');
                return;
            }
            let branchId = $('#branch_id').val();
            let shippingMode = $('select[name="shipping_mode"]').val() || 'Surface';
            let serviceType = (shippingMode === 'Surface') ? 'surface' : 'express';
            if (awb === '') {
                if (!branchId || branchId <= 0) {
                    $('#awbValidationMsg').html('<span class="text-muted">Leave empty to assign next serial from allocation.</span>').addClass('text-muted');
                    return;
                }
                $.get('api/serial_allocation/get_available_serials.php', { branch_id: branchId, service_type: serviceType }, function (res) {
                    if (res.status === 'success' && res.total > 0) {
                        $('#awbValidationMsg').html('<span class="text-muted">Leave empty to assign next serial from allocation.</span>').addClass('text-muted');
                    } else {
                        $('#awbValidationMsg').html('<span class="text-danger">No serials in allocation for this branch and shipping mode. Add serials or enter AWB for the box.</span>').addClass('text-danger');
                    }
                }, 'json').fail(function () {
                    $('#awbValidationMsg').html('<span class="text-muted">Leave empty to assign next serial from allocation.</span>').addClass('text-muted');
                });
                return;
            }
            $.get('api/serial_allocation/check_serial_awb.php', {
                serial_number: awb,
                branch_id: branchId,
                service_type: serviceType
            }, function (res) {
                if (res.valid) {
                    $('#awbValidationMsg').html('<span class="text-success">Box ' + boxNum + ': Valid available serial.</span>').addClass('text-success');
                } else {
                    $('#awbValidationMsg').html('<span class="text-danger">Box ' + boxNum + ': ' + (res.message || 'Invalid or not available') + '</span>').addClass('text-danger');
                }
            }, 'json').fail(function () {
                $('#awbValidationMsg').html('<span class="text-danger">Box ' + boxNum + ': Could not validate AWB.</span>').addClass('text-danger');
            });
        });

        // --- Fetch Last Details by Phone ---
        $('#btnGetShipper').click(function () {
            let phone = $('#shipper_phone').val();
            if (phone.length < 10) return;
            let $btn = $(this);
            $btn.prop('disabled', true).html('...');
            $.get('api/booking/fetch_last_by_phone.php', { phone: phone, type: 'shipper' }, function (res) {
                $btn.prop('disabled', false).html('Get');
                if (res.status === 'success') {
                    $('#shipper_name').val(res.data.name);
                    $('#shipper_address').val(res.data.address);
                    $('#shipper_pin').val(res.data.pin);
                    $('#shipper_city').val(res.data.city);
                    $('#shipper_state').val(res.data.state);
                } else {
                    if (typeof showtoastt === 'function') showtoastt(res.message, 'info');
                }
            });
        });

        $('#btnGetConsignee').click(function () {
            let phone = $('#consignee_phone').val();
            if (phone.length < 10) return;
            let $btn = $(this);
            $btn.prop('disabled', true).html('...');
            $.get('api/booking/fetch_last_by_phone.php', { phone: phone, type: 'consignee' }, function (res) {
                $btn.prop('disabled', false).html('Get');
                if (res.status === 'success') {
                    $('#consignee_name').val(res.data.name);
                    $('#consignee_address').val(res.data.address);
                    $('#consignee_pin').val(res.data.pin);
                    $('#consignee_city').val(res.data.city);
                    $('#consignee_state').val(res.data.state);
                    $('#consignee_email').val(res.data.email);
                    $('#consignee_gst').val(res.data.gst);
                } else {
                    if (typeof showtoastt === 'function') showtoastt(res.message, 'info');
                }
            });
        });

        // Payment Mode
        $('select[name="payment_mode"]').change(function () {
            if ($(this).val() === 'COD') {
                $('input[name="cod_amount"]').prop('readonly', false);
            } else {
                $('input[name="cod_amount"]').prop('readonly', true).val(0);
            }
        });

        // --- Expected TAT (Delhivery) ---
        let tatFetchTimer = null;
        let tatRequestSeq = 0;

        function digitsOnly(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function updateTatResult(html, statusClass = 'text-muted') {
            const $tat = $('#tatResult');
            $tat.removeClass('text-muted text-info text-success text-danger text-warning');
            $tat.addClass(statusClass).html(html);
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getExpectedPickupDate() {
            const bookingDate = $('input[name="booking_date"]').val();
            if (!bookingDate) {
                return '';
            }
            return `${bookingDate} 10:00`;
        }

        function callTatApi(modeCode, modeLabel, payload) {
            return new Promise(function (resolve) {
                const requestData = $.extend({}, payload, { mot: modeCode });
                $.get('api/tat/delhivery.php', requestData, function (res) {
                    if (res && res.status === 'success') {
                        resolve({ ok: true, modeLabel: modeLabel, data: res });
                    } else {
                        resolve({
                            ok: false,
                            modeLabel: modeLabel,
                            message: (res && res.message) ? res.message : 'Unable to fetch TAT'
                        });
                    }
                }, 'json').fail(function (xhr) {
                    const err = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'TAT request failed';
                    resolve({ ok: false, modeLabel: modeLabel, message: err });
                });
            });
        }

        function renderTatModeLine(item) {
            const label = `<span class="fw-semibold">${item.modeLabel}</span>`;
            if (!item.ok) {
                return `<div class="mb-1">${label}: <span class="text-danger">${escapeHtml(item.message)}</span></div>`;
            }

            const res = item.data || {};
            let badges = '';

            if (res.tat_days !== null && res.tat_days !== undefined && String(res.tat_days) !== '') {
                badges += `<span class="badge bg-success-subtle text-success">TAT: ${escapeHtml(res.tat_days)} day(s)</span>`;
            }

            if (res.expected_delivery_date) {
                badges += ` <span class="badge bg-info-subtle text-info">ETA: ${escapeHtml(res.expected_delivery_date)}</span>`;
            }

            if (!badges) {
                badges = '<span class="badge bg-success-subtle text-success">TAT available</span>';
            }

            return `<div class="mb-1">${label}: ${badges}</div>`;
        }

        function fetchExpectedTat() {
            if (!$('#checkTAT').is(':checked')) {
                return;
            }

            const originPin = digitsOnly($('#shipper_pin').val());
            const destinationPin = digitsOnly($('input[name="consignee_pin"]').val());
            const courierId = $('#courier_id').val();

            if (!courierId) {
                updateTatResult('Select courier to check TAT.', 'text-warning');
                return;
            }

            if (originPin.length !== 6 || destinationPin.length !== 6) {
                updateTatResult('Enter valid 6-digit origin and destination PIN.', 'text-muted');
                return;
            }

            const requestId = ++tatRequestSeq;

            updateTatResult('Checking Surface and Express TAT...', 'text-info');

            const payload = {
                courier_id: courierId,
                origin_pin: originPin,
                destination_pin: destinationPin,
                pdt: 'B2C',
                expected_pickup_date: getExpectedPickupDate()
            };

            Promise.all([
                callTatApi('S', 'Surface', payload),
                callTatApi('E', 'Express', payload)
            ]).then(function (results) {
                if (requestId !== tatRequestSeq) {
                    return;
                }

                const html = results.map(renderTatModeLine).join('');
                const anySuccess = results.some(function (x) {
                    return x.ok;
                });

                updateTatResult(html, anySuccess ? 'text-success' : 'text-danger');
            });
        }

        function scheduleTatFetch() {
            clearTimeout(tatFetchTimer);
            tatFetchTimer = setTimeout(fetchExpectedTat, 350);
        }

        $('#checkTAT').change(function () {
            if ($(this).is(':checked')) {
                fetchExpectedTat();
            } else {
                updateTatResult('Enable check and enter destination PIN.', 'text-muted');
            }
        });

        $(document).on('input', 'input[name="consignee_pin"], #shipper_pin', function () {
            if ($('#checkTAT').is(':checked')) {
                scheduleTatFetch();
            }
        });

        $(document).on('change', '#courier_id, select[name="shipping_mode"], input[name="booking_date"]', function () {
            if ($('#checkTAT').is(':checked')) {
                fetchExpectedTat();
            }
        });

        // --- Dynamic Package Table ---
        function buildPackageRow(rowNo, rowData = {}) {
            return `
        <tr class="pkg-row">
            <td class="text-center align-middle fw-semibold pkg-row-no">${rowNo}</td>
            <td><input type="text" name="pkg_awb_no[]" class="form-control form-control-sm" placeholder="AWB / EWB No" value="${rowData.pkg_awb_no || ''}"></td>
            <td><input type="number" name="length[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" placeholder="L" value="${rowData.length || ''}" required></td>
            <td><input type="number" name="width[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" placeholder="W" value="${rowData.width || ''}" required></td>
            <td><input type="number" name="height[]" class="form-control form-control-sm calc-trigger" min="0.01" step="0.01" placeholder="H" value="${rowData.height || ''}" required></td>
            <td><input type="number" name="boxes[]" class="form-control form-control-sm calc-trigger" min="1" step="1" value="${rowData.boxes || 1}" required></td>
            <td><input type="number" step="0.01" min="0.01" name="actual_weight[]" class="form-control form-control-sm calc-trigger" value="${rowData.actual_weight || ''}" required></td>
            <td><input type="text" name="vol_weight[]" class="form-control form-control-sm bg-light" value="${rowData.vol_weight || ''}" readonly></td>
            <td><input type="text" name="charged_weight[]" class="form-control form-control-sm bg-light fw-bold" value="${rowData.charged_weight || ''}" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ti ti-x"></i></button></td>
        </tr>`;
        }

        function renumberPackageRows() {
            $('#pkgTable tbody .pkg-row').each(function (idx) {
                $(this).find('.pkg-row-no').text(idx + 1);
            });
        }

        function addRow(rowData = {}) {
            const rowNo = $('#pkgTable tbody .pkg-row').length + 1;
            $('#pkgTable tbody').append(buildPackageRow(rowNo, rowData));
            recalculatePackageRow($('#pkgTable tbody .pkg-row:last'));
            calculateTotal();
        }

        function getLastRowData() {
            const $last = $('#pkgTable tbody .pkg-row:last');
            if (!$last.length) {
                return null;
            }

            return {
                length: $last.find('input[name="length[]"]').val(),
                width: $last.find('input[name="width[]"]').val(),
                height: $last.find('input[name="height[]"]').val(),
                boxes: $last.find('input[name="boxes[]"]').val(),
                actual_weight: $last.find('input[name="actual_weight[]"]').val()
            };
        }

        $('#btnAddRow').click(function () {
            addRow();
        });

        $('#btnCloneLastRow').click(function () {
            const rowData = getLastRowData() || {};
            addRow(rowData);
        });

        $(document).on('click', '.remove-row', function () {
            if ($('#pkgTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                renumberPackageRows();
                calculateTotal();
            }
        });

        function recalculatePackageRow(row) {
            let L = parseFloat(row.find('input[name="length[]"]').val()) || 0;
            let W = parseFloat(row.find('input[name="width[]"]').val()) || 0;
            let H = parseFloat(row.find('input[name="height[]"]').val()) || 0;
            let boxes = parseFloat(row.find('input[name="boxes[]"]').val()) || 0;
            let actWtPerBox = parseFloat(row.find('input[name="actual_weight[]"]').val()) || 0;

            if (boxes < 1) {
                boxes = 1;
                row.find('input[name="boxes[]"]').val(1);
            }

            let volWtPerBox = (L * W * H) / 5000;
            let chgWtPerBox = Math.max(actWtPerBox, volWtPerBox);

            row.find('input[name="vol_weight[]"]').val((volWtPerBox * boxes).toFixed(2));
            row.find('input[name="charged_weight[]"]').val((chgWtPerBox * boxes).toFixed(2));
        }

        $(document).on('input', '.calc-trigger', function () {
            let row = $(this).closest('tr');
            recalculatePackageRow(row);

            calculateTotal();
        });

        function calculateTotal() {
            let totalChg = 0;
            let totalBoxes = 0;
            let totalActual = 0;

            $('.pkg-row').each(function () {
                let boxes = parseFloat($(this).find('input[name="boxes[]"]').val()) || 0;
                let aw = parseFloat($(this).find('input[name="actual_weight[]"]').val()) || 0;
                let cw = parseFloat($(this).find('input[name="charged_weight[]"]').val()) || 0;

                totalBoxes += boxes;
                totalActual += (boxes * aw);
                totalChg += cw;
            });

            $('#total_boxes').val(totalBoxes.toFixed(0));
            $('#total_actual_weight').val(totalActual.toFixed(2));
            $('#total_weight').val(totalChg.toFixed(2));
        }

        calculateTotal();

        // RTO Toggle
        $('#sameAsConsignor').change(function () {
            if ($(this).is(':checked')) {
                $('#rtoFields').hide();
                $('#rtoMessage').show();
            } else {
                $('#rtoFields').show();
                $('#rtoMessage').hide();
            }
        });

        // --- Validation (Tab-wise + Final Submit) ---
        const tabValidationRules = {
            originInfo: [
                { id: "branch_id", message: "Branch is required", required: true },
                { name: "booking_type", message: "Type is required", required: true },
                { name: "booking_date", message: "Date is required", required: true },
                { id: "pickup_point_id", message: "Pickup Point is required", required: true },
                { id: "courier_id", message: "Courier is required", required: true }
            ],
            consignorInfo: [
                { id: "shipper_name", message: "Consignor Name is required", required: true },
                { id: "shipper_phone", message: "Consignor Phone is required", required: true, pattern: /^\d{10}$/, patternMessage: "Consignor Phone must be 10 digits" },
                { id: "shipper_address", message: "Consignor Address is required", required: true },
                { id: "shipper_pin", message: "Consignor Pincode is required", required: true },
                { id: "shipper_city", message: "Consignor City is required", required: true },
                { id: "shipper_state", message: "Consignor State is required", required: true }
            ],
            consigneeInfo: [
                { name: "consignee_name", message: "Consignee Name is required", required: true },
                { name: "consignee_phone", message: "Consignee Phone is required", required: true, pattern: /^\d{10}$/, patternMessage: "Consignee Phone must be 10 digits" },
                { name: "consignee_address", message: "Consignee Address is required", required: true },
                { name: "consignee_pin", message: "Consignee PIN is required", required: true },
                { name: "consignee_city", message: "Consignee City is required", required: true },
                { name: "consignee_state", message: "Consignee State is required", required: true }
            ],
            confirmInfo: [
                { name: "payment_mode", message: "Payment Mode is required", required: true }
            ]
        };

        function showValidationError(message) {
            if (typeof showtoastt === 'function') {
                showtoastt(message, 'error');
            } else {
                alert(message);
            }
        }

        function getFieldElement(field) {
            if (field.id) {
                return $('#' + field.id);
            }
            return $(`[name="${field.name}"]`).first();
        }

        function markFieldInvalid($el) {
            $el.addClass('is-invalid');
            if ($el.is('select.select2')) {
                $el.next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        }

        function clearFieldInvalid($el) {
            $el.removeClass('is-invalid');
            if ($el.is('select.select2')) {
                $el.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }
        }

        function validateField(field, errors) {
            const $el = getFieldElement(field);
            if (!$el.length) {
                return true;
            }

            clearFieldInvalid($el);

            const rawValue = $el.val();
            const value = rawValue == null ? '' : String(rawValue).trim();

            // Special case: Pickup Point is only required if courier is NOT Own Courier (ID=2)
            let shouldBeRequired = field.required;
            if (field.id === 'pickup_point_id') {
                const courierId = $('#courier_id').val();
                shouldBeRequired = field.required && courierId != 2;
            }

            if (shouldBeRequired && !value) {
                markFieldInvalid($el);
                errors.push(field.message);
                return false;
            }

            if (value && field.pattern && !field.pattern.test(value)) {
                markFieldInvalid($el);
                errors.push(field.patternMessage || field.message);
                return false;
            }

            return true;
        }

        function validatePackageTab(errors) {
            let isValid = true;
            let pkgRows = $('#pkgTable tbody tr');

            if (pkgRows.length === 0) {
                errors.push("At least one package is required");
                return false;
            }

            if ($('#courier_id').val() == 2) {
                let seen = {};
                pkgRows.each(function (idx) {
                    let awb = ($(this).find('input[name="pkg_awb_no[]"]').val() || '').trim().toLowerCase();
                    if (awb === '') return;
                    if (seen[awb] !== undefined) {
                        errors.push("Same AWB/Serial cannot be used in more than one box (e.g. box " + (seen[awb] + 1) + " and " + (idx + 1) + "). Use a unique serial per box or leave empty.");
                        isValid = false;
                        return false;
                    }
                    seen[awb] = idx;
                });
                if (!isValid) return false;
            }

            pkgRows.each(function () {
                $(this).find('input[required]').each(function () {
                    const $input = $(this);
                    const value = $input.val();
                    const num = parseFloat(value);

                    $input.removeClass('is-invalid');

                    if (value === '' || isNaN(num) || num <= 0) {
                        $input.addClass('is-invalid');
                        if (errors.length === 0) {
                            errors.push("Package dimensions, box count, and actual weight must be greater than 0");
                        }
                        isValid = false;
                    }
                });
            });

            return isValid;
        }

        function validateTab(tabId) {
            let isValid = true;
            let errors = [];
            let rules = tabValidationRules[tabId] || [];

            rules.forEach(function (rule) {
                if (!validateField(rule, errors)) {
                    isValid = false;
                }
            });

            if (tabId === 'packageInfo' && !validatePackageTab(errors)) {
                isValid = false;
            }

            if (!isValid) {
                showValidationError(errors[0] || "Please fill all required fields");
            }

            return isValid;
        }

        function validateCurrentTab() {
            const activeTabId = $('.tab-pane.show.active').attr('id');
            if (!activeTabId) {
                return true;
            }
            return validateTab(activeTabId);
        }

        function validateForm() {
            const orderedTabs = ['originInfo', 'consignorInfo', 'consigneeInfo', 'packageInfo', 'confirmInfo'];

            for (let i = 0; i < orderedTabs.length; i++) {
                const tabId = orderedTabs[i];
                if (!validateTab(tabId)) {
                    const tabTrigger = document.querySelector(`[data-wizard-nav] .nav-link[href="#${tabId}"]`);
                    if (tabTrigger) {
                        new bootstrap.Tab(tabTrigger).show();
                    }
                    return false;
                }
            }

            return true;
        }

        // Block wizard next when current tab is invalid.
        document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (!validateCurrentTab()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        });

        // Prevent skipping ahead by clicking tab headers without validating current tab.
        $('[data-wizard-nav] .nav-link').on('click', function (e) {
            const targetTabId = ($(this).attr('href') || '').replace('#', '');
            const activeTabId = $('.tab-pane.show.active').attr('id');
            const orderedTabs = ['originInfo', 'consignorInfo', 'consigneeInfo', 'packageInfo', 'confirmInfo'];

            const targetIndex = orderedTabs.indexOf(targetTabId);
            const activeIndex = orderedTabs.indexOf(activeTabId);

            if (targetIndex > activeIndex && !validateCurrentTab()) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });

        // Clear invalid marker while user edits.
        $(document).on('input change', '#shipmentForm input, #shipmentForm textarea, #shipmentForm select', function () {
            clearFieldInvalid($(this));
        });

        // Submit
        $('#btnSubmitShipment').click(function () {

            if (!validateForm()) {
                return;
            }

            const formData = $('#shipmentForm').serialize();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Creating...');

            $.post('api/booking/create.php', formData, function (response) {
                if (response.status === 'success') {
                    if (typeof showtoastt === 'function') {
                        showtoastt('Shipment Created! Waybill: ' + response.waybill, 'success');
                    } else {
                        alert('Shipment Created! Waybill: ' + response.waybill);
                    }
                    setTimeout(() => window.location.href = 'shipment-list.php', 1500);
                    // Print Label
                    window.open('shipment-label-print.php?waybill=' + response.waybill, '_blank');
                } else {
                    if (typeof showtoastt === 'function') {
                        showtoastt('Error: ' + response.message, 'error');
                    } else {
                        alert('Error: ' + response.message);
                    }
                    btn.prop('disabled', false).html('<i class="ti ti-check"></i> Create Shipment');
                }
            }, 'json').fail(function (xhr) {
                if (typeof showtoastt === 'function') {
                    showtoastt('Request Failed: ' + xhr.responseText, 'error');
                } else {
                    alert('Request Failed: ' + xhr.responseText);
                }
                btn.prop('disabled', false).html('<i class="ti ti-check"></i> Create Shipment');
            });
        });

    });
</script>