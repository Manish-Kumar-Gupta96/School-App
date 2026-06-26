<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Grade mapping function
function getGrade($percentage) {
    if ($percentage >= 91) return 'A+';
    if ($percentage >= 81) return 'A';
    if ($percentage >= 71) return 'B+';
    if ($percentage >= 61) return 'B';
    if ($percentage >= 51) return 'C';
    if ($percentage >= 41) return 'D';
    return 'Fail';
}

// 1. Manual Evaluation Submit
if (isset($_POST['evaluate_answers'])) {
    $attempt_id = (int)$_POST['attempt_id'];
    $marks_array = $_POST['marks'] ?? [];
    
    try {
        $pdo->beginTransaction();
        $stmt_upd = $pdo->prepare("UPDATE exam_answers SET obtained_marks = ? WHERE id = ? AND attempt_id = ?");
        foreach ($marks_array as $ans_id => $marks) {
            $stmt_upd->execute([(float)$marks, $ans_id, $attempt_id]);
        }
        $pdo->commit();
        $message = "Subjective evaluation saved.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Evaluation failed: " . $e->getMessage();
    }
}

// 2. Publish Results (Calculate Total Marks, Percentage, Grade, and Rank)
if (isset($_POST['publish_results'])) {
    $exam_id = (int)$_POST['exam_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get all submitted attempts for this exam
        $stmt_attempts = $pdo->prepare("SELECT id, student_id FROM exam_attempts WHERE exam_id = ? AND status = 'submitted'");
        $stmt_attempts->execute([$exam_id]);
        $attempts = $stmt_attempts->fetchAll(PDO::FETCH_ASSOC);
        
        // Get exam total marks
        $stmt_exam = $pdo->prepare("SELECT total_marks FROM exams WHERE id = ?");
        $stmt_exam->execute([$exam_id]);
        $exam_total = (float)$stmt_exam->fetchColumn();

        $results_data = [];

        foreach ($attempts as $att) {
            // Sum all obtained marks in exam_answers for this attempt
            $stmt_sum = $pdo->prepare("SELECT COALESCE(SUM(obtained_marks), 0) FROM exam_answers WHERE attempt_id = ?");
            $stmt_sum->execute([$att['id']]);
            $obtained = (float)$stmt_sum->fetchColumn();
            
            $percentage = ($exam_total > 0) ? ($obtained / $exam_total) * 100 : 0;
            $grade = getGrade($percentage);

            $results_data[] = [
                'student_id' => $att['student_id'],
                'total_marks' => $obtained,
                'percentage' => $percentage,
                'grade' => $grade
            ];
        }

        // Sort by percentage descending to assign ranks
        usort($results_data, function($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        // Insert into exam_results
        $stmt_res = $pdo->prepare("
            INSERT INTO exam_results (school_id, exam_id, student_id, total_marks, percentage, grade, rank_no)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            total_marks = VALUES(total_marks), percentage = VALUES(percentage), grade = VALUES(grade), rank_no = VALUES(rank_no)
        ");

        $rank = 1;
        foreach ($results_data as $rd) {
            $stmt_res->execute([$schoolId, $exam_id, $rd['student_id'], $rd['total_marks'], $rd['percentage'], $rd['grade'], $rank]);
            $rank++;
        }

        $pdo->commit();
        $message = "Results published and rankings generated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Publish failed: " . $e->getMessage();
    }
}

// Fetch teacher's exams
$stmt_exams = $pdo->prepare("
    SELECT e.*, c.class_name, sub.subject_name
    FROM exams e
    JOIN classes c ON e.class_id = c.id
    JOIN subjects sub ON e.subject_id = sub.id
    WHERE e.school_id = ? AND e.created_by = ?
    ORDER BY e.id DESC
");
$stmt_exams->execute([$schoolId, $teacher_id]);
$exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$evaluate_attempt_id = isset($_GET['evaluate']) ? (int)$_GET['evaluate'] : 0;

$students_attempts = [];
$eval_data = [];

if ($selected_exam_id > 0) {
    // List all attempts for this exam
    $stmt_att = $pdo->prepare("
        SELECT ea.*, s.first_name, s.last_name, s.roll_number,
               (SELECT total_marks FROM exam_results er WHERE er.exam_id = ea.exam_id AND er.student_id = ea.student_id) as published_marks,
               (SELECT grade FROM exam_results er WHERE er.exam_id = ea.exam_id AND er.student_id = ea.student_id) as published_grade
        FROM exam_attempts ea
        JOIN students s ON ea.student_id = s.id
        WHERE ea.exam_id = ?
        ORDER BY s.roll_number ASC
    ");
    $stmt_att->execute([$selected_exam_id]);
    $students_attempts = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
}

if ($evaluate_attempt_id > 0) {
    // Load student's answers for subjective evaluation
    $stmt_ans = $pdo->prepare("
        SELECT a.*, q.question, q.question_type, q.marks as max_marks
        FROM exam_answers a
        JOIN exam_questions eq ON a.question_id = eq.question_id
        JOIN question_bank q ON eq.question_id = q.id
        WHERE a.attempt_id = ? AND eq.exam_id = ?
    ");
    $stmt_ans->execute([$evaluate_attempt_id, $selected_exam_id]);
    $eval_data = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Exam Results & Evaluation | Teacher Portal";
$page_header = "Evaluation";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Evaluate Subjective Answers & Publish Results</h5>
    <div class="d-flex gap-2">
        <a href="question_bank.php" class="btn btn-outline-primary"><i class="fa fa-database me-1"></i> Question Bank</a>
        <a href="builder.php" class="btn btn-outline-primary"><i class="fa fa-file-alt me-1"></i> Manage Exams</a>
        <a href="results.php" class="btn btn-success"><i class="fa fa-chart-bar me-1"></i> Results & Evaluation</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Select Exam -->
    <div class="col-lg-3 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h6 class="fw-bold mb-0">Select Exam</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach($exams as $e): ?>
                    <a href="?exam_id=<?= $e['id'] ?>" class="list-group-item list-group-item-action <?= ($selected_exam_id == $e['id']) ? 'active fw-bold' : '' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($e['exam_name']) ?></h6>
                        </div>
                        <small><?= htmlspecialchars($e['class_name']) ?> - <?= htmlspecialchars($e['subject_name']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Exam Attempts & Publish -->
    <div class="col-lg-9">
        <?php if ($evaluate_attempt_id > 0 && !empty($eval_data)): ?>
            <!-- Manual Evaluation View -->
            <div class="card shadow border-0 border-warning" style="border-radius: 12px;">
                <div class="card-header bg-warning border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Evaluating Attempt #<?= $evaluate_attempt_id ?></h5>
                    <a href="?exam_id=<?= $selected_exam_id ?>" class="btn btn-sm btn-dark">Close Evaluation</a>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="attempt_id" value="<?= $evaluate_attempt_id ?>">
                        <input type="hidden" name="exam_id" value="<?= $selected_exam_id ?>">
                        
                        <?php foreach($eval_data as $ans): ?>
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="fw-bold m-0"><?= nl2br(htmlspecialchars($ans['question'])) ?></h6>
                                    <span class="badge bg-secondary"><?= $ans['question_type'] ?> | Max: <?= $ans['max_marks'] ?></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Student's Answer:</strong><br>
                                    <div class="p-2 border rounded bg-white mt-1">
                                        <?= nl2br(htmlspecialchars($ans['answer'])) ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="fw-semibold text-primary">Award Marks:</label>
                                    <?php if(in_array($ans['question_type'], ['mcq', 'true_false'])): ?>
                                        <input type="number" step="0.1" class="form-control w-25 d-inline-block" value="<?= $ans['obtained_marks'] ?>" readonly disabled>
                                        <small class="text-muted ms-2">(Auto-evaluated)</small>
                                    <?php else: ?>
                                        <input type="number" step="0.1" name="marks[<?= $ans['id'] ?>]" class="form-control w-25 d-inline-block" value="<?= $ans['obtained_marks'] ?>" max="<?= $ans['max_marks'] ?>" required>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" name="evaluate_answers" class="btn btn-primary w-100 mt-3">Save Subjective Marks</button>
                    </form>
                </div>
            </div>
        <?php elseif ($selected_exam_id > 0): ?>
            <!-- Attempts List -->
            <div class="card shadow border-0" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Student Attempts</h5>
                    <form method="POST" class="m-0" onsubmit="return confirm('This will calculate ranks and grades based on current marks. Proceed?');">
                        <input type="hidden" name="exam_id" value="<?= $selected_exam_id ?>">
                        <button type="submit" name="publish_results" class="btn btn-sm btn-success"><i class="fa fa-bullhorn"></i> Publish Results & Ranks</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Status</th>
                                    <th>Start Time</th>
                                    <th>Submit Time</th>
                                    <th>Result</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($students_attempts) > 0): ?>
                                    <?php foreach($students_attempts as $att): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                <?= htmlspecialchars($att['first_name'].' '.$att['last_name']) ?>
                                                <div class="small text-muted">Roll: <?= htmlspecialchars($att['roll_number']) ?></div>
                                            </td>
                                            <td>
                                                <?php if($att['status'] == 'submitted'): ?>
                                                    <span class="badge bg-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">In Progress</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small"><?= $att['start_time'] ?></td>
                                            <td class="small"><?= $att['submit_time'] ?? '-' ?></td>
                                            <td>
                                                <?php if($att['published_marks'] !== null): ?>
                                                    <span class="fw-bold text-primary"><?= $att['published_marks'] ?></span>
                                                    <span class="badge bg-dark ms-1"><?= $att['published_grade'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Not published</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($att['status'] == 'submitted'): ?>
                                                    <a href="?exam_id=<?= $selected_exam_id ?>&evaluate=<?= $att['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill">Evaluate</a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary rounded-pill" disabled>Wait</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted p-4">No attempts recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px;">
                <i class="fa fa-info-circle me-2"></i> Please select an exam from the left panel to view student attempts and evaluate answers.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
