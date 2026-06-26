<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/notifications.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Marks Entry
if (isset($_POST['evaluate_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $assignment_id = (int)$_POST['assignment_id'];
    $student_id = (int)$_POST['student_id'];
    $marks = (float)$_POST['marks'];
    $feedback = trim($_POST['feedback']);

    try {
        $pdo->beginTransaction();

        $stmt_marks = $pdo->prepare("
            INSERT INTO assignment_marks (submission_id, marks, feedback, checked_by, checked_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE marks = VALUES(marks), feedback = VALUES(feedback), checked_at = NOW()
        ");
        $stmt_marks->execute([$submission_id, $marks, $feedback, $teacher_id]);

        $stmt_upd = $pdo->prepare("UPDATE assignment_submissions SET status = 'reviewed' WHERE id = ?");
        $stmt_upd->execute([$submission_id]);

        $pdo->commit();
        $message = "Marks and feedback saved successfully.";

        // Send Parent & Student Notification
        $recipients = [
            ['user_type' => 'student', 'user_id' => $student_id],
            ['user_type' => 'parent', 'user_id' => $student_id]
        ];
        sendNotification(
            $pdo,
            "Homework Evaluated",
            "Marks: {$marks} | Feedback: {$feedback}",
            "success",
            $recipients,
            $_SESSION['user_id']
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Evaluation failed: " . $e->getMessage();
    }
}

// Fetch all assignments for dropdown
$stmt_assign = $pdo->prepare("SELECT id, title, total_marks FROM assignments WHERE teacher_id = ? AND school_id = ? ORDER BY due_date DESC");
$stmt_assign->execute([$teacher_id, $schoolId]);
$assignments = $stmt_assign->fetchAll(PDO::FETCH_ASSOC);

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : (count($assignments) > 0 ? $assignments[0]['id'] : 0);
$selected_assignment = null;
$submissions = [];

if ($assignment_id > 0) {
    $stmt_cur = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ?");
    $stmt_cur->execute([$assignment_id, $teacher_id]);
    $selected_assignment = $stmt_cur->fetch(PDO::FETCH_ASSOC);

    if ($selected_assignment) {
        // Fetch submissions including students who haven't submitted
        $stmt_sub = $pdo->prepare("
            SELECT s.id as student_id, s.first_name, s.last_name, s.roll_number,
                   sub.id as submission_id, sub.submission_text, sub.file_path, sub.submitted_at, sub.status,
                   m.marks, m.feedback
            FROM students s
            LEFT JOIN assignment_submissions sub ON s.id = sub.student_id AND sub.assignment_id = ?
            LEFT JOIN assignment_marks m ON sub.id = m.submission_id
            WHERE s.class_id = ? AND s.section_id = ?
            ORDER BY s.roll_number ASC
        ");
        $stmt_sub->execute([$assignment_id, $selected_assignment['class_id'], $selected_assignment['section_id']]);
        $submissions = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
    }
}

$root_path = "../../";
$page_title = "Review Assignments | Teacher Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Review Submissions and Provide Feedback</h5>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-primary"><i class="fa fa-chart-pie me-1"></i> Dashboard</a>
        <a href="create.php" class="btn btn-outline-primary"><i class="fa fa-plus me-1"></i> New Assignment</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Assignment Selection -->
    <div class="col-lg-3 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h6 class="fw-bold mb-0">Select Assignment</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach($assignments as $a): ?>
                    <a href="?assignment_id=<?= $a['id'] ?>" class="list-group-item list-group-item-action <?= ($assignment_id == $a['id']) ? 'active fw-bold' : '' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-truncate" style="max-width: 150px;"><?= htmlspecialchars($a['title']) ?></h6>
                            <small>Max: <?= $a['total_marks'] ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Review Panel -->
    <div class="col-lg-9">
        <?php if ($selected_assignment): ?>
            <div class="card shadow border-0" style="border-radius: 12px;">
                <div class="card-header bg-primary text-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($selected_assignment['title']) ?></h5>
                    <span class="badge bg-light text-primary fs-6">Total Marks: <?= $selected_assignment['total_marks'] ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Status</th>
                                    <th>Submission Info</th>
                                    <th>Evaluated</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($submissions as $sub): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">
                                            <?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?>
                                            <div class="small text-muted">Roll: <?= htmlspecialchars($sub['roll_number']) ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                                if (!$sub['submission_id']) echo '<span class="badge bg-light text-muted border">Not Submitted</span>';
                                                elseif ($sub['status'] === 'late') echo '<span class="badge bg-danger">Late</span>';
                                                elseif ($sub['status'] === 'reviewed') echo '<span class="badge bg-success">Reviewed</span>';
                                                else echo '<span class="badge bg-warning text-dark">Submitted</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($sub['submission_id']): ?>
                                                <div class="small"><i class="fa fa-clock"></i> <?= date('d M, h:i A', strtotime($sub['submitted_at'])) ?></div>
                                                <?php if($sub['file_path']): ?>
                                                    <a href="<?= $root_path . htmlspecialchars($sub['file_path']) ?>" target="_blank" class="small text-primary"><i class="fa fa-paperclip"></i> View File</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($sub['marks'] !== null): ?>
                                                <span class="fw-bold text-success"><?= $sub['marks'] ?> / <?= $selected_assignment['total_marks'] ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-4">
                                            <?php if($sub['submission_id']): ?>
                                                <button class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $sub['submission_id'] ?>">
                                                    Evaluate
                                                </button>
                                                
                                                <!-- Review Modal -->
                                                <div class="modal fade" id="reviewModal<?= $sub['submission_id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form method="POST" class="modal-content text-start">
                                                            <div class="modal-header bg-light">
                                                                <h5 class="modal-title fw-bold">Evaluate: <?= htmlspecialchars($sub['first_name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="submission_id" value="<?= $sub['submission_id'] ?>">
                                                                <input type="hidden" name="assignment_id" value="<?= $selected_assignment['id'] ?>">
                                                                <input type="hidden" name="student_id" value="<?= $sub['student_id'] ?>">
                                                                
                                                                <div class="mb-3 bg-light p-3 border rounded">
                                                                    <label class="fw-semibold">Student's Text Submission:</label>
                                                                    <p class="small text-muted m-0 mt-1"><?= nl2br(htmlspecialchars($sub['submission_text'] ?? 'No text provided.')) ?></p>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-6 mb-3">
                                                                        <label class="form-label fw-semibold">Marks Awarded</label>
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.5" name="marks" class="form-control fw-bold text-success" value="<?= $sub['marks'] ?? '' ?>" max="<?= $selected_assignment['total_marks'] ?>" required>
                                                                            <span class="input-group-text">/ <?= $selected_assignment['total_marks'] ?></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 mb-3">
                                                                        <label class="form-label fw-semibold">Teacher Feedback</label>
                                                                        <textarea name="feedback" class="form-control" rows="3" placeholder="e.g. Good Work, improve handwriting..."><?= htmlspecialchars($sub['feedback'] ?? '') ?></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="evaluate_submission" class="btn btn-primary w-100">Save Marks & Notify Parent</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px;">
                Please select an assignment from the left to start evaluating.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
