<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('../../config/database.php');
require_once('../middleware/JwtAuth.php');

// Protect route and get user payload
$user = JwtAuth::protectRoute($pdo);

if ($user['role'] !== 'admin') {
    JwtAuth::jsonError("Access denied. Admin only.", 403);
}

$admin_id = $user['role_specific_id'];

try {
    // 1. Fetch Key Stats
    $total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
    $total_teachers = $pdo->query("SELECT COUNT(*) FROM teachers WHERE status = 'Active'")->fetchColumn();
    
    // Revenue (Total fees collected today)
    $today_revenue = $pdo->query("SELECT SUM(amount) FROM fee_transactions WHERE DATE(payment_date) = CURDATE() AND status = 'Success'")->fetchColumn() ?: 0;
    
    // Admissions CRM Leads (New leads today)
    $new_leads = $pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'new'")->fetchColumn();

    // Today's Staff Attendance Summary
    $present_staff = $pdo->query("SELECT COUNT(*) FROM staff_attendance WHERE date = CURDATE() AND status = 'present'")->fetchColumn() ?: 0;

    // Build Response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'profile' => [
                'name' => $user['name']
            ],
            'stats' => [
                'total_students' => (int)$total_students,
                'total_teachers' => (int)$total_teachers,
                'today_revenue' => (float)$today_revenue,
                'new_leads' => (int)$new_leads,
                'present_staff_today' => (int)$present_staff
            ]
        ]
    ]);

} catch (PDOException $e) {
    JwtAuth::jsonError("Database Error: " . $e->getMessage(), 500);
}
?>
