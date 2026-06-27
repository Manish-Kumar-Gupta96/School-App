<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SessionManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

SessionManager::startSecureSession();

// Restrict entry exclusively to Authorized roles (Admins and Teachers)
$currentRole = $_SESSION['user_role'] ?? null;
if (!in_array($currentRole, ['admin', 'superadmin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Access Denied. Insufficient operational scope.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Only POST requests are evaluated.']);
    exit;
}

// Extract properties out of input payloads
$classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$topic = filter_input(INPUT_POST, 'topic', FILTER_SANITIZE_SPECIAL_CHARS);
$startTime = $_POST['start_time'] ?? '';
$platform = filter_input(INPUT_POST, 'platform', FILTER_SANITIZE_SPECIAL_CHARS); // ZOOM or GOOGLE_MEET
$meetingUrl = filter_input(INPUT_POST, 'meeting_url', FILTER_VALIDATE_URL);

if (!$classId || empty($topic) || empty($startTime) || !$meetingUrl || !in_array($platform, ['ZOOM', 'GOOGLE_MEET'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Malformed dataset. Please verify URLs and tracking metrics.']);
    exit;
}

try {
    $db = getDBConnection();
    
    $query = "INSERT INTO live_classes (class_id, teacher_id, topic, start_time, platform, meeting_url, status, created_at) 
              VALUES (:class_id, :teacher_id, :topic, :start_time, :platform, :meeting_url, 'SCHEDULED', NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':class_id'   => $classId,
        ':teacher_id' => $_SESSION['user_id'],
        ':topic'      => $topic,
        ':start_time' => $startTime,
        ':platform'   => $platform,
        ':meeting_url'=> $meetingUrl
    ]);

    // Track state change inside immutable database records
    AuditLogger::log('LIVE_CLASS_CREATED', "Topic: {$topic} scheduled via platform: {$platform}");

    http_response_code(201);
    echo json_encode(['status' => true, 'message' => 'Live class session synchronized successfully. Notification triggers initialized.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Database Sync Fault: ' . $e->getMessage()]);
}
?>
