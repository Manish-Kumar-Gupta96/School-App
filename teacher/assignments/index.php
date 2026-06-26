<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Fetch teacher's assignments
$stmt_assign = $pdo->prepare("
    SELECT a.*, c.class_name, s.section_name, sub.subject_name,
           (SELECT COUNT(*) FROM students stu WHERE stu.class_id = a.class_id AND stu.section_id = a.section_id) as total_students,
           (SELECT COUNT(*) FROM assignment_submissions sub_s WHERE sub_s.assignment_id = a.id AND sub_s.status IN ('submitted', 'reviewed')) as submitted_count,
           (SELECT COUNT(*) FROM assignment_submissions sub_l WHERE sub_l.assignment_id = a.id AND sub_l.status = 'late') as late_count
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN sections s ON a.section_id = s.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.teacher_id = ? AND a.school_id = ?
    ORDER BY a.due_date DESC
");
$stmt_assign->execute([$teacher_id, $schoolId]);
$assignments = $stmt_assign->fetchAll(PDO::FETCH_ASSOC);

$total_assigned = count($assignments);
$total_submissions = 0;
$total_late = 0;
$overall_expected = 0;

foreach ($assignments as $a) {
    $overall_expected += $a['total_students'];
    $total_submissions += $a['submitted_count'];
    $total_late += $a['late_count'];
}

$pending_count = $overall_expected - ($total_submissions + $total_late);
$late_percentage = ($overall_expected > 0) ? round(($total_late / $overall_expected) * 100, 1) : 0;

$root_path = "../../";
$page_title = "Assignment Dashboard | Teacher Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Homework & Assignment Analytics</h5>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-primary"><i class="fa fa-chart-pie me-1"></i> Dashboard</a>
        <a href="create.php" class="btn btn-outline-primary"><i class="fa fa-plus me-1"></i> New Assignment</a>
        <a href="review.php" class="btn btn-outline-success"><i class="fa fa-check-double me-1"></i> Review Submissions</a>
    </div>
</div>

<!-- Analytics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body">
                <h6 class="fw-bold opacity-75">Total Assignments</h6>
                <h2 class="fw-bold mb-0"><?= $total_assigned ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body">
                <h6 class="fw-bold opacity-75">Total Submitted</h6>
                <h2 class="fw-bold mb-0"><?= $total_submissions ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-dark" style="border-radius: 12px;">
            <div class="card-body">
                <h6 class="fw-bold opacity-75">Pending Submissions</h6>
                <h2 class="fw-bold mb-0"><?= $pending_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white" style="border-radius: 12px;">
            <div class="card-body">
                <h6 class="fw-bold opacity-75">Late Submission %</h6>
                <h2 class="fw-bold mb-0"><?= $late_percentage ?>%</h2>
            </div>
        </div>
    </div>
</div>

<!-- Assignments List -->
<div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">My Assignments</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Title</th>
                        <th>Class & Subject</th>
                        <th>Due Date</th>
                        <th>Stats (Expected: Sub / Late)</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($assignments) > 0): ?>
                        <?php foreach($assignments as $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="small text-muted">Marks: <?= $a['total_marks'] ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-primary"><?= htmlspecialchars($a['subject_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($a['class_name']) ?> - Sec <?= htmlspecialchars($a['section_name']) ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $due = strtotime($a['due_date']);
                                        $now = time();
                                        $is_past = $now > $due;
                                    ?>
                                    <div class="small fw-bold <?= $is_past ? 'text-danger' : 'text-success' ?>">
                                        <?= date('d M Y', $due) ?>
                                    </div>
                                    <?php if($is_past): ?><span class="badge bg-danger small">Passed</span><?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $a['total_students'] ?> Expected</span>
                                    <span class="badge bg-success"><?= $a['submitted_count'] ?> Sub</span>
                                    <span class="badge bg-danger"><?= $a['late_count'] ?> Late</span>
                                </td>
                                <td class="text-center pe-4">
                                    <a href="review.php?assignment_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                        View Submissions
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted p-5">You haven't posted any assignments yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
