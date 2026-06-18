<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
LOAD STUDENTS
========================== */
$students = $pdo->query("
    SELECT id, CONCAT(first_name, ' ', last_name) AS student_name, class
    FROM students
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$student_id = $_GET['student_id'] ?? '';
$progress = [];

if($student_id){
    $stmt = $pdo->prepare("
        SELECT *
        FROM student_progress
        WHERE student_id=?
        ORDER BY id DESC
    ");
    $stmt->execute([$student_id]);
    $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Academic Progress | VIC ERP</title>
</head>
<body>

<?php
// Layout setup
$root_path = "../../";
$page_title = "Academic Progress | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">View student academic marks, exam sheets, and grades</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-outline-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-outline-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="notifications.php" class="btn btn-outline-primary">
            <i class="fa fa-bullhorn me-1"></i> Notifications
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET">
            <div class="row align-items-end text-start">
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-semibold">Select Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Choose Student</option>
                        <?php foreach($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($student['student_name']) ?> (Class <?= htmlspecialchars($student['class']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search me-1"></i> View Progress
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if($student_id): ?>
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark text-start">Exam Performance Sheet</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Exam / Test</th>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th class="pe-4">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php if (count($progress) > 0): ?>
                            <?php foreach($progress as $row): ?>
                                <?php
                                $percent = round(($row['marks_obtained'] / $row['total_marks']) * 100, 2);
                                $gradeBadge = 'success';
                                if($percent < 40) $gradeBadge = 'danger';
                                elseif($percent < 60) $gradeBadge = 'warning';
                                elseif($percent < 80) $gradeBadge = 'info';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['exam_name']) ?></td>
                                    <td class="fw-semibold text-muted"><?= htmlspecialchars($row['subject']) ?></td>
                                    <td class="fw-bold text-dark"><?= number_format($row['marks_obtained'], 1) ?></td>
                                    <td class="text-muted"><?= number_format($row['total_marks'], 0) ?></td>
                                    <td class="fw-bold text-primary"><?= $percent ?> %</td>
                                    <td>
                                        <span class="badge bg-<?= $gradeBadge ?>-subtle text-<?= $gradeBadge ?> border border-<?= $gradeBadge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                            <?= htmlspecialchars($row['grade']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-muted small"><?= htmlspecialchars($row['remarks'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa fa-chart-line fs-2 mb-2 d-block"></i>
                                    No academic records seeded or found for this student.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
</body>
</html>
