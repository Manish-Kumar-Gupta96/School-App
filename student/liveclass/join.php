<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Assuming the user is a logged-in student
if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied. Only students can join live classes.");
}

$student_id = $_SESSION['student_id'];
$live_class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($live_class_id <= 0) {
    die("Invalid Live Class ID.");
}

// Get Student details to verify class/section
$stmt_stu = $pdo->prepare("SELECT class_id, section_id FROM students WHERE id = ?");
$stmt_stu->execute([$student_id]);
$student = $stmt_stu->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student record not found.");
}

// Get Live Class details
$stmt_class = $pdo->prepare("SELECT * FROM live_classes WHERE id = ? AND class_id = ? AND section_id = ?");
$stmt_class->execute([$live_class_id, $student['class_id'], $student['section_id']]);
$live_class = $stmt_class->fetch(PDO::FETCH_ASSOC);

if (!$live_class) {
    die("You are not assigned to this class or the class doesn't exist.");
}

// Calculate Attendance
$now = new DateTime();
$start_time = new DateTime($live_class['start_time']);

// Joined before 10 mins after start -> Present. Else -> Late.
// If it's way too early (e.g., > 1 hour before), we might still allow joining but mark present.
$diff_minutes = ($now->getTimestamp() - $start_time->getTimestamp()) / 60;
$attendance_status = 'present';
if ($diff_minutes > 10) {
    $attendance_status = 'late';
}

// Log attendance
$stmt_att = $pdo->prepare("SELECT id FROM live_class_attendance WHERE live_class_id = ? AND student_id = ?");
$stmt_att->execute([$live_class_id, $student_id]);
$existing_att = $stmt_att->fetchColumn();

if (!$existing_att) {
    // Insert new attendance record
    $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
    $stmt_insert = $pdo->prepare("
        INSERT INTO live_class_attendance (school_id, live_class_id, student_id, join_time, attendance_status)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt_insert->execute([$schoolId, $live_class_id, $student_id, $attendance_status]);
} else {
    // Optionally update join time if needed, but usually we keep the first join time.
}

// Redirect to meeting link
header("Location: " . $live_class['meeting_link']);
exit();
