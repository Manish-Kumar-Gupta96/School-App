<?php
require_once 'config/database.php';
$stmt = $pdo->query("SHOW CREATE TABLE users");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo $res['Create Table'];
