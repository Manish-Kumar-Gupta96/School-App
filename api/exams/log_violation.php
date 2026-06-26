<?php
require_once('../../config/database.php');

// Enable error reporting for debug, usually turned off in prod for APIs
ini_set('display_errors', 0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit();
}

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$violation_type = isset($_POST['violation_type']) ? trim($_POST['violation_type']) : '';

if ($exam_id > 0 && $student_id > 0 && !empty($violation_type)) {
    try {
        $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO exam_violations (school_id, exam_id, student_id, violation_type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$schoolId, $exam_id, $student_id, $violation_type]);
        
        echo json_encode(['status' => 'success', 'message' => 'Logged']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
}
