<?php
require_once('../config/database.php');
require_once('includes/header.php');

$teacher_id = $_SESSION['teacher_id'];

/* ==========================
TEACHER INFO
========================== */
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

/* ==========================
ASSIGNED CLASSES & SUBJECTS
========================== */
$stmt_tca = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name, sub.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    JOIN subjects sub ON tca.subject_id = sub.id
    WHERE tca.teacher_id = ?
");
$stmt_tca->execute([$teacher_id]);
$subjectClasses = $stmt_tca->fetchAll(PDO::FETCH_ASSOC);

$stmt_ct = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name
    FROM class_teachers ct
    JOIN classes c ON ct.class_id = c.id
    JOIN sections s ON ct.section_id = s.id
    WHERE ct.teacher_id = ?
");
$stmt_ct->execute([$teacher_id]);
$inchargeClasses = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
STATS
========================== */
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalAttendanceMarked = $pdo->query("SELECT COUNT(*) FROM student_attendance")->fetchColumn();
$totalMarksEntered = $pdo->query("SELECT COUNT(*) FROM student_progress")->fetchColumn();

/* ==========================
LEAVE STATUS
========================== */
$leaves = $pdo->prepare("
    SELECT * 
    FROM employee_leaves 
    WHERE employee_id=? 
    ORDER BY id DESC LIMIT 5
");
$leaves->execute([$teacher_id]);
$leaveList = $leaves->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LIVE CLASSROOM STATS
========================== */
$today_count = 0;
$upcoming_count = 0;
$completed_count = 0;
$missed_count = 0;
$today_classes_list = [];

try {
    $stmt_today = $pdo->prepare("SELECT COUNT(*) FROM online_classes WHERE DATE(start_time) = CURDATE() AND teacher_id = ?");
    $stmt_today->execute([$teacher_id]);
    $today_count = (int)$stmt_today->fetchColumn();

    $stmt_up = $pdo->prepare("SELECT COUNT(*) FROM online_classes WHERE start_time > NOW() AND status = 'UPCOMING' AND teacher_id = ?");
    $stmt_up->execute([$teacher_id]);
    $upcoming_count = (int)$stmt_up->fetchColumn();

    $stmt_comp = $pdo->prepare("SELECT COUNT(*) FROM online_classes WHERE status = 'COMPLETED' AND teacher_id = ?");
    $stmt_comp->execute([$teacher_id]);
    $completed_count = (int)$stmt_comp->fetchColumn();

    $stmt_miss = $pdo->prepare("SELECT COUNT(*) FROM online_classes WHERE end_time < NOW() AND status = 'UPCOMING' AND teacher_id = ?");
    $stmt_miss->execute([$teacher_id]);
    $missed_count = (int)$stmt_miss->fetchColumn();

    $stmt_today_list = $pdo->prepare("
        SELECT * FROM online_classes 
        WHERE DATE(start_time) = CURDATE() AND teacher_id = ?
        ORDER BY start_time ASC
    ");
    $stmt_today_list->execute([$teacher_id]);
    $today_classes_list = $stmt_today_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($teacher['name']) ?>!</h2>
        <p class="text-muted mb-0">Here is your daily class schedule and management summary</p>
    </div>
    <span class="badge bg-success px-3 py-2 fw-semibold">ID: <?= htmlspecialchars($teacher['employee_id']) ?></span>
</div>

<!-- STATS CARDS -->
<div class="row g-4 mb-4">
    <!-- Total Students -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Total Students</h6>
                <i class="fa fa-user-graduate text-primary fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalStudents ?></h3>
            <p class="text-muted small mb-0 mt-1">Total active enrollments in ERP</p>
        </div>
    </div>

    <!-- Attendance Entries -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Attendance Entries</h6>
                <i class="fa fa-calendar-check text-success fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalAttendanceMarked ?></h3>
            <p class="text-muted small mb-0 mt-1">Total attendance marks saved</p>
        </div>
    </div>

    <!-- Marks Entries -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Marks Entries</h6>
                <i class="fa fa-edit text-warning fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalMarksEntered ?></h3>
            <p class="text-muted small mb-0 mt-1">Total exam marks files saved</p>
        </div>
    </div>
</div>

<!-- LIVE CLASS STATS CARDS -->
<div class="row g-3 mb-4 text-start">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #0dcaf0 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Today's Classes</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $today_count ?></h4>
                </div>
                <i class="fa fa-video text-info fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #6f42c1 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Upcoming</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $upcoming_count ?></h4>
                </div>
                <i class="fa fa-calendar-alt text-purple fs-3" style="color: #6f42c1;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Completed</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $completed_count ?></h4>
                </div>
                <i class="fa fa-circle-check text-success fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Missed Sessions</span>
                    <h4 class="fw-bold mb-0 text-danger"><?= $missed_count ?></h4>
                </div>
                <i class="fa fa-circle-exclamation text-danger fs-3"></i>
            </div>
        </div>
    </div>
</div>

<!-- TODAY'S SCHEDULE -->
<?php if (count($today_classes_list) > 0): ?>
    <div class="card shadow-sm border-0 mb-4 text-start" style="border-radius: 15px;">
        <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-chalkboard text-primary me-2"></i>Today's Live Class Schedule</h5>
            <a href="online-classes.php" class="btn btn-sm btn-outline-primary rounded-pill">Go to Schedule Manager</a>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <?php foreach ($today_classes_list as $tc): ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="background-color: #fafbfd; border-radius: 10px;">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($tc['title']) ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($tc['subject'] ?: 'Subject') ?></span>
                                <span class="badge bg-light text-dark border small ms-1"><?= htmlspecialchars($tc['class_name']) ?></span>
                                <span class="d-block text-muted small mt-2"><i class="fa fa-clock me-1"></i>Time: <?= date('h:i A', strtotime($tc['start_time'])) ?> to <?= date('h:i A', strtotime($tc['end_time'])) ?></span>
                            </div>
                            <div>
                                <?php if ($tc['status'] === 'UPCOMING'): ?>
                                    <a href="online-classes.php?action=start&id=<?= $tc['id'] ?>" class="btn btn-sm btn-success py-1.5"><i class="fa fa-play me-1"></i> Start</a>
                                <?php elseif ($tc['status'] === 'LIVE'): ?>
                                    <a href="end-class.php?id=<?= $tc['id'] ?>" class="btn btn-sm btn-danger py-1.5"><i class="fa fa-stop me-1"></i> End</a>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fw-semibold small">Completed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- CLASSES & LEAVE STATUS -->
<div class="row g-4">
    <!-- Assigned Classes -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 p-4 text-start" style="border-radius: 15px; height: 100%;">
            <h5 class="fw-bold text-dark mb-4"><i class="fa fa-school me-2 text-primary"></i>Assigned Classes</h5>
            
            <?php if (count($inchargeClasses) > 0): ?>
                <div class="mb-3">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Class Incharge (Class Teacher)</span>
                    <div class="row g-2">
                        <?php foreach($inchargeClasses as $ic): ?>
                            <div class="col-12">
                                <div class="alert alert-success d-flex align-items-center mb-0 fw-bold border-0 py-2 px-3" style="border-radius: 8px; font-size: 0.9rem;">
                                    <i class="fa fa-award me-2 text-success"></i> Class <?= htmlspecialchars($ic['class_name']) ?> - Section <?= htmlspecialchars($ic['section_name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-2">Subject Assignments</span>
                <div class="row g-2">
                    <?php if (count($subjectClasses) > 0): ?>
                        <?php foreach($subjectClasses as $sc): ?>
                            <div class="col-12">
                                <div class="alert alert-primary d-flex align-items-center justify-content-between mb-0 fw-bold border-0 py-2 px-3" style="border-radius: 8px; font-size: 0.9rem;">
                                    <span><i class="fa fa-book-open me-2 text-primary"></i> Class <?= htmlspecialchars($sc['class_name']) ?> - Sec <?= htmlspecialchars($sc['section_name']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($sc['subject_name']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-4 text-muted small">No subject assignments.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Requests -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden; height: 100%;">
            <div class="card-header bg-white border-0 py-3 ps-4 text-start">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-plane-departure me-2 text-success"></i>Recent Leave Requests</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="pe-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($leaveList) > 0): ?>
                                <?php foreach($leaveList as $l): ?>
                                    <?php
                                    $badge = 'warning';
                                    if($l['status'] == 'Approved') $badge = 'success';
                                    elseif($l['status'] == 'Rejected') $badge = 'danger';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($l['leave_type']) ?></td>
                                        <td><?= date('d M Y', strtotime($l['from_date'])) ?></td>
                                        <td><?= date('d M Y', strtotime($l['to_date'])) ?></td>
                                        <td class="pe-4 text-center">
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <?= htmlspecialchars($l['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No recent leave requests logged.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
