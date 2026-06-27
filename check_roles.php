<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT id, email, role_id FROM users LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query("SELECT * FROM roles");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
