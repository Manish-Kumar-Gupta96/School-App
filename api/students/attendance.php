<?php
require_once('../middleware/verify-token.php');
require_once('../../config/database.php');

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id <= 0) {
    error("Invalid Student ID parameter.");
}

try {
    $stmt = $pdo->prepare("SELECT attendance_date, status FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC");
    $stmt->execute([$student_id]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    success($attendance);
} catch (PDOException $e) {
    error("Database query failed: " . $e->getMessage(), 500);
}
?>
