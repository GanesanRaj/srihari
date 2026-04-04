<?php
require __DIR__ . '/../vendor/autoload.php';

class PusherHelper {
    // TODO: Replace with your actual Pusher credentials from https://dashboard.pusher.com/
    private $app_id = 'e36c9530f80045163aae';
    private $key = '2eb9af8d97aa6df85425';
    private $secret = '2077799';
    private $cluster = 'ap2';
    private $pusher;

    public function __construct() {
        $options = array(
            'cluster' => $this->cluster,
            'useTLS' => true
        );

        $this->pusher = new Pusher\Pusher(
            $this->key,
            $this->app_id,
            $this->secret,
            $options
        );
    }

    /**
     * Send notification to specific user via device token
     */
    public function sendNotification($deviceToken, $title, $message, $data = []) {
        try {
            // Store notification in database for the user
            $this->storeNotification($deviceToken, $title, $message, $data);

            // Try to send real-time notification via Pusher
            try {
                $pusherData = [
                    'title' => $title,
                    'message' => $message,
                    'timestamp' => time(),
                    'data' => $data
                ];

                // Use device token as channel name for user-specific notifications
                $channel = 'user-' . $deviceToken;
                $event = 'notification';

                $this->pusher->trigger($channel, $event, $pusherData);

                error_log("Pusher notification sent - Channel: {$channel}, Event: {$event}");
                $pusherSuccess = true;

            } catch (Exception $pusherException) {
                // Pusher failed, but notification is still stored in database
                error_log("Pusher notification failed: " . $pusherException->getMessage());
                error_log("Pusher notification details - Channel: {$channel}, Event: {$event}, Device Token: {$deviceToken}");

                // Log more details for debugging
                if (strpos($pusherException->getMessage(), 'not in this cluster') !== false) {
                    error_log("PUSHER ERROR: App not configured for cluster '{$this->cluster}'");
                } elseif (strpos($pusherException->getMessage(), 'App key') !== false) {
                    error_log("PUSHER ERROR: Invalid app key or credentials");
                }

                $pusherSuccess = false;
            }

            return [
                'status' => 'success',
                'message' => $pusherSuccess ? 'Notification sent successfully via Pusher' : 'Notification stored successfully (Pusher failed - check credentials)',
                'pusher_status' => $pusherSuccess ? 'sent' : 'failed'
            ];

        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Store notification in database
     */
    private function storeNotification($deviceToken, $title, $message, $data = []) {
        global $pdo;

        try {
            // Find user by device token
            $stmt = $pdo->prepare("SELECT id FROM tbl_employee WHERE device_token = ?");
            $stmt->execute([$deviceToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Insert notification using the correct column names
                $stmt = $pdo->prepare("
                    INSERT INTO notifications
                    (recipient_user_id, title, message, data, type, priority, is_sent, sent_at, created_at, created_by)
                    VALUES (?, ?, ?, ?, 'push', 'medium', 1, NOW(), NOW(), ?)
                ");
                $stmt->execute([
                    $user['id'],
                    $title,
                    $message,
                    json_encode($data),
                    $user['id'] // created_by
                ]);
            }
        } catch (Exception $e) {
            error_log("Error storing notification: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Register device token for user
     */
    public function registerDeviceToken($userId, $deviceToken) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
                UPDATE tbl_employee
                SET device_token = ?, token_updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$deviceToken, $userId]);

            return [
                'status' => 'success',
                'message' => 'Device token registered successfully'
            ];

        } catch (Exception $e) {
            error_log("Error registering device token: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to employee when service is assigned
     */
    public function sendServiceAssignmentNotification($employeeId, $serviceData) {
        global $pdo;

        try {
            // Get employee device token
            $stmt = $pdo->prepare("SELECT device_token FROM tbl_employee WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee && $employee['device_token']) {
                $title = "New Service Assigned";
                $message = "You have been assigned a new service for {$serviceData['customer_name']} - {$serviceData['product_name']}";

                $data = [
                    'service_id' => $serviceData['service_id'],
                    'customer_name' => $serviceData['customer_name'],
                    'product_name' => $serviceData['product_name'],
                    'service_date' => $serviceData['service_date'],
                    'type' => 'service_assignment'
                ];

                return $this->sendNotification($employee['device_token'], $title, $message, $data);
            }

            return [
                'status' => 'error',
                'message' => 'Employee not found or no device token registered'
            ];

        } catch (Exception $e) {
            error_log("Error sending service assignment notification: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current configuration (for debugging)
     */
    public function getConfig() {
        return [
            'app_id' => $this->app_id,
            'key' => $this->key,
            'secret' => $this->secret,
            'cluster' => $this->cluster
        ];
    }

    /**
     * Test Pusher connection with basic trigger
     */
    public function testPusherConnection() {
        try {
            $data['message'] = 'hello world';
            $this->pusher->trigger('my-channel', 'my-event', $data);

            error_log("Pusher test connection successful - sent 'hello world' to my-channel");
            return [
                'status' => 'success',
                'message' => 'Pusher connection test successful'
            ];
        } catch (Exception $e) {
            error_log("Pusher test connection failed: " . $e->getMessage());

            // Check if it's a credentials/configuration issue
            if (strpos($e->getMessage(), 'not in this cluster') !== false ||
                strpos($e->getMessage(), 'App key') !== false) {
                return [
                    'status' => 'error',
                    'message' => 'Pusher credentials appear to be invalid or demo credentials. Notifications will be stored in database only.',
                    'details' => $e->getMessage()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Pusher connection test failed: ' . $e->getMessage()
            ];
        }
    }
}
?>
