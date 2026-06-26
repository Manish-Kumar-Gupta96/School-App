<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    die("Unauthorized Access");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)$_POST['teacher_id'];
    $class_id   = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $subject_id = (int)$_POST['subject_id'];
    $admin_id   = $_SESSION['admin_id'] ?? 1;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO teacher_class_subjects 
            (school_id, teacher_id, class_id, section_id, subject_id, assigned_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$schoolId, $teacher_id, $class_id, $section_id, $subject_id, $admin_id]);
        echo "Teacher Assigned Successfully";
    } catch(PDOException $e) {
        if($e->getCode() == 23000) { // Integrity constraint violation (Unique key)
            http_response_code(400);
            echo "Error: This teacher is already assigned to this class, section, and subject.";
        } else {
            http_response_code(500);
            echo "Database Error: " . $e->getMessage();
        }
    }
}
