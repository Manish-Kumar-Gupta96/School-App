<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../config/database.php';

new BaseController();
BaseController::enforceRole(['student', 'parent', 'admin', 'superadmin']);

$db = getDBConnection();
$targetClass = 'Class 10'; // Dynamic tracking can be linked directly with logged student profile session

// Fetch all schedules matrix items 
$stmt = $db->prepare("SELECT day_of_week, period_number, subject_name, room_number FROM school_timetable WHERE class_name = :class ORDER BY period_number ASC");
$stmt->execute([':class' => $targetClass]);
$rows = $stmt->fetchAll();

// Pivot array conversion allocation logic layers
$matrix = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($rows as $r) {
    $matrix[$r['day_of_week']][$r['period_number']] = $r['subject_name'] . " (" . ($r['room_number'] ?? 'N/A') . ")";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly Academic Routine Sheet</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white font-weight-bold">
            Weekly Class Routine Dashboard Overview - <?php echo $targetClass; ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped text-center align-middle mb-0 font-monospace small">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 12%;">Day / Week</th>
                        <th>Period 1</th>
                        <th>Period 2</th>
                        <th>Period 3</th>
                        <th>Period 4</th>
                        <th>Period 5</th>
                        <th>Period 6</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                    <tr>
                        <td class="table-secondary font-weight-bold"><strong><?php echo $day; ?></strong></td>
                        <?php for ($p = 1; $p <= 6; $p++): ?>
                            <td>
                                <?php echo isset($matrix[$day][$p]) ? htmlspecialchars($matrix[$day][$p]) : '<span class="text-muted text-decoration-none">-</span>'; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
