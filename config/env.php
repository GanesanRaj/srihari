<?php

// Prevent multiple inclusions
if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);
    
    /**
     * Load environment variables from .env file
     */
    function loadEnv($filePath = null) {
        if ($filePath === null) {
            $filePath = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments and empty lines
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Set environment variable
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                
                // Also define as constant for backward compatibility
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get environment variable
     */
    function env($key, $default = null) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    
    /**
     * Get boolean environment variable
     */
    function envBool($key, $default = false) {
        $value = env($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get integer environment variable
     */
    function envInt($key, $default = 0) {
        return (int) env($key, $default);
    }
    
    /**
     * Get array environment variable (comma-separated)
     */
    function envArray($key, $default = []) {
        $value = env($key, '');
        return empty($value) ? $default : array_map('trim', explode(',', $value));
    }
    
    // Load environment variables
    loadEnv();
    
    // Set timezone from environment
    $timezone = env('APP_TIMEZONE', 'Asia/Kolkata');
    date_default_timezone_set($timezone);
}
