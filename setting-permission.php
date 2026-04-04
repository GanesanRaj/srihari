<?php
require_once 'header.php';
require_once 'config/middleware.php'; // Ensure database connection is included
require_permission('setting-role', 'is_edit');

// Function to get modules
function getModulesList() {
    global $pdo;
    
    if (!$pdo) {
        error_log("Database connection is missing.");
        return []; // Return an empty array to prevent further errors
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM permission_modules ORDER BY sorted ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching modules: " . $e->getMessage());
    }
}

$role_id = intval($_GET['id'] ?? 0);

if ($role_id <= 0) {
    $_SESSION['access_denied_message'] = 'Please select a role.';
    header('Location: setting-role.php');
    exit;
}

// Fetch Role details
try {
    $rStmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $rStmt->execute([$role_id]);
    $roleRow = $rStmt->fetch();
    $roleName = $roleRow ? $roleRow['name'] : 'Unknown';
} catch (Exception $e) {
    $roleName = "Unknown";
}

// Saving permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    if (!$pdo) {
        die("Database connection is missing.");
    }

    try {
        $role_id = intval($_POST['role_id']);
        $privileges = $_POST['privileges'] ?? [];

        // Fetch existing privileges for comparison
        $stmt = $pdo->prepare("SELECT permission_id FROM staff_privileges WHERE role_id = ?");
        $stmt->execute([$role_id]);
        $existing_permissions = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission_id');

        foreach ($privileges as $permission_id => $value) {
            $permission_id = intval($permission_id);
            
            $is_add = isset($value['add']) && !empty($value['add']) ? 1 : 0;
            $is_edit = isset($value['edit']) && !empty($value['edit']) ? 1 : 0;
            $is_view = isset($value['view']) && !empty($value['view']) ? 1 : 0;
            $is_delete = isset($value['delete']) && !empty($value['delete']) ? 1 : 0;

            if (in_array($permission_id, $existing_permissions)) {
                // Update existing record
                $update_query = "UPDATE staff_privileges 
                                 SET is_add = ?, is_edit = ?, is_view = ?, is_delete = ? 
                                 WHERE role_id = ? AND permission_id = ?";
                $stmt = $pdo->prepare($update_query);
                $stmt->execute([$is_add, $is_edit, $is_view, $is_delete, $role_id, $permission_id]);
            } else {
                // Insert new record
                $insert_query = "INSERT INTO staff_privileges 
                                 (role_id, permission_id, is_add, is_edit, is_view, is_delete) 
                                 VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($insert_query);
                $stmt->execute([$role_id, $permission_id, $is_add, $is_edit, $is_view, $is_delete]);
            }
        }

        echo "<script>alert('Information updated successfully'); window.location.href='setting-permission.php?id=$role_id';</script>";
    } catch (PDOException $e) {
        die("Error updating permissions: " . $e->getMessage());
    }
}

$modules = getModulesList();
?>

