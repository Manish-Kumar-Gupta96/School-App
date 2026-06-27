<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../config/database.php';

new BaseController();
BaseController::enforceRole(['parent', 'student']);

$db = getDBConnection();

// Dynamic extraction of student linked session metrics
$studentId = $_SESSION['user_id']; 

try {
    // 1. Fetch Aggregated Statistics Counts
    $statQuery = "SELECT 
                    COUNT(CASE WHEN status = 'P' THEN 1 END) as presents,
                    COUNT(CASE WHEN status = 'A' THEN 1 END) as absents,
                    COUNT(CASE WHEN status = 'L' THEN 1 END) as leaves,
                    COUNT(*) as total_days
                  FROM student_attendance WHERE student_id = :sid";
                  
    $stmt = $db->prepare($statQuery);
    $stmt->execute([':sid' => $studentId]);
    $stats = $stmt->fetch();

    // 2. Load Itemized History Log
    $logQuery = "SELECT attendance_date, status FROM student_attendance WHERE student_id = :sid ORDER BY attendance_date DESC LIMIT 30";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([':sid' => $studentId]);
    $history = $logStmt->fetchAll();

    // Percentage Operations logic
    $totalDays = $stats['total_days'] ?? 0;
    $presentDays = $stats['presents'] ?? 0;
    $attendancePercentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 100;

} catch (PDOException $e) {
    die("Data profiling mapping exception: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Attendance Tracking Summary</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm text-center border-0 bg-white">
                <h3 class="text-primary mb-0"><?php echo number_format($attendancePercentage, 1); ?>%</h3>
                <small class="text-muted text-uppercase font-weight-bold">Net Percentage</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm text-center border-0 bg-success text-white">
                <h3 class="mb-0"><?php echo $presentDays; ?> Days</h3>
                <small class="text-white-50 text-uppercase font-weight-bold">Present Score</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm text-center border-0 bg-danger text-white">
                <h3 class="mb-0"><?php echo $stats['absents'] ?? 0; ?> Days</h3>
                <small class="text-white-50 text-uppercase font-weight-bold">Absent Log</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm text-center border-0 bg-warning text-dark">
                <h3 class="mb-0"><?php echo $stats['leaves'] ?? 0; ?> Days</h3>
                <small class="text-dark-50 text-uppercase font-weight-bold">Authorized Leaves</small>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white font-weight-bold">Recent 30 Days Activity Log</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Calendar Date</th>
                        <th>Status Evaluation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) === 0): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">No tracking metadata registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo $row['attendance_date']; ?></td>
                            <td>
                                <?php if ($row['status'] === 'P'): ?>
                                    <span class="badge bg-success">PRESENT</span>
                                <?php elseif ($row['status'] === 'A'): ?>
                                    <span class="badge bg-danger">ABSENT</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">ON LEAVE</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
