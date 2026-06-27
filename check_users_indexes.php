<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    echo "<h3>Analyzing Database Foreign Keys & Indices Constraints...</h3>";

    // Array of key indices to evaluate and map
    $optimizations = [
        ['table' => 'users', 'column' => 'username', 'index_name' => 'idx_users_username', 'type' => 'UNIQUE'],
        ['table' => 'attendance', 'column' => 'student_id', 'index_name' => 'idx_attendance_student', 'type' => 'INDEX'],
        ['table' => 'attendance', 'column' => 'date', 'index_name' => 'idx_attendance_date', 'type' => 'INDEX'],
        ['table' => 'fees', 'column' => 'student_id', 'index_name' => 'idx_fees_student', 'type' => 'INDEX']
    ];

    foreach ($optimizations as $opt) {
        // Verify if index already exists to avoid redundant statement compilation
        $checkStmt = $db->prepare("SHOW INDEX FROM `{$opt['table']}` WHERE Key_name = :idx");
        $checkStmt->execute([':idx' => $opt['index_name']]);
        
        if (!$checkStmt->fetch()) {
            if ($opt['type'] === 'UNIQUE') {
                $db->exec("ALTER TABLE `{$opt['table']}` ADD UNIQUE `{$opt['index_name']}` (`{$opt['column']}`);");
            } else {
                $db->exec("ALTER TABLE `{$opt['table']}` ADD INDEX `{$opt['index_name']}` (`{$opt['column']}`);");
            }
            echo "<p style='color:green;'>✔ Index '{$opt['index_name']}' successfully compiled on table '{$opt['table']}'.</p>";
        } else {
            echo "<p style='color:orange;'>ℹ Index '{$opt['index_name']}' is already present. Skipping optimization.</p>";
        }
    }
    echo "<strong>Database optimizations verified successfully for XAMPP deployment.</strong>";
} catch (PDOException $e) {
    die("<p style='color:red;'>Optimization Execution Interrupted: " . $e->getMessage() . "</p>");
}
?>
