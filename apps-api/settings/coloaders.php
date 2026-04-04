<?php
/**
 * Settings API – Coloader Dropdown
 * Location: /apps-api/settings/coloaders.php
 * Method: GET | POST
 * Params:
 *   search (opt) – partial name search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../config/config.php';

$req = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : $_GET;

$search = trim($req['search'] ?? '');

try {
    $where  = "WHERE status = 'active'";
    $params = [];

    if ($search !== '') {
        $where .= ' AND name LIKE :search';
        $params[':search'] = "%$search%";
    }

    $stmt = $pdo->prepare("SELECT id, name, mobile_number FROM tbl_coloader $where ORDER BY name ASC");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
