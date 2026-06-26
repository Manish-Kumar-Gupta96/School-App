<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('../../config/database.php');
require_once('../middleware/JwtAuth.php');

// Protect route and get user payload
$user = JwtAuth::protectRoute($pdo);

if ($user['role'] !== 'parent') {
    JwtAuth::jsonError("Access denied. Parent only.", 403);
}

$parent_id = $user['role_specific_id'];

try {
    // 1. Fetch Parent Details
    $stmt = $pdo->prepare("SELECT * FROM parents WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        JwtAuth::jsonError("Parent profile not found", 404);
    }

    // 2. Fetch Linked Students
    $stmt_students = $pdo->prepare("
        SELECT s.id, s.first_name, s.last_name, s.class, s.section, s.roll_number 
        FROM students s
        JOIN parent_student_map psm ON s.id = psm.student_id
        WHERE psm.parent_id = ?
    ");
    $stmt_students->execute([$user['user_id']]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // 3. Collect Data per Student
    $children_data = [];
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        // Attendance Summary
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

        // Pending Fees Summary
        $stmt_fees = $pdo->prepare("
            SELECT SUM(due_amount) as total_due 
            FROM student_fee_invoices 
            WHERE student_id = ? AND status != 'Paid'
        ");
        $stmt_fees->execute([$student_id]);
        $fees_due = $stmt_fees->fetchColumn() ?: 0;

        $children_data[] = [
            'profile' => $student,
            'attendance_percentage' => $attendance_percentage,
            'fees_due' => (float)$fees_due
        ];
    }

    // Build Response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'profile' => [
                'father_name' => $parent['father_name'],
                'mother_name' => $parent['mother_name'],
                'phone' => $parent['father_phone']
            ],
            'children' => $children_data
        ]
    ]);

} catch (PDOException $e) {
    JwtAuth::jsonError("Database Error: " . $e->getMessage(), 500);
}
?>
