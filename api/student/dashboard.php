<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('../../config/database.php');
require_once('../middleware/JwtAuth.php');

// Protect route and get user payload
$user = JwtAuth::protectRoute($pdo);

if ($user['role'] !== 'student') {
    JwtAuth::jsonError("Access denied. Student only.", 403);
}

$student_id = $user['role_specific_id'];

try {
    // 1. Fetch Student Details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        JwtAuth::jsonError("Student profile not found", 404);
    }

    $class = $student['class'];
    $section = $student['section'];

    // 2. Attendance Summary
    $stmt_att = $pdo->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
        FROM attendance 
        WHERE student_id = ?
    ");
    $stmt_att->execute([$student_id]);
    $attendance = $stmt_att->fetch(PDO::FETCH_ASSOC);
    $attendance_percentage = $attendance['total_days'] > 0 ? round(($attendance['present_days'] / $attendance['total_days']) * 100, 2) : 0;

    // 3. Upcoming Live Classes
    $c_name = $class;
    $s_name = $section;
    if (preg_match('/^([^-]+)-([A-Z])$/', $class, $m)) {
        $c_name = $m[1];
        $s_name = $m[2];
    }
    
    $stmt_classes = $pdo->prepare("
        SELECT oc.*, t.name as teacher_name 
        FROM online_classes oc 
        LEFT JOIN teachers t ON oc.teacher_id = t.id 
        WHERE (oc.class_name = ? OR oc.class_name = ?) 
          AND oc.status IN ('scheduled', 'UPCOMING', 'LIVE')
        ORDER BY oc.start_time ASC LIMIT 3
    ");
    $stmt_classes->execute([$class, $c_name]);
    $live_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // 4. Pending Assignments / Homework
    $stmt_hw = $pdo->prepare("
        SELECT h.*, s.subject_name 
        FROM homework h
        LEFT JOIN subjects s ON h.subject_id = s.id
        WHERE (h.class = ? OR h.class = ?) AND h.due_date >= CURDATE()
        ORDER BY h.due_date ASC LIMIT 5
    ");
    $stmt_hw->execute([$class, $c_name]);
    $homework = $stmt_hw->fetchAll(PDO::FETCH_ASSOC);

    // Build Response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'profile' => [
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'class' => $class,
                'section' => $section,
                'roll_number' => $student['roll_number']
            ],
            'attendance' => [
                'percentage' => $attendance_percentage,
                'present' => $attendance['present_days'] ?? 0,
                'absent' => $attendance['absent_days'] ?? 0
            ],
            'live_classes' => $live_classes,
            'homework' => $homework
        ]
    ]);

} catch (PDOException $e) {
    JwtAuth::jsonError("Database Error: " . $e->getMessage(), 500);
}
?>