<!-- Vendors CSS -->
<style>
    .table-condensed td, .table-condensed th {
        padding: 8px 10px !important;
    }
    .check-cell {
        text-align: center;
        vertical-align: middle;
    }
    .check-cell input[type="checkbox"] {
        transform: scale(1.2);
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
                            <h4 class="mb-1 ms-2 mt-2">Role Permissions: <span class="text-primary"><?php echo htmlspecialchars($roleName); ?></span></h4>
                        </div>
                        <div class="col-md-4 text-end mt-2 me-n2">
                            <a href="setting-role.php"><button type="button"
                                    class="btn btn-xs rounded-pill btn-primary waves-effect waves-light mt-1 me-2">
                                    <i class="ri-arrow-left-circle-fill"></i> &nbsp;&nbsp;Back to Roles
                                </button></a>
                        </div>
                    </div>

                    <div class="card-body" style="padding: 5px 20px;">
                        <form action="" method="post">
                            <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-condensed mt-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Feature</th>
                                            <th class="text-nowrap" style="max-width:120px;">Prefix <small class="text-muted">(menu check)</small></th>
                                            <th class="check-cell"><input type="checkbox" id="all_view"> <label for="all_view" class="ms-1 cursor-pointer">View</label></th>
                                            <th class="check-cell"><input type="checkbox" id="all_add"> <label for="all_add" class="ms-1 cursor-pointer">Add</label></th>
                                            <th class="check-cell"><input type="checkbox" id="all_edit"> <label for="all_edit" class="ms-1 cursor-pointer">Edit</label></th>
                                            <th class="check-cell"><input type="checkbox" id="all_delete"> <label for="all_delete" class="ms-1 cursor-pointer">Delete</label></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modules as $module): ?>
                                            <tr class="table-active">
                                                <th colspan="6" class="py-2">
                                                    <strong><?php echo htmlspecialchars($module['name']); ?></strong>
                                                </th>
                                            </tr>
                                            <?php
                                            if (!$pdo) {
                                                continue;
                                            }

                                            $stmt = $pdo->prepare("SELECT p.*, 
                                                    COALESCE(sp.is_add, 0) AS is_add, 
                                                    COALESCE(sp.is_edit, 0) AS is_edit, 
                                                    COALESCE(sp.is_view, 0) AS is_view, 
                                                    COALESCE(sp.is_delete, 0) AS is_delete
                                                 FROM permission p
                                                 LEFT JOIN staff_privileges sp 
                                                 ON p.id = sp.permission_id AND sp.role_id = ?
                                                 WHERE p.module_id = ?
                                                 ORDER BY CASE WHEN p.prefix = 'pickuppoint' THEN 1 WHEN p.prefix = 'coloader' THEN 2 ELSE 0 END, p.id");
                                            $stmt->execute([$role_id, $module['id']]);

                                            while ($permission = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td class="ps-3"><?php echo htmlspecialchars($permission['name']); ?></td>
                                                    <td class="text-muted small" style="max-width:120px;" title="Used by menu: can_view(this)"><?php echo htmlspecialchars($permission['prefix'] ?? '—'); ?></td>
                                                    <td class="check-cell">  
                                                        <input type="hidden" name="privileges[<?php echo $permission['id']; ?>][view]" value="0">
                                                        <?php if ($permission['show_view']): ?>
                                                            <input type="checkbox" class="view" name="privileges[<?php echo $permission['id']; ?>][view]" value="1" <?php echo ($permission['is_view'] ? 'checked' : ''); ?>>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="check-cell">
                                                        <input type="hidden" name="privileges[<?php echo $permission['id']; ?>][add]" value="0">
                                                        <?php if ($permission['show_add']): ?>
                                                            <input type="checkbox" class="add" name="privileges[<?php echo $permission['id']; ?>][add]" value="1" <?php echo ($permission['is_add'] ? 'checked' : ''); ?>>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="check-cell">
                                                        <input type="hidden" name="privileges[<?php echo $permission['id']; ?>][edit]" value="0">
                                                        <?php if ($permission['show_edit']): ?>
                                                            <input type="checkbox" class="edit" name="privileges[<?php echo $permission['id']; ?>][edit]" value="1" <?php echo ($permission['is_edit'] ? 'checked' : ''); ?>>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="check-cell">
                                                        <input type="hidden" name="privileges[<?php echo $permission['id']; ?>][delete]" value="0">
                                                        <?php if ($permission['show_delete']): ?>
                                                            <input type="checkbox" class="delete" name="privileges[<?php echo $permission['id']; ?>][delete]" value="1" <?php echo ($permission['is_delete'] ? 'checked' : ''); ?>>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-3 mb-2">
                                <div class="col-12 text-center">
                                    <button type="submit" name="send" class="btn btn-primary rounded-pill">
                                        <i class="ri-save-line"></i> Update Permissions
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <?php require_once 'footer.php'; ?>

            <script src="assets/plugins/jquery/jquery.min.js"></script>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    function toggleColumn(className, checkboxId) {
                        document.getElementById(checkboxId).addEventListener('change', function() {
                            document.querySelectorAll('.' + className).forEach(cb => cb.checked = this.checked);
                        });
                    }
                    toggleColumn('view', 'all_view');
                    toggleColumn('add', 'all_add');
                    toggleColumn('edit', 'all_edit');
                    toggleColumn('delete', 'all_delete');
                });
            </script>
        </div>
    </div>
</body>

</html>
