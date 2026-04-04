<?php
/**
 * OneSignal Configuration
 * Replace these values with your actual OneSignal credentials
 */

// OneSignal App ID - Get this from your OneSignal dashboard
define('ONESIGNAL_APP_ID', '5fc02aa5-d34c-464e-8c33-af476ef173ff');

// OneSignal REST API Key - Get this from your OneSignal dashboard
define('ONESIGNAL_REST_API_KEY', 'YOUR_ONESIGNAL_REST_API_KEY');

// OneSignal configuration array
$onesignal_config = [
    'app_id' => ONESIGNAL_APP_ID,
    'api_key' => ONESIGNAL_REST_API_KEY,
    'allow_localhost_secure' => true, // Allow localhost for development
    'notification_click_handler' => true,
    'notification_display_foreground' => true,
    'persist_notification' => false,
    'webhooks' => [
        'enabled' => false, // Set to true if you want webhooks
        'url' => '', // Webhook URL for delivery tracking
    ]
];

// Default notification segments
$onesignal_segments = [
    'All',
    'Active Users',
    'Inactive Users',
    'Subscribed Users',
    'Unsubscribed Users'
];

// Notification priority mapping
$onesignal_priorities = [
    'normal' => 0,  // Normal priority
    'high' => 7,    // High priority
    'urgent' => 10  // Urgent priority (highest)
];

// Common notification types
$onesignal_types = [
    'system' => 'System Notification',
    'lead' => 'Lead Notification',
    'customer' => 'Customer Notification',
    'followup' => 'Follow-up Reminder',
    'service' => 'Service Notification',
    'alert' => 'Alert Notification'
];

// Default notification settings
$onesignal_defaults = [
    'ttl' => 259200, // 3 days in seconds
    'priority' => 'normal',
    'sound' => 'default',
    'badge' => true,
    'vibrate' => true,
    'lights' => true
];
?>
