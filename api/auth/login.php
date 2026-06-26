<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once('../../config/database.php');
require_once('../middleware/JwtAuth.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JwtAuth::jsonError("Method not allowed", 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['password'])) {
    JwtAuth::jsonError("Email and password are required");
}

$email = trim($data['email']);
$password = $data['password'];
$device_id = $data['device_id'] ?? null;
$fcm_token = $data['fcm_token'] ?? null;
$platform = $data['platform'] ?? 'android';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'ACTIVE'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $role_name = 'guest';
        $role_specific_id = null;

        if ($user['role_id'] == 1 || $user['role_id'] == 2) {
            $role_name = 'admin';
            $stmt_adm = $pdo->prepare("SELECT id, name FROM admins WHERE email = ?");
            $stmt_adm->execute([$user['email']]);
            $specific = $stmt_adm->fetch(PDO::FETCH_ASSOC);
            $role_specific_id = $specific['id'] ?? null;
            $name = $specific['name'] ?? 'Admin';
        } else if ($user['role_id'] == 4) {
            $role_name = 'teacher';
            $stmt_tch = $pdo->prepare("SELECT id, name FROM teachers WHERE email = ?");
            $stmt_tch->execute([$user['email']]);
            $specific = $stmt_tch->fetch(PDO::FETCH_ASSOC);
            $role_specific_id = $specific['id'] ?? null;
            $name = $specific['name'] ?? 'Teacher';
        } else if ($user['role_id'] == 7) {
            $role_name = 'parent';
            $stmt_prn = $pdo->prepare("SELECT id, father_name as name FROM parents WHERE email = ?");
            $stmt_prn->execute([$user['email']]);
            $specific = $stmt_prn->fetch(PDO::FETCH_ASSOC);
            $role_specific_id = $specific['id'] ?? null;
            $name = $specific['name'] ?? 'Parent';
        } else if ($user['role_id'] == 8) {
            $role_name = 'student';
            $stmt_std = $pdo->prepare("SELECT id, first_name as name FROM students WHERE email = ?");
            $stmt_std->execute([$user['email']]);
            $specific = $stmt_std->fetch(PDO::FETCH_ASSOC);
            $role_specific_id = $specific['id'] ?? null;
            $name = $specific['name'] ?? 'Student';
        }

        if (!$role_specific_id) {
            JwtAuth::jsonError("Profile configuration incomplete", 403);
        }

        // Generate JWT Payload
        $payload = [
            'user_id' => $user['id'],
            'role_specific_id' => $role_specific_id,
            'role' => $role_name,
            'email' => $user['email'],
            'name' => $name
        ];

        // Token expires in 30 days for mobile apps
        $token = JwtAuth::generateToken($payload, 86400 * 30);

        // Store Token in user_tokens
        $expiry_date = date('Y-m-d H:i:s', time() + (86400 * 30));
        $pdo->prepare("INSERT INTO user_tokens (user_id, user_type, token, expires_at) VALUES (?, ?, ?, ?)")
            ->execute([$user['id'], $role_name, $token, $expiry_date]);

        // Register FCM Device Token if provided
        if ($fcm_token && $device_id) {
            $stmt_dev = $pdo->prepare("
                INSERT INTO device_tokens (user_id, user_type, device_id, fcm_token, platform) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE fcm_token = ?, platform = ?, last_active = CURRENT_TIMESTAMP
            ");
            $stmt_dev->execute([$user['id'], $role_name, $device_id, $fcm_token, $platform, $fcm_token, $platform]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'user' => $payload
        ]);
    } else {
        JwtAuth::jsonError("Invalid credentials", 401);
    }
} catch (PDOException $e) {
    JwtAuth::jsonError("Server error: " . $e->getMessage(), 500);
}
?>
