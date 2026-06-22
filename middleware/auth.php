<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $execScript = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']);
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    if (strpos(strtolower($execScript), strtolower($projectRoot)) === 0) {
        $subpath = substr($execScript, strlen($projectRoot));
        $dirCount = substr_count(trim($subpath, '/'), '/');
        $backPath = str_repeat('../', $dirCount);
        header("Location: " . $backPath . "auth/login.php");
    } else {
        header("Location: /auth/login.php");
    }
    exit;
}
?>
