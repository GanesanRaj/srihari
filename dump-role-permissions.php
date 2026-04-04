<?php
/**
 * Dump permission modules, permissions (with prefix), and for a given role the granted flags.
 * Usage: dump-role-permissions.php?role_id=3
 * Run in browser (logged in) or CLI: php -r "parse_str('role_id=3', $_GET); include 'dump-role-permissions.php';"
 */
require_once __DIR__ . '/config/config.php';

$role_id = isset($_GET['role_id']) ? (int) $_GET['role_id'] : 0;
if ($role_id <= 0) {
    $role_id = 3; // default for quick check
}

header('Content-Type: application/json; charset=utf-8');

try {
    $roleStmt = $pdo->prepare("SELECT id, name, prefix AS role_prefix FROM roles WHERE id = ?");
    $roleStmt->execute([$role_id]);
    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$role) {
        echo json_encode(['error' => 'Role not found', 'role_id' => $role_id], JSON_PRETTY_PRINT);
        exit;
    }

    $modulesStmt = $pdo->query("SELECT id, name, sorted FROM permission_modules ORDER BY sorted ASC");
    $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [
        'role_id'   => $role_id,
        'role_name' => $role['name'],
        'role_prefix'=> $role['role_prefix'] ?? '',
        'modules'   => [],
    ];

    foreach ($modules as $mod) {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.prefix, p.show_view, p.show_add, p.show_edit, p.show_delete,
                COALESCE(sp.is_view, 0) AS is_view, COALESCE(sp.is_add, 0) AS is_add, COALESCE(sp.is_edit, 0) AS is_edit, COALESCE(sp.is_delete, 0) AS is_delete
            FROM permission p
            LEFT JOIN staff_privileges sp ON sp.permission_id = p.id AND sp.role_id = ?
            WHERE p.module_id = ?
            ORDER BY p.id");
        $stmt->execute([$role_id, $mod['id']]);
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out['modules'][] = [
            'id'    => (int) $mod['id'],
            'name'  => $mod['name'],
            'sorted'=> (int) ($mod['sorted'] ?? 0),
            'permissions' => $perms,
        ];
    }

    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
