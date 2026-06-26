<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    print_r($tables);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
