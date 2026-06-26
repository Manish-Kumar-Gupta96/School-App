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

// Add Question
if (isset($_POST['add_question'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $chapter_name = trim($_POST['chapter_name']);
    $question_type = trim($_POST['question_type']); // mcq, true_false, short, long
    $question_text = trim($_POST['question_text']);
    $marks = (int)$_POST['marks'];

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO question_bank (school_id, class_id, subject_id, chapter_name, question_type, question, marks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$schoolId, $class_id, $subject_id, $chapter_name, $question_type, $question_text, $marks, $teacher_id]);
        $question_id = $pdo->lastInsertId();

        // Handle Options for MCQ / True False
        if ($question_type === 'mcq') {
            $options = $_POST['options'] ?? [];
            $correct_index = (int)$_POST['correct_mcq_option'];
            $stmt_opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            foreach ($options as $idx => $opt_text) {
                if (trim($opt_text) !== '') {
                    $is_correct = ($idx === $correct_index) ? 1 : 0;
                    $stmt_opt->execute([$question_id, trim($opt_text), $is_correct]);
                }
            }
        } elseif ($question_type === 'true_false') {
            $correct_tf = trim($_POST['correct_tf_option']);
            $stmt_opt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            $stmt_opt->execute([$question_id, 'True', ($correct_tf === 'True') ? 1 : 0]);
            $stmt_opt->execute([$question_id, 'False', ($correct_tf === 'False') ? 1 : 0]);
        }

        $pdo->commit();
        $message = "Question added to bank successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to add question: " . $e->getMessage();
    }
}

// Delete Question
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt_chk = $pdo->prepare("SELECT id FROM question_bank WHERE id = ? AND created_by = ?");
    $stmt_chk->execute([$del_id, $teacher_id]);
    if ($stmt_chk->fetch()) {
        $pdo->prepare("DELETE FROM question_options WHERE question_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM question_bank WHERE id = ?")->execute([$del_id]);
        $message = "Question deleted.";
    }
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

// Fetch Bank
$stmt_bank = $pdo->prepare("
    SELECT q.*, c.class_name, sub.subject_name,
           (SELECT COUNT(*) FROM question_options WHERE question_id = q.id) as opt_count
    FROM question_bank q
    JOIN classes c ON q.class_id = c.id
    JOIN subjects sub ON q.subject_id = sub.id
    WHERE q.school_id = ? AND q.created_by = ?
    ORDER BY q.id DESC
");
$stmt_bank->execute([$schoolId, $teacher_id]);
$questions = $stmt_bank->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Question Bank | Teacher Portal";
$page_header = "Online Exams";
$active_menu = "question_bank";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Centralized Question Bank</h5>
    <div class="d-flex gap-2">
        <a href="question_bank.php" class="btn btn-primary"><i class="fa fa-database me-1"></i> Question Bank</a>
        <a href="builder.php" class="btn btn-outline-primary"><i class="fa fa-file-alt me-1"></i> Manage Exams</a>
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
    <!-- Add Question -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">Add New Question</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Class & Subject</label>
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

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Chapter / Topic</label>
                            <input type="text" name="chapter_name" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Marks</label>
                            <input type="number" name="marks" class="form-control" value="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question Type</label>
                        <select name="question_type" id="question_type" class="form-select" required onchange="toggleOptions()">
                            <option value="mcq">Multiple Choice (MCQ)</option>
                            <option value="true_false">True / False</option>
                            <option value="short">Short Answer</option>
                            <option value="long">Long Essay</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question Content</label>
                        <textarea name="question_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- MCQ Options -->
                    <div id="mcq_block" class="mb-3 border p-3 bg-light rounded">
                        <label class="form-label fw-semibold mb-2">Options (Select radio for correct answer)</label>
                        <?php for($i=0; $i<4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text">
                                    <input type="radio" name="correct_mcq_option" value="<?= $i ?>" <?= $i==0?'checked':'' ?> required>
                                </div>
                                <input type="text" name="options[<?= $i ?>]" class="form-control" placeholder="Option <?= chr(65+$i) ?>">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- True/False Options -->
                    <div id="tf_block" class="mb-3 border p-3 bg-light rounded d-none">
                        <label class="form-label fw-semibold mb-2">Correct Answer</label>
                        <select name="correct_tf_option" class="form-select">
                            <option value="True">True</option>
                            <option value="False">False</option>
                        </select>
                    </div>

                    <button type="submit" name="add_question" class="btn btn-primary w-100">Save to Bank</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Question Bank List -->
    <div class="col-lg-7">
        <div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">My Question Bank</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Question</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Marks</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($questions as $q): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="small fw-bold text-dark text-wrap" style="max-width: 250px;">
                                            <?= htmlspecialchars(substr($q['question'], 0, 50)) ?>...
                                        </div>
                                        <div class="small text-muted mt-1">Ch: <?= htmlspecialchars($q['chapter_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-primary"><?= htmlspecialchars($q['subject_name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($q['class_name']) ?></div>
                                    </td>
                                    <td>
                                        <?php
                                            $types = [
                                                'mcq' => '<span class="badge bg-info">MCQ</span>',
                                                'true_false' => '<span class="badge bg-secondary">T/F</span>',
                                                'short' => '<span class="badge bg-warning text-dark">Short</span>',
                                                'long' => '<span class="badge bg-danger">Long</span>'
                                            ];
                                            echo $types[$q['question_type']] ?? '';
                                        ?>
                                    </td>
                                    <td class="fw-bold"><?= $q['marks'] ?></td>
                                    <td class="text-center pe-4">
                                        <a href="?delete=<?= $q['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this question?')">
                                            <i class="fa fa-trash"></i>
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

<script>
function toggleOptions() {
    const type = document.getElementById('question_type').value;
    document.getElementById('mcq_block').classList.add('d-none');
    document.getElementById('tf_block').classList.add('d-none');

    if (type === 'mcq') {
        document.getElementById('mcq_block').classList.remove('d-none');
    } else if (type === 'true_false') {
        document.getElementById('tf_block').classList.remove('d-none');
    }
}
</script>

<?php require_once('../includes/footer.php'); ?>
