<?php
require_once('auth.php');

if ($_SESSION['role_id'] != 4) {
    http_response_code(403);
    die("Access Denied: Teacher authorization required.");
}
?>
