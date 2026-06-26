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

// Create Exam
if (isset($_POST['create_exam'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $exam_name = trim($_POST['exam_name']);
    $total_marks = (int)$_POST['total_marks'];
    $passing_marks = (int)$_POST['passing_marks'];
    $exam_date = trim($_POST['exam_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $exam_type = 'online'; // forcing online as per context

    $stmt = $pdo->prepare("
        INSERT INTO exams (school_id, exam_name, class_id, subject_id, total_marks, passing_marks, exam_date, start_time, end_time, exam_type, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$schoolId, $exam_name, $class_id, $subject_id, $total_marks, $passing_marks, $exam_date, $start_time, $end_time, $exam_type, $teacher_id])) {
        $message = "Exam template created successfully. Select it to add questions.";
        $_GET['exam_id'] = $pdo->lastInsertId();
    } else {
        $error = "Failed to create exam.";
    }
}

// Add Questions to Exam
if (isset($_POST['assign_questions'])) {
    $exam_id = (int)$_POST['exam_id'];
    $questions = $_POST['questions'] ?? [];

    if (!empty($questions)) {
        try {
            $pdo->beginTransaction();
            $stmt_ins = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
            foreach ($questions as $qid) {
                // Check if already assigned
                $stmt_chk = $pdo->prepare("SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ?");
                $stmt_chk->execute([$exam_id, $qid]);
                if (!$stmt_chk->fetch()) {
                    $stmt_ins->execute([$exam_id, $qid]);
                }
            }
            $pdo->commit();
            $message = "Questions assigned successfully.";
            $_GET['exam_id'] = $exam_id;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to assign questions.";
        }
    }
}

// Remove Question from Exam
if (isset($_GET['remove_q']) && isset($_GET['exam_id'])) {
    $q_id = (int)$_GET['remove_q'];
    $e_id = (int)$_GET['exam_id'];
    $stmt = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ? AND question_id = ?");
    $stmt->execute([$e_id, $q_id]);
    $message = "Question removed from exam.";
}

// Fetch assigned subjects
$stmt_assigned = $pdo->prepare("
    SELECT tca.class_id, tca.subject_id, c.class_name, sub.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN subjects sub ON tca.subject_id = sub.id
    WHERE tca.teacher_id = ?
    GROUP BY tca.class_id, tca.subject_id
");
$stmt_assigned->execute([$teacher_id]);
$assigned_classes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

// Fetch teacher's exams
$stmt_exams = $pdo->prepare("
    SELECT e.*, c.class_name, sub.subject_name,
           (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as q_count
    FROM exams e
    JOIN classes c ON e.class_id = c.id
    JOIN subjects sub ON e.subject_id = sub.id
    WHERE e.school_id = ? AND e.created_by = ?
    ORDER BY e.id DESC
");
$stmt_exams->execute([$schoolId, $teacher_id]);
$exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$current_exam = null;
$available_questions = [];
$assigned_questions = [];

if ($selected_exam_id > 0) {
    // Get exam details
    $stmt_ce = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt_ce->execute([$selected_exam_id]);
    $current_exam = $stmt_ce->fetch(PDO::FETCH_ASSOC);

    // Get available questions from bank for this subject & class
    $stmt_aq = $pdo->prepare("
        SELECT q.* FROM question_bank q
        WHERE q.class_id = ? AND q.subject_id = ?
        AND q.id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
    ");
    $stmt_aq->execute([$current_exam['class_id'], $current_exam['subject_id'], $selected_exam_id]);
    $available_questions = $stmt_aq->fetchAll(PDO::FETCH_ASSOC);

    // Get assigned questions
    $stmt_assigned_qs = $pdo->prepare("
        SELECT q.* FROM exam_questions eq
        JOIN question_bank q ON eq.question_id = q.id
        WHERE eq.exam_id = ?
    ");
    $stmt_assigned_qs->execute([$selected_exam_id]);
    $assigned_questions = $stmt_assigned_qs->fetchAll(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Exam Builder | Teacher Portal";
$page_header = "Online Exams";
$active_menu = "exams";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Build and manage online exams</h5>
    <div class="d-flex gap-2">
        <a href="question_bank.php" class="btn btn-outline-primary"><i class="fa fa-database me-1"></i> Question Bank</a>
        <a href="builder.php" class="btn btn-primary"><i class="fa fa-file-alt me-1"></i> Manage Exams</a>
        <a href="results.php" class="btn btn-outline-success"><i class="fa fa-chart-bar me-1"></i> Results & Evaluation</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Exam Creator / Question Assigner -->
    <div class="col-lg-5 mb-4">
        <!-- Step 1 -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">Step 1: Exam Settings</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class & Subject</label>
                        <select name="mapping" class="form-select" required onchange="this.form.class_id.value=this.value.split('-')[0];this.form.subject_id.value=this.value.split('-')[1];">
                            <option value="">Select...</option>
                            <?php foreach($assigned_classes as $ac): ?>
                                <option value="<?= $ac['class_id'].'-'.$ac['subject_id'] ?>">
                                    <?= htmlspecialchars($ac['class_name']) ?> (<?= htmlspecialchars($ac['subject_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="class_id">
                        <input type="hidden" name="subject_id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Name</label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. Mid Term Exam" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Total Marks</label>
                            <input type="number" name="total_marks" class="form-control" required value="100">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Passing Marks</label>
                            <input type="number" name="passing_marks" class="form-control" required value="40">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Start Time</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">End Time</label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" name="create_exam" class="btn btn-primary w-100">Create Exam Template</button>
                </form>
            </div>
        </div>

        <!-- Step 2 -->
        <?php if ($selected_exam_id > 0 && $current_exam): ?>
        <div class="card shadow border-0 border-primary" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">Step 2: Assign Questions to "<?= htmlspecialchars($current_exam['exam_name']) ?>"</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="exam_id" value="<?= $selected_exam_id ?>">
                    <div style="max-height: 300px; overflow-y: auto;" class="mb-3 border rounded p-2">
                        <?php if(count($available_questions) > 0): ?>
                            <?php foreach($available_questions as $aq): ?>
                                <div class="form-check mb-2 pb-2 border-bottom">
                                    <input class="form-check-input" type="checkbox" name="questions[]" value="<?= $aq['id'] ?>" id="q<?= $aq['id'] ?>">
                                    <label class="form-check-label d-block" for="q<?= $aq['id'] ?>">
                                        <div class="fw-bold small"><?= htmlspecialchars(substr($aq['question'], 0, 80)) ?>...</div>
                                        <div class="text-muted small">Type: <?= $aq['question_type'] ?> | Marks: <?= $aq['marks'] ?></div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted small p-2">No available questions left in the bank for this class/subject.</div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="assign_questions" class="btn btn-success w-100">Assign Selected Questions</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Exam List & Assigned Questions -->
    <div class="col-lg-7">
        <?php if ($selected_exam_id > 0): ?>
            <!-- Currently assigned questions -->
            <div class="card shadow border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between">
                    <h5 class="fw-bold mb-0 text-success">Questions Assigned to this Exam</h5>
                    <a href="builder.php" class="btn btn-sm btn-outline-secondary">Close View</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Question</th>
                                    <th>Type</th>
                                    <th>Marks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($assigned_questions) > 0): ?>
                                    <?php foreach($assigned_questions as $asq): ?>
                                        <tr>
                                            <td class="ps-4 small text-wrap" style="max-width: 250px;">
                                                <?= htmlspecialchars(substr($asq['question'], 0, 50)) ?>...
                                            </td>
                                            <td class="small"><span class="badge bg-secondary"><?= $asq['question_type'] ?></span></td>
                                            <td class="fw-bold"><?= $asq['marks'] ?></td>
                                            <td>
                                                <a href="?exam_id=<?= $selected_exam_id ?>&remove_q=<?= $asq['id'] ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted p-4">No questions assigned yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Exam List -->
        <div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">My Created Exams</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Exam Name</th>
                                <th>Subject</th>
                                <th>Timing</th>
                                <th>Qs</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($exams as $e): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <?= htmlspecialchars($e['exam_name']) ?>
                                        <div class="small text-muted">Pass: <?= $e['passing_marks'] ?>/<?= $e['total_marks'] ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-primary"><?= htmlspecialchars($e['subject_name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($e['class_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold"><?= date('d M Y', strtotime($e['exam_date'])) ?></div>
                                        <div class="small text-danger"><?= date('H:i', strtotime($e['start_time'])) ?> to <?= date('H:i', strtotime($e['end_time'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-info"><?= $e['q_count'] ?></span></td>
                                    <td class="text-center">
                                        <a href="?exam_id=<?= $e['id'] ?>" class="btn btn-sm <?= ($selected_exam_id == $e['id']) ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3">
                                            Manage Qs
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
