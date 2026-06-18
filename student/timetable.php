<?php
require_once('../config/database.php');
$active_menu = "timetable";
$page_title = "Class Timetable | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student class
$stmt = $pdo->prepare("SELECT class FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$class = $student['class'] ?? '';

// Get timetable
$stmt = $pdo->prepare("
    SELECT * FROM timetable
    WHERE class=?
    ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), period
");
$stmt->execute([$class]);
$timetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$schedule = [];
foreach($days as $day){
    $schedule[$day] = [];
}
foreach($timetableData as $t){
    $schedule[$t['day']][] = $t;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📅 Class Timetable</h2>
        <p class="text-muted mb-0">Class: <strong>Class <?= htmlspecialchars($class) ?></strong> | View your daily periods schedule and mapped subjects.</p>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden; text-align: start;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-calendar-alt me-2 text-primary"></i>Weekly Schedule</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4" style="width: 15%;">Day</th>
                        <th colspan="8">Periods Schedule (Subject - Faculty | Time Slot)</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php foreach($days as $day): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark table-light align-middle"><?= htmlspecialchars($day) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 py-2">
                                    <?php if(count($schedule[$day]) > 0): ?>
                                        <?php foreach($schedule[$day] as $p): ?>
                                            <div class="card border border-primary-subtle p-3 shadow-xs text-center" style="border-radius: 8px; min-width: 160px; background: #f8fafc;">
                                                <span class="badge bg-primary mb-2">Period <?= (int)$p['period'] ?></span>
                                                <h6 class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($p['subject']) ?></h6>
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem;"><?= htmlspecialchars($p['teacher_name']) ?></p>
                                                <span class="text-secondary small fw-semibold" style="font-size: 0.7rem;">
                                                    <i class="fa fa-clock me-1"></i> <?= date('h:i A', strtotime($p['start_time'])) ?> - <?= date('h:i A', strtotime($p['end_time'])) ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small my-2">No periods scheduled for this day.</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
