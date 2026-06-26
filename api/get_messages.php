<?php
// api/get_messages.php
require_once('../config/database.php');

header('Content-Type: application/json');

if (!isset($_GET['conversation_id'])) {
    echo json_encode([]);
    exit;
}

$conversation_id = (int)$_GET['conversation_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

try {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM messages 
        WHERE conversation_id = ? AND school_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id, $schoolId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
