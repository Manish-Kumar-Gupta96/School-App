<?php
require_once __DIR__ . '/permission.php';

function requirePermission($pdo, $permission)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }

    if (!hasPermission($pdo, $_SESSION['user_id'], $permission)) {
        // Redirect to a 403 page or dashboard with error
        header("Location: /index.php?error=access_denied");
        exit();
    }
}

function requireRole($role)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role'])) {
        header("Location: /login.php");
        exit();
    }

    if ($_SESSION['role'] !== $role) {
        header("Location: /index.php?error=access_denied");
        exit();
    }
}
