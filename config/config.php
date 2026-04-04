<?php

// Load environment variables first
include 'env.php';
include 'db.php';
include 'helper.php';
include 'google_maps_config.php';

// Application constants from environment (only if not already defined)
if (!defined('APP_NAME')) {
    define('APP_NAME', env('APP_NAME', 'WHMS'));
}
if (!defined('APP_ENV')) {
    define('APP_ENV', env('APP_ENV', 'production'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', envBool('APP_DEBUG', false));
}
if (!defined('APP_URL')) {
    define('APP_URL', env('APP_URL', 'http://localhost'));
}

// Default role (can be overridden by environment)
$role_id = envInt('DEFAULT_ROLE_ID', 2);
