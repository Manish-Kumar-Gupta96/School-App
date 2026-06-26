<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('../../config/database.php');
require_once('../middleware/JwtAuth.php');

// Protect route and get user payload
$user = JwtAuth::protectRoute($pdo);

if ($user['role'] !== 'teacher') {
    JwtAuth::jsonError("Access denied. Teacher only.", 403);
}

$teacher_id = $user['role_specific_id'];

try {
    // 1. Fetch Teacher Details
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        JwtAuth::jsonError("Teacher profile not found", 404);
    }

    // 2. Today's Live Classes
    $stmt_classes = $pdo->prepare("
        SELECT * FROM online_classes 
        WHERE teacher_id = ? 
          AND DATE(start_time) = CURDATE()
        ORDER BY start_time ASC
    ");
    $stmt_classes->execute([$teacher_id]);
    $todays_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Pending Homework Submissions to Grade
    $stmt_hw = $pdo->prepare("
        SELECT hs.id as submission_id, h.title as homework_title, h.class, 
               s.first_name, s.last_name, hs.submitted_at
        FROM homework_submissions hs
        JOIN homework h ON hs.homework_id = h.id
        JOIN students s ON hs.student_id = s.id
        WHERE h.teacher_id = ? AND hs.status = 'submitted'
        ORDER BY hs.submitted_at DESC LIMIT 5
    ");
    $stmt_hw->execute([$teacher_id]);
    $pending_grading = $stmt_hw->fetchAll(PDO::FETCH_ASSOC);

    // Build Response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'profile' => [
                'name' => $teacher['name'],
                'department' => $teacher['department'],
                'phone' => $teacher['phone']
            ],
            'todays_classes' => $todays_classes,
            'pending_grading' => $pending_grading
        ]
    ]);

} catch (PDOException $e) {
    JwtAuth::jsonError("Database Error: " . $e->getMessage(), 500);
}
?>
