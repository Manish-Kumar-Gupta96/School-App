<?php
// teacher/send_message.php
require_once('../config/database.php');
session_start();

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(403);
    echo json_encode(['status' => false, 'error' => 'Unauthorized']);
    exit;
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$conversation_id = (int)$_POST['conversation_id'];
$message = trim($_POST['message'] ?? '');
$message_type = 'text';
$attachment = null;

// Handle file upload if present
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/chat/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileInfo = pathinfo($_FILES['file']['name']);
    $extension = strtolower($fileInfo['extension']);
    
    $filename = time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $attachment = 'uploads/chat/' . $filename;
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $message_type = 'image';
        } elseif ($extension === 'pdf') {
            $message_type = 'pdf';
        } elseif (in_array($extension, ['mp3', 'wav', 'ogg'])) {
            $message_type = 'voice';
        }
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages 
        (school_id, conversation_id, sender_id, sender_role, message_type, message, attachment) 
        VALUES (?, ?, ?, 'teacher', ?, ?, ?)
    ");
    $stmt->execute([$schoolId, $conversation_id, $_SESSION['teacher_id'], $message_type, $message, $attachment]);
    
    echo json_encode(['status' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}
