<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions based on mode (Add or Edit)
if (isset($_GET['id'])) {
    require_permission('status', 'is_edit');
} else {
    require_permission('status', 'is_add');
}
?>

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0 10px;">

                <div class="card" style="margin-bottom:5px; margin-top:10px;">
                    <div class="row" style="padding:5px 5px;">
                        <div class="col-md-8">
                            <!-- optional title -->
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="status-list.php">
                                <button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-arrow-left-circle-fill"></i>&nbsp;&nbsp;Back to Status List
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body" style="padding:5px 20px;">
                        <form id="statusForm" class="row" method="POST" novalidate>
                            <input type="hidden" id="statusId" name="id" value="">

                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <div class="mb-3 row">
                                        <label class="col-sm-4 col-form-label" for="name">Name <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="e.g., In Transit" required>
                                            <div class="invalid-feedback">Name is required.</div>
                                        </div>
                                    </div>

                                    <div class="mb-3 row">
                                        <label class="col-sm-4 col-form-label" for="code">Code <span
                                                class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="code" name="code"
                                                placeholder="e.g., IN_TRANSIT" required>
                                            <div class="invalid-feedback">Code is required.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3 row">
                                        <label class="col-sm-4 col-form-label" for="status">Status</label>
                                        <div class="col-sm-8">
                                            <select class="form-control" id="status" name="status">
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3 row">
                                        <label class="col-sm-4 col-form-label" for="remarks">Remarks</label>
                                        <div class="col-sm-8">
                                            <textarea class="form-control" id="remarks" name="remarks" rows="2"
                                                placeholder="Additional notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Save Status
                                    </button>
                                    <a href="status-list.php" class="btn btn-secondary rounded-pill">
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
            <script>
                (function () {
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }

                    const selectedId = getQueryParam('id');

                    if (selectedId) {
                        $.get('api/status/read_single.php?id=' + selectedId, function (res) {
                            if (res.status === 'success' && res.data) {
                                const d = res.data;
                                $('#statusId').val(d.id);
                                $('#name').val(d.name);
                                $('#code').val(d.code);
                                $('#status').val(d.status);
                                $('#remarks').val(d.remarks);
                            } else {
                                if (typeof showtoastt === 'function') {
                                    showtoastt(res.message || 'Status not found', 'error');
                                } else {
                                    alert(res.message || 'Status not found');
                                }
                                setTimeout(function () {
                                    window.location.href = 'status-list.php';
                                }, 1500);
                            }
                        }).fail(function () {
                            if (typeof showtoastt === 'function') {
                                showtoastt('Error loading status record', 'error');
                            } else {
                                alert('Error loading status record');
                            }
                            setTimeout(function () {
                                window.location.href = 'status-list.php';
                            }, 1500);
                        });
                    }

                    function validateForm() {
                        let isValid = true;
                        $('.is-invalid').removeClass('is-invalid');

                        const name = $('#name').val().trim();
                        const code = $('#code').val().trim();

                        if (!name) {
                            $('#name').addClass('is-invalid');
                            isValid = false;
                        }
                        if (!code) {
                            $('#code').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!isValid && typeof showtoastt === 'function') {
                            showtoastt('Please fill all required fields', 'error');
                        }
                        return isValid;
                    }

                    $('#statusForm').on('submit', function (e) {
                        e.preventDefault();
                        if (!validateForm()) return;

                        const $btn = $(this).find('button[type="submit"]');
                        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Saving...');

                        const formData = new FormData(this);
                        const url = selectedId ? 'api/status/update.php' : 'api/status/create.php';

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                if (res.status === 'success') {
                                    if (typeof showtoastt === 'function') {
                                        showtoastt(res.message, 'success');
                                    } else {
                                        alert(res.message);
                                    }
                                    setTimeout(function () {
                                        window.location.href = 'status-list.php';
                                    }, 1200);
                                } else {
                                    if (typeof showtoastt === 'function') {
                                        showtoastt(res.message || 'Save failed', 'error');
                                    } else {
                                        alert(res.message || 'Save failed');
                                    }
                                }
                            },
                            error: function () {
                                if (typeof showtoastt === 'function') {
                                    showtoastt('Server error while saving', 'error');
                                } else {
                                    alert('Server error while saving');
                                }
                            },
                            complete: function () {
                                $btn.prop('disabled', false).html('<i class="ri-save-line"></i> Save Status');
                            }
                        });
                    });
                })();
            </script>
        </div>
    </div>
</body>

</html>