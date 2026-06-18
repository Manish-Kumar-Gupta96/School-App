<?php
require_once(__DIR__ . '/../config/response.php');

$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($auth_header)) {
    // Check capitalization fallback
    $auth_header = isset($headers['authorization']) ? $headers['authorization'] : '';
}

if (empty($auth_header)) {
    error("Authorization Token Missing", 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$payload_json = base64_decode($token);
$data = json_decode($payload_json, true);

if (!$data || !isset($data['id']) || !isset($data['exp'])) {
    error("Invalid Authentication Token structure.", 401);
}

if (time() > $data['exp']) {
    error("Authentication Token has expired.", 401);
}

$userId = (int)$data['id'];
$roleId = isset($data['role']) ? (int)$data['role'] : 0;
$schoolId = isset($data['school_id']) ? (int)$data['school_id'] : 1;
?>
