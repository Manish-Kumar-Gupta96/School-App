<?php
require_once 'config/database.php';
require_once 'helpers/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = isset($_POST['student_name']) ? sanitize($_POST['student_name']) : '';
    $parent_name = isset($_POST['parent_name']) ? sanitize($_POST['parent_name']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $class_interested = isset($_POST['class_interested']) ? sanitize($_POST['class_interested']) : '';
    $message = isset($_POST['message']) ? sanitize($_POST['message']) : '';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO crm_leads (
                student_name,
                parent_name,
                phone,
                class_interested,
                message,
                name,
                class_applied,
                source,
                status,
                lead_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Website', 'new', 'website')
        ");

        $stmt->execute([
            $student_name,
            $parent_name,
            $phone,
            $class_interested,
            $message,
            $student_name, // legacy column name mapping
            $class_interested // legacy column name mapping
        ]);
    } catch (PDOException $e) {
        error_log("Failed to save CRM lead: " . $e->getMessage());
    }
}

header("Location: thank-you.php");
exit;
?>
