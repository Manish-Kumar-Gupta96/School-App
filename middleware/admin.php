<?php
require_once('auth.php');

// Admin roles: 1 (Super Admin), 2 (Admin)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    http_response_code(403);
    die("Access Denied: Admin authorization required. (Current Role ID: " . ($_SESSION['role_id'] ?? 'None') . ")");
}
?>
