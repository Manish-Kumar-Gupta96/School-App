<?php
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$domain = explode(':', $http_host)[0];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (!empty($origin)) {
    $parsed_url = parse_url($origin);
    $origin_host = $parsed_url['host'] ?? '';
    if ($origin_host === 'localhost' || $origin_host === '127.0.0.1' || $origin_host === $domain || str_ends_with($origin_host, '.' . $domain)) {
        header("Access-Control-Allow-Origin: " . $origin);
    } else {
        header("Access-Control-Allow-Origin: http://" . $domain);
    }
} else {
    header("Access-Control-Allow-Origin: http://" . $domain);
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once('../../config/database.php');

// Get POST body
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password) && !empty($data->role)) {
    $email = $data->email;
    $password = $data->password;
    $role = strtolower($data->role);

    $user = null;
    $table = "";
    
    if ($role === 'admin') {
        $table = "admins";
    } elseif ($role === 'teacher') {
        $table = "teachers";
    } elseif ($role === 'student') {
        $table = "students";
    } elseif ($role === 'parent') {
        $table = "parents";
    }

    if (!empty($table)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            // Note: Since students/teachers/parents tables in the seed/legacy system may or may not use hashed passwords, we verify using password_verify OR fallback to plain verification
            $authenticated = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $authenticated = true;
                } else if ($password === $user['password']) { // Fallback for plain-text seeding
                    $authenticated = true;
                }
            }

            if ($authenticated) {
                // Mock JWT Token generation
                $token = base64_encode(json_encode([
                    "id" => $user['id'],
                    "role" => $role,
                    "email" => $user['email'],
                    "school_id" => $user['school_id'] ?? 1,
                    "exp" => time() + 3600
                ]));

                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful",
                    "token" => "Bearer " . $token,
                    "user" => [
                        "id" => $user['id'],
                        "name" => $user['name'] ?? ($user['first_name'] . ' ' . $user['last_name'] ?? ($user['father_name'] ?? 'User')),
                        "email" => $user['email'],
                        "role" => $role,
                        "school_id" => $user['school_id'] ?? 1
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid role specified."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Incomplete request parameters."]);
}
?>
