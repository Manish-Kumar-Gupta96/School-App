<?php
require_once 'config/database.php';

$tables = ['roles', 'permissions', 'role_permissions', 'user_permissions', 'login_logs'];

foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Table $t:\n";
        echo $res['Create Table'] . "\n\n";
    } catch (PDOException $e) {
        echo "Table $t error: " . $e->getMessage() . "\n\n";
    }
}
