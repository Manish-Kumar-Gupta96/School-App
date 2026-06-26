<?php
header('Content-Type: text/plain');
require 'config/database.php';
try {
    $q = $pdo->query("SELECT DISTINCT module_name FROM audit_logs WHERE module_name IS NOT NULL AND module_name != ''");
    echo "SUCCESS: " . count($q->fetchAll()) . " modules found.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
