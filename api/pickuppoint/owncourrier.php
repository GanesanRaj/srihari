<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/middleware.php';

/**
 * Own Courier API Utility
 * This file handles logic specific to "Own Courier" (id 2).
 * Currently, pickup points for ID 2 are managed via create.php and update.php 
 * which use the services/owncourrier.php handler.
 */

echo json_encode([
    'status' => 'success',
    'message' => 'Own Courier logic is active. No external API sync required for Courier ID 2.',
    'courier_id' => 2
]);
?>