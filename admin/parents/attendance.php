<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
LOAD STUDENTS
========================== */
$students = $pdo->query("
    SELECT id, CONCAT(first_name, ' ', last_name) AS student_name, class
    FROM students
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$student_id = $_GET['student_id'] ?? '';
$attendanceRecords = [];
$totalDays = 0;
$presentDays = 0;
$absentDays = 0;
$leaveDays = 0;
$attendancePercentage = 0;

if($student_id){
    $stmt = $pdo->prepare("
        SELECT *
        FROM student_attendance
        WHERE student_id=?
        ORDER BY attendance_date DESC
    ");
    $stmt->execute([$student_id]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($attendanceRecords as $row){
        $totalDays++;
        if($row['status'] == 'Present'){
            $presentDays++;
        } elseif($row['status'] == 'Absent'){
            $absentDays++;
        } elseif($row['status'] == 'Leave'){
            $leaveDays++;
        }
    }

    if($totalDays > 0){
        $attendancePercentage = round(($presentDays / $totalDays) * 100, 2);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Attendance | VIC ERP</title>
</head>
<body>

<?php
// Layout setup
$root_path = "../../";
$page_title = "Student Attendance | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Overview of student attendance status percentages and diaries</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-outline-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-outline-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="notifications.php" class="btn btn-outline-primary">
            <i class="fa fa-bullhorn me-1"></i> Notifications
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET">
            <div class="row align-items-end text-start">
                <div class="col-md-6 mb-2">
                    <label class="form-label fw-semibold">Select Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Choose Student</option>
                        <?php foreach($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= ($student_id == $student['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['student_name']) ?> (Class <?= htmlspecialchars($student['class']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search me-1"></i> View Attendance
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if($student_id): ?>
    <!-- COUNTERS -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Present Days</h6>
                    <h3 class="fw-bold mb-0 text-success"><?= (int)$presentDays ?> / <?= (int)$totalDays ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Absent Days</h6>
                    <h3 class="fw-bold mb-0 text-danger"><?= (int)$absentDays ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Approved Leaves</h6>
                    <h3 class="fw-bold mb-0 text-warning"><?= (int)$leaveDays ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Attendance Ratio</h6>
                    <h3 class="fw-bold mb-0 text-primary"><?= $attendancePercentage ?> %</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- DIARY HISTORY -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark text-start">Attendance Ledger Logs</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Status Badge</th>
                            <th class="pe-4">Remarks / Explanations</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php if (count($attendanceRecords) > 0): ?>
                            <?php foreach($attendanceRecords as $row): ?>
                                <?php
                                $badge = 'success';
                                if($row['status'] == 'Absent') $badge = 'danger';
                                elseif($row['status'] == 'Leave') $badge = 'warning';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= date('d M Y, l', strtotime($row['attendance_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-muted small"><?= htmlspecialchars($row['remarks'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa fa-calendar-check fs-2 mb-2 d-block"></i>
                                    No attendance registers logged for this student.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
</body>
</html>
