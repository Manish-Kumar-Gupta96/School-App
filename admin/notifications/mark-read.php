<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notification_recipients 
            SET status = 'READ', read_at = NOW() 
            WHERE user_type = 'ADMIN' AND status = 'PENDING'
        ");
        $stmt->execute();
    } catch (PDOException $e) {
        // Ignore
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';
header("Location: " . $referer);
exit;
?>
