<?php
require_once('../config/database.php');
$active_menu = "marks";
$page_title = "Exam-wise Marks Entry | Teacher Portal";
require_once('includes/header.php');

$message = '';
$error = '';
$teacher_id = $_SESSION['teacher_id'];

/* ==========================
GET ALLOWED CLASSES & SUBJECTS
========================== */
// 1. Fetch teacher's subject allocations
$stmt_tca = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    WHERE tca.teacher_id = ?
");
$stmt_tca->execute([$teacher_id]);
$tca_allocs = $stmt_tca->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch teacher's class teacher allocations
$stmt_ct = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name
    FROM class_teachers ct
    JOIN classes c ON ct.class_id = c.id
    JOIN sections s ON ct.section_id = s.id
    WHERE ct.teacher_id = ?
");
$stmt_ct->execute([$teacher_id]);
$ct_allocs = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);

$allocations = array_merge($tca_allocs, $ct_allocs);

// Fetch all student class and section combinations
$student_classes = $pdo->query("
    SELECT DISTINCT class, section 
    FROM students 
    WHERE class IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

$allowed_classes = [];
$match = function($student_class, $student_section, $class_name, $section_name) {
    $sc = strtolower(trim($student_class));
    $ss = strtolower(trim($student_section ?? ''));
    $cn = strtolower(trim($class_name));
    $sn = strtolower(trim($section_name));

    if ($sc === $cn && $ss === $sn) return true;
    if ($sc === $cn . '-' . $sn) return true;
    
    $cn_no_class = str_replace('class ', '', $cn);
    if ($sc === $cn_no_class . '-' . $sn) return true;
    if ($sc === 'class ' . $cn_no_class . '-' . $sn) return true;
    if ($sc === 'class ' . $cn_no_class && $ss === $sn) return true;
    
    if ($ss === $sn) {
        if ($sc === $cn || $sc === 'class ' . $cn || str_replace('class ', '', $sc) === $cn) {
            return true;
        }
    }
    return false;
};

foreach ($student_classes as $sc) {
    foreach ($allocations as $alloc) {
        if ($match($sc['class'], $sc['section'] ?? '', $alloc['class_name'], $alloc['section_name'])) {
            $allowed_classes[] = $sc['class'];
            break;
        }
    }
}
$allowed_classes = array_unique($allowed_classes);

// Filter the classes dropdown list
$classes_raw = $pdo->prepare("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND school_id = ? ORDER BY class ASC");
$classes_raw->execute([CURRENT_SCHOOL_ID]);
$classes_raw = $classes_raw->fetchAll(PDO::FETCH_ASSOC);

$classes = [];
foreach ($classes_raw as $c_raw) {
    if (in_array($c_raw['class'], $allowed_classes)) {
        $classes[] = $c_raw;
    }
}

$class = $_GET['class'] ?? '';
$exam  = $_GET['exam'] ?? '';

if ($class && !in_array($class, $allowed_classes)) {
    $error = "Access Denied: You are not assigned to Class " . htmlspecialchars($class);
    $class = ''; // block loading students
}

// Helper to get allowed subjects for the teacher in this class
function getTeacherAllowedSubjectsForClass($pdo, $teacher_id, $student_class) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.class_name, s.section_name, sub.subject_name
        FROM teacher_class_assignments tca
        JOIN classes c ON tca.class_id = c.id
        JOIN sections s ON tca.section_id = s.id
        JOIN subjects sub ON tca.subject_id = sub.id
        WHERE tca.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $allocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_sec = $pdo->prepare("SELECT DISTINCT section FROM students WHERE class = ? AND section IS NOT NULL");
    $stmt_sec->execute([$student_class]);
    $secs = $stmt_sec->fetchAll(PDO::FETCH_COLUMN);

    $match = function($student_class, $student_section, $class_name, $section_name) {
        $sc = strtolower(trim($student_class));
        $ss = strtolower(trim($student_section ?? ''));
        $cn = strtolower(trim($class_name));
        $sn = strtolower(trim($section_name));

        if ($sc === $cn && $ss === $sn) return true;
        if ($sc === $cn . '-' . $sn) return true;
        
        $cn_no_class = str_replace('class ', '', $cn);
        if ($sc === $cn_no_class . '-' . $sn) return true;
        if ($sc === 'class ' . $cn_no_class . '-' . $sn) return true;
        if ($sc === 'class ' . $cn_no_class && $ss === $sn) return true;
        
        if ($ss === $sn) {
            if ($sc === $cn || $sc === 'class ' . $cn || str_replace('class ', '', $sc) === $cn) {
                return true;
            }
        }
        return false;
    };

    $allowed_subjects = [];
    foreach ($allocs as $alloc) {
        $matched = false;
        if (empty($secs)) {
            if ($match($student_class, '', $alloc['class_name'], $alloc['section_name'])) {
                $matched = true;
            }
        } else {
            foreach ($secs as $sec) {
                if ($match($student_class, $sec, $alloc['class_name'], $alloc['section_name'])) {
                    $matched = true;
                    break;
                }
            }
        }
        if ($matched) {
            $allowed_subjects[] = $alloc['subject_name'];
        }
    }
    return array_unique($allowed_subjects);
}

