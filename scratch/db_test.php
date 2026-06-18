<?php
require_once __DIR__ . '/../config/database.php';

echo "USERS WITH HASHED PASSWORDS:\n";
try {
    $users = $pdo->query("SELECT id, name, email, password, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo " - Name: {$u['name']}, Email: {$u['email']}, Hashed Password: {$u['password']}, Role ID: {$u['role_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
