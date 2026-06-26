<?php
session_start();
require_once('config/database.php');

if (isset($_SESSION['login_log_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE login_logs SET logout_time=NOW() WHERE id=?");
        $stmt->execute([$_SESSION['login_log_id']]);
    } catch (PDOException $e) {
        error_log("Logout log update failed: " . $e->getMessage());
    }
}

session_unset();
if (php_sapi_name() !== 'cli-server' || strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    session_destroy();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: login.php");
exit();
?>
