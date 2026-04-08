<?php
require_once 'config/config.php';

echo "<h2>Resetting and Updating Navbar Permission Structure</h2>";

// Define navbar structure matching horizontal-menu.php
$navbar_structure = [
    ['module_name' => 'Master Data', 'sorted' => 1, 'permissions' => [
        ['name' => 'Company', 'prefix' => 'company'],
        ['name' => 'Company List', 'prefix' => 'company_list'],
        ['name' => 'Branch', 'prefix' => 'branch'],
        ['name' => 'Status', 'prefix' => 'status'],
        ['name' => 'Custom Description', 'prefix' => 'custom_description'],
        ['name' => 'Client', 'prefix' => 'client'],
        ['name' => 'Consignor', 'prefix' => 'consignor'],
        ['name' => 'Consignee', 'prefix' => 'consignee'],
        ['name' => 'Pickup Points', 'prefix' => 'pickuppoint'],
        ['name' => 'Coloader', 'prefix' => 'coloader'],
    ]],
    ['module_name' => 'Booking', 'sorted' => 2, 'permissions' => [
        ['name' => 'Create Shipment', 'prefix' => 'shipment-create'],
        ['name' => 'Bulk Upload', 'prefix' => 'shipment-bulk'],
        ['name' => 'Shipment List', 'prefix' => 'shipment-list'],
        ['name' => 'Shipment Status Update', 'prefix' => 'shipment-status-update'],
        ['name' => 'Tracking', 'prefix' => 'tracking'],
        ['name' => 'Rate Calculator', 'prefix' => 'rate-calculator'],
        ['name' => 'NDR Shipments', 'prefix' => 'ndr-shipments'],
        ['name' => 'NDR Status', 'prefix' => 'ndr_status'],
        ['name' => 'Pickup Request', 'prefix' => 'pickup_request'],
    ]],
    ['module_name' => 'Delhivery', 'sorted' => 3, 'permissions' => [
        ['name' => 'Delhivery B2C Booking', 'prefix' => 'delhivery-b2c-booking'],
        ['name' => 'Delhivery B2C List', 'prefix' => 'delhivery-b2c-list'],
        ['name' => 'Delhivery Bulk Upload', 'prefix' => 'delhivery-bulk'],
        ['name' => 'Delhivery Pickup Point', 'prefix' => 'delhivery-pickuppoint'],
        ['name' => 'Delhivery Pickup Request', 'prefix' => 'delhivery-pickup-request'],
        ['name' => 'Delhivery NDR', 'prefix' => 'delhivery-ndr'],
    ]],
    ['module_name' => 'Shiprocket', 'sorted' => 4, 'permissions' => [
        ['name' => 'Shiprocket Booking List', 'prefix' => 'shiprocket-list'],
        ['name' => 'Shiprocket Bulk Upload', 'prefix' => 'shiprocket-bulk'],
        ['name' => 'Shiprocket Manifest', 'prefix' => 'shiprocket-manifest'],
        ['name' => 'Shiprocket Pickup', 'prefix' => 'shiprocket-pickup'],
    ]],
    ['module_name' => 'SHA & WHMS', 'sorted' => 5, 'permissions' => [
        ['name' => 'WHMS Booking', 'prefix' => 'whms_booking'],
        ['name' => 'WHMS Shipment', 'prefix' => 'whms_shipment'],
        ['name' => 'WHMS Pickup', 'prefix' => 'whms_pickup'],
        ['name' => 'WHMS Tag', 'prefix' => 'whms_tag'],
        ['name' => 'WHMS Manifest', 'prefix' => 'whms_manifest'],
        ['name' => 'WHMS Runsheet', 'prefix' => 'whms_runsheet'],
        ['name' => 'WHMS POD', 'prefix' => 'whms_pod'],
        ['name' => 'WHMS Tracking', 'prefix' => 'whms_tracking'],
    ]],
    ['module_name' => 'Tickets', 'sorted' => 6, 'permissions' => [
        ['name' => 'Tickets', 'prefix' => 'ticket'],
    ]],
    ['module_name' => 'Serial Allocation', 'sorted' => 7, 'permissions' => [
        ['name' => 'Serial Allocation', 'prefix' => 'serial_allocation'],
    ]],
    ['module_name' => 'HR & Payroll', 'sorted' => 8, 'permissions' => [
        ['name' => 'Employee', 'prefix' => 'employee'],
        ['name' => 'Department', 'prefix' => 'department'],
        ['name' => 'Designation', 'prefix' => 'designation'],
        ['name' => 'Salary Template', 'prefix' => 'salary_template'],
        ['name' => 'Client Based User', 'prefix' => 'client_based_user'],
    ]],
    ['module_name' => 'Reports & Tools', 'sorted' => 9, 'permissions' => [
        ['name' => 'Setting Role', 'prefix' => 'setting-role'],
        ['name' => 'MIS Reports', 'prefix' => 'mis-reports'],
        ['name' => 'Account Reports', 'prefix' => 'account-reports'],
        ['name' => 'Status Reports', 'prefix' => 'status-reports'],
        ['name' => 'Shipment Reports', 'prefix' => 'shipment-reports'],
        ['name' => 'Attendance Reports', 'prefix' => 'attendance-reports'],
        ['name' => 'Payroll Reports', 'prefix' => 'payroll-reports'],
        ['name' => 'WhatsApp Settings', 'prefix' => 'whatsapp-settings'],
        ['name' => 'Mail Settings', 'prefix' => 'mail-settings'],
    ]],
    ['module_name' => 'Settings', 'sorted' => 10, 'permissions' => [
        ['name' => 'APIs', 'prefix' => 'apis'],
        ['name' => 'Couriers', 'prefix' => 'couriers'],
        ['name' => 'Courier Partner', 'prefix' => 'courier_partner'],
        ['name' => 'About', 'prefix' => 'about'],
        ['name' => 'Enable Shiprocket', 'prefix' => 'enable-shiprocket'],
    ]],
];

