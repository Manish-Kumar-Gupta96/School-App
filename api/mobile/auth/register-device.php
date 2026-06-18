<?php
require_once('../../middleware/verify-token.php');
require_once('../../../config/database.php');

$device_token = isset($_POST['device_token']) ? trim($_POST['device_token']) : '';
$device_type = isset($_POST['device_type']) ? trim($_POST['device_type']) : 'ANDROID';

if (empty($device_token)) {
    error("Device token is required.");
}

try {
    $stmt = $pdo->prepare("INSERT INTO mobile_devices (user_id, device_token, device_type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $device_token, $device_type]);
    success(["message" => "Device Registered successfully."]);
} catch (PDOException $e) {
    error("Registration failed: " . $e->getMessage(), 500);
}
?>
