<?php
require_once('../middleware/verify-token.php');
require_once('../../config/database.php');

try {
    $stmt = $pdo->prepare("SELECT id, roll_no, first_name, last_name, gender, class, section, email FROM students WHERE school_id = ?");
    $stmt->execute([$schoolId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    success($students);
} catch (PDOException $e) {
    error("Database query failed: " . $e->getMessage(), 500);
}
?>
