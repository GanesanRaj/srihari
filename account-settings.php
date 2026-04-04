<?php
require_once 'header.php';
?>

<body>
    <!-- Begin page -->
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
                                <i class="ti ti-settings-2 me-1"></i> Account Settings
                            </h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Notification Settings -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Notification Preferences</h5>
                                    <form id="notificationForm">
                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-label mb-0 fw-medium">Email Notifications</label>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Receive notifications via email</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications">
                                            </div>
                                        </div>

                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-label mb-0 fw-medium">SMS Notifications</label>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Receive notifications via SMS</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications">
                                            </div>
                                        </div>

                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-label mb-0 fw-medium">Booking Alerts</label>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Get notified about new bookings</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="booking_alerts" name="booking_alerts">
                                            </div>
                                        </div>

                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-label mb-0 fw-medium">Payment Alerts</label>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Get notified about payment updates</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="payment_alerts" name="payment_alerts">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-soft-primary">
                                            <i class="ti ti-device-floppy me-1"></i> Save Notification Settings
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Language & Timezone -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Regional Settings</h5>
                                    <form id="regionalForm">
                                        <div class="mb-3">
                                            <label class="form-label">Language Preference</label>
                                            <select class="form-select form-select-sm" id="language" name="language">
                                                <option value="en">English</option>
                                                <option value="es">Spanish</option>
                                                <option value="fr">French</option>
                                                <option value="de">German</option>
                                                <option value="it">Italian</option>
                                                <option value="hi">Hindi</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-select form-select-sm" id="timezone" name="timezone">
                                                <option value="UTC">UTC (GMT+0:00)</option>
                                                <option value="America/New_York">Eastern Time (GMT-5:00)</option>
                                                <option value="America/Chicago">Central Time (GMT-6:00)</option>
                                                <option value="America/Denver">Mountain Time (GMT-7:00)</option>
                                                <option value="America/Los_Angeles">Pacific Time (GMT-8:00)</option>
                                                <option value="Europe/London">London (GMT+0:00)</option>
                                                <option value="Europe/Paris">Paris (GMT+1:00)</option>
                                                <option value="Asia/Dubai">Dubai (GMT+4:00)</option>
                                                <option value="Asia/Kolkata">India (GMT+5:30)</option>
                                                <option value="Asia/Singapore">Singapore (GMT+8:00)</option>
                                                <option value="Asia/Tokyo">Tokyo (GMT+9:00)</option>
                                                <option value="Australia/Sydney">Sydney (GMT+11:00)</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-soft-primary">
                                            <i class="ti ti-device-floppy me-1"></i> Save Regional Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <!-- Security Settings -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Security Settings</h5>
                                    <form id="securityForm">
                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <label class="form-label mb-0 fw-medium">Two-Factor Authentication</label>
                                                <p class="text-muted mb-0" style="font-size: 12px;">Add an extra layer of security to your account</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="two_factor_auth" name="two_factor_auth">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Session Timeout (minutes)</label>
                                            <select class="form-select form-select-sm" id="session_timeout" name="session_timeout">
                                                <option value="15">15 minutes</option>
                                                <option value="30">30 minutes</option>
                                                <option value="60">1 hour</option>
                                                <option value="120">2 hours</option>
                                                <option value="240">4 hours</option>
                                                <option value="480">8 hours</option>
                                            </select>
                                            <small class="text-muted">Auto logout after period of inactivity</small>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-soft-primary">
                                            <i class="ti ti-device-floppy me-1"></i> Save Security Settings
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Display Settings -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3 fw-semibold">Display Settings</h5>
                                    <form id="displayForm">
                                        <div class="mb-3">
                                            <label class="form-label">Date Format</label>
                                            <select class="form-select form-select-sm" id="date_format" name="date_format">
                                                <option value="YYYY-MM-DD">YYYY-MM-DD (2024-01-31)</option>
                                                <option value="DD-MM-YYYY">DD-MM-YYYY (31-01-2024)</option>
                                                <option value="MM-DD-YYYY">MM-DD-YYYY (01-31-2024)</option>
                                                <option value="DD/MM/YYYY">DD/MM/YYYY (31/01/2024)</option>
                                                <option value="MM/DD/YYYY">MM/DD/YYYY (01/31/2024)</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Time Format</label>
                                            <select class="form-select form-select-sm" id="time_format" name="time_format">
                                                <option value="24">24 Hour (14:30)</option>
                                                <option value="12">12 Hour (2:30 PM)</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Items Per Page</label>
                                            <select class="form-select form-select-sm" id="items_per_page" name="items_per_page">
                                                <option value="10">10 items</option>
                                                <option value="25">25 items</option>
                                                <option value="50">50 items</option>
                                                <option value="100">100 items</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-soft-primary">
                                            <i class="ti ti-device-floppy me-1"></i> Save Display Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>
            <script>
                $(document).ready(function() {
                    // Load settings
                    loadSettings();

                    // Notification form
                    $('#notificationForm').on('submit', function(e) {
                        e.preventDefault();
                        saveSettings('notification', $(this).serialize());
                    });

                    // Regional form
                    $('#regionalForm').on('submit', function(e) {
                        e.preventDefault();
                        saveSettings('regional', $(this).serialize());
                    });

                    // Security form
                    $('#securityForm').on('submit', function(e) {
                        e.preventDefault();
                        saveSettings('security', $(this).serialize());
                    });

                    // Display form
                    $('#displayForm').on('submit', function(e) {
                        e.preventDefault();
                        saveSettings('display', $(this).serialize());
                    });

                    function loadSettings() {
                        $.ajax({
                            url: 'api/user/get-settings.php',
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    var data = response.data;

                                    // Notification settings
                                    $('#email_notifications').prop('checked', data.email_notifications == 1);
                                    $('#sms_notifications').prop('checked', data.sms_notifications == 1);
                                    $('#booking_alerts').prop('checked', data.booking_alerts == 1);
                                    $('#payment_alerts').prop('checked', data.payment_alerts == 1);

                                    // Regional settings
                                    $('#language').val(data.language || 'en');
                                    $('#timezone').val(data.timezone || 'UTC');

                                    // Security settings
                                    $('#two_factor_auth').prop('checked', data.two_factor_auth == 1);
                                    $('#session_timeout').val(data.session_timeout || '30');

                                    // Display settings
                                    $('#date_format').val(data.date_format || 'YYYY-MM-DD');
                                    $('#time_format').val(data.time_format || '24');
                                    $('#items_per_page').val(data.items_per_page || '25');
                                }
                            }
                        });
                    }

                    function saveSettings(type, data) {
                        $.ajax({
                            url: 'api/user/update-settings.php',
                            type: 'POST',
                            data: data + '&type=' + type,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    showtoastt(response.message, 'success');
                                } else {
                                    showtoastt(response.message, 'error');
                                }
                            },
                            error: function() {
                                showtoastt('Error saving settings', 'error');
                            }
                        });
                    }
                });
            </script>
        </div>
    </div>
</body>

<style>
    .form-control-sm, .form-select-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 13px !important;
    }

    .form-check-input {
        cursor: pointer;
    }
</style>

</html>
