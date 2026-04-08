<?php
require_once 'config/config.php';

echo "<h2>Fix Permission Prefix Mapping</h2>";

$canonicalModules = [
    ['name' => 'Master Data', 'sorted' => 1, 'permissions' => [
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
    ['name' => 'Booking', 'sorted' => 2, 'permissions' => [
        ['name' => 'Create Shipment', 'prefix' => 'shipment-create'],
        ['name' => 'Bulk Upload', 'prefix' => 'shipment-bulk'],
        ['name' => 'Shipment List', 'prefix' => 'shipment-list'],
        ['name' => 'Shipment Status Update', 'prefix' => 'shipment-status-update'],
        ['name' => 'Tracking', 'prefix' => 'tracking'],
        ['name' => 'Rate Calculator', 'prefix' => 'rate-calculator'],
        ['name' => 'NDR Shipments', 'prefix' => 'ndr-shipments'],
        ['name' => 'NDR Status', 'prefix' => 'ndr_status'],
        ['name' => 'Pickup Point', 'prefix' => 'booking-pickuppoint'],
        ['name' => 'Pickup Request', 'prefix' => 'pickup_request'],
    ]],
    ['name' => 'Delhivery', 'sorted' => 3, 'permissions' => [
        ['name' => 'Delhivery B2C Booking', 'prefix' => 'delhivery-b2c-booking'],
        ['name' => 'Delhivery B2C List', 'prefix' => 'delhivery-b2c-list'],
        ['name' => 'Delhivery Bulk Upload', 'prefix' => 'delhivery-bulk'],
        ['name' => 'Delhivery Pickup Point', 'prefix' => 'delhivery-pickuppoint'],
        ['name' => 'Delhivery Pickup Request', 'prefix' => 'delhivery-pickup-request'],
        ['name' => 'Delhivery NDR', 'prefix' => 'delhivery-ndr'],
    ]],
    ['name' => 'Shiprocket', 'sorted' => 4, 'permissions' => [
        ['name' => 'Shiprocket Booking List', 'prefix' => 'shiprocket-list'],
        ['name' => 'Shiprocket Bulk Upload', 'prefix' => 'shiprocket-bulk'],
        ['name' => 'Shiprocket Manifest', 'prefix' => 'shiprocket-manifest'],
        ['name' => 'Shiprocket Pickup', 'prefix' => 'shiprocket-pickup'],
    ]],
    ['name' => 'SHA & WHMS', 'sorted' => 5, 'permissions' => [
        ['name' => 'WHMS Booking', 'prefix' => 'whms_booking'],
        ['name' => 'WHMS Shipment', 'prefix' => 'whms_shipment'],
        ['name' => 'WHMS Pickup', 'prefix' => 'whms_pickup'],
        ['name' => 'WHMS Tag', 'prefix' => 'whms_tag'],
        ['name' => 'WHMS Manifest', 'prefix' => 'whms_manifest'],
        ['name' => 'WHMS Runsheet', 'prefix' => 'whms_runsheet'],
        ['name' => 'WHMS POD', 'prefix' => 'whms_pod'],
        ['name' => 'WHMS Tracking', 'prefix' => 'whms_tracking'],
    ]],
    ['name' => 'Tickets', 'sorted' => 6, 'permissions' => [
        ['name' => 'Tickets', 'prefix' => 'ticket'],
    ]],
    ['name' => 'Serial Allocation', 'sorted' => 7, 'permissions' => [
        ['name' => 'Serial Allocation', 'prefix' => 'serial_allocation'],
    ]],
    ['name' => 'HR & Payroll', 'sorted' => 8, 'permissions' => [
        ['name' => 'Employee', 'prefix' => 'employee'],
        ['name' => 'Department', 'prefix' => 'department'],
        ['name' => 'Designation', 'prefix' => 'designation'],
        ['name' => 'Salary Template', 'prefix' => 'salary_template'],
        ['name' => 'Client Based User', 'prefix' => 'client_based_user'],
    ]],
    ['name' => 'Reports & Tools', 'sorted' => 9, 'permissions' => [
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
    ['name' => 'Settings', 'sorted' => 10, 'permissions' => [
        ['name' => 'APIs', 'prefix' => 'apis'],
        ['name' => 'Couriers', 'prefix' => 'couriers'],
        ['name' => 'Courier Partner', 'prefix' => 'courier_partner'],
        ['name' => 'About', 'prefix' => 'about'],
        ['name' => 'Enable Shiprocket', 'prefix' => 'enable-shiprocket'],
    ]],
];

function modulePrefixFromName ($name)
    {
    return strtolower ( str_replace ( [ ' ', '&' ], [ '_', 'and' ], $name ) );
    }

try {
    $pdo->beginTransaction ();

    // Remove duplicate permission_modules by name (case-insensitive) before syncing modules.
    $dupModules = $pdo->query ( "
        SELECT LOWER(TRIM(name)) AS nkey, MIN(id) AS keep_id
        FROM permission_modules
        GROUP BY LOWER(TRIM(name))
        HAVING COUNT(*) > 1
    " )->fetchAll ( PDO::FETCH_ASSOC );

    foreach ($dupModules as $dup) {
        $nkey = (string) $dup[ 'nkey' ];
        $keepId = (int) $dup[ 'keep_id' ];

        $idsStmt = $pdo->prepare ( "
            SELECT id, name
            FROM permission_modules
            WHERE LOWER(TRIM(name)) = ?
            ORDER BY id ASC
        " );
        $idsStmt->execute ( [ $nkey ] );
        $rows = $idsStmt->fetchAll ( PDO::FETCH_ASSOC );

        foreach ($rows as $row) {
            $dupId = (int) $row['id'];
            if ($dupId === $keepId) {
                continue;
            }

            // Move permissions to canonical module ID.
            $pdo->prepare ( "UPDATE permission SET module_id = ? WHERE module_id = ?" )->execute ( [ $keepId, $dupId ] );
            // Remove duplicate module row.
            $pdo->prepare ( "DELETE FROM permission_modules WHERE id = ?" )->execute ( [ $dupId ] );

            echo "<p style='color:#b02a37;'>Module duplicate merged: " . htmlspecialchars ( (string) $row['name'] ) . " (removed module_id $dupId, kept $keepId)</p>";
        }
    }

    $moduleMap = [];
    foreach ($canonicalModules as $module) {
        $stmt = $pdo->prepare ( "SELECT id FROM permission_modules WHERE name = ? LIMIT 1" );
        $stmt->execute ( [ $module[ 'name' ] ] );
        $existing = $stmt->fetch ( PDO::FETCH_ASSOC );

        if ($existing) {
            $moduleId = (int) $existing[ 'id' ];
            $upd = $pdo->prepare ( "UPDATE permission_modules SET sorted = ?, prefix = ? WHERE id = ?" );
            $upd->execute ( [ (int) $module[ 'sorted' ], modulePrefixFromName ( $module[ 'name' ] ), $moduleId ] );
            } else {
            $ins = $pdo->prepare ( "INSERT INTO permission_modules (name, prefix, sorted, system, in_module) VALUES (?, ?, ?, 0, 1)" );
            $ins->execute ( [ $module[ 'name' ], modulePrefixFromName ( $module[ 'name' ] ), (int) $module[ 'sorted' ] ] );
            $moduleId = (int) $pdo->lastInsertId ();
            }

        $moduleMap[ $module[ 'name' ] ] = $moduleId;
        echo "<p style='color:#0d6efd;'>Module synced: " . htmlspecialchars ( $module[ 'name' ] ) . " (ID $moduleId)</p>";
        }

    foreach ($canonicalModules as $module) {
        $moduleId = $moduleMap[ $module[ 'name' ] ];

        foreach ($module[ 'permissions' ] as $perm) {
            $sel = $pdo->prepare ( "SELECT id FROM permission WHERE prefix = ? ORDER BY id ASC" );
            $sel->execute ( [ $perm[ 'prefix' ] ] );
            $rows = $sel->fetchAll ( PDO::FETCH_ASSOC );

            if (empty ( $rows )) {
                $ins = $pdo->prepare ( "INSERT INTO permission (module_id, name, prefix, show_view, show_add, show_edit, show_delete) VALUES (?, ?, ?, 1, 1, 1, 1)" );
                $ins->execute ( [ $moduleId, $perm[ 'name' ], $perm[ 'prefix' ] ] );
                $keepId = (int) $pdo->lastInsertId ();
                echo "<p style='color:green;'>Inserted: " . htmlspecialchars ( $perm[ 'name' ] ) . " (" . htmlspecialchars ( $perm[ 'prefix' ] ) . ")</p>";
                } else {
                $keepId = (int) $rows[ 0 ][ 'id' ];
                $upd = $pdo->prepare ( "UPDATE permission SET module_id = ?, name = ?, show_view = 1, show_add = 1, show_edit = 1, show_delete = 1 WHERE id = ?" );
                $upd->execute ( [ $moduleId, $perm[ 'name' ], $keepId ] );
                echo "<p style='color:#198754;'>Updated: " . htmlspecialchars ( $perm[ 'name' ] ) . " (" . htmlspecialchars ( $perm[ 'prefix' ] ) . ")</p>";

                if (count ( $rows ) > 1) {
                    for ($i = 1; $i < count ( $rows ); $i++) {
                        $dupId = (int) $rows[ $i ][ 'id' ];

                        $mergeInsert = $pdo->prepare ( "
                            INSERT IGNORE INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete)
                            SELECT role_id, ?, is_view, is_add, is_edit, is_delete
                            FROM staff_privileges
                            WHERE permission_id = ?
                        " );
                        $mergeInsert->execute ( [ $keepId, $dupId ] );

                        $mergeUpdate = $pdo->prepare ( "
                            UPDATE staff_privileges t
                            JOIN staff_privileges s ON s.role_id = t.role_id
                            SET
                                t.is_view = GREATEST(t.is_view, s.is_view),
                                t.is_add = GREATEST(t.is_add, s.is_add),
                                t.is_edit = GREATEST(t.is_edit, s.is_edit),
                                t.is_delete = GREATEST(t.is_delete, s.is_delete)
                            WHERE t.permission_id = ? AND s.permission_id = ?
                        " );
                        $mergeUpdate->execute ( [ $keepId, $dupId ] );

                        $delPriv = $pdo->prepare ( "DELETE FROM staff_privileges WHERE permission_id = ?" );
                        $delPriv->execute ( [ $dupId ] );
                        $delPerm = $pdo->prepare ( "DELETE FROM permission WHERE id = ?" );
                        $delPerm->execute ( [ $dupId ] );

                        echo "<p style='color:#fd7e14;'>Merged duplicate permission ID $dupId into $keepId for prefix " . htmlspecialchars ( $perm[ 'prefix' ] ) . "</p>";
                        }
                    }
                }
            }
        }

    // Enforce unique feature-name per module using canonical prefix mapping.
    foreach ($canonicalModules as $module) {
        $moduleId = $moduleMap[ $module[ 'name' ] ];
        $nameToPrefix = [];
        foreach ($module['permissions'] as $perm) {
            $nameToPrefix[$perm['name']] = $perm['prefix'];
        }

        $dupByName = $pdo->prepare("
            SELECT name
            FROM permission
            WHERE module_id = ?
            GROUP BY name
            HAVING COUNT(*) > 1
        ");
        $dupByName->execute([$moduleId]);
        $dupNames = $dupByName->fetchAll(PDO::FETCH_COLUMN);

        foreach ($dupNames as $dupName) {
            $canonicalPrefix = $nameToPrefix[$dupName] ?? null;
            if (!$canonicalPrefix) {
                continue;
            }

            $rowsStmt = $pdo->prepare("SELECT id, prefix FROM permission WHERE module_id = ? AND name = ? ORDER BY id ASC");
            $rowsStmt->execute([$moduleId, $dupName]);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) < 2) {
                continue;
            }

            $keepId = 0;
            foreach ($rows as $row) {
                if ($row['prefix'] === $canonicalPrefix) {
                    $keepId = (int) $row['id'];
                    break;
                }
            }
            if ($keepId === 0) {
                // If canonical prefix row is missing, keep first and force canonical prefix.
                $keepId = (int) $rows[0]['id'];
                $pdo->prepare("UPDATE permission SET prefix = ? WHERE id = ?")->execute([$canonicalPrefix, $keepId]);
            }

            foreach ($rows as $row) {
                $dupId = (int) $row['id'];
                if ($dupId === $keepId) {
                    continue;
                }

                $mergeInsert = $pdo->prepare("
                    INSERT IGNORE INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete)
                    SELECT role_id, ?, is_view, is_add, is_edit, is_delete
                    FROM staff_privileges
                    WHERE permission_id = ?
                ");
                $mergeInsert->execute([$keepId, $dupId]);

                $mergeUpdate = $pdo->prepare("
                    UPDATE staff_privileges t
                    JOIN staff_privileges s ON s.role_id = t.role_id
                    SET
                        t.is_view = GREATEST(t.is_view, s.is_view),
                        t.is_add = GREATEST(t.is_add, s.is_add),
                        t.is_edit = GREATEST(t.is_edit, s.is_edit),
                        t.is_delete = GREATEST(t.is_delete, s.is_delete)
                    WHERE t.permission_id = ? AND s.permission_id = ?
                ");
                $mergeUpdate->execute([$keepId, $dupId]);

                $pdo->prepare("DELETE FROM staff_privileges WHERE permission_id = ?")->execute([$dupId]);
                $pdo->prepare("DELETE FROM permission WHERE id = ?")->execute([$dupId]);

                echo "<p style='color:#d63384;'>Name-duplicate merged in module " . htmlspecialchars($module['name']) . ": " . htmlspecialchars($dupName) . " (removed ID $dupId, kept $keepId / " . htmlspecialchars($canonicalPrefix) . ")</p>";
            }
        }
    }

    // De-duplicate staff_privileges by (role_id, permission_id).
    // Keep the minimum id row and merge permissions using MAX across duplicates.
    $dupPairs = $pdo->query ( "
        SELECT role_id, permission_id, MIN(id) AS keep_id
        FROM staff_privileges
        GROUP BY role_id, permission_id
        HAVING COUNT(*) > 1
    " )->fetchAll ( PDO::FETCH_ASSOC );

    foreach ($dupPairs as $pair) {
        $roleId = (int) $pair['role_id'];
        $permId = (int) $pair['permission_id'];
        $keepId = (int) $pair['keep_id'];

        $agg = $pdo->prepare ( "
            SELECT
                MAX(is_view) AS is_view,
                MAX(is_add) AS is_add,
                MAX(is_edit) AS is_edit,
                MAX(is_delete) AS is_delete
            FROM staff_privileges
            WHERE role_id = ? AND permission_id = ?
        " );
        $agg->execute ( [ $roleId, $permId ] );
        $row = $agg->fetch ( PDO::FETCH_ASSOC ) ?: [ 'is_view' => 0, 'is_add' => 0, 'is_edit' => 0, 'is_delete' => 0 ];

        $pdo->prepare ( "
            UPDATE staff_privileges
            SET is_view = ?, is_add = ?, is_edit = ?, is_delete = ?
            WHERE id = ?
        " )->execute ( [ (int) $row['is_view'], (int) $row['is_add'], (int) $row['is_edit'], (int) $row['is_delete'], $keepId ] );

        $del = $pdo->prepare ( "
            DELETE FROM staff_privileges
            WHERE role_id = ? AND permission_id = ? AND id <> ?
        " );
        $del->execute ( [ $roleId, $permId, $keepId ] );
        $deleted = $del->rowCount ();

        if ($deleted > 0) {
            echo "<p style='color:#0f5132;'>Staff privilege duplicate merged: role_id $roleId, permission_id $permId (removed $deleted duplicate rows)</p>";
        }
    }

    // Normalize common alias prefixes into canonical ones where menus expect underscore/hyphen variants.
    $aliasMap = [
        'serial-allocation' => 'serial_allocation',
        'salary-template' => 'salary_template',
        'courier-partner' => 'courier_partner',
    ];
    foreach ($aliasMap as $alias => $canonical) {
        $selCanonical = $pdo->prepare ( "SELECT id FROM permission WHERE prefix = ? ORDER BY id ASC LIMIT 1" );
        $selCanonical->execute ( [ $canonical ] );
        $canonicalId = (int) ($selCanonical->fetchColumn () ?: 0);

        $selAlias = $pdo->prepare ( "SELECT id FROM permission WHERE prefix = ? ORDER BY id ASC" );
        $selAlias->execute ( [ $alias ] );
        $aliasRows = $selAlias->fetchAll ( PDO::FETCH_ASSOC );
        if ($canonicalId > 0 && ! empty ( $aliasRows )) {
            foreach ($aliasRows as $row) {
                $aliasId = (int) $row[ 'id' ];
                if ($aliasId === $canonicalId) {
                    continue;
                    }
                $mergeInsert = $pdo->prepare ( "
                    INSERT IGNORE INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete)
                    SELECT role_id, ?, is_view, is_add, is_edit, is_delete
                    FROM staff_privileges
                    WHERE permission_id = ?
                " );
                $mergeInsert->execute ( [ $canonicalId, $aliasId ] );

                $mergeUpdate = $pdo->prepare ( "
                    UPDATE staff_privileges t
                    JOIN staff_privileges s ON s.role_id = t.role_id
                    SET
                        t.is_view = GREATEST(t.is_view, s.is_view),
                        t.is_add = GREATEST(t.is_add, s.is_add),
                        t.is_edit = GREATEST(t.is_edit, s.is_edit),
                        t.is_delete = GREATEST(t.is_delete, s.is_delete)
                    WHERE t.permission_id = ? AND s.permission_id = ?
                " );
                $mergeUpdate->execute ( [ $canonicalId, $aliasId ] );
                $pdo->prepare ( "DELETE FROM staff_privileges WHERE permission_id = ?" )->execute ( [ $aliasId ] );
                $pdo->prepare ( "DELETE FROM permission WHERE id = ?" )->execute ( [ $aliasId ] );
                echo "<p style='color:#6f42c1;'>Alias merged: " . htmlspecialchars ( $alias ) . " -> " . htmlspecialchars ( $canonical ) . "</p>";
                }
            }
        }

    $pdo->commit ();

    // Add unique index to prevent future duplicates (DDL outside transaction).
    $idxCheck = $pdo->query ( "SHOW INDEX FROM staff_privileges WHERE Key_name = 'uniq_role_permission'" )->fetch ( PDO::FETCH_ASSOC );
    if (!$idxCheck) {
        $pdo->exec ( "ALTER TABLE staff_privileges ADD UNIQUE KEY uniq_role_permission (role_id, permission_id)" );
        echo "<p style='color:#084298;'>Added unique index uniq_role_permission on staff_privileges(role_id, permission_id)</p>";
    }

    echo "<h3 style='color:green;'>Permission mapping normalization completed.</h3>";

    // Role 6 verification block for Shiprocket visibility.
    $verifyPrefixes = [ 'enable-shiprocket', 'shiprocket-list', 'shiprocket-bulk', 'shiprocket-manifest', 'shiprocket-pickup' ];
    echo "<h4>Role 6 verification (Shiprocket permissions)</h4>";
    echo "<table border='1' cellpadding='6' cellspacing='0'><tr><th>Prefix</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr>";
    $verifySql = $pdo->prepare ( "
        SELECT p.prefix, COALESCE(sp.is_view,0) AS is_view, COALESCE(sp.is_add,0) AS is_add, COALESCE(sp.is_edit,0) AS is_edit, COALESCE(sp.is_delete,0) AS is_delete
        FROM permission p
        LEFT JOIN staff_privileges sp ON sp.permission_id = p.id AND sp.role_id = 6
        WHERE p.prefix = ?
        ORDER BY p.id ASC
        LIMIT 1
    " );
    foreach ($verifyPrefixes as $prefix) {
        $verifySql->execute ( [ $prefix ] );
        $r = $verifySql->fetch ( PDO::FETCH_ASSOC );
        if ($r) {
            echo "<tr><td>" . htmlspecialchars ( $prefix ) . "</td><td>{$r['is_view']}</td><td>{$r['is_add']}</td><td>{$r['is_edit']}</td><td>{$r['is_delete']}</td></tr>";
            } else {
            echo "<tr><td>" . htmlspecialchars ( $prefix ) . "</td><td colspan='4' style='color:red;'>Missing permission row</td></tr>";
            }
        }
    echo "</table>";

} catch (Exception $e) {
    if ($pdo->inTransaction ()) {
        $pdo->rollBack ();
        }
    echo "<h3 style='color:red;'>Error: " . htmlspecialchars ( $e->getMessage () ) . "</h3>";
}
?>
