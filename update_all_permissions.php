<?php
require_once 'config/config.php';

echo "<h2>Complete Permission System Update</h2>";

// First, let's extract all permission prefixes used in the navbar
$navbar_permissions = [
    // Dashboard
    'dashboard',
    
    // Master Data
    'company', 'company_list', 'branch', 'status', 'custom_description', 'client', 'consignor', 'consignee', 'pickuppoint', 'coloader',
    
    // Booking
    'shipment-create', 'shipment-bulk', 'shipment-list', 'shipment-status-update', 'tracking', 'rate-calculator', 'ndr-shipments', 'ndr_status', 'pickup_request',
    
    // Delhivery specific
    'delhivery-b2c-shipment-create', 'delhivery-b2c-shipment-list', 'delhivery-b2c-pickuppoint-list', 'delhivery-b2c-pickup-request-list', 'delhivery-b2c-ndr-shipments',
    
    // Shiprocket specific
    'shiprocke-lists', 'shiprocket-bulk-upload', 'shiprocket-manifest-list',
    
    // SHA & WHMS
    'whms_booking', 'whms_shipment', 'whms_pickup', 'whms_tag', 'whms_manifest', 'whms_runsheet', 'whms_pod', 'whms_tracking',
    
    // Tickets
    'ticket', 'tickets',
    
    // Serial Allocation
    'serial_allocation',
    
    // HR & Payroll
    'employee', 'department', 'designation', 'salary_template', 'client_based_user', 'shift', 'attendance', 'payroll',
    
    // Reports & Tools
    'mis-reports', 'account-reports', 'status-reports', 'shipment-reports', 'attendance-reports', 'payroll-reports', 'whatsapp', 'mail',
    
    // Settings
    'courier_partner', 'apis', 'couriers', 'setting-role', 'setting-permission'
];

// Update permission modules first
echo "<h3>1. Updating Permission Modules</h3>";

$modules = [
    [1, 'Dashboard', 1],
    [2, 'Master Data', 2],
    [3, 'Booking', 3],
    [4, 'Delhivery', 4],
    [5, 'Shiprocket', 5],
    [6, 'SHA & WHMS', 6],
    [7, 'Tickets', 7],
    [8, 'Serial Allocation', 8],
    [9, 'HR & Payroll', 9],
    [10, 'Reports & Tools', 10],
    [11, 'Settings', 11],
    [12, 'Client Based', 12]
];

foreach ($modules as $module) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `permission_modules` (`id`, `name`, `sorted`) VALUES (?, ?, ?)");
        $stmt->execute($module);
        echo "<p style='color: green;'>Module '{$module[1]}' updated/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error updating module '{$module[1]}': " . $e->getMessage() . "</p>";
    }
}

// Insert all permissions
echo "<h3>2. Inserting All Permissions</h3>";

