<?php
require_once('../config/database.php');
require_once('includes/header.php');

$message = '';
$error = '';
$teacher_id = $_SESSION['teacher_id'];

/* ==========================
GET CONFIG DATA (ALLOWED CLASSES & SUBJECTS)
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

// Filter classes dropdown
$classes_raw = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL ORDER BY class ASC")->fetchAll(PDO::FETCH_ASSOC);
$classes = [];
foreach ($classes_raw as $c_raw) {
    if (in_array($c_raw['class'], $allowed_classes)) {
        $classes[] = $c_raw;
    }
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

$class     = $_GET['class'] ?? '';
$exam_name = $_GET['exam_name'] ?? '';
$subject   = $_GET['subject'] ?? '';

if ($class && !in_array($class, $allowed_classes)) {
    $error = "Access Denied: You are not assigned to Class " . htmlspecialchars($class);
    $class = '';
    $subject = '';
}

if ($class && $subject) {
    $class_allowed_subjects = getTeacherAllowedSubjectsForClass($pdo, $teacher_id, $class);
    $sub_ok = false;
    foreach ($class_allowed_subjects as $allowed_sub) {
        $db_sub = strtolower(trim($allowed_sub));
        $sel_sub = strtolower(trim($subject));
        if ($db_sub === $sel_sub) { $sub_ok = true; break; }
        if ($sel_sub === 'math' && ($db_sub === 'maths' || $db_sub === 'mathematics')) { $sub_ok = true; break; }
        if ($sel_sub === 'maths' && ($db_sub === 'math' || $db_sub === 'mathematics')) { $sub_ok = true; break; }
        if ($sel_sub === 'mathematics' && ($db_sub === 'math' || $db_sub === 'maths')) { $sub_ok = true; break; }
        if (strpos($db_sub, $sel_sub) !== false || strpos($sel_sub, $db_sub) !== false) { $sub_ok = true; break; }
    }
    if (!$sub_ok) {
        $error = "Access Denied: You are not assigned to teach " . htmlspecialchars($subject) . " in Class " . htmlspecialchars($class);
        $subject = '';
    }
}

// Fetch subjects for dropdown dynamically
$subjectList = [];
if ($class) {
    $subjectList = getTeacherAllowedSubjectsForClass($pdo, $teacher_id, $class);
} else {
    $stmt_subs = $pdo->prepare("
        SELECT DISTINCT sub.subject_name
        FROM teacher_class_assignments tca
        JOIN subjects sub ON tca.subject_id = sub.id
        WHERE tca.teacher_id = ?
    ");
    $stmt_subs->execute([$teacher_id]);
    $subjectList = $stmt_subs->fetchAll(PDO::FETCH_COLUMN);
}

$students = [];
$existingMarks = [];

if($class && $exam_name && $subject){
    /* LOAD STUDENTS */
    $stmt = $pdo->prepare("
        SELECT *, CONCAT(first_name, ' ', last_name) AS student_name 
        FROM students
        WHERE class=?
        ORDER BY first_name ASC
    ");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* LOAD EXISTING MARKS */
    $stmt_marks = $pdo->prepare("
        SELECT sp.student_id, sp.marks_obtained, sp.total_marks, sp.remarks
        FROM student_progress sp
        LEFT JOIN students s ON sp.student_id = s.id
        WHERE s.class=? AND sp.exam_name=? AND sp.subject=?
    ");
    $stmt_marks->execute([$class, $exam_name, $subject]);
    $marksData = $stmt_marks->fetchAll(PDO::FETCH_ASSOC);
    foreach($marksData as $row){
        $existingMarks[$row['student_id']] = [
            'obtained' => $row['marks_obtained'],
            'total' => $row['total_marks'],
            'remarks' => $row['remarks']
        ];
    }
}

/* ==========================
SAVE MARKS
========================== */
if(isset($_POST['save_marks'])){
    $class     = $_POST['class'];
    $exam_name = $_POST['exam_name'];
    $subject   = $_POST['subject'];
    $totals    = $_POST['total_marks'];
    $obtaineds = $_POST['marks_obtained'];
    $remarks   = $_POST['remarks'] ?? [];

    // Check permissions
    $class_allowed_subjects = getTeacherAllowedSubjectsForClass($pdo, $teacher_id, $class);
    $sub_ok = false;
    foreach ($class_allowed_subjects as $allowed_sub) {
        $db_sub = strtolower(trim($allowed_sub));
        $sel_sub = strtolower(trim($subject));
        if ($db_sub === $sel_sub) { $sub_ok = true; break; }
        if ($sel_sub === 'math' && ($db_sub === 'maths' || $db_sub === 'mathematics')) { $sub_ok = true; break; }
        if ($sel_sub === 'maths' && ($db_sub === 'math' || $db_sub === 'mathematics')) { $sub_ok = true; break; }
        if ($sel_sub === 'mathematics' && ($db_sub === 'math' || $db_sub === 'maths')) { $sub_ok = true; break; }
        if (strpos($db_sub, $sel_sub) !== false || strpos($sel_sub, $db_sub) !== false) { $sub_ok = true; break; }
    }

    if (!in_array($class, $allowed_classes)) {
        $error = "Access Denied: You are not assigned to Class " . htmlspecialchars($class);
    } elseif (!$sub_ok) {
        $error = "Access Denied: You are not assigned to teach " . htmlspecialchars($subject) . " in Class " . htmlspecialchars($class);
    } elseif(empty($class) || empty($exam_name) || empty($subject)){
        $error = "Class, Exam Name, and Subject are required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            foreach($obtaineds as $student_id => $obtained_val){
                if($obtained_val === '') continue; // Skip if empty

                $obtained = (float)$obtained_val;
                $total    = (float)$totals[$student_id];
                $remark   = trim($remarks[$student_id] ?? '');

                // Auto Grade calculation
                $percentage = ($total > 0) ? ($obtained / $total) * 100 : 0;
                $grade = 'F';
                if($percentage >= 90) $grade = 'A+';
                elseif($percentage >= 80) $grade = 'A';
                elseif($percentage >= 70) $grade = 'B';
                elseif($percentage >= 60) $grade = 'C';
                elseif($percentage >= 50) $grade = 'D';
                elseif($percentage >= 40) $grade = 'E';

                // Check if progress entry already exists
                $check = $pdo->prepare("SELECT id FROM student_progress WHERE student_id = ? AND exam_name = ? AND subject = ?");
                $check->execute([$student_id, $exam_name, $subject]);
                $existsId = $check->fetchColumn();

                if ($existsId) {
                    // Update
                    $update = $pdo->prepare("
                        UPDATE student_progress 
                        SET marks_obtained = ?, total_marks = ?, grade = ?, remarks = ?
                        WHERE id = ?
                    ");
                    $update->execute([$obtained, $total, $grade, $remark, $existsId]);
                } else {
                    // Insert
                    $insert = $pdo->prepare("
                        INSERT INTO student_progress(student_id, exam_name, subject, marks_obtained, total_marks, grade, remarks)
                        VALUES(?,?,?,?,?,?,?)
                    ");
                    $insert->execute([$student_id, $exam_name, $subject, $obtained, $total, $grade, $remark]);
                }
            }

            $pdo->commit();
            $message = "Academic progress marks updated successfully!";

            // Reload marks
            $stmt_marks = $pdo->prepare("
                SELECT sp.student_id, sp.marks_obtained, sp.total_marks, sp.remarks
                FROM student_progress sp
                LEFT JOIN students s ON sp.student_id = s.id
                WHERE s.class=? AND sp.exam_name=? AND sp.subject=?
            ");
            $stmt_marks->execute([$class, $exam_name, $subject]);
            $marksData = $stmt_marks->fetchAll(PDO::FETCH_ASSOC);
            $existingMarks = [];
            foreach($marksData as $row){
                $existingMarks[$row['student_id']] = [
                    'obtained' => $row['marks_obtained'],
                    'total' => $row['total_marks'],
                    'remarks' => $row['remarks']
                ];
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction Failed: " . $e->getMessage();
        }
    }
}

// Layout configuration
$active_menu = "marks";
$page_title = "Academic Marks Entry | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">Academic Marks Entry</h2>
        <p class="text-muted mb-0">Record exam marks and auto-calculate grades for students</p>
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

<!-- FILTER FORM -->
<div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
    <form method="GET" class="row g-3 align-items-end text-start">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Choose Class</label>
            <select name="class" class="form-select" required>
                <option value="">Select Class</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['class'] ?>" <?= ($class == $c['class']) ? 'selected' : ''; ?>>
                        Class <?= htmlspecialchars($c['class']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Exam Type</label>
            <select name="exam_name" class="form-select" required>
                <option value="">Select Exam</option>
                <option value="Unit Test - I" <?= ($exam_name == 'Unit Test - I') ? 'selected' : '' ?>>Unit Test - I</option>
                <option value="Half Yearly Exam" <?= ($exam_name == 'Half Yearly Exam') ? 'selected' : '' ?>>Half Yearly Exam</option>
                <option value="Unit Test - II" <?= ($exam_name == 'Unit Test - II') ? 'selected' : '' ?>>Unit Test - II</option>
                <option value="Annual Exam" <?= ($exam_name == 'Annual Exam') ? 'selected' : '' ?>>Annual Exam</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Subject</label>
            <select name="subject" class="form-select" required>
                <option value="">Select Subject</option>
                <?php foreach($subjectList as $sub_name): ?>
                    <option value="<?= htmlspecialchars($sub_name) ?>" <?= ($subject == $sub_name) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sub_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-sync-alt me-1"></i> Load Students
            </button>
        </div>
    </form>
</div>

<!-- ENTRY FORM -->
<?php if($class && $exam_name && $subject && count($students) > 0): ?>
    <form method="POST">
        <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
        <input type="hidden" name="exam_name" value="<?= htmlspecialchars($exam_name) ?>">
        <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">

        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-edit me-2 text-primary"></i>Marks Sheet (<?= htmlspecialchars($subject) ?> - <?= htmlspecialchars($exam_name) ?>)
                </h5>
                <span class="text-muted small">Class: <strong><?= htmlspecialchars($class) ?></strong></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4" style="width: 30%;">Student Name</th>
                                <th style="width: 15%;">Roll No</th>
                                <th style="width: 20%;">Obtained Marks</th>
                                <th style="width: 15%;">Total Marks</th>
                                <th class="pe-4" style="width: 20%;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php foreach($students as $s): ?>
                                <?php
                                $val = $existingMarks[$s['id']] ?? ['obtained' => '', 'total' => 100, 'remarks' => ''];
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['student_name']) ?></td>
                                    <td><span class="badge bg-light text-secondary border px-2 py-1">Roll #<?= htmlspecialchars($s['roll_no'] ?: '-') ?></span></td>
                                    
                                    <td>
                                        <input type="number" step="0.01" name="marks_obtained[<?= $s['id'] ?>]" value="<?= htmlspecialchars($val['obtained']) ?>" class="form-control text-center mx-auto" style="max-width: 120px;" placeholder="Obtained" min="0" max="1000">
                                    </td>
                                    
                                    <td>
                                        <input type="number" name="total_marks[<?= $s['id'] ?>]" value="<?= htmlspecialchars($val['total']) ?>" class="form-control text-center mx-auto" style="max-width: 100px;" required placeholder="Total" min="1">
                                    </td>
                                    
                                    <td class="pe-4">
                                        <input type="text" name="remarks[<?= $s['id'] ?>]" value="<?= htmlspecialchars($val['remarks']) ?>" class="form-control text-muted small" placeholder="Remarks...">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-light text-end">
                    <button type="submit" name="save_marks" class="btn btn-primary px-5 py-2 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save Marks Records
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

<?php
require_once('includes/footer.php');
?>
