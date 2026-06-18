<?php
require_once('../config/database.php');
require_once('includes/header.php');

$message = '';
$error = '';
$teacher_id = $_SESSION['teacher_id'];

/* ==========================
GET ALLOWED CLASSES FOR TEACHER
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
$classes_raw = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL ORDER BY class ASC")->fetchAll(PDO::FETCH_ASSOC);
$classes = [];
foreach ($classes_raw as $c_raw) {
    if (in_array($c_raw['class'], $allowed_classes)) {
        $classes[] = $c_raw;
    }
}

$class = $_GET['class'] ?? '';
$date  = $_GET['date'] ?? date('Y-m-d');

if ($class && !in_array($class, $allowed_classes)) {
    $error = "Access Denied: You are not assigned to Class " . htmlspecialchars($class);
    $class = ''; // block loading students
}

$students = [];
$markedAttendance = [];

if($class){
    /* LOAD STUDENTS */
    $stmt = $pdo->prepare("
        SELECT *, CONCAT(first_name, ' ', last_name) AS student_name 
        FROM students
        WHERE class=?
        ORDER BY first_name ASC
    ");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* LOAD EXISTING ATTENDANCE FOR THE DAY */
    $stmt_att = $pdo->prepare("
        SELECT sa.student_id, sa.status 
        FROM student_attendance sa
        LEFT JOIN students s ON sa.student_id = s.id
        WHERE s.class=? AND sa.attendance_date=?
    ");
    $stmt_att->execute([$class, $date]);
    $existing = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
    foreach($existing as $row){
        $markedAttendance[$row['student_id']] = $row['status'];
    }
}

/* ==========================
SAVE ATTENDANCE
========================== */
if(isset($_POST['save_attendance'])){
    $attendance_date = $_POST['attendance_date'];
    $class = $_POST['class'];

    if (!in_array($class, $allowed_classes)) {
        $error = "Access Denied: You cannot mark attendance for Class " . htmlspecialchars($class);
    } elseif(empty($attendance_date) || empty($class)){
        $error = "Class and Attendance Date are required.";
    } elseif(count($_POST['status'] ?? []) == 0){
        $error = "No student attendance status selected.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO student_attendance(student_id, attendance_date, status)
                VALUES(?,?,?)
                ON DUPLICATE KEY UPDATE status=VALUES(status)
            ");

            foreach($_POST['status'] as $student_id => $status){
                $stmt->execute([
                    $student_id,
                    $attendance_date,
                    $status
                ]);
            }

            $pdo->commit();
            $message = "Attendance Marked and Saved Successfully!";

            // Reload existing attendance values
            $stmt_att = $pdo->prepare("
                SELECT sa.student_id, sa.status 
                FROM student_attendance sa
                LEFT JOIN students s ON sa.student_id = s.id
                WHERE s.class=? AND sa.attendance_date=?
            ");
            $stmt_att->execute([$class, $attendance_date]);
            $existing = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
            $markedAttendance = [];
            foreach($existing as $row){
                $markedAttendance[$row['student_id']] = $row['status'];
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Layout configuration
$active_menu = "attendance";
$page_title = "Class Attendance System | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">Mark Student Attendance</h2>
        <p class="text-muted mb-0">Filter by class and date to mark presence or leave</p>
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
            <label class="form-label fw-semibold">Choose Class Group</label>
            <select name="class" class="form-select" required>
                <option value="">Select Class</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['class'] ?>" <?= ($class == $c['class']) ? 'selected' : ''; ?>>
                        Class <?= htmlspecialchars($c['class']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Attendance Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control">
        </div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-success w-100 py-2">
                <i class="fa fa-sync-alt me-1"></i> Load Students
            </button>
        </div>
    </form>
</div>

<!-- ATTENDANCE PANEL -->
<?php if($class && count($students) > 0): ?>
    <form method="POST">
        <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($date) ?>">
        <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">

        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-users me-2 text-primary"></i>Students of Class <?= htmlspecialchars($class) ?></h5>
                <span class="text-muted small">Selected Date: <strong><?= date('d M Y', strtotime($date)) ?></strong></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student Details</th>
                                <th>Roll Number</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">On Leave</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php foreach($students as $s): ?>
                                <?php
                                $statusVal = $markedAttendance[$s['id']] ?? 'Present'; // default to Present if unmarked
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['student_name']) ?></td>
                                    <td><span class="badge bg-light text-secondary border px-2 py-1">Roll #<?= htmlspecialchars($s['roll_no'] ?: '-') ?></span></td>
                                    
                                    <td class="text-center">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input" type="radio" name="status[<?= $s['id'] ?>]" value="Present" <?= ($statusVal == 'Present') ? 'checked' : '' ?> required>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input" type="radio" name="status[<?= $s['id'] ?>]" value="Absent" <?= ($statusVal == 'Absent') ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input" type="radio" name="status[<?= $s['id'] ?>]" value="Leave" <?= ($statusVal == 'Leave') ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 bg-light text-end">
                    <button type="submit" name="save_attendance" class="btn btn-success px-5 py-2 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save Attendance Records
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
