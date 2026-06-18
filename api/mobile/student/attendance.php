<?php
require_once('../../middleware/verify-token.php');
require_once('../../../config/database.php');

try {
    $stmt = $pdo->prepare("SELECT attendance_date, status FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC");
    $stmt->execute([$userId]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    success($attendance);
} catch (PDOException $e) {
    error("Query failed: " . $e->getMessage(), 500);
}
?>
