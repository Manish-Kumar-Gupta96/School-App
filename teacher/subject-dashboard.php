<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../config/database.php';

new BaseController();
BaseController::enforceRole(['teacher']);

$db = getDBConnection();
// Naye query structure filter check implementation loop standard:
$teacherId = $_SESSION['user_id']; 
$targetClass = 'Class 10';
$targetSection = 'Sec-A';

try {
    $query = "SELECT s.id, s.name, s.roll_no 
              FROM students s
              JOIN unified_academic_mapping uam ON s.class_name = uam.class_name
              WHERE uam.teacher_id = :tid 
              AND s.class_name = :class 
              AND s.section_name = :sec
              AND s.verification_status = 'ACTIVE'"; // Verified & Connected Auto Filter State
              
    $stmt = $db->prepare($query);
    $stmt->execute([':tid' => $teacherId, ':class' => $targetClass, ':sec' => $targetSection]);
    $myClassStudents = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Data Integration Fault: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Subject Teacher Dashboard - <?php echo htmlspecialchars($targetClass . ' ' . $targetSection); ?></h3>
    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Roll No</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($myClassStudents)): ?>
                <tr>
                    <td colspan="3" class="text-center">No active students found in this class yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($myClassStudents as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
