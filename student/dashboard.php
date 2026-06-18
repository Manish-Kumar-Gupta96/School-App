<?php
require_once('../config/database.php');
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

/* ==========================
STUDENT INFO
========================== */
$stmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = $student['first_name'] . ' ' . $student['last_name'];

/* ==========================
ATTENDANCE
========================== */
$total = $pdo->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id=?");
$total->execute([$student_id]);
$totalDays = $total->fetchColumn();

$present = $pdo->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id=? AND status='Present'");
$present->execute([$student_id]);
$presentDays = $present->fetchColumn();

$attendancePercent = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100, 2) : 0;

/* ==========================
LATEST RESULT
========================== */
$result = $pdo->prepare("
    SELECT * FROM student_progress
    WHERE student_id=?
    ORDER BY id DESC LIMIT 5
");
$result->execute([$student_id]);
$results = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($fullName) ?>!</h2>
        <p class="text-muted mb-0">Here is your academic and administrative summary overview</p>
    </div>
    <span class="badge bg-primary px-3 py-2 fw-semibold">Class <?= htmlspecialchars($student['class']) ?> - Sec <?= htmlspecialchars($student['section'] ?: 'A') ?></span>
</div>

<!-- STATS HIGHLIGHTS -->
<div class="row g-4 mb-4">
    <!-- Attendance -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Attendance Rate</h6>
                <i class="fa fa-calendar-check text-success fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $attendancePercent ?> %</h3>
            <p class="text-muted small mb-0 mt-1">Present: <?= (int)$presentDays ?> of <?= (int)$totalDays ?> days</p>
        </div>
    </div>

    <!-- Total Class Days -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Total Class Days</h6>
                <i class="fa fa-school text-primary fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalDays ?> Days</h3>
            <p class="text-muted small mb-0 mt-1">Total recorded attendance periods</p>
        </div>
    </div>
</div>

<!-- RECENT MARKS / REPORT CARD PREVIEW -->
<div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-header bg-white border-0 py-3 ps-4 text-start">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-chart-line me-2 text-primary"></i>Latest Exam Results</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Exam / Test</th>
                        <th>Subject</th>
                        <th>Obtained Marks</th>
                        <th>Total Marks</th>
                        <th>Percentage</th>
                        <th class="pe-4 text-center">Grade</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($results) > 0): ?>
                        <?php foreach($results as $r): ?>
                            <?php
                            $perc = round(($r['marks_obtained'] / $r['total_marks']) * 100, 2);
                            $badge = 'success';
                            if($perc < 40) $badge = 'danger';
                            elseif($perc < 65) $badge = 'warning';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($r['exam_name']) ?></td>
                                <td class="fw-semibold text-muted"><?= htmlspecialchars($r['subject']) ?></td>
                                <td class="fw-bold text-dark"><?= number_format($r['marks_obtained'], 1) ?></td>
                                <td class="text-muted"><?= number_format($r['total_marks'], 0) ?></td>
                                <td class="fw-bold text-primary"><?= $perc ?> %</td>
                                <td class="pe-4 text-center">
                                    <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars($r['grade']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa fa-graduation-cap fs-2 mb-2 d-block"></i>
                                No exam results have been published for you yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
