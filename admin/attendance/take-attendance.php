<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$class = $_GET['class'] ?? '';

/* ==========================
SAVE ATTENDANCE
========================== */
if(isset($_POST['save_attendance'])){
    $attendance_date = $_POST['attendance_date'];
    $class_name = $_POST['class_name'];

    foreach($_POST['status'] as $student_id => $status){
        $remarks = $_POST['remarks'][$student_id] ?? '';

        // Check if already exists for this date and student
        $check = $pdo->prepare("
            SELECT id
            FROM attendance
            WHERE student_id=?
            AND attendance_date=?
        ");
        $check->execute([$student_id, $attendance_date]);

        if($check->rowCount() == 0){
            $insert = $pdo->prepare("
                INSERT INTO attendance(
                    student_id,
                    class,
                    attendance_date,
                    status,
                    remarks
                )
                VALUES(?,?,?,?,?)
            ");
            $insert->execute([
                $student_id,
                $class_name,
                $attendance_date,
                $status,
                $remarks
            ]);
        } else {
            // Update instead of insert if it already exists
            $update = $pdo->prepare("
                UPDATE attendance
                SET status = ?, remarks = ?, class = ?
                WHERE student_id = ? AND attendance_date = ?
            ");
            $update->execute([
                $status,
                $remarks,
                $class_name,
                $student_id,
                $attendance_date
            ]);
        }
    }
    require_once('../includes/audit-helper.php');
    addAuditLog(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['name'] ?? 'Admin',
        $_SESSION['role'] ?? 'Admin',
        'Attendance marked/updated for Class: ' . $class_name . ' (Date: ' . $attendance_date . ')',
        'Attendance'
    );

    $message = "Attendance Saved Successfully";
}

/* ==========================
LOAD STUDENTS
========================== */
$students = [];
if($class){
    $stmt = $pdo->prepare("
        SELECT *
        FROM students
        WHERE class=?
        ORDER BY first_name ASC
    ");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll();
}

// Fetch distinct classes from database
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Layout setup
$root_path = "../../";
$page_title = "Take Attendance | VIC ERP";
$page_header = "Take Daily Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Select Class</label>
                <select name="class" class="form-select" required>
                    <option value="">Select Class</option>
                    <?php if (count($classes) > 0): ?>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($class == $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback list -->
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="Class <?= $i ?>" <?= ($class == "Class $i") ? 'selected' : '' ?>>
                                Class <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">
                    <i class="fa fa-users me-1"></i> Load Students
                </button>
            </div>
        </form>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($class && $students): ?>
    <form method="POST">
        <input type="hidden" name="class_name" value="<?= htmlspecialchars($class) ?>">
        
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-0">Attendance Date</label>
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="attendance_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th width="200">Attendance Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($student['id']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($student['admission_no']) ?></span></td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </td>
                                    <td>
                                        <select name="status[<?= $student['id'] ?>]" class="form-select form-select-sm">
                                            <option value="Present">Present</option>
                                            <option value="Absent">Absent</option>
                                            <option value="Late">Late</option>
                                            <option value="Half Day">Half Day</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?= $student['id'] ?>]" class="form-control form-control-sm" placeholder="Any remarks...">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3 ps-4">
                <button type="submit" name="save_attendance" class="btn btn-success px-4">
                    <i class="fa fa-save me-1"></i> Save Attendance
                </button>
            </div>
        </div>
    </form>
<?php elseif($class): ?>
    <div class="alert alert-warning">
        <i class="fa fa-info-circle me-2"></i> No students registered in <?= htmlspecialchars($class) ?>.
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
