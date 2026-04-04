<?php
require_once 'header.php';
require_once 'config/middleware.php';

$edit_mode = false;
$shift = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $shift_id = $_GET['id'];

    // Fetch shift details
    require_once 'config/db.php';
    $stmt = $pdo->prepare("SELECT * FROM tbl_shifts WHERE id = ?");
    $stmt->execute([$shift_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

<body>
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                    <div class="row" style="padding: 5px 5px;">
                        <div class="col-md-8">
                            <h4 class="mb-0"><?= $edit_mode ? 'Edit Shift' : 'Add New Shift' ?></h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="shift-list.php">
                                <button type="button" class="btn btn-xs rounded-pill btn-secondary">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </button>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="shiftForm">
                            <input type="hidden" name="id" value="<?= $edit_mode ? $shift['id'] : '' ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="shift_name"
                                        value="<?= $edit_mode ? htmlspecialchars($shift['shift_name']) : '' ?>"
                                        required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <option value="active" <?= $edit_mode && $shift['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $edit_mode && $shift['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="start_time"
                                        value="<?= $edit_mode ? $shift['start_time'] : '' ?>" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="end_time"
                                        value="<?= $edit_mode ? $shift['end_time'] : '' ?>" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Duration (Hours)</label>
                                    <input type="number" step="0.1" class="form-control" name="duration_hours"
                                        value="<?= $edit_mode ? $shift['duration_hours'] : '8.0' ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Break Time (Minutes)</label>
                                    <input type="number" class="form-control" name="break_minutes"
                                        value="<?= $edit_mode ? $shift['break_minutes'] : '30' ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Shift Allowance (₹)</label>
                                    <input type="number" step="0.01" class="form-control" name="shift_allowance"
                                        value="<?= $edit_mode ? $shift['shift_allowance'] : '0' ?>">
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line"></i> <?= $edit_mode ? 'Update Shift' : 'Save Shift' ?>
                                </button>
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
                    $('#shiftForm').on('submit', function (e) {
                        e.preventDefault();

                        var formData = $(this).serialize();
                        var url = <?= $edit_mode ? '"api/shift/update.php"' : '"api/shift/create.php"' ?>;

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    alert(response.message);
                                    window.location.href = 'shift-list.php';
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function () {
                                alert('Error processing request');
                            }
                        });
                    });
                });
            </script>