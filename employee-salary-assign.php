<?php
require_once 'header.php';
require_once 'config/middleware.php';

// Check permissions
if (isset($_GET['id'])) {
    // Edit Mode - viewing/changing assignment
    // require_permission('salary_template', 'is_edit');
} else {
    // View Mode - list all assignments
    // require_permission('salary_template', 'is_view');
}
?>

<!-- Vendors CSS -->
<link rel="stylesheet" href="assets/plugins/select2/select2.min.css">

<style>
    .assignment-card {
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 4px;
    }

    .assignment-card.active {
        border-left-color: #28a745;
    }

    .assignment-card.inactive {
        border-left-color: #dc3545;
    }

    .salary-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .summary-box {
        background: white;
        padding: 12px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }

    .summary-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 500;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .summary-value {
        font-size: 18px;
        font-weight: bold;
        color: #007bff;
    }

    .history-table {
        font-size: 13px;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 15px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 5px;
        width: 12px;
        height: 12px;
        background: #007bff;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #007bff;
    }

    .timeline-item.inactive::before {
        background: #dc3545;
        box-shadow: 0 0 0 2px #dc3545;
    }
</style>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'topbar.php'; ?>

        <div class="content-page">
            <div class="" style="padding: 0px 10px;">

                <?php if (!isset($_GET['id'])): ?>
                    <!-- LIST VIEW -->
                    <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                        <div class="row" style="padding: 5px 5px;">
                            <div class="col-md-8">
                                <h4 class="mb-0">Employee Salary Assignments</h4>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button"
                                    onclick="window.location.href='employee-salary-assign.php?action=new&id=0'"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light">
                                    <i class="ri-add-circle-fill"></i> &nbsp;&nbsp;New Assignment
                                </button>
                            </div>
                        </div>

                        <div class="card-body" style="padding: 5px 20px;">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select id="employeeFilter" class="form-select form-select-sm select2">
                                        <option value="">All Employees</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select id="statusFilter" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" id="searchBox" class="form-control form-control-sm"
                                        placeholder="Search...">
                                </div>
                            </div>

                            <!-- Assignments List -->
                            <div id="assignmentsList"></div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- ASSIGNMENT DETAIL VIEW / NEW ASSIGNMENT -->
                    <div class="card" style="margin-bottom:5px; margin-top: 10px;">
                        <div class="row" style="padding: 5px 5px;">
                            <div class="col-md-8">
                                <h4 class="mb-0" id="pageTitle">Loading...</h4>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="employee-salary-assign.php"><button type="button"
                                        class="btn btn-xs rounded-pill btn-secondary waves-effect waves-light">
                                        <i class="ri-arrow-left-line"></i> &nbsp;&nbsp;Back
                                    </button></a>
                            </div>
                        </div>

                        <div class="card-body" style="padding: 20px;">
                            <!-- Employee Info / Selection -->
                            <div class="row mb-3" id="employeeInfoDiv">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Employee</label>
                                        <input type="text" class="form-control" id="employeeName" readonly>
                                        <input type="hidden" id="selectedEmployeeId">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" id="employeeCode" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Selection for New Assignment -->
                            <div class="row mb-3" id="employeeSelectDiv" style="display:none;">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="selectEmployee">Select Employee <span
                                                class="text-danger">*</span></label>
                                        <select id="selectEmployee" class="form-select select2" required>
                                            <option value="">-- Choose Employee --</option>
                                        </select>
                                        <small class="text-danger" id="employeeSelectError" style="display:none;"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Assignment -->
                            <div id="currentAssignmentDiv" style="display:none;">
                                <div class="section-divider"
                                    style="margin-top: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #007bff;">
                                </div>
                                <h5 style="color: #007bff; margin-bottom: 15px;">Current Assignment</h5>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Current Template</label>
                                            <input type="text" class="form-control" id="currentTemplate" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Assigned Date</label>
                                            <input type="text" class="form-control" id="currentAssignedDate" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="salary-summary" id="currentSalarySummary"></div>
                            </div>

                            <!-- New Assignment Form -->
                            <div id="assignmentFormDiv">
                                <div class="section-divider"
                                    style="margin-top: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #007bff;">
                                </div>
                                <h5 style="color: #007bff; margin-bottom: 15px;" id="formTitle">Assign New Template</h5>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="salaryTemplate">Salary Template <span
                                                    class="text-danger">*</span></label>
                                            <select id="salaryTemplate" class="form-select select2" required>
                                                <option value="">-- Select Template --</option>
                                            </select>
                                            <small class="text-danger" id="templateError" style="display:none;"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="assignedDate">Assigned Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" id="assignedDate" class="form-control" required>
                                            <small class="text-danger" id="dateError" style="display:none;"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="effectiveDate">Effective Date</label>
                                            <input type="date" id="effectiveDate" class="form-control">
                                            <small class="form-text text-muted">When template becomes active (if different
                                                from assigned date)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="assignmentStatus">Status</label>
                                            <select id="assignmentStatus" class="form-select">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Preview -->
                                <div id="templatePreview" style="display:none;">
                                    <div class="section-divider"></div>
                                    <h5 style="color: #007bff; margin-bottom: 15px;">Template Preview</h5>
                                    <div class="salary-summary" id="newTemplateSummary"></div>
                                </div>

                                <!-- Form Actions -->
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="saveAssignmentBtn">
                                        <i class="ri-check-line"></i> Save Assignment
                                    </button>
                                    <button type="button" class="btn btn-secondary"
                                        onclick="window.location.href='employee-salary-assign.php'">
                                        <i class="ri-close-line"></i> Cancel
                                    </button>
                                </div>
                            </div>

                            <!-- Assignment History -->
                            <div id="historyDiv" style="display:none;">
                                <div class="section-divider"
                                    style="margin-top: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #007bff;">
                                </div>
                                <h5 style="color: #007bff; margin-bottom: 15px;">Assignment History</h5>
                                <div id="historyTimeline"></div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

            <?php require_once 'footer.php'; ?>
        </div>
    </div>

    <!-- jQuery (Must be before other plugins) -->
    <script src="assets/plugins/jquery/jquery.min.js"></script>

    <!-- Vendors JS -->
    <script src="assets/plugins/select2/select2.min.js"></script>

    <script>
        // Base configuration
        const API_BASE = 'api/employee_salary_assignment/';
        const SALARY_TEMPLATE_API = 'api/salary_template/';

        <?php if (!isset($_GET['id'])): ?>
            // LIST VIEW
            $(function () {
                loadAssignments();
                loadEmployeeFilter();

                // Event listeners
                $('#employeeFilter, #statusFilter').change(loadAssignments);
                $('#searchBox').on('keyup', debounce(loadAssignments, 300));
            });

            function loadAssignments() {
                const filters = {
                    employee_id: $('#employeeFilter').val(),
                    status: $('#statusFilter').val(),
                    search: $('#searchBox').val()
                };

                $.ajax({
                    url: API_BASE + 'read.php',
                    type: 'GET',
                    data: filters,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            renderAssignmentsList(response.data);
                        } else {
                            showToast('Error loading assignments', 'error');
                        }
                    },
                    error: function () {
                        showToast('Failed to load assignments', 'error');
                    }
                });
            }

            function renderAssignmentsList(assignments) {
                let html = '';

                if (assignments.length === 0) {
                    html = '<div class="alert alert-info">No assignments found</div>';
                } else {
                    assignments.forEach(assignment => {
                        html += `
                            <div class="assignment-card ${assignment.status}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 style="margin: 0; color: #333;">${assignment.employee_name}</h6>
                                        <small class="text-muted">ID: ${assignment.employee_code}</small>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>${assignment.template_name}</strong><br>
                                        <small class="text-muted">Assigned: ${assignment.assigned_date}</small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-${assignment.status === 'active' ? 'success' : 'danger'}">
                                            ${assignment.status}
                                        </span>
                                        <br>
                                        <a href="employee-salary-assign.php?id=${assignment.id}" class="btn btn-sm btn-primary mt-2">
                                            <i class="ri-edit-line"></i> View/Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                $('#assignmentsList').html(html);
            }

            function loadEmployeeFilter() {
                $.ajax({
                    url: 'api/employee/get_employees.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            let options = '<option value="">All Employees</option>';
                            response.data.forEach(emp => {
                                options += `<option value="${emp.id}">${emp.name} (${emp.code})</option>`;
                            });
                            $('#employeeFilter').html(options).select2();
                        }
                    }
                });
            }

        <?php else: ?>
            // DETAIL VIEW / NEW ASSIGNMENT
            const assignmentId = <?php echo isset($_GET['id']) && intval($_GET['id']) !== 0 ? intval($_GET['id']) : 'null'; ?>;
            const action = '<?php echo isset($_GET['action']) ? $_GET['action'] : 'view'; ?>';
            let currentEmployeeId = null;

            $(function () {
                if (action === 'new') {
                    // New assignment mode
                    $('#pageTitle').text('Create New Salary Assignment');
                    $('#employeeInfoDiv').hide();
                    $('#employeeSelectDiv').show();
                    $('#assignedDate').val(new Date().toISOString().split('T')[0]);
                    $('#assignmentFormDiv').show();
                    loadEmployeesForSelection();
                    loadSalaryTemplates();
                } else {
                    // Edit existing assignment
                    $('#employeeSelectDiv').hide();
                    $('#employeeInfoDiv').show();
                    loadAssignmentDetails();
                }

                // Event listeners
                $('#salaryTemplate').change(loadTemplatePreview);
                $('#selectEmployee').change(onEmployeeSelected);
                $('#saveAssignmentBtn').click(saveAssignment);
            });

            function loadEmployeesForSelection() {
                $.ajax({
                    url: 'api/employee/get_employees.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            let options = '<option value="">-- Choose Employee --</option>';
                            response.data.forEach(emp => {
                                options += `<option value="${emp.id}" data-name="${emp.name}" data-code="${emp.code}">${emp.name} (${emp.code})</option>`;
                            });
                            $('#selectEmployee').html(options).select2();
                        }
                    },
                    error: function () {
                        showToast('Failed to load employees', 'error');
                    }
                });
            }

            function onEmployeeSelected() {
                const selectedOption = $('#selectEmployee').find(':selected');
                const employeeId = selectedOption.val();

                if (employeeId) {
                    currentEmployeeId = employeeId;
                    const employeeName = selectedOption.data('name');
                    const employeeCode = selectedOption.data('code');

                    $('#selectedEmployeeId').val(employeeId);
                    $('#pageTitle').text('Create Assignment for ' + employeeName);
                    $('#employeeSelectError').hide();
                }
            }

            function loadAssignmentDetails() {
                $.ajax({
                    url: API_BASE + 'read_single.php?id=' + assignmentId,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            const data = response.data;

                            // Update page title and employee info
                            $('#pageTitle').text(data.employee_name);
                            $('#employeeName').val(data.employee_name);
                            $('#employeeCode').val(data.employee_code);
                            $('#selectedEmployeeId').val(data.employee_id);

                            // Load current assignment
                            $('#currentTemplate').val(data.template_name);
                            $('#currentAssignedDate').val(data.assigned_date);
                            $('#currentAssignmentDiv').show();

                            // Load salary summary
                            loadSalarySummary(data.salary_template_id, '#currentSalarySummary');

                            // Load salary templates for new assignment
                            loadSalaryTemplates();

                            // Load history
                            loadAssignmentHistory(data.employee_id);
                        }
                    }
                });
            }

            function loadSalaryTemplates() {
                $.ajax({
                    url: SALARY_TEMPLATE_API + 'read.php?status=active&length=1000',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            let options = '<option value="">-- Select Template --</option>';
                            response.data.forEach(template => {
                                options += `<option value="${template.id}">${template.template_name}</option>`;
                            });
                            $('#salaryTemplate').html(options).select2();
                        }
                    }
                });
            }

            function loadTemplatePreview() {
                const templateId = $('#salaryTemplate').val();
                if (templateId) {
                    loadSalarySummary(templateId, '#newTemplateSummary');
                    $('#templatePreview').show();
                } else {
                    $('#templatePreview').hide();
                }
            }

            function loadSalarySummary(templateId, selector) {
                $.ajax({
                    url: SALARY_TEMPLATE_API + 'read_single.php?id=' + templateId,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            const data = response.data;

                            const gross = parseFloat(data.basic_salary) + parseFloat(data.hra) +
                                parseFloat(data.da) + parseFloat(data.medical_allowance) +
                                parseFloat(data.conveyance) + parseFloat(data.other_allowances);

                            const deductions = parseFloat(data.pf_deduction) + parseFloat(data.insurance_deduction) +
                                parseFloat(data.tax_deduction) + parseFloat(data.other_deductions);

                            const net = gross - deductions;

                            const html = `
                                <div class="summary-box">
                                    <div class="summary-label">Basic Salary</div>
                                    <div class="summary-value">₹${parseFloat(data.basic_salary).toLocaleString('en-IN', { maximumFractionDigits: 2 })}</div>
                                </div>
                                <div class="summary-box">
                                    <div class="summary-label">Gross Salary</div>
                                    <div class="summary-value">₹${gross.toLocaleString('en-IN', { maximumFractionDigits: 2 })}</div>
                                </div>
                                <div class="summary-box">
                                    <div class="summary-label">Deductions</div>
                                    <div class="summary-value">₹${deductions.toLocaleString('en-IN', { maximumFractionDigits: 2 })}</div>
                                </div>
                                <div class="summary-box">
                                    <div class="summary-label">Net Salary</div>
                                    <div class="summary-value" style="color: #28a745;">₹${net.toLocaleString('en-IN', { maximumFractionDigits: 2 })}</div>
                                </div>
                            `;

                            $(selector).html(html);
                        }
                    }
                });
            }

            function loadAssignmentHistory(employeeId) {
                $.ajax({
                    url: API_BASE + 'history.php?employee_id=' + employeeId,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success' && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(record => {
                                html += `
                                    <div class="timeline-item ${record.status}">
                                        <div>
                                            <strong>${record.template_name}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Assigned: ${record.assigned_date}
                                                ${record.effective_date && record.effective_date !== record.assigned_date ? ` | Effective: ${record.effective_date}` : ''}
                                            </small>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#historyTimeline').html(html);
                            $('#historyDiv').show();
                        }
                    }
                });
            }

            function saveAssignment() {
                // Validation
                const templateId = $('#salaryTemplate').val();
                const assignedDate = $('#assignedDate').val();

                // Clear previous errors
                $('#templateError, #dateError, #employeeSelectError').hide();

                if (action === 'new') {
                    const employeeId = $('#selectEmployee').val();
                    if (!employeeId) {
                        $('#employeeSelectError').text('Please select an employee').show();
                        return;
                    }
                    if (!templateId) {
                        $('#templateError').text('Please select a salary template').show();
                        return;
                    }
                    if (!assignedDate) {
                        $('#dateError').text('Please select assigned date').show();
                        return;
                    }
                } else {
                    if (!templateId) {
                        $('#templateError').text('Please select a salary template').show();
                        return;
                    }
                    if (!assignedDate) {
                        $('#dateError').text('Please select assigned date').show();
                        return;
                    }
                }

                const employeeId = action === 'new' ?
                    $('#selectEmployee').val() :
                    $('#selectedEmployeeId').val();

                const data = {
                    employee_id: employeeId,
                    salary_template_id: templateId,
                    assigned_date: assignedDate,
                    effective_date: $('#effectiveDate').val() || assignedDate,
                    status: $('#assignmentStatus').val()
                };

                const url = action === 'new' ?
                    API_BASE + 'create.php' :
                    API_BASE + 'update.php?id=' + assignmentId;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            showToast('Assignment saved successfully', 'success');
                            setTimeout(() => {
                                window.location.href = 'employee-salary-assign.php';
                            }, 1500);
                        } else {
                            showToast(response.message || 'Failed to save assignment', 'error');
                        }
                    },
                    error: function (xhr) {
                        let errorMsg = 'Error saving assignment';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showToast(errorMsg, 'error');
                    }
                });
            }

        <?php endif; ?>

        // Utility functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showToast(message, type = 'info') {
            // Create and show toast notification
            const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
            const toastHtml = `
                <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            // Create a container if it doesn't exist
            if ($('#toastContainer').length === 0) {
                $('body').append('<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
            }

            const toastElement = $(toastHtml);
            $('#toastContainer').append(toastElement);

            // Show toast
            const bsToast = new bootstrap.Toast(toastElement[0]);
            bsToast.show();

            // Remove after shown
            toastElement.on('hidden.bs.toast', function () {
                $(this).remove();
            });

            // Also log to console for debugging
            console.log(type + ': ' + message);
        }
    </script>
</body>

</html>