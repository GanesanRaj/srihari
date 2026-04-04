<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

try {
    $data = [];

    // Show current session login info
    $deviceInfo = $_SESSION['device_info'] ?? [];
    if (!empty($deviceInfo)) {
        $data[] = [
            'activity'   => 'Login',
            'datetime'   => $deviceInfo['login_time'] ?? '',
            'ip_address' => $deviceInfo['ip_address'] ?? '',
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
