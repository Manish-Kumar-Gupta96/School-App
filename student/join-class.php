<?php
require_once('../config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure logged-in student
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($class_id <= 0) {
    die("Invalid class request.");
}

try {
    // 1. Fetch Student class/section targets to verify enrollment
    $stmt_s = $pdo->prepare("SELECT class, section FROM students WHERE id = ?");
    $stmt_s->execute([$student_id]);
    $res = $stmt_s->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        die("Student profile not found.");
    }

    $student_class = $res['class'] ?? '';
    $student_section = $res['section'] ?? '';

    // Resolve class_id and section_id from strings
    $clean_class_name = $student_class;
    if (preg_match('/^([^-]+)-[A-Z]$/', $student_class, $m)) {
        $clean_class_name = $m[1]; // '10'
    }

    $stmt_c_id = $pdo->prepare("SELECT id FROM classes WHERE class_name = ? OR class_name = ?");
    $stmt_c_id->execute([$clean_class_name, $student_class]);
    $student_class_id = (int)$stmt_c_id->fetchColumn();

    $stmt_s_id = $pdo->prepare("SELECT id FROM sections WHERE section_name = ?");
    $stmt_s_id->execute([$student_section]);
    $student_section_id = (int)$stmt_s_id->fetchColumn();

    // 2. Fetch class target details
    $stmt_class = $pdo->prepare("SELECT * FROM online_classes WHERE id = ?");
    $stmt_class->execute([$class_id]);
    $class = $stmt_class->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        die("Scheduled class not found.");
    }

    // Verify student belongs to this class + section
    if ((int)$class['class_id'] !== $student_class_id || (int)$class['section_id'] !== $student_section_id) {
        die("Access Denied: You are not enrolled in this target classroom.");
    }

    // 3. Log join attendance
    $stmt_att = $pdo->prepare("
        INSERT INTO online_class_attendance (class_id, student_id, join_time, leave_time, duration)
        VALUES (?, ?, NOW(), NULL, NULL)
        ON DUPLICATE KEY UPDATE join_time = NOW(), leave_time = NULL, duration = NULL
    ");
    $stmt_att->execute([$class_id, $student_id]);

    // 4. Redirect to live stream
    if (!empty($class['meeting_link'])) {
        header("Location: " . $class['meeting_link']);
        exit;
    } else {
        die("Live meeting link is not available yet.");
    }

} catch (PDOException $e) {
    die("System database error: " . $e->getMessage());
}
?>
