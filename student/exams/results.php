<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Fetch Exam Results for this Student
$stmt_res = $pdo->prepare("
    SELECT er.*, e.exam_name, e.total_marks as max_marks, sub.subject_name
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects sub ON e.subject_id = sub.id
    WHERE er.student_id = ? AND er.school_id = ?
    ORDER BY er.id DESC
");
$stmt_res->execute([$student_id, $schoolId]);
$results = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$detailed_answers = [];
$selected_exam = null;

if ($view_id > 0) {
    // Get exam details
    $stmt_se = $pdo->prepare("SELECT e.exam_name, e.total_marks, sub.subject_name FROM exams e JOIN subjects sub ON e.subject_id=sub.id WHERE e.id=?");
    $stmt_se->execute([$view_id]);
    $selected_exam = $stmt_se->fetch(PDO::FETCH_ASSOC);

    // Get Attempt ID
    $stmt_att = $pdo->prepare("SELECT id FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
    $stmt_att->execute([$view_id, $student_id]);
    $attempt_id = $stmt_att->fetchColumn();

    if ($attempt_id) {
        $stmt_ans = $pdo->prepare("
            SELECT a.*, q.question, q.question_type, q.marks as max_marks
            FROM exam_answers a
            JOIN exam_questions eq ON a.question_id = eq.question_id
            JOIN question_bank q ON eq.question_id = q.id
            WHERE a.attempt_id = ? AND eq.exam_id = ?
        ");
        $stmt_ans->execute([$attempt_id, $view_id]);
        $detailed_answers = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);
    }
}

$root_path = "../../";
$page_title = "Exam Results | Student Portal";
$page_header = "Online Exams";
$active_menu = "exams";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">My Exam Results & Report Cards</h5>
    <div class="d-flex gap-2">
        <a href="list.php" class="btn btn-outline-primary"><i class="fa fa-list me-1"></i> Exam List</a>
        <a href="results.php" class="btn btn-success"><i class="fa fa-trophy me-1"></i> My Results</a>
    </div>
</div>

<?php if ($view_id > 0 && $selected_exam): ?>
    <!-- Detailed View -->
    <div class="card shadow border-0" style="border-radius: 12px;">
        <div class="card-header bg-primary text-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Detailed Review: <?= htmlspecialchars($selected_exam['exam_name']) ?></h5>
            <a href="results.php" class="btn btn-sm btn-light text-primary fw-bold">Back to Summary</a>
        </div>
        <div class="card-body p-4">
            <?php if(empty($detailed_answers)): ?>
                <div class="alert alert-warning">No detailed answers available.</div>
            <?php else: ?>
                <?php $q=1; foreach($detailed_answers as $ans): ?>
                    <div class="border rounded p-3 mb-3 <?= ($ans['obtained_marks'] == $ans['max_marks']) ? 'border-success' : 'border-danger' ?>">
                        <div class="d-flex justify-content-between">
                            <h6 class="fw-bold m-0">Q<?= $q ?>. <?= nl2br(htmlspecialchars($ans['question'])) ?></h6>
                            <span class="badge <?= ($ans['obtained_marks'] > 0) ? 'bg-success' : 'bg-danger' ?>">
                                <?= $ans['obtained_marks'] ?> / <?= $ans['max_marks'] ?>
                            </span>
                        </div>
                        <div class="mt-2 p-2 bg-light rounded text-muted small">
                            <strong>Your Answer:</strong><br>
                            <?php
                                if ($ans['question_type'] === 'mcq' || $ans['question_type'] === 'true_false') {
                                    // fetch option text
                                    $stmt_o = $pdo->prepare("SELECT option_text FROM question_options WHERE id = ?");
                                    $stmt_o->execute([(int)$ans['answer']]);
                                    echo htmlspecialchars($stmt_o->fetchColumn());
                                } else {
                                    echo nl2br(htmlspecialchars($ans['answer']));
                                }
                            ?>
                        </div>
                    </div>
                <?php $q++; endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Summary List -->
    <div class="row">
        <?php if(count($results) > 0): ?>
            <?php foreach($results as $r): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; position: relative; overflow: hidden;">
                        <?php if ($r['grade'] === 'A+' || $r['grade'] === 'A'): ?>
                            <div class="position-absolute top-0 end-0 p-2 text-warning"><i class="fa fa-star fa-2x"></i></div>
                        <?php endif; ?>
                        
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($r['exam_name']) ?></h5>
                            <div class="text-primary fw-semibold small"><?= htmlspecialchars($r['subject_name']) ?></div>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-6 border-end">
                                    <h3 class="fw-bold text-success m-0"><?= $r['percentage'] ?>%</h3>
                                    <small class="text-muted">Score (<?= $r['total_marks'] ?>/<?= $r['max_marks'] ?>)</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="fw-bold text-dark m-0"><?= htmlspecialchars($r['grade']) ?></h3>
                                    <small class="text-muted">Class Rank: #<?= $r['rank_no'] ?></small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="?view=<?= $r['exam_id'] ?>" class="btn btn-outline-primary w-50">Review</a>
                                <button class="btn btn-outline-secondary w-50" onclick="alert('PDF Generation under construction.')"><i class="fa fa-download"></i> PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-light text-center py-5 text-muted border">No results published yet.</div></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once('../includes/footer.php'); ?>
