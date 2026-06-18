<?php
session_start();
require_once('../config/database.php');

if (isset($_SESSION['user_id'])) {
    try {
        // Log logout audit
        $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt_audit->execute([$_SESSION['user_id'], "Logout Successful", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (PDOException $e) {
        // Fail silently
    }
}

// Unset session and destroy
session_unset();
session_destroy();

header("Location: login.php");
exit;
?>
