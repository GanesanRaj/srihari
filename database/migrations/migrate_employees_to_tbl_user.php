<?php
/**
 * One-time migration: create tbl_user rows for existing tbl_employees that don't have a user record.
 * Run from browser: http://localhost/steve/database/migrations/migrate_employees_to_tbl_user.php
 * Or CLI: php migrate_employees_to_tbl_user.php (from database/migrations folder)
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: text/plain; charset=utf-8');

$created = 0;
$skipped = 0;
$errors = [];

try {
    $stmt = $pdo->query("SELECT id, user_id, password, branch_id, role_id, status FROM tbl_employees ORDER BY id");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error loading employees: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if tbl_user has user_id column (link to employee id)
$hasUserIdColumn = false;
try {
    $pdo->query("SELECT user_id FROM tbl_user LIMIT 1");
    $hasUserIdColumn = true;
} catch (PDOException $e) {
    // column may not exist
}

$checkByEmployeeId = $hasUserIdColumn ? $pdo->prepare("SELECT id FROM tbl_user WHERE user_id = :eid LIMIT 1") : null;
$checkByUsername  = $pdo->prepare("SELECT id FROM tbl_user WHERE username = :uname LIMIT 1");
$insertWithUserId = $pdo->prepare("INSERT INTO tbl_user (username, password, branch_id, role_id, status, user_type, user_id) VALUES (:username, :password, :branch_id, :role_id, :status, 'employee', :user_id)");
$insertNoUserId   = $pdo->prepare("INSERT INTO tbl_user (username, password, branch_id, role_id, status, user_type) VALUES (:username, :password, :branch_id, :role_id, :status, 'employee')");

foreach ($employees as $emp) {
    $eid     = (int) $emp['id'];
    $username = trim($emp['user_id'] ?? '');
    if ($username === '') {
        $skipped++;
        $errors[] = "Employee id=$eid has no user_id (username); skipped.";
        continue;
    }

    $exists = false;
    if ($hasUserIdColumn && $checkByEmployeeId) {
        $checkByEmployeeId->execute([':eid' => $eid]);
        if ($checkByEmployeeId->fetch()) $exists = true;
    }
    if (!$exists) {
        $checkByUsername->execute([':uname' => $username]);
        if ($checkByUsername->fetch()) $exists = true;
    }

    if ($exists) {
        $skipped++;
        continue;
    }

    $params = [
        ':username'  => $username,
        ':password'  => $emp['password'] ?? '',
        ':branch_id' => (int) ($emp['branch_id'] ?? 0),
        ':role_id'   => (int) ($emp['role_id'] ?? 0),
        ':status'    => in_array($emp['status'] ?? '', ['active', 'inactive']) ? $emp['status'] : 'active',
    ];
    try {
        if ($hasUserIdColumn) {
            $insertWithUserId->execute($params + [':user_id' => $eid]);
        } else {
            $insertNoUserId->execute($params);
        }
        $created++;
    } catch (PDOException $e) {
        $errors[] = "Employee id=$eid ($username): " . $e->getMessage();
    }
}

echo "Migration complete.\n";
echo "Created tbl_user rows: $created\n";
echo "Skipped (already have user): $skipped\n";
if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $err) echo "  - $err\n";
}
