<?php
/**
 * Firebase Cloud Messaging (FCM) Push Notification Service
 */
class FirebasePushService {
    
    private $pdo;
    
    // Server key from Firebase Console -> Cloud Messaging
    private $server_key = 'YOUR_FCM_SERVER_KEY_HERE';
    private $api_url = 'https://fcm.googleapis.com/fcm/send';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // In a real application, load this from .env
        if (file_exists(__DIR__ . '/../../.env')) {
            $env = parse_ini_file(__DIR__ . '/../../.env');
            if (!empty($env['FCM_SERVER_KEY'])) {
                $this->server_key = $env['FCM_SERVER_KEY'];
            }
        }
    }

    /**
     * Send Push Notification to a Specific User
     */
    public function sendToUser($user_id, $user_type, $title, $body, $data = []) {
        // Fetch user's registered device tokens
        $stmt = $this->pdo->prepare("SELECT fcm_token FROM device_tokens WHERE user_id = ? AND user_type = ?");
        $stmt->execute([$user_id, $user_type]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tokens)) {
            return false; // No registered devices for this user
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send Push Notification to Multiple Tokens
     */
    public function sendToTokens(array $tokens, $title, $body, $data = []) {
        $fields = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default'
            ],
            'data' => $data // Additional custom data (e.g., action links)
        ];

        return $this->executeCurl($fields);
    }

    /**
     * Send to a specific Topic (e.g., "all_parents", "class_10")
     */
    public function sendToTopic($topic, $title, $body, $data = []) {
        $fields = [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default'
            ],
            'data' => $data
        ];

        return $this->executeCurl($fields);
    }

    private function executeCurl($fields) {
        $headers = [
            'Authorization: key=' . $this->server_key,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            // Log curl error
            error_log('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }
}
?>
