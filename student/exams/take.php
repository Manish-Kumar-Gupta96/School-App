<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Get student details
$stmt_stu = $pdo->prepare("SELECT class_id, section_id FROM students WHERE id = ?");
$stmt_stu->execute([$student_id]);
$student = $stmt_stu->fetch(PDO::FETCH_ASSOC);

$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Submit Exam
if (isset($_POST['submit_exam'])) {
    $sub_exam_id = (int)$_POST['exam_id'];
    $answers = $_POST['answers'] ?? [];

    // Fetch correct answers
    $stmt_q = $pdo->prepare("SELECT id, correct_option, marks FROM exam_questions WHERE exam_id = ?");
    $stmt_q->execute([$sub_exam_id]);
    $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

    $total_qs = count($questions);
    $correct_count = 0;
    $score = 0;
    $max_score = 0;

    foreach ($questions as $q) {
        $max_score += $q['marks'];
        $ans = $answers[$q['id']] ?? '';
        if ($ans === $q['correct_option']) {
            $correct_count++;
            $score += $q['marks'];
        }
    }

    $percentage = ($max_score > 0) ? ($score / $max_score) * 100 : 0;

    $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
    $stmt_res = $pdo->prepare("
        INSERT INTO exam_results (school_id, exam_id, student_id, total_questions, correct_answers, score, percentage, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt_res->execute([$schoolId, $sub_exam_id, $student_id, $total_qs, $correct_count, $score, $percentage])) {
        $message = "Exam submitted successfully! Your Score: $score / $max_score (" . number_format($percentage, 2) . "%)";
        $exam_id = 0; // return to list
    } else {
        $error = "Failed to submit exam or already submitted.";
    }
}

// Logic to load specific exam or list available exams
if ($exam_id > 0) {
    // Load specific exam to take
    $stmt_ex = $pdo->prepare("SELECT * FROM online_exams WHERE id = ? AND class_id = ? AND start_time <= NOW() AND end_time >= NOW()");
    $stmt_ex->execute([$exam_id, $student['class_id']]);
    $current_exam = $stmt_ex->fetch(PDO::FETCH_ASSOC);

    if (!$current_exam) {
        $error = "Exam not available or expired.";
        $exam_id = 0;
    } else {
        // Check if already taken
        $stmt_check = $pdo->prepare("SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ?");
        $stmt_check->execute([$exam_id, $student_id]);
        if ($stmt_check->fetch()) {
            $error = "You have already completed this exam.";
            $exam_id = 0;
        } else {
            // Fetch questions
            $stmt_qs = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = ?");
            $stmt_qs->execute([$exam_id]);
            $exam_questions = $stmt_qs->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

if ($exam_id == 0) {
    // List available exams for the student's class
    $stmt_list = $pdo->prepare("
        SELECT e.*, sub.subject_name,
               (SELECT id FROM exam_results r WHERE r.exam_id = e.id AND r.student_id = ?) as taken
        FROM online_exams e
        JOIN subjects sub ON e.subject_id = sub.id
        WHERE e.class_id = ?
        ORDER BY e.start_time DESC
    ");
    $stmt_list->execute([$student_id, $student['class_id']]);
    $available_exams = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Online Exams | Student Portal";
$page_header = "Online Exams";
$active_menu = "exams";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Take your online MCQ tests</h5>
    <?php if ($exam_id > 0): ?>
        <a href="take.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success fw-bold text-center fs-5 py-4"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($exam_id == 0): ?>
    <!-- List of Exams -->
    <div class="row">
        <?php foreach($available_exams as $ae): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 border-0" style="border-radius: 12px;">
                    <div class="card-header bg-white border-0 py-3 ps-4">
                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($ae['exam_title']) ?></h5>
                        <div class="text-primary fw-semibold small"><?= htmlspecialchars($ae['subject_name']) ?></div>
                    </div>
                    <div class="card-body px-4">
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span><i class="fa fa-clock"></i> <?= $ae['duration_minutes'] ?> mins</span>
                            <span><i class="fa fa-bullseye"></i> Pass: <?= $ae['passing_percentage'] ?>%</span>
                        </div>
                        <p class="small mb-1"><strong>Window:</strong><br> <?= date('d M Y, h:i A', strtotime($ae['start_time'])) ?> to <?= date('d M Y, h:i A', strtotime($ae['end_time'])) ?></p>
                        
                        <div class="mt-4">
                            <?php if ($ae['taken']): ?>
                                <button class="btn btn-secondary w-100 disabled">Completed</button>
                            <?php else: ?>
                                <?php 
                                    $now = time();
                                    $start = strtotime($ae['start_time']);
                                    $end = strtotime($ae['end_time']);
                                    if ($now >= $start && $now <= $end) {
                                        echo '<a href="?id='.$ae['id'].'" class="btn btn-success w-100 fw-bold">Start Exam Now</a>';
                                    } elseif ($now < $start) {
                                        echo '<button class="btn btn-outline-secondary w-100 disabled">Upcoming</button>';
                                    } else {
                                        echo '<button class="btn btn-danger w-100 disabled">Expired</button>';
                                    }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Taking the Exam -->
    <div class="card shadow border-0" style="border-radius: 12px;">
        <div class="card-header bg-primary text-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><?= htmlspecialchars($current_exam['exam_title']) ?></h5>
            <span class="badge bg-light text-primary"><i class="fa fa-clock"></i> <?= $current_exam['duration_minutes'] ?> Mins Limit</span>
        </div>
        <div class="card-body p-4 bg-light">
            <form method="POST">
                <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                
                <?php $i=1; foreach($exam_questions as $q): ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 text-dark">Q<?= $i ?>. <?= nl2br(htmlspecialchars($q['question_text'])) ?> <span class="badge bg-secondary float-end"><?= $q['marks'] ?> Marks</span></h6>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>A" value="A">
                                <label class="form-check-label" for="q<?= $q['id'] ?>A"> A. <?= htmlspecialchars($q['option_a']) ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>B" value="B">
                                <label class="form-check-label" for="q<?= $q['id'] ?>B"> B. <?= htmlspecialchars($q['option_b']) ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>C" value="C">
                                <label class="form-check-label" for="q<?= $q['id'] ?>C"> C. <?= htmlspecialchars($q['option_c']) ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>D" value="D">
                                <label class="form-check-label" for="q<?= $q['id'] ?>D"> D. <?= htmlspecialchars($q['option_d']) ?></label>
                            </div>
                        </div>
                    </div>
                <?php $i++; endforeach; ?>
                
                <div class="text-center mt-4">
                    <button type="submit" name="submit_exam" class="btn btn-success btn-lg px-5 rounded-pill shadow" onclick="return confirm('Are you sure you want to submit your final answers?')">Submit Final Answers</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once('../includes/footer.php'); ?>
