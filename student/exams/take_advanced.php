<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exam_id <= 0) {
    header("Location: list.php");
    exit();
}

// Check Exam Validity
$stmt_ex = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND start_time <= NOW() AND end_time >= NOW()");
$stmt_ex->execute([$exam_id]);
$exam = $stmt_ex->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam is not active or has expired.");
}

// Check or Create Attempt
$stmt_att = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
$stmt_att->execute([$exam_id, $student_id]);
$attempt = $stmt_att->fetch(PDO::FETCH_ASSOC);

if ($attempt && $attempt['status'] === 'submitted') {
    die("You have already submitted this exam.");
}

$attempt_id = 0;
if (!$attempt) {
    $stmt_ins = $pdo->prepare("INSERT INTO exam_attempts (school_id, exam_id, student_id, start_time, status) VALUES (?, ?, ?, NOW(), 'started')");
    $stmt_ins->execute([$schoolId, $exam_id, $student_id]);
    $attempt_id = $pdo->lastInsertId();
} else {
    $attempt_id = $attempt['id'];
}

// Handle Form Submission
if (isset($_POST['submit_exam'])) {
    $answers = $_POST['answers'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        $stmt_ans = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer, obtained_marks) VALUES (?, ?, ?, ?)");
        
        foreach ($answers as $q_id => $ans_val) {
            // Check question type
            $stmt_qt = $pdo->prepare("SELECT question_type, marks FROM question_bank WHERE id = ?");
            $stmt_qt->execute([$q_id]);
            $q_info = $stmt_qt->fetch(PDO::FETCH_ASSOC);
            
            $obtained_marks = 0;
            
            // Auto Evaluate MCQ / TF
            if ($q_info['question_type'] === 'mcq' || $q_info['question_type'] === 'true_false') {
                $stmt_corr = $pdo->prepare("SELECT id FROM question_options WHERE question_id = ? AND is_correct = 1");
                $stmt_corr->execute([$q_id]);
                $correct_opt_id = $stmt_corr->fetchColumn();
                
                // ans_val for MCQ/TF is the option_id
                if ((int)$ans_val === (int)$correct_opt_id) {
                    $obtained_marks = $q_info['marks'];
                }
            }
            
            $stmt_ans->execute([$attempt_id, $q_id, $ans_val, $obtained_marks]);
        }
        
        // Update Attempt
        $stmt_upd = $pdo->prepare("UPDATE exam_attempts SET submit_time = NOW(), status = 'submitted' WHERE id = ?");
        $stmt_upd->execute([$attempt_id]);
        
        $pdo->commit();
        echo "<script>alert('Exam submitted successfully!'); window.location.href='list.php';</script>";
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Submission failed: " . $e->getMessage());
    }
}

// Fetch Questions
$stmt_qs = $pdo->prepare("
    SELECT q.* FROM exam_questions eq
    JOIN question_bank q ON eq.question_id = q.id
    WHERE eq.exam_id = ?
");
$stmt_qs->execute([$exam_id]);
$questions = $stmt_qs->fetchAll(PDO::FETCH_ASSOC);

// Calculate remaining time
$end_timestamp = strtotime($exam['end_time']);
$now_timestamp = time();
$remaining_seconds = $end_timestamp - $now_timestamp;
if ($remaining_seconds <= 0) {
    die("Time is up.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exam['exam_name']) ?> - Active Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f8f9fa; user-select: none; }
        .exam-header { position: sticky; top: 0; z-index: 1000; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .timer-box { font-size: 1.5rem; font-weight: bold; font-family: monospace; }
        .q-box { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="exam-header py-3 px-4 d-flex justify-content-between align-items-center">
    <h4 class="m-0 fw-bold text-primary"><?= htmlspecialchars($exam['exam_name']) ?></h4>
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-danger p-2 fs-6">
            ⏳ Time Left: <span id="timer" class="timer-box"><?= gmdate("H:i:s", $remaining_seconds) ?></span>
        </span>
        <button type="button" class="btn btn-success fw-bold" onclick="document.getElementById('examForm').submit();">Submit Exam</button>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    <div class="alert alert-warning">
        <strong>⚠️ Anti-Cheat Active:</strong> Do not switch tabs, minimize this window, or exit fullscreen. Violations are automatically recorded and reported.
    </div>

    <form method="POST" id="examForm">
        <?php $q_num = 1; foreach($questions as $q): ?>
            <div class="q-box">
                <div class="d-flex justify-content-between">
                    <h5 class="fw-bold">Q<?= $q_num ?>. <?= nl2br(htmlspecialchars($q['question'])) ?></h5>
                    <span class="badge bg-secondary h-100"><?= $q['marks'] ?> Marks</span>
                </div>
                
                <div class="mt-3">
                    <?php if($q['question_type'] === 'mcq' || $q['question_type'] === 'true_false'): ?>
                        <?php
                            $stmt_opts = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ?");
                            $stmt_opts->execute([$q['id']]);
                            $options = $stmt_opts->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach($options as $opt): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="opt_<?= $opt['id'] ?>" value="<?= $opt['id'] ?>">
                                <label class="form-check-label" for="opt_<?= $opt['id'] ?>">
                                    <?= htmlspecialchars($opt['option_text']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif($q['question_type'] === 'short'): ?>
                        <input type="text" name="answers[<?= $q['id'] ?>]" class="form-control" placeholder="Type your short answer here...">
                    <?php elseif($q['question_type'] === 'long'): ?>
                        <textarea name="answers[<?= $q['id'] ?>]" class="form-control" rows="5" placeholder="Type your essay/long answer here..."></textarea>
                    <?php endif; ?>
                </div>
            </div>
        <?php $q_num++; endforeach; ?>
        <input type="hidden" name="submit_exam" value="1">
    </form>
</div>

<script>
    // Timer Logic
    let remaining = <?= $remaining_seconds ?>;
    const timerDisplay = document.getElementById('timer');
    
    setInterval(() => {
        if (remaining <= 0) {
            document.getElementById('examForm').submit();
        } else {
            remaining--;
            let h = Math.floor(remaining / 3600);
            let m = Math.floor((remaining % 3600) / 60);
            let s = remaining % 60;
            timerDisplay.innerText = 
                (h < 10 ? "0" + h : h) + ":" + 
                (m < 10 ? "0" + m : m) + ":" + 
                (s < 10 ? "0" + s : s);
        }
    }, 1000);

    // Anti-Cheat: Tab Switch Detection
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            logViolation('Tab Switched / Minimized');
        }
    });

    window.addEventListener("blur", () => {
        logViolation('Window lost focus');
    });

    // Disable right click, copy, paste
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('copy', event => {
        event.preventDefault();
        logViolation('Attempted to Copy Text');
    });
    
    function logViolation(type) {
        $.post('../../api/exams/log_violation.php', {
            exam_id: <?= $exam_id ?>,
            student_id: <?= $student_id ?>,
            violation_type: type
        }).done(function(data){
            console.log("Violation logged.");
        });
        alert('WARNING: ' + type + ' detected. This violation has been logged.');
    }
</script>
</body>
</html>