$permissions = [
    // Dashboard (Module ID: 1)
    [1, 1, 'Dashboard', 'dashboard'],
    
    // Master Data (Module ID: 2)
    [2, 2, 'Company', 'company'],
    [3, 2, 'Company List', 'company_list'],
    [4, 2, 'Branch', 'branch'],
    [5, 2, 'Status Description', 'status'],
    [6, 2, 'Custom Description', 'custom_description'],
    [7, 2, 'Client', 'client'],
    [8, 2, 'Consignor', 'consignor'],
    [9, 2, 'Consignee', 'consignee'],
    [10, 2, 'Pickup Points', 'pickuppoint'],
    [11, 2, 'Coloader', 'coloader'],
    
    // Booking (Module ID: 3)
    [12, 3, 'Shipment Create', 'shipment-create'],
    [13, 3, 'Shipment Bulk', 'shipment-bulk'],
    [14, 3, 'Shipment List', 'shipment-list'],
    [15, 3, 'Shipment Status Update', 'shipment-status-update'],
    [16, 3, 'Tracking', 'tracking'],
    [17, 3, 'Rate Calculator', 'rate-calculator'],
    [18, 3, 'NDR Shipments', 'ndr-shipments'],
    [19, 3, 'NDR Status', 'ndr_status'],
    [20, 3, 'Pickup Request', 'pickup_request'],
    
    // Delhivery (Module ID: 4)
    [21, 4, 'Delhivery B2C Shipment Create', 'delhivery-b2c-shipment-create'],
    [22, 4, 'Delhivery B2C Shipment List', 'delhivery-b2c-shipment-list'],
    [23, 4, 'Delhivery B2C Pickup Point List', 'delhivery-b2c-pickuppoint-list'],
    [24, 4, 'Delhivery B2C Pickup Request List', 'delhivery-b2c-pickup-request-list'],
    [25, 4, 'Delhivery B2C NDR Shipments', 'delhivery-b2c-ndr-shipments'],
    
    // Shiprocket (Module ID: 5)
    [26, 5, 'Shiprocket Lists', 'shiprocke-lists'],
    [27, 5, 'Shiprocket Bulk Upload', 'shiprocket-bulk-upload'],
    [28, 5, 'Shiprocket Manifest List', 'shiprocket-manifest-list'],
    
    // SHA & WHMS (Module ID: 6)
    [29, 6, 'WHMS Booking', 'whms_booking'],
    [30, 6, 'WHMS Shipment', 'whms_shipment'],
    [31, 6, 'WHMS Pickup', 'whms_pickup'],
    [32, 6, 'WHMS Tag', 'whms_tag'],
    [33, 6, 'WHMS Manifest', 'whms_manifest'],
    [34, 6, 'WHMS Runsheet', 'whms_runsheet'],
    [35, 6, 'WHMS POD', 'whms_pod'],
    [36, 6, 'WHMS Tracking', 'whms_tracking'],
    
    // Tickets (Module ID: 7)
    [37, 7, 'Tickets', 'tickets'],
    [38, 7, 'Ticket', 'ticket'],
    
    // Serial Allocation (Module ID: 8)
    [39, 8, 'Serial Allocation', 'serial_allocation'],
    
    // HR & Payroll (Module ID: 9)
    [40, 9, 'Employee', 'employee'],
    [41, 9, 'Department', 'department'],
    [42, 9, 'Designation', 'designation'],
    [43, 9, 'Salary Template', 'salary_template'],
    [44, 9, 'Client Based User', 'client_based_user'],
    [45, 9, 'Shift', 'shift'],
    [46, 9, 'Attendance', 'attendance'],
    [47, 9, 'Payroll', 'payroll'],
    
    // Reports & Tools (Module ID: 10)
    [48, 10, 'MIS Reports', 'mis-reports'],
    [49, 10, 'Account Reports', 'account-reports'],
    [50, 10, 'Status Reports', 'status-reports'],
    [51, 10, 'Shipment Reports', 'shipment-reports'],
    [52, 10, 'Attendance Reports', 'attendance-reports'],
    [53, 10, 'Payroll Reports', 'payroll-reports'],
    [54, 10, 'WhatsApp', 'whatsapp'],
    [55, 10, 'Mail', 'mail'],
    
    // Settings (Module ID: 11)
    [56, 11, 'Courier Partner', 'courier_partner'],
    [57, 11, 'APIs', 'apis'],
    [58, 11, 'Couriers', 'couriers'],
    [59, 11, 'Role Settings', 'setting-role'],
    [60, 11, 'Permission Settings', 'setting-permission'],
    
    // Client Based (Module ID: 12)
    [61, 12, 'Client Based User', 'client_based_user']
];

foreach ($permissions as $permission) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `permission` (`id`, `module_id`, `name`, `prefix`) VALUES (?, ?, ?, ?)");
        $stmt->execute($permission);
        echo "<p style='color: green;'>Permission '{$permission[2]}' inserted/verified</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error inserting permission '{$permission[2]}': " . $e->getMessage() . "</p>";
    }
}

// Set up default permissions for Administrator (full access)
echo "<h3>3. Setting Up Administrator Permissions</h3>";
try {
    $stmt = $pdo->query("SELECT id FROM permission");
    $permission_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    foreach ($permission_ids as $permission_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `staff_privileges` (`role_id`, `permission_id`, `is_view`, `is_add`, `is_edit`, `is_delete`) VALUES (?, ?, 1, 1, 1, 1)");
        $stmt->execute([1, $permission_id]);
    }
    echo "<p style='color: green;'>Administrator full permissions set up successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error setting up administrator permissions: " . $e->getMessage() . "</p>";
}

// Show current database state
echo "<h3>4. Current Database State</h3>";
try {
    echo "<h4>Permission Modules:</h4>";
    $stmt = $pdo->query("SELECT id, name, sorted FROM permission_modules ORDER BY sorted");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Sort Order</th></tr>";
    foreach ($modules as $module) {
        echo "<tr><td>{$module['id']}</td><td>" . htmlspecialchars($module['name']) . "</td><td>{$module['sorted']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h4>All Permissions by Module:</h4>";
    $stmt = $pdo->query("SELECT p.id, p.name, p.prefix, pm.name as module FROM permission p JOIN permission_modules pm ON p.module_id = pm.id ORDER BY pm.sorted, p.id");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Prefix</th><th>Module</th></tr>";
    foreach ($permissions as $permission) {
        echo "<tr><td>{$permission['id']}</td><td>" . htmlspecialchars($permission['name']) . "</td><td>" . htmlspecialchars($permission['prefix']) . "</td><td>" . htmlspecialchars($permission['module']) . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching current data: " . $e->getMessage() . "</p>";
}

echo "<h2>Update Complete!</h2>";
echo "<p><a href='setting-role.php'>Manage Roles</a></p>";
echo "<p><a href='setting-permission.php?id=1'>Manage Administrator Permissions</a></p>";
echo "<p><a href='test_permissions.php'>Test Permission System</a></p>";
?>
