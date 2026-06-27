<?php
// Strict memory limit extensions to process massive database dumps flawlessly
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SessionManager.php';

// Detect context trigger: can run via CLI console directly (cron runner) or active auth admins session
if (php_sapi_name() !== 'cli') {
    SessionManager::startSecureSession();
    if (($_SESSION['user_role'] ?? '') !== 'superadmin' && ($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die("Operational backup access violation.");
    }
}

$backupDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Named matching standard timestamp signatures
$backupFileName = 'backup_vic_school_' . date('Y-m-d_H-i-s') . '.sql';
$backupFilePath = $backupDir . DIRECTORY_SEPARATOR . $backupFileName;

try {
    $db = getDBConnection();
    $sqlDump = "-- School Management System SQL Dump\n";
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // 1. Fetch all tables from current scope
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        // 2. Compile raw Table Create Statements structural components
        $createTableStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $sqlDump .= "\n\n-- Structure for table: {$table}\n";
        $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sqlDump .= $createTableStmt['Create Table'] . ";\n\n";

        // 3. Compile matching row structural data values
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $sqlDump .= "-- Data inserts for table: {$table}\n";
            foreach ($rows as $row) {
                $columns = array_map(function($col) { return "`{$col}`"; }, array_keys($row));
                $values = array_map(function($val) use ($db) {
                    if ($val === null) return "NULL";
                    return $db->quote($val);
                }, array_values($row));

                $sqlDump .= "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
        }
    }

    $sqlDump .= "\n\nSET FOREIGN_KEY_CHECKS=1;\n";

    // 4. Save file payload to server system paths
    file_put_contents($backupFilePath, $sqlDump);
    
    if (php_sapi_name() === 'cli') {
        echo "Database backup snapshot successful: {$backupFileName}\n";
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => true, 'message' => 'System structural state snapshot compiled successfully.', 'file' => $backupFileName]);
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "CRITICAL BACKUP FAILED: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Backup Compilation Exception: ' . $e->getMessage()]);
    }
}
?>
