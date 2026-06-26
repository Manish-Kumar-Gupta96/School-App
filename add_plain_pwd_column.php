<?php
require_once('config/database.php');
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN plain_password VARCHAR(255) NULL AFTER password;");
    echo "Success adding column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
