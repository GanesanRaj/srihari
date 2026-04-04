<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Google Maps API Configuration from environment
if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE'));
}

// Function to get Google Maps API key
function getGoogleMapsApiKey() {
    return GOOGLE_MAPS_API_KEY;
}

// Function to check if Google Maps API key is configured
function isGoogleMapsApiConfigured() {
    return GOOGLE_MAPS_API_KEY !== 'YOUR_GOOGLE_MAPS_API_KEY_HERE' && !empty(GOOGLE_MAPS_API_KEY);
}
