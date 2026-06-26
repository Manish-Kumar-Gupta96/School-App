<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/notifications.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Low Attendance Alert
if (isset($_POST['send_alert'])) {
    $student_id = (int)$_POST['student_id'];
    $percentage = $_POST['percentage'];
    
    try {
        $stmt_stu = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
        $stmt_stu->execute([$student_id]);
        $s = $stmt_stu->fetch(PDO::FETCH_ASSOC);

        if ($s) {
            $recipients = [
                ['user_type' => 'parent', 'user_id' => $student_id],
                ['user_type' => 'student', 'user_id' => $student_id]
            ];
            $body = "Warning: {$s['first_name']} {$s['last_name']}'s attendance has dropped to {$percentage}%. Please ensure regular attendance to avoid academic penalties.";
            
            sendNotification($pdo, "Low Attendance Warning", $body, "danger", $recipients, $_SESSION['user_id']);
            $message = "Alert sent successfully to {$s['first_name']}'s parent.";
        }
    } catch (Exception $e) {
        $error = "Error sending alert: " . $e->getMessage();
    }
}

// Fetch assigned classes
$stmt_assigned = $pdo->prepare("
    SELECT DISTINCT tca.class_id, tca.section_id, c.class_name, s.section_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    WHERE tca.teacher_id = ?
");
$stmt_assigned->execute([$teacher_id]);
$assigned_classes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

$selected_class_sec = isset($_GET['class_sec']) ? $_GET['class_sec'] : '';
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$reports = [];
$total_classes = 0;

if ($selected_class_sec) {
    list($class_id, $section_id) = explode('-', $selected_class_sec);
    
    // We can fetch from `attendance_summary` if the cron has run,
    // but to be live, we can aggregate directly for the selected month.
    $stmt_rep = $pdo->prepare("
        SELECT s.id, s.first_name, s.last_name, s.roll_number,
               COUNT(a.id) as total_days,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
               SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
               SUM(CASE WHEN a.status = 'halfday' THEN 1 ELSE 0 END) as halfday_days
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id 
             AND MONTH(a.attendance_date) = ? 
             AND YEAR(a.attendance_date) = ?
        WHERE s.class_id = ? AND s.section_id = ? AND s.school_id = ?
        GROUP BY s.id
        ORDER BY s.roll_number ASC
    ");
    $stmt_rep->execute([$selected_month, $selected_year, $class_id, $section_id, $schoolId]);
    $reports = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

    // Get max days any student was marked to figure out "total working days" roughly
    // Or we just calculate percentage based on total_days marked for that student.
}

$root_path = "../../";
$page_title = "Attendance Reports | Teacher Portal";
$page_header = "Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Monthly Attendance Analytics</h5>
    <div class="d-flex gap-2">
        <a href="mark.php" class="btn btn-outline-primary"><i class="fa fa-user-check me-1"></i> Mark Attendance</a>
        <a href="leave_approvals.php" class="btn btn-outline-info"><i class="fa fa-envelope-open-text me-1"></i> Leave Requests</a>
        <a href="report.php" class="btn btn-secondary text-white"><i class="fa fa-chart-line me-1"></i> Reports</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Class & Section <span class="text-danger">*</span></label>
                <select name="class_sec" class="form-select" required>
                    <option value="">-- Choose Assigned Class --</option>
                    <?php foreach($assigned_classes as $ac): ?>
                        <option value="<?= $ac['class_id'].'-'.$ac['section_id'] ?>" <?= ($selected_class_sec === $ac['class_id'].'-'.$ac['section_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ac['class_name']) ?> - <?= htmlspecialchars($ac['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Month</label>
                <select name="month" class="form-select">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Year</label>
                <select name="year" class="form-select">
                    <?php for($y=date('Y')-1; $y<=date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa fa-chart-bar me-1"></i> Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_class_sec && !empty($reports)): ?>
    <div class="card shadow border-0" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Attendance Report (<?= date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)) ?>)</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Roll No</th>
                            <th>Student Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late / Halfday</th>
                            <th>Percentage</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $r): ?>
                            <?php 
                                $total_marked = $r['total_days'];
                                // Percentage logic: full present = 1, late = 1 (or 0.5 depending on rule, let's say 1), halfday = 0.5
                                // For simplicity: (present + late + halfday) / total_marked * 100
                                $attended = $r['present_days'] + $r['late_days'] + ($r['halfday_days'] * 0.5);
                                $percent = ($total_marked > 0) ? round(($attended / $total_marked) * 100, 1) : 0;
                                $is_low = ($percent < 75 && $total_marked > 5); // only flag if more than 5 days marked
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted"><?= htmlspecialchars($r['roll_number']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                <td class="text-success fw-bold"><?= $r['present_days'] ?></td>
                                <td class="text-danger fw-bold"><?= $r['absent_days'] ?></td>
                                <td class="text-warning fw-bold"><?= $r['late_days'] ?> / <?= $r['halfday_days'] ?></td>
                                <td>
                                    <?php if ($total_marked == 0): ?>
                                        <span class="badge bg-secondary">N/A</span>
                                    <?php else: ?>
                                        <span class="badge <?= $is_low ? 'bg-danger' : 'bg-success' ?> fs-6"><?= $percent ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <?php if($is_low): ?>
                                        <form method="POST">
                                            <input type="hidden" name="student_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="percentage" value="<?= $percent ?>">
                                            <button type="submit" name="send_alert" class="btn btn-sm btn-danger rounded-pill px-3" onclick="return confirm('Send warning alert to parent?');">
                                                <i class="fa fa-bell"></i> Send Alert
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Good</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($selected_class_sec): ?>
    <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px;">
        No data found for this period.
    </div>
<?php endif; ?>

<?php require_once('../includes/footer.php'); ?>
