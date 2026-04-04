<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/middleware.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Payroll ID is required');
    }

    $id = $_GET['id'];

    $query = "
        SELECT 
            p.*,
            e.name as employee_name,
            d.designation as employee_designation,
            b.branch_name as employee_branch,
            c.company_name,
            c.address as company_address,
            c.city as company_city,
            c.state as company_state,
            c.pincode as company_pincode,
            c.phone_number as company_phone,
            c.company_logo,
            c.gst_no
        FROM tbl_payroll p
        LEFT JOIN tbl_employees e ON p.employee_id = e.id
        LEFT JOIN tbl_designations d ON e.designation_id = d.id
        LEFT JOIN tbl_branch b ON e.branch_id = b.id
        LEFT JOIN tbl_company c ON b.company_id = c.id
        WHERE p.id = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        throw new Exception('Payroll record not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $payroll
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
