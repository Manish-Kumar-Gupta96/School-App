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

// Fetch Assignments and Submissions
$stmt_assign = $pdo->prepare("
    SELECT a.*, sub.subject_name, t.name as teacher_name, 
           subm.id as submission_id, subm.status, subm.submitted_at, subm.file_path as sub_file,
           m.marks, m.feedback
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN teachers t ON a.teacher_id = t.id
    LEFT JOIN assignment_submissions subm ON a.id = subm.assignment_id AND subm.student_id = ?
    LEFT JOIN assignment_marks m ON subm.id = m.submission_id
    WHERE a.class_id = ? AND a.section_id = ? AND a.school_id = ?
    ORDER BY a.due_date DESC
");
$stmt_assign->execute([$student_id, $student['class_id'], $student['section_id'], $schoolId]);
$all_assignments = $stmt_assign->fetchAll(PDO::FETCH_ASSOC);

$pending = [];
$submitted = [];
$reviewed = [];

$now = date('Y-m-d');

foreach ($all_assignments as $a) {
    if (!$a['submission_id']) {
        // Pending (Check if late)
        if ($now > $a['due_date']) {
            $a['is_late'] = true;
        } else {
            $a['is_late'] = false;
        }
        $pending[] = $a;
    } elseif ($a['status'] === 'submitted' || $a['status'] === 'late') {
        $submitted[] = $a;
    } elseif ($a['status'] === 'reviewed') {
        $reviewed[] = $a;
    }
}

$root_path = "../../";
$page_title = "My Homework | Student Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">View your homework and track grades</h5>
</div>

<ul class="nav nav-tabs mb-4" id="assignmentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button">
            📝 Pending <span class="badge bg-warning text-dark ms-1"><?= count($pending) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#submitted-pane" type="button">
            ✅ Submitted <span class="badge bg-primary ms-1"><?= count($submitted) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#reviewed-pane" type="button">
            🏆 Reviewed <span class="badge bg-success ms-1"><?= count($reviewed) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Pending Tab -->
    <div class="tab-pane fade show active" id="pending-pane">
        <div class="row">
            <?php if(count($pending) > 0): ?>
                <?php foreach($pending as $a): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100 <?= $a['is_late'] ? 'border-danger' : 'border-warning' ?>" style="border-radius: 12px; border-width: 2px;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($a['title']) ?></h5>
                                    <?php if($a['is_late']): ?>
                                        <span class="badge bg-danger">Past Due</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="text-primary fw-bold mb-3"><?= htmlspecialchars($a['subject_name']) ?> <span class="text-muted small fw-normal">| By <?= htmlspecialchars($a['teacher_name']) ?></span></h6>
                                
                                <p class="small text-muted mb-3"><i class="fa fa-calendar"></i> Due Date: <?= date('d M Y', strtotime($a['due_date'])) ?></p>
                                
                                <div class="d-flex gap-2">
                                    <a href="submit.php?id=<?= $a['id'] ?>" class="btn <?= $a['is_late'] ? 'btn-danger' : 'btn-primary' ?> w-100">
                                        Submit Homework
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No pending assignments. Great job!</div></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submitted Tab -->
    <div class="tab-pane fade" id="submitted-pane">
        <div class="row">
            <?php if(count($submitted) > 0): ?>
                <?php foreach($submitted as $a): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                            <div class="card-body">
                                <h5 class="fw-bold mb-2"><?= htmlspecialchars($a['title']) ?></h5>
                                <h6 class="text-primary fw-bold mb-3"><?= htmlspecialchars($a['subject_name']) ?></h6>
                                
                                <div class="p-3 bg-light rounded mb-3">
                                    <p class="small mb-1"><i class="fa fa-check-circle text-success"></i> Submitted on: <?= date('d M Y, h:i A', strtotime($a['submitted_at'])) ?></p>
                                    <p class="small m-0">Status: <span class="badge bg-secondary"><?= ucfirst($a['status']) ?></span></p>
                                </div>

                                <button class="btn btn-outline-secondary w-100 disabled">Waiting for Teacher Review</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No assignments waiting for review.</div></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviewed Tab -->
    <div class="tab-pane fade" id="reviewed-pane">
        <div class="row">
            <?php if(count($reviewed) > 0): ?>
                <?php foreach($reviewed as $a): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow border-0 h-100 border-success" style="border-radius: 12px; border-width: 2px;">
                            <div class="card-header bg-success text-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0 text-truncate" style="max-width: 200px;"><?= htmlspecialchars($a['title']) ?></h5>
                                <span class="badge bg-light text-success fs-6"><?= $a['marks'] ?> / <?= $a['total_marks'] ?></span>
                            </div>
                            <div class="card-body">
                                <h6 class="text-dark fw-bold mb-3"><?= htmlspecialchars($a['subject_name']) ?> <span class="text-muted small fw-normal">| Evaluated</span></h6>
                                
                                <div class="p-3 bg-light rounded border border-success">
                                    <p class="small text-muted mb-1 fw-bold">Teacher's Feedback:</p>
                                    <p class="m-0 fst-italic">"<?= nl2br(htmlspecialchars($a['feedback'])) ?>"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No reviewed assignments yet.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
