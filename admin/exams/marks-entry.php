<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

$class = $_GET['class'] ?? '';
$exam_id = $_GET['exam_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';

/* ========================
SAVE MARKS
======================== */
if (isset($_POST['save_marks'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];
    $class = $_POST['class_name'];

    try {
        $pdo->beginTransaction();

        // Fetch subject details to get total marks
        $stmt_subj = $pdo->prepare("SELECT total_marks FROM subjects WHERE id = ?");
        $stmt_subj->execute([$subject_id]);
        $subject = $stmt_subj->fetch(PDO::FETCH_ASSOC);
        $total_marks = $subject ? $subject['total_marks'] : 100;

        foreach ($_POST['marks'] as $student_id => $obtained_marks) {
            if ($obtained_marks === '') {
                continue; // Skip empty fields
            }
            $obtained_marks = (float)$obtained_marks;
            $remarks = $_POST['remarks'][$student_id] ?? '';

            // Check if record exists
            $check = $pdo->prepare("
                SELECT id FROM marks 
                WHERE student_id = ? AND exam_id = ? AND subject_id = ?
            ");
            $check->execute([$student_id, $exam_id, $subject_id]);

            if ($check->rowCount() > 0) {
                // Update
                $update = $pdo->prepare("
                    UPDATE marks 
                    SET obtained_marks = ?, total_marks = ?, remarks = ?
                    WHERE student_id = ? AND exam_id = ? AND subject_id = ?
                ");
                $update->execute([$obtained_marks, $total_marks, $remarks, $student_id, $exam_id, $subject_id]);
            } else {
                // Insert
                $insert = $pdo->prepare("
                    INSERT INTO marks (student_id, exam_id, subject_id, obtained_marks, total_marks, remarks)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([$student_id, $exam_id, $subject_id, $obtained_marks, $total_marks, $remarks]);
            }
        }

        require_once('../includes/audit-helper.php');
        addAuditLog(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['name'] ?? 'Admin',
            $_SESSION['role'] ?? 'Admin',
            'Marks recorded/updated for Subject ID: ' . $subject_id . ' (Exam ID: ' . $exam_id . ', Class: ' . $class . ')',
            'Exams',
            $exam_id
        );

        $pdo->commit();
        $message = "Marks Saved Successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save marks: " . $e->getMessage();
    }
}

// Fetch distinct classes from database
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Fetch subjects and exams if class selected
$subjects = [];
$exams = [];
$students = [];

if ($class) {
    // Fetch subjects for this class
    $stmt_s = $pdo->prepare("SELECT id, subject_name, subject_code, total_marks FROM subjects WHERE class_name = ? ORDER BY subject_name ASC");
    $stmt_s->execute([$class]);
    $subjects = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

    // Fetch exams for this class
    $stmt_e = $pdo->prepare("SELECT id, exam_name FROM exams WHERE class_name = ? ORDER BY id DESC");
    $stmt_e->execute([$class]);
    $exams = $stmt_e->fetchAll(PDO::FETCH_ASSOC);

    // Load students with marks pre-filled if exam and subject are selected
    if ($exam_id && $subject_id) {
        $stmt_st = $pdo->prepare("
            SELECT 
                s.id, 
                s.admission_no, 
                s.first_name, 
                s.last_name, 
                m.obtained_marks,
                m.remarks
            FROM students s
            LEFT JOIN marks m ON s.id = m.student_id AND m.exam_id = ? AND m.subject_id = ?
            WHERE s.class = ?
            ORDER BY s.first_name ASC
        ");
        $stmt_st->execute([$exam_id, $subject_id, $class]);
        $students = $stmt_st->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Marks Entry | VIC ERP";
$page_header = "Marks Entry Board";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Record student marks subject-wise</h5>
    <a href="create-exam.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Exams
    </a>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- SELECT CLASS & PARAMETERS -->
<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Class</label>
                <select name="class" class="form-select" onchange="this.form.submit()" required>
                    <option value="">Select Class</option>
                    <?php if (count($classes) > 0): ?>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($class == $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="Class <?= $i ?>" <?= ($class == "Class $i") ? 'selected' : '' ?>>
                                Class <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    <?php endif; ?>
                </select>
            </div>

            <?php if ($class): ?>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Exam</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">Select Exam</option>
                        <?php foreach($exams as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($exam_id == $e['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['exam_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">Select Subject</option>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($subject_id == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['subject_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary">
                        <i class="fa fa-users me-1"></i> Load Students
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($class && $exam_id && $subject_id): ?>
    <?php if (count($students) > 0): ?>
        <form method="POST">
            <input type="hidden" name="class_name" value="<?= htmlspecialchars($class) ?>">
            <input type="hidden" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
            <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">

            <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Admission No</th>
                                    <th>Student Name</th>
                                    <th width="200">Marks Obtained</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $st): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars($st['admission_no']) ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                        <td>
                                            <input type="number" name="marks[<?= $st['id'] ?>]" class="form-control form-control-sm" min="0" max="100" step="0.5" placeholder="Enter marks" value="<?= $st['obtained_marks'] !== null ? htmlspecialchars($st['obtained_marks']) : '' ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?= $st['id'] ?>]" class="form-control form-control-sm" placeholder="Remarks (optional)" value="<?= htmlspecialchars($st['remarks'] ?: '') ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3 ps-4">
                    <button type="submit" name="save_marks" class="btn btn-success px-4">
                        <i class="fa fa-save me-1"></i> Save Marks
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle me-2"></i> No students registered under class <?= htmlspecialchars($class) ?>.
        </div>
    <?php endif; ?>
<?php elseif ($class): ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle me-2"></i> Please select Exam and Subject, then click 'Load Students'.
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
