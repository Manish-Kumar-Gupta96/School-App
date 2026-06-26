<?php
require_once 'config/database.php';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->exec("UPDATE users SET password = '$hash', force_password_change=0 WHERE email = 'superadmin@vicschool.edu.in'");
echo "Password reset to admin123";
