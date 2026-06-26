<?php
require_once('config/database.php');
$stmt = $pdo->query("SHOW INDEX FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
