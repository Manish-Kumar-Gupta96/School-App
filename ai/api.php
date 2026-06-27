<?php
require_once __DIR__ . '/../includes/SessionManager.php';
require_once __DIR__ . '/../includes/Csrf.php';

SessionManager::startSecureSession();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Read raw JSON input from assistant.js or fetch calls
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($inputData['message'] ?? '');

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Message content cannot be empty.']);
    exit;
}

// Python AI Server Endpoint (Running locally or internally)
$pythonAIUrl = 'http://127.0.0.1:5000/api/chat'; 

$postData = json_encode([
    'message' => htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8'),
    'user_id' => $_SESSION['user_id'] ?? 'guest',
    'role' => $_SESSION['user_role'] ?? 'guest'
]);

// cURL configuration to request Python AI Backend safely
$ch = curl_init($pythonAIUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $errorMsg = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'AI Service temporarily unavailable.', 'error' => $errorMsg]);
    exit;
}

curl_close($ch);

// Return the exact response from AI model down to frontend
http_response_code($httpCode);
echo $response;
?>
