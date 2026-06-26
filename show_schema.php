<?php
require_once 'config/database.php';
$stmt = $pdo->query("SHOW CREATE TABLE users");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
$stmt = $pdo->query("SHOW CREATE TABLE permissions");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