try {
    $pdo->beginTransaction();
    
    // Get current max IDs
    $max_module_id = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM permission_modules")->fetchColumn();
    $max_perm_id = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM permission")->fetchColumn();
    
    echo "<p>Current max module ID: $max_module_id</p>";
    echo "<p>Current max permission ID: $max_perm_id</p>";
    
    $module_id_start = $max_module_id + 1;
    $permission_id_start = $max_perm_id + 1;
    
    foreach ($navbar_structure as $module) {
        // Check if module already exists
        $check = $pdo->prepare("SELECT id FROM permission_modules WHERE name = ?");
        $check->execute([$module['module_name']]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $module_id = $existing['id'];
            echo "<p style='color: orange;'>Module: {$module['module_name']} - Already exists (ID: $module_id)</p>";
        } else {
            // Insert new module
            $module_prefix = strtolower(str_replace([' ', '&'], ['_', 'and'], $module['module_name']));
            $stmt = $pdo->prepare("INSERT INTO permission_modules (id, name, prefix, sorted, system, in_module) 
                                   VALUES (:id, :name, :prefix, :sorted, 0, 1)");
            $stmt->execute([
                ':id' => $module_id_start,
                ':name' => $module['module_name'],
                ':prefix' => $module_prefix,
                ':sorted' => $module['sorted']
            ]);
            $module_id = $module_id_start;
            $module_id_start++;
            echo "<p style='color: green;'>Module: {$module['module_name']} - Added (ID: $module_id)</p>";
        }
        
        // Insert permissions for this module
        foreach ($module['permissions'] as $perm) {
            // Check if permission already exists
            $check = $pdo->prepare("SELECT id FROM permission WHERE prefix = ?");
            $check->execute([$perm['prefix']]);
            $existing_perm = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_perm) {
                // Update existing permission
                $stmt = $pdo->prepare("UPDATE permission SET name = ?, module_id = ? WHERE id = ?");
                $stmt->execute([$perm['name'], $module_id, $existing_perm['id']]);
                echo "<p style='color: blue;'>  - Updated: {$perm['name']} ({$perm['prefix']})</p>";
            } else {
                // Insert new permission
                $stmt = $pdo->prepare("INSERT INTO permission (id, module_id, name, prefix, show_view, show_add, show_edit, show_delete) 
                                       VALUES (:id, :module_id, :name, :prefix, 1, 1, 1, 1)");
                $stmt->execute([
                    ':id' => $permission_id_start,
                    ':module_id' => $module_id,
                    ':name' => $perm['name'],
                    ':prefix' => $perm['prefix']
                ]);
                
                // Grant full access to Administrator (role_id = 1)
                $stmt = $pdo->prepare("INSERT INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete) 
                                       VALUES (1, :perm_id, 1, 1, 1, 1)
                                       ON DUPLICATE KEY UPDATE 
                                       is_view = 1, is_add = 1, is_edit = 1, is_delete = 1");
                $stmt->execute([':perm_id' => $permission_id_start]);
                
                echo "<p style='color: blue;'>  - Added: {$perm['name']} ({$perm['prefix']})</p>";
                $permission_id_start++;
            }
        }
    }
    
    $pdo->commit();
    echo "<h2 style='color: green;'>Update Complete!</h2>";
    echo "<p><a href='setting-role.php'>Go to Role Management</a></p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
}
?>
