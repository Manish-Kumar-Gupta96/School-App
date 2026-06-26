<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Get student details
$stmt_stu = $pdo->prepare("SELECT class_id, section_id FROM students WHERE id = ?");
$stmt_stu->execute([$student_id]);
$student = $stmt_stu->fetch(PDO::FETCH_ASSOC);

// Fetch Exams assigned to the student's class
$stmt_exams = $pdo->prepare("
    SELECT e.*, sub.subject_name,
           (SELECT status FROM exam_attempts ea WHERE ea.exam_id = e.id AND ea.student_id = ?) as attempt_status,
           (SELECT percentage FROM exam_results er WHERE er.exam_id = e.id AND er.student_id = ?) as result_percentage
    FROM exams e
    JOIN subjects sub ON e.subject_id = sub.id
    WHERE e.class_id = ? AND e.school_id = ?
    ORDER BY e.start_time ASC
");
$stmt_exams->execute([$student_id, $student_id, $student['class_id'], $schoolId]);
$all_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$upcoming = [];
$active = [];
$completed = [];

$now = time();

foreach ($all_exams as $e) {
    $start = strtotime($e['start_time']);
    $end = strtotime($e['end_time']);
    
    if ($e['attempt_status'] === 'submitted' || $e['result_percentage'] !== null) {
        $completed[] = $e;
    } elseif ($now >= $start && $now <= $end) {
        $active[] = $e;
    } elseif ($now < $start) {
        $upcoming[] = $e;
    } else {
        // missed exam
        $completed[] = $e; 
    }
}

$root_path = "../../";
$page_title = "My Exams | Student Portal";
$page_header = "Online Exams";
$active_menu = "exams";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">View and take your assigned online exams</h5>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-primary"><i class="fa fa-list me-1"></i> Exam List</a>
        <a href="results.php" class="btn btn-outline-success"><i class="fa fa-trophy me-1"></i> My Results</a>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="examTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#active-pane" type="button">
            🚨 Active Now <span class="badge bg-danger ms-1"><?= count($active) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#upcoming-pane" type="button">
            📅 Upcoming <span class="badge bg-primary ms-1"><?= count($upcoming) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#completed-pane" type="button">
            ✅ Completed <span class="badge bg-success ms-1"><?= count($completed) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Active Exams -->
    <div class="tab-pane fade show active" id="active-pane">
        <div class="row">
            <?php if(count($active) > 0): ?>
                <?php foreach($active as $e): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm border-danger h-100" style="border-radius: 12px; border-width: 2px;">
                            <div class="card-header bg-danger text-white border-0 py-3">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($e['exam_name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-dark fw-bold mb-3"><?= htmlspecialchars($e['subject_name']) ?></h6>
                                <p class="small text-muted mb-1"><i class="fa fa-clock"></i> Duration: <?= round((strtotime($e['end_time']) - strtotime($e['start_time']))/60) ?> Mins</p>
                                <p class="small text-muted mb-3"><i class="fa fa-calendar"></i> Ends: <?= date('h:i A', strtotime($e['end_time'])) ?></p>
                                
                                <?php if($e['attempt_status'] == 'started'): ?>
                                    <a href="take_advanced.php?id=<?= $e['id'] ?>" class="btn btn-warning w-100 fw-bold">Resume Exam</a>
                                <?php else: ?>
                                    <a href="take_advanced.php?id=<?= $e['id'] ?>" class="btn btn-danger w-100 fw-bold">Start Exam Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No active exams right now.</div></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Exams -->
    <div class="tab-pane fade" id="upcoming-pane">
        <div class="row">
            <?php if(count($upcoming) > 0): ?>
                <?php foreach($upcoming as $e): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($e['exam_name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-primary fw-bold mb-3"><?= htmlspecialchars($e['subject_name']) ?></h6>
                                <p class="small text-muted mb-1"><i class="fa fa-calendar"></i> Starts: <?= date('d M Y, h:i A', strtotime($e['start_time'])) ?></p>
                                <p class="small text-muted mb-3"><i class="fa fa-clock"></i> Duration: <?= round((strtotime($e['end_time']) - strtotime($e['start_time']))/60) ?> Mins</p>
                                <button class="btn btn-secondary w-100" disabled>Waiting...</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No upcoming exams scheduled.</div></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Completed Exams -->
    <div class="tab-pane fade" id="completed-pane">
        <div class="row">
            <?php if(count($completed) > 0): ?>
                <?php foreach($completed as $e): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm border-success h-100" style="border-radius: 12px; opacity: 0.8;">
                            <div class="card-header bg-success text-white border-0 py-3">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($e['exam_name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-dark fw-bold mb-3"><?= htmlspecialchars($e['subject_name']) ?></h6>
                                <?php if($e['attempt_status'] === 'submitted'): ?>
                                    <p class="text-success fw-bold mb-2"><i class="fa fa-check-circle"></i> Successfully Submitted</p>
                                    <?php if($e['result_percentage'] !== null): ?>
                                        <p class="text-primary fw-bold">Score: <?= $e['result_percentage'] ?>%</p>
                                        <a href="results.php" class="btn btn-outline-success btn-sm w-100">View Full Result</a>
                                    <?php else: ?>
                                        <p class="text-muted small">Pending evaluation...</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-danger fw-bold"><i class="fa fa-times-circle"></i> Missed Exam</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No completed exams yet.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
