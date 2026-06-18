<?php
require_once('auth.php');

if ($_SESSION['role_id'] != 7) {
    http_response_code(403);
    die("Access Denied: Parent authorization required.");
}
?>
