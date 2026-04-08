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

// Default role (can be overridden by environment) only for unauthenticated flows.
// Never override role_id resolved from session/helper for logged-in users.
if (!isset($_SESSION['role_id'])) {
    $role_id = envInt('DEFAULT_ROLE_ID', 2);
    // #region agent log
    if (function_exists('_agent_debug_log')) {
        _agent_debug_log([
            'hypothesisId' => 'H6',
            'location' => 'config/config.php:default_role_assignment',
            'message' => 'Assigned default role_id because session role_id missing',
            'data' => [
                'assigned_role_id' => (int) $role_id
            ]
        ]);
    }
    // #endregion
} else {
    $role_id = (int) $_SESSION['role_id'];
    // #region agent log
    if (function_exists('_agent_debug_log')) {
        _agent_debug_log([
            'hypothesisId' => 'H6',
            'location' => 'config/config.php:session_role_assignment',
            'message' => 'Using session role_id without override',
            'data' => [
                'session_role_id' => (int) $_SESSION['role_id']
            ]
        ]);
    }
    // #endregion
}
