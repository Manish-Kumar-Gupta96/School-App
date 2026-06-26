<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if ($assignment_id <= 0) {
    header("Location: view.php");
    exit();
}

// Fetch assignment details
$stmt_assign = $pdo->prepare("
    SELECT a.*, sub.subject_name, t.name as teacher_name
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN teachers t ON a.teacher_id = t.id
    WHERE a.id = ? AND a.school_id = ?
");
$stmt_assign->execute([$assignment_id, $schoolId]);
$assignment = $stmt_assign->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found.");
}

// Fetch teacher attachments
$stmt_files = $pdo->prepare("SELECT * FROM assignment_files WHERE assignment_id = ?");
$stmt_files->execute([$assignment_id]);
$teacher_files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

// Check if already submitted
$stmt_check = $pdo->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
$stmt_check->execute([$assignment_id, $student_id]);
$existing_sub = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($existing_sub && $existing_sub['status'] === 'reviewed') {
    $error = "This assignment has already been evaluated by the teacher. Modifications are not allowed.";
}

if (isset($_POST['submit_homework']) && (!$existing_sub || $existing_sub['status'] !== 'reviewed')) {
    $submission_text = trim($_POST['submission_text']);
    
    // Determine Late Status
    $now_date = date('Y-m-d');
    $status = ($now_date > $assignment['due_date']) ? 'late' : 'submitted';

    $file_path = $existing_sub ? $existing_sub['file_path'] : null;

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/assignments/submissions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $original_name = basename($_FILES['submission_file']['name']);
        $file_name = time() . '_' . $student_id . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $original_name);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
            $file_path = 'uploads/assignments/submissions/' . $file_name;
        } else {
            $error = "Failed to upload file.";
        }
    }

    if (empty($error)) {
        try {
            if ($existing_sub) {
                $stmt_upd = $pdo->prepare("UPDATE assignment_submissions SET submission_text = ?, file_path = ?, submitted_at = NOW(), status = ? WHERE id = ?");
                $stmt_upd->execute([$submission_text, $file_path, $status, $existing_sub['id']]);
                $message = "Submission updated successfully!";
            } else {
                $stmt_ins = $pdo->prepare("INSERT INTO assignment_submissions (school_id, assignment_id, student_id, submission_text, file_path, submitted_at, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                $stmt_ins->execute([$schoolId, $assignment_id, $student_id, $submission_text, $file_path, $status]);
                $message = "Homework submitted successfully!";
            }
            
            // Refresh sub
            $stmt_check->execute([$assignment_id, $student_id]);
            $existing_sub = $stmt_check->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$root_path = "../../";
$page_title = "Submit Homework | Student Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Submit your homework</h5>
    <div class="d-flex gap-2">
        <a href="view.php" class="btn btn-outline-primary"><i class="fa fa-arrow-left me-1"></i> Back to List</a>
        <a href="discussion.php?id=<?= $assignment_id ?>" class="btn btn-outline-info"><i class="fa fa-comments me-1"></i> Discussion / Doubts</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Assignment Info -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-light border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($assignment['title']) ?></h5>
                <small class="text-primary fw-bold"><?= htmlspecialchars($assignment['subject_name']) ?> | <?= htmlspecialchars($assignment['teacher_name']) ?></small>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4"><i class="fa fa-calendar text-danger"></i> Due: <span class="fw-bold"><?= date('d M Y', strtotime($assignment['due_date'])) ?></span></p>
                
                <h6 class="fw-bold text-dark mb-2">Instructions:</h6>
                <div class="p-3 bg-light rounded border text-muted small mb-4">
                    <?= nl2br(htmlspecialchars($assignment['description'] ?? 'No extra instructions provided.')) ?>
                </div>

                <h6 class="fw-bold text-dark mb-2">Reference Files:</h6>
                <?php if(count($teacher_files) > 0): ?>
                    <ul class="list-group mb-0">
                        <?php foreach($teacher_files as $f): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="small text-truncate" style="max-width: 200px;"><?= htmlspecialchars($f['file_name']) ?></span>
                                <a href="<?= $root_path . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary rounded-circle"><i class="fa fa-download"></i></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="small text-muted fst-italic">No files attached by teacher.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Submission Form -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Your Submission</h5>
                <?php if($existing_sub): ?>
                    <span class="badge <?= ($existing_sub['status'] == 'late') ? 'bg-danger' : 'bg-success' ?>">
                        Status: <?= ucfirst($existing_sub['status']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($existing_sub && $existing_sub['status'] === 'reviewed'): ?>
                    <div class="alert alert-success border-0">
                        <h6 class="fw-bold"><i class="fa fa-check-circle"></i> Evaluated</h6>
                        <p class="mb-0 small">This assignment has been marked by your teacher. You can no longer edit your submission. Check the <strong>Reviewed</strong> tab for marks and feedback.</p>
                    </div>
                    <?php if($existing_sub['file_path']): ?>
                        <p class="mt-3 small fw-bold">Your Uploaded File: <a href="<?= $root_path . htmlspecialchars($existing_sub['file_path']) ?>" target="_blank">View File</a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Write your answer (Optional)</label>
                            <textarea name="submission_text" class="form-control" rows="6" placeholder="Type your answer here..."><?= htmlspecialchars($existing_sub['submission_text'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Upload Document/Image</label>
                            <?php if($existing_sub && $existing_sub['file_path']): ?>
                                <p class="small mb-2">Current file: <a href="<?= $root_path . htmlspecialchars($existing_sub['file_path']) ?>" target="_blank" class="text-primary fw-bold"><i class="fa fa-paperclip"></i> View Attached</a></p>
                            <?php endif; ?>
                            <input type="file" name="submission_file" class="form-control">
                            <small class="text-muted">Max file size: 10MB. Allowed: PDF, DOCX, JPG, PNG.</small>
                        </div>

                        <button type="submit" name="submit_homework" class="btn btn-primary w-100 fw-bold">
                            <i class="fa fa-upload me-1"></i> <?= $existing_sub ? 'Update Submission' : 'Submit Homework' ?>
                        </button>
                        
                        <?php 
                            $now_date = date('Y-m-d');
                            if ($now_date > $assignment['due_date']): 
                        ?>
                            <div class="text-danger small mt-2 text-center fw-bold"><i class="fa fa-exclamation-triangle"></i> Note: This submission will be marked as LATE.</div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
