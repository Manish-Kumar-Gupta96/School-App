<?php
require_once('config/database.php');
$stmt = $pdo->query("SHOW COLUMNS FROM permissions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
