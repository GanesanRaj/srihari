<?php
/**
 * Download all branches as Excel (Branch Name, Code).
 * Used on WHMS Bulk Shipment page for template reference.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

if ( ! get_permission('branch', 'is_view') && ! get_permission('whms_booking', 'is_view') && ! get_permission('whms_shipment', 'is_view') ) {
    require_api_permission('branch', 'is_view');
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Branch_Codes_' . date('Y-m-d') . '.xls"');

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

try {
    $sql = "SELECT branch_name, branch_code FROM tbl_branch ORDER BY branch_name ASC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: text/plain');
    echo 'Database error: ' . $e->getMessage();
    exit;
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <style>
        th { background-color: #4472C4; color: #fff; font-weight: bold; padding: 4px 8px; }
        td { padding: 4px 8px; }
        td { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <table border="1">
        <tr>
            <th>Branch Name</th>
            <th>Code</th>
        </tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo h($r['branch_name']); ?></td>
            <td><?php echo h($r['branch_code']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
