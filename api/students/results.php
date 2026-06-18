<?php
require_once('../middleware/verify-token.php');
require_once('../../config/database.php');

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id <= 0) {
    error("Invalid Student ID parameter.");
}

try {
    $stmt = $pdo->prepare("SELECT exam_name, subject, marks, total_marks FROM results WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    success($results);
} catch (PDOException $e) {
    error("Database query failed: " . $e->getMessage(), 500);
}
?>
