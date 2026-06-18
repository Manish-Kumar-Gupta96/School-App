<?php
require_once('../../middleware/verify-token.php');
require_once('../../../config/database.php');

$device_token = isset($_POST['device_token']) ? trim($_POST['device_token']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($device_token) || empty($title) || empty($message)) {
    error("Device token, Title, and Message are required parameters.");
}

try {
    // Log the notification in push_notifications table
    $stmt = $pdo->prepare("INSERT INTO push_notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $message]);

    // Simulated FCM response structure
    $fcm_payload = [
        "to" => $device_token,
        "notification" => [
            "title" => $title,
            "body" => $message
        ]
    ];

    success([
        "message" => "Push notification simulated dispatched successfully.",
        "fcm_payload" => $fcm_payload
    ]);
} catch (PDOException $e) {
    error("Failed to dispatch simulation push: " . $e->getMessage(), 500);
}
?>