$allowed_subjects = $class ? getTeacherAllowedSubjectsForClass($pdo, $teacher_id, $class) : [];

$isSubjectAllowed = function($subj) use ($allowed_subjects) {
    foreach ($allowed_subjects as $allowed_sub) {
        $db_sub = strtolower(trim($allowed_sub));
        $grid_sub = strtolower(trim($subj));
        if ($db_sub === $grid_sub) return true;
        if ($grid_sub === 'math' && ($db_sub === 'maths' || $db_sub === 'mathematics')) return true;
        if ($grid_sub === 'maths' && ($db_sub === 'math' || $db_sub === 'mathematics')) return true;
        if ($grid_sub === 'mathematics' && ($db_sub === 'math' || $db_sub === 'maths')) return true;
        if (strpos($db_sub, $grid_sub) !== false || strpos($grid_sub, $db_sub) !== false) return true;
    }
    return false;
};

$students = [];

if($class){
    $stmt = $pdo->prepare("
        SELECT *, CONCAT(first_name, ' ', last_name) AS student_name 
        FROM students
        WHERE class=? AND school_id=?
        ORDER BY first_name ASC
    ");
    $stmt->execute([$class, CURRENT_SCHOOL_ID]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if(isset($_POST['save_marks'])){
    $exam_name = $_POST['exam_name'];
    if (empty($class) || !in_array($class, $allowed_classes)) {
        $error = "Access Denied: You cannot save marks for this class.";
    } else {
        try {
            $pdo->beginTransaction();

            foreach($_POST['marks'] as $student_id => $subjects){
                foreach($subjects as $subject => $data){
                    // Verify if this subject is allowed for the teacher
                    if (!$isSubjectAllowed($subject)) {
                        continue; // Skip unauthorized subject
                    }

                    $obtained = $data['obtained'] === '' ? 0 : (float)$data['obtained'];
                    $total    = $data['total'] === '' ? 100 : (float)$data['total'];

                    $percentage = 0;
                    if($total > 0){
                        $percentage = ($obtained / $total) * 100;
                    }

                    if($percentage >= 90) $grade = "A+";
                    elseif($percentage >= 75) $grade = "A";
                    elseif($percentage >= 60) $grade = "B";
                    elseif($percentage >= 40) $grade = "C";
                    else $grade = "F";

                    $stmt = $pdo->prepare("
                        INSERT INTO student_marks (
                            student_id, exam_name, subject, marks_obtained, total_marks, percentage, grade, school_id
                        ) VALUES (?,?,?,?,?,?,?,?)
                        ON DUPLICATE KEY UPDATE
                            marks_obtained = VALUES(marks_obtained),
                            total_marks = VALUES(total_marks),
                            percentage = VALUES(percentage),
                            grade = VALUES(grade)
                    ");

                    $stmt->execute([
                        $student_id,
                        $exam_name,
                        $subject,
                        $obtained,
                        $total,
                        $percentage,
                        $grade,
                        CURRENT_SCHOOL_ID
                    ]);
                }
            }
            $pdo->commit();
            $message = "Marks Saved Successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to save marks: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📝 Marks Entry (Grid view)</h2>
        <p class="text-muted mb-0">Record exam marks for Math, Science, and English on a class-wide grid.</p>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- FILTER CARD -->
<div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
    <form method="GET" class="row g-3 align-items-end text-start">
        <div class="col-md-5">
            <label class="form-label fw-semibold">Choose Class</label>
            <select name="class" class="form-select" required>
                <option value="">Select Class</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= htmlspecialchars($c['class']) ?>" <?= ($class == $c['class']) ? 'selected' : ''; ?>>
                        Class <?= htmlspecialchars($c['class']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Exam Name</label>
            <input type="text" name="exam" class="form-control" placeholder="e.g. Unit Test - I, Final Exam" value="<?= htmlspecialchars($exam) ?>" required>
        </div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-success w-100 py-2">
                <i class="fa fa-sync-alt me-1"></i> Load Class List
            </button>
        </div>
    </form>
</div>

<!-- MARKS GRID FORM -->
<?php if($class && $exam && count($students) > 0): ?>
    <form method="POST">
        <input type="hidden" name="exam_name" value="<?= htmlspecialchars($exam) ?>">

        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-table me-2 text-success"></i>Marksheet - Class <?= htmlspecialchars($class) ?> (<?= htmlspecialchars($exam) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th class="text-center">Math (Obtained / Total) <?= !$isSubjectAllowed('Math') ? '<br><span class="badge bg-secondary small" style="font-size:0.75rem;">Read-Only</span>' : '' ?></th>
                                <th class="text-center">Science (Obtained / Total) <?= !$isSubjectAllowed('Science') ? '<br><span class="badge bg-secondary small" style="font-size:0.75rem;">Read-Only</span>' : '' ?></th>
                                <th class="text-center">English (Obtained / Total) <?= !$isSubjectAllowed('English') ? '<br><span class="badge bg-secondary small" style="font-size:0.75rem;">Read-Only</span>' : '' ?></th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php foreach($students as $s): ?>
                                <?php
                                // Fetch any existing records for standard subjects
                                $stmt_m = $pdo->prepare("SELECT subject, marks_obtained, total_marks FROM student_marks WHERE student_id=? AND exam_name=?");
                                $stmt_m->execute([$s['id'], $exam]);
                                $existing = $stmt_m->fetchAll(PDO::FETCH_KEY_PAIR);
                                
                                // Setup default total mark
                                $math_ob = isset($existing['Math']) ? $existing['Math'] : '';
                                $math_tot = 100;
                                $sci_ob = isset($existing['Science']) ? $existing['Science'] : '';
                                $sci_tot = 100;
                                $eng_ob = isset($existing['English']) ? $existing['English'] : '';
                                $eng_tot = 100;
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['student_name']) ?></td>
                                    
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="number" step="0.01" name="marks[<?= $s['id'] ?>][Math][obtained]" value="<?= htmlspecialchars($math_ob) ?>" class="form-control text-center py-1" style="max-width: 80px;" placeholder="Ob" <?= !$isSubjectAllowed('Math') ? 'disabled' : '' ?>>
                                            <span class="text-muted">/</span>
                                            <input type="number" name="marks[<?= $s['id'] ?>][Math][total]" value="<?= htmlspecialchars($math_tot) ?>" class="form-control text-center py-1" style="max-width: 70px;" required placeholder="Tot" <?= !$isSubjectAllowed('Math') ? 'disabled' : '' ?>>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="number" step="0.01" name="marks[<?= $s['id'] ?>][Science][obtained]" value="<?= htmlspecialchars($sci_ob) ?>" class="form-control text-center py-1" style="max-width: 80px;" placeholder="Ob" <?= !$isSubjectAllowed('Science') ? 'disabled' : '' ?>>
                                            <span class="text-muted">/</span>
                                            <input type="number" name="marks[<?= $s['id'] ?>][Science][total]" value="<?= htmlspecialchars($sci_tot) ?>" class="form-control text-center py-1" style="max-width: 70px;" required placeholder="Tot" <?= !$isSubjectAllowed('Science') ? 'disabled' : '' ?>>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="number" step="0.01" name="marks[<?= $s['id'] ?>][English][obtained]" value="<?= htmlspecialchars($eng_ob) ?>" class="form-control text-center py-1" style="max-width: 80px;" placeholder="Ob" <?= !$isSubjectAllowed('English') ? 'disabled' : '' ?>>
                                            <span class="text-muted">/</span>
                                            <input type="number" name="marks[<?= $s['id'] ?>][English][total]" value="<?= htmlspecialchars($eng_tot) ?>" class="form-control text-center py-1" style="max-width: 70px;" required placeholder="Tot" <?= !$isSubjectAllowed('English') ? 'disabled' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-light text-end">
                    <button type="submit" name="save_marks" class="btn btn-success px-5 py-2 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save Marks Grid
                    </button>
                </div>
            </div>
        </div>
    </form>
<?php elseif($class): ?>
    <div class="alert alert-warning text-center shadow-sm py-4 border-0">
        <i class="fa fa-info-circle me-2"></i> No active students found in Class <?= htmlspecialchars($class) ?>.
    </div>
<?php endif; ?>

<?php require_once('includes/footer.php'); ?>
