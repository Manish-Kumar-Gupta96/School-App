<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0 AFTER password");
    // Also we should drop plain_password or just stop using it. Let's drop it for security.
    $pdo->exec("ALTER TABLE users DROP COLUMN plain_password");
    echo "Successfully altered users table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
