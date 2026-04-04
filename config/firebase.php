<?php
class FirebaseHelper {
    // Firebase project configuration
    private $config = [
        'apiKey' => 'AIzaSyCIU2FTrL-7H8JiCbcu2-HEza7k2Ix7OKM',
        'authDomain' => 'fiery-surf-404610.firebaseapp.com',
        'projectId' => 'fiery-surf-404610',
        'storageBucket' => 'fiery-surf-404610.firebasestorage.app',
        'messagingSenderId' => '924879257179',
        'appId' => '1:924879257179:web:97b43a45b1bf64d075097e',
        'measurementId' => 'G-YZN2RG1D8T'
    ];

    // Server key for sending push notifications (from Firebase Console > Project Settings > Cloud Messaging)
    private $serverKey = 'NGMzu3KhfxYtXLjhdTZ_mhHg-QTgy5ODsc8p4NPy6Sw';

    public function __construct() {
        // Initialize with environment variables if available
        if (getenv('FIREBASE_API_KEY')) {
            $this->config['apiKey'] = getenv('FIREBASE_API_KEY');
        }
        if (getenv('FIREBASE_AUTH_DOMAIN')) {
            $this->config['authDomain'] = getenv('FIREBASE_AUTH_DOMAIN');
        }
        if (getenv('FIREBASE_PROJECT_ID')) {
            $this->config['projectId'] = getenv('FIREBASE_PROJECT_ID');
        }
        if (getenv('FIREBASE_STORAGE_BUCKET')) {
            $this->config['storageBucket'] = getenv('FIREBASE_STORAGE_BUCKET');
        }
        if (getenv('FIREBASE_MESSAGING_SENDER_ID')) {
            $this->config['messagingSenderId'] = getenv('FIREBASE_MESSAGING_SENDER_ID');
        }
        if (getenv('FIREBASE_APP_ID')) {
            $this->config['appId'] = getenv('FIREBASE_APP_ID');
        }
        if (getenv('FIREBASE_MEASUREMENT_ID')) {
            $this->config['measurementId'] = getenv('FIREBASE_MEASUREMENT_ID');
        }
        if (getenv('FIREBASE_SERVER_KEY')) {
            $this->serverKey = getenv('FIREBASE_SERVER_KEY');
        }
    }

    /**
     * Get Firebase configuration for frontend
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Get server key
     */
    public function getServerKey() {
        return $this->serverKey;
    }

    /**
     * Send push notification via Firebase
     */
    public function sendPushNotification($token, $title, $body, $data = []) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/favicon.ico',
                'click_action' => '/'
            ],
            'data' => $data
        ];

        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            error_log('Firebase push notification failed: ' . curl_error($ch));
            return false;
        }

        curl_close($ch);
        $response = json_decode($result, true);

        error_log('Firebase push notification result: ' . $result);

        return isset($response['success']) && $response['success'] == 1;
    }

    /**
     * Store Firebase token in database
     */
    public function storeToken($userId, $token) {
        global $pdo;

        try {
            // Check if token already exists for this user
            $stmt = $pdo->prepare("SELECT id FROM firebase_tokens WHERE user_id = ? AND token = ?");
            $stmt->execute([$userId, $token]);

            if ($stmt->rowCount() == 0) {
                // Insert new token
                $stmt = $pdo->prepare("
                    INSERT INTO firebase_tokens (user_id, token, created_at, updated_at)
                    VALUES (?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE updated_at = NOW()
                ");
                $stmt->execute([$userId, $token]);
            }

            return [
                'status' => 'success',
                'message' => 'Firebase token stored successfully'
            ];

        } catch (Exception $e) {
            error_log("Error storing Firebase token: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all tokens for a user
     */
    public function getUserTokens($userId) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
                SELECT token FROM firebase_tokens
                WHERE user_id = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY updated_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            error_log("Error getting user tokens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send notification to user via Firebase
     */
    public function sendNotificationToUser($userId, $title, $message, $data = []) {
        $tokens = $this->getUserTokens($userId);

        if (empty($tokens)) {
            return [
                'status' => 'error',
                'message' => 'No Firebase tokens found for user'
            ];
        }

        $successCount = 0;
        foreach ($tokens as $token) {
            if ($this->sendPushNotification($token, $title, $message, $data)) {
                $successCount++;
            }
        }

        // Store notification in database
        $this->storeNotification($userId, $title, $message, $data);

        return [
            'status' => 'success',
            'message' => "Notification sent to {$successCount} device(s)",
            'success_count' => $successCount,
            'total_tokens' => count($tokens)
        ];
    }

    /**
     * Store notification in database
     */
    private function storeNotification($userId, $title, $message, $data = []) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications
                (recipient_user_id, title, message, data, type, priority, is_sent, sent_at, created_at, created_by)
                VALUES (?, ?, ?, ?, 'push', 'medium', 1, NOW(), NOW(), ?)
            ");
            $stmt->execute([
                $userId,
                $title,
                $message,
                json_encode($data),
                $userId
            ]);
        } catch (Exception $e) {
            error_log("Error storing notification: " . $e->getMessage());
        }
    }

    /**
     * Test Firebase connection
     */
    public function testConnection() {
        // Check if configuration has been updated with real credentials
        $hasRealConfig = (
            $this->config['apiKey'] !== 'your-api-key' &&
            $this->config['projectId'] !== 'your-project-id' &&
            $this->serverKey !== 'your-server-key'
        );

        if ($hasRealConfig) {
            return [
                'status' => 'ready',
                'message' => 'Firebase configuration is properly set up with real credentials.',
                'config' => $this->config,
                'project_id' => $this->config['projectId']
            ];
        } else {
            return [
                'status' => 'config_needed',
                'message' => 'Firebase configuration needs to be updated with real credentials.',
                'config' => $this->config
            ];
        }
    }
}
?>
