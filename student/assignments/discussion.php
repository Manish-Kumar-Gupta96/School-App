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

// Handle new comment
if (isset($_POST['post_comment'])) {
    $comment_text = trim($_POST['comment_text']);
    if (!empty($comment_text)) {
        try {
            $stmt_ins = $pdo->prepare("INSERT INTO assignment_comments (school_id, assignment_id, user_type, user_id, comment, created_at) VALUES (?, ?, 'student', ?, ?, NOW())");
            $stmt_ins->execute([$schoolId, $assignment_id, $student_id, $comment_text]);
            header("Location: discussion.php?id=" . $assignment_id);
            exit();
        } catch (Exception $e) {
            $error = "Error posting comment: " . $e->getMessage();
        }
    }
}

// Fetch comments
$stmt_comm = $pdo->prepare("
    SELECT ac.*, 
           COALESCE(s.first_name, t.name) as user_name,
           COALESCE(s.last_name, '') as user_lname
    FROM assignment_comments ac
    LEFT JOIN students s ON ac.user_type = 'student' AND ac.user_id = s.id
    LEFT JOIN teachers t ON ac.user_type = 'teacher' AND ac.user_id = t.id
    WHERE ac.assignment_id = ? AND ac.school_id = ?
    ORDER BY ac.created_at ASC
");
$stmt_comm->execute([$assignment_id, $schoolId]);
$comments = $stmt_comm->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Discussion | Student Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Discussion / Doubts</h5>
    <div class="d-flex gap-2">
        <a href="submit.php?id=<?= $assignment_id ?>" class="btn btn-outline-primary"><i class="fa fa-arrow-left me-1"></i> Back to Assignment</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-light border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($assignment['title']) ?></h5>
                    <small class="text-primary fw-bold"><?= htmlspecialchars($assignment['subject_name']) ?> | <?= htmlspecialchars($assignment['teacher_name']) ?></small>
                </div>
            </div>
            
            <div class="card-body p-4" style="max-height: 500px; overflow-y: auto; background-color: #f8f9fa;">
                <?php if(count($comments) > 0): ?>
                    <?php foreach($comments as $c): ?>
                        <?php $is_me = ($c['user_type'] === 'student' && $c['user_id'] == $student_id); ?>
                        <div class="d-flex mb-4 <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="<?= $is_me ? 'bg-primary text-white' : 'bg-white border' ?>" style="max-width: 75%; border-radius: 15px; padding: 12px 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                <div class="d-flex justify-content-between align-items-baseline mb-1 gap-3">
                                    <strong class="<?= $is_me ? 'text-light' : ($c['user_type'] == 'teacher' ? 'text-danger' : 'text-dark') ?> small">
                                        <?= $c['user_type'] == 'teacher' ? '<i class="fa fa-user-tie"></i> ' : '' ?>
                                        <?= $is_me ? 'You' : htmlspecialchars(trim($c['user_name'].' '.$c['user_lname'])) ?>
                                    </strong>
                                    <small class="<?= $is_me ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.7rem;"><?= date('d M, h:i A', strtotime($c['created_at'])) ?></small>
                                </div>
                                <p class="m-0" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa fa-comments fa-3x mb-3 text-light"></i>
                        <p>No doubts asked yet. Start the discussion!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-footer bg-white border-0 p-3">
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="comment_text" class="form-control rounded-pill px-4" placeholder="Type your doubt here..." required autocomplete="off">
                    <button type="submit" name="post_comment" class="btn btn-primary rounded-pill px-4"><i class="fa fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
