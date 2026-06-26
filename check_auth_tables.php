<?php
require_once('config/database.php');
$tables = ['users', 'admins', 'teachers', 'students', 'parents'];
foreach($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $t");
        echo "$t:\n";
        print_r(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field'));
    } catch (Exception $e) {
        echo "Table $t not found.\n";
    }
}
?>
