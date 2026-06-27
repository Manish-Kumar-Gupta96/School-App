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

/* ==========================
UPCOMING LIVE CLASSES
========================== */
$upcoming_classes = [];
try {
    $student_class = $student['class'] ?? '';
    $student_section = $student['section'] ?? '';

    $clean_class_name = $student_class;
    if (preg_match('/^([^-]+)-[A-Z]$/', $student_class, $m)) {
        $clean_class_name = $m[1]; // '10'
    }

    $stmt_c_id = $pdo->prepare("SELECT id FROM classes WHERE class_name = ? OR class_name = ?");
    $stmt_c_id->execute([$clean_class_name, $student_class]);
    $class_id = (int)$stmt_c_id->fetchColumn();

    $stmt_s_id = $pdo->prepare("SELECT id FROM sections WHERE section_name = ?");
    $stmt_s_id->execute([$student_section]);
    $section_id = (int)$stmt_s_id->fetchColumn();

    $stmt_c = $pdo->prepare("
        SELECT oc.*, t.name AS teacher_name 
        FROM online_classes oc
        LEFT JOIN teachers t ON oc.teacher_id = t.id
        WHERE oc.class_id = ? AND oc.section_id = ? AND oc.status != 'COMPLETED' AND oc.end_time > NOW()
        ORDER BY oc.start_time ASC
        LIMIT 4
    ");
    $stmt_c->execute([$class_id, $section_id]);
    $upcoming_classes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently
}
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

<!-- UPCOMING LIVE CLASSES WIDGET -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
    <div class="card-header bg-white border-0 py-3 ps-4 text-start d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-circle-play me-2 text-danger animate-pulse"></i>Upcoming & Live Classes</h5>
        <a href="online-classes.php" class="btn btn-sm btn-outline-primary rounded-pill">View Schedule</a>
    </div>
    <div class="card-body p-4 text-start">
        <?php if (count($upcoming_classes) > 0): ?>
            <div class="row g-3">
                <?php foreach ($upcoming_classes as $c): ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 d-flex flex-column justify-content-between" style="background-color: #fafbfd; border-radius: 10px;">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($c['title']) ?></h6>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($c['subject'] ?: 'Subject') ?></span>
                                        <?php if ($c['platform'] === 'ZOOM'): ?>
                                            <span class="badge bg-primary-subtle text-primary small"><i class="fa fa-video me-1"></i> Zoom</span>
                                        <?php elseif ($c['platform'] === 'GOOGLE_MEET'): ?>
                                            <span class="badge bg-success-subtle text-success small"><i class="fa fa-calendar me-1"></i> Meet</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($c['status'] === 'LIVE'): ?>
                                            <span class="badge bg-danger animate-pulse">LIVE NOW</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">UPCOMING</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2"><i class="fa fa-chalkboard-user me-2"></i>Instructor: <?= htmlspecialchars($c['teacher_name'] ?: 'Faculty') ?></p>
                                <p class="text-muted small mb-3"><i class="fa fa-clock me-2"></i>Time: <?= date('d M, h:i A', strtotime($c['start_time'])) ?></p>
                            </div>
                            <?php if ($c['status'] === 'LIVE'): ?>
                                <a href="join-class.php?class_id=<?= $c['id'] ?>" target="_blank" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                                    <i class="fa fa-arrow-up-right-from-square me-2"></i> Join Live Class
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary w-100 py-2 fw-semibold" disabled style="border-radius: 8px;">
                                    <i class="fa fa-clock me-2"></i> Scheduled
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fa fa-video-slash fs-3 mb-2 d-block"></i>
                No upcoming online classes scheduled for your class target.
            </div>
        <?php endif; ?>
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
