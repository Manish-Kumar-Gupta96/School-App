<?php
require_once('config/database.php');
$stmt = $pdo->query("SHOW TRIGGERS");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Triggers created: " . count($res) . "\n";
print_r(array_column($res, 'Trigger'));

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
echo "Users count: " . $stmt->fetchColumn() . "\n";
?>
