<?php
require_once 'header.php';
require_once 'config/middleware.php';
?>

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
                            <h4 class="fs-18 fw-semibold m-0">WhatsApp Templates</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <div class="mb-4">
                                        <i class="ti ti-template display-1 text-primary"></i>
                                    </div>
                                    <h3 class="mb-3">Coming Soon</h3>
                                    <p class="text-muted">Create and manage WhatsApp message templates for various notifications.</p>
                                    <p class="text-muted">This feature is currently under development.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'footer.php'; ?>
        </div>
    </div>
</body>
</html>
