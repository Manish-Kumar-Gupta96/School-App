<?php
session_start();
unset($_SESSION['teacher_id']);
unset($_SESSION['teacher_name']);
session_destroy();
header("Location: login.php");
exit;
?>
