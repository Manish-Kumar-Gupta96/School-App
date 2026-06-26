<?php
header('Content-Type: text/plain');
require 'config/database.php';
echo "DB Host: " . (getenv('DB_HOST') ?: "127.0.0.1") . "\n";
echo "DB Name: " . (getenv('DB_NAME') ?: "vic_school") . "\n";
echo "DB User: " . (getenv('DB_USER') ?: "root") . "\n";

try {
    $q = $pdo->query("DESCRIBE audit_logs");
    echo "Columns seen by web server:\n";
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
