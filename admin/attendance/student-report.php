<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$student_id = $_GET['student_id'] ?? '';
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

if (!$student_id) {
    header("Location: monthly-report.php");
    exit();
}

// 1. Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student Not Found");
}

// 2. Fetch daily attendance log for the selected month/year
$stmt_log = $pdo->prepare("
    SELECT * 
    FROM attendance 
    WHERE student_id = ? 
    AND MONTH(attendance_date) = ? 
    AND YEAR(attendance_date) = ?
    ORDER BY attendance_date ASC
");
$stmt_log->execute([$student_id, $month, $year]);
$logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

// Count summary
$total = count($logs);
$present = 0;
$absent = 0;
$late = 0;
$half_day = 0;

foreach ($logs as $log) {
    if ($log['status'] === 'Present') $present++;
    elseif ($log['status'] === 'Absent') $absent++;
    elseif ($log['status'] === 'Late') $late++;
    elseif ($log['status'] === 'Half Day') $half_day++;
}

$weight_sum = $present + $late + ($half_day * 0.5);
$percentage = $total > 0 ? round(($weight_sum / $total) * 100) : 0;

$months_list = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// Layout setup
$root_path = "../../";
$page_title = "Student Attendance Report | VIC ERP";
$page_header = "Student Attendance Log";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="row">
    <!-- STUDENT CARD -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0 text-center p-3" style="border-radius: 15px;">
            <div class="card-body">
                <?php if($student['photo'] && file_exists("../../uploads/students/" . $student['photo'])) : ?>
                    <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" width="120" height="120" style="object-fit:cover; border-radius:50%; border: 3px solid var(--primary);" class="mb-3 shadow-sm">
                <?php else : ?>
                    <img src="../../assets/images/default-user.png" width="120" height="120" style="object-fit:cover; border-radius:50%;" class="mb-3">
                <?php endif; ?>

                <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h4>
                <p class="text-muted small mb-3">Admission No: <span class="badge bg-secondary"><?= htmlspecialchars($student['admission_no']) ?></span></p>
                
                <hr>

                <div class="row text-center mt-3 g-2">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block">Present</span>
                            <span class="text-success fw-bold fs-5"><?= $present ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block">Absent</span>
                            <span class="text-danger fw-bold fs-5"><?= $absent ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block">Late</span>
                            <span class="text-warning fw-bold fs-5"><?= $late ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block">Half Day</span>
                            <span class="text-info fw-bold fs-5"><?= $half_day ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 border rounded bg-light text-center">
                    <span class="text-muted small d-block mb-1">Attendance Percentage</span>
                    <?php 
                    $badge_color = ($percentage >= 75) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger');
                    ?>
                    <h2 class="text-<?= $badge_color ?> fw-bold mb-0"><?= $percentage ?>%</h2>
                </div>
                
                <div class="d-grid mt-4">
                    <a href="monthly-report.php?class=<?= urlencode($student['class']) ?>" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back to Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & ATTENDANCE LOG -->
    <div class="col-lg-8">
        <!-- FILTER DATE -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="student_id" value="<?= $student_id ?>">
                    
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Month</label>
                        <select name="month" class="form-select">
                            <?php foreach($months_list as $m_num => $m_name): ?>
                                <option value="<?= $m_num ?>" <?= ($month == $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Year</label>
                        <select name="year" class="form-select">
                            <?php for($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary">
                            <i class="fa fa-sync me-1"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DAILY LOG TABLE -->
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Daily Attendance Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= date('d M Y', strtotime($log['attendance_date'])) ?></td>
                                        <td class="text-muted"><?= date('l', strtotime($log['attendance_date'])) ?></td>
                                        <td>
                                            <?php
                                            if($log['status'] == 'Present'){
                                                echo "<span class='badge bg-success-subtle text-success border border-success-subtle px-3 py-2'>Present</span>";
                                            } elseif($log['status'] == 'Absent'){
                                                echo "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2'>Absent</span>";
                                            } elseif($log['status'] == 'Late'){
                                                echo "<span class='badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2'>Late</span>";
                                            } else {
                                                echo "<span class='badge bg-info-subtle text-info border border-info-subtle px-3 py-2'>Half Day</span>";
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['remarks'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-calendar-times fs-2 mb-2 d-block"></i>
                                        No attendance logs recorded for this student in <?= $months_list[$month] ?> <?= $year ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
