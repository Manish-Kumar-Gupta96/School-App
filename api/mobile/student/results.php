<?php
require_once('../../middleware/verify-token.php');
require_once('../../../config/database.php');

try {
    $stmt = $pdo->prepare("SELECT subject, marks, total_marks, exam_name FROM results WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    success($results);
} catch (PDOException $e) {
    error("Query failed: " . $e->getMessage(), 500);
}
?>
