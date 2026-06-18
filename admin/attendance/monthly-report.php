<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$class = $_GET['class'] ?? '';
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

$report_data = [];

if ($class) {
    // 1. Fetch all students in the class
    $stmt = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE class = ? ORDER BY first_name ASC");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch monthly attendance data for each student
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        // Count totals
        $stmt_att = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'Half Day' THEN 1 ELSE 0 END) as half_day
            FROM attendance
            WHERE student_id = ? 
            AND MONTH(attendance_date) = ?
            AND YEAR(attendance_date) = ?
        ");
        $stmt_att->execute([$student_id, $month, $year]);
        $counts = $stmt_att->fetch(PDO::FETCH_ASSOC);

        $total = (int)$counts['total'];
        $present = (int)$counts['present'];
        $absent = (int)$counts['absent'];
        $late = (int)$counts['late'];
        $half_day = (int)$counts['half_day'];

        // Calculate attendance weight (Present = 1, Late = 1, Half Day = 0.5)
        $weight_sum = $present + $late + ($half_day * 0.5);
        $percentage = $total > 0 ? round(($weight_sum / $total) * 100) : 0;

        $report_data[] = [
            'id' => $student_id,
            'admission_no' => $student['admission_no'],
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $half_day,
            'total' => $total,
            'percentage' => $percentage
        ];
    }
}

// Fetch classes
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Months list
$months_list = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// Layout setup
$root_path = "../../";
$page_title = "Monthly Attendance Report | VIC ERP";
$page_header = "Monthly Attendance Report";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Class</label>
                <select name="class" class="form-select" required>
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
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">Month</label>
                <select name="month" class="form-select">
                    <?php foreach($months_list as $m_num => $m_name): ?>
                        <option value="<?= $m_num ?>" <?= ($month == $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    <?php for($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">
                    <i class="fa fa-chart-line me-1"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($class && count($report_data) > 0): ?>
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="export-report.php?type=csv&class=<?= urlencode($class) ?>&month=<?= urlencode($month) ?>&year=<?= urlencode($year) ?>" class="btn btn-outline-success">
            <i class="fa fa-file-csv me-1"></i> Export CSV
        </a>
        <a href="export-report.php?type=pdf&class=<?= urlencode($class) ?>&month=<?= urlencode($month) ?>&year=<?= urlencode($year) ?>" class="btn btn-outline-danger" target="_blank">
            <i class="fa fa-file-pdf me-1"></i> Export PDF
        </a>
    </div>

    <!-- REPORT TABLE -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr class="text-start">
                            <th class="ps-4 text-center">Admission No</th>
                            <th class="text-start">Student Name</th>
                            <th class="text-center">Present Days</th>
                            <th class="text-center">Absent Days</th>
                            <th class="text-center">Late Days</th>
                            <th class="text-center">Half Days</th>
                            <th class="text-center">Total Classes</th>
                            <th class="text-center">Percentage</th>
                            <th class="text-center">History</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $row): ?>
                            <tr class="text-start">
                                <td class="ps-4 text-center"><span class="badge bg-secondary"><?= htmlspecialchars($row['admission_no']) ?></span></td>
                                <td class="fw-semibold text-start"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center text-success fw-bold"><?= $row['present'] ?></td>
                                <td class="text-center text-danger fw-bold"><?= $row['absent'] ?></td>
                                <td class="text-center text-warning fw-bold"><?= $row['late'] ?></td>
                                <td class="text-center text-info fw-bold"><?= $row['half_day'] ?></td>
                                <td class="text-center"><?= $row['total'] ?></td>
                                <td class="text-center">
                                    <?php 
                                    $pct = $row['percentage'];
                                    $badge_color = ($pct >= 75) ? 'success' : (($pct >= 50) ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?= $badge_color ?> fs-6 px-3 py-1"><?= $pct ?>%</span>
                                </td>
                                <td class="text-center">
                                    <a href="student-report.php?student_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-history"></i> History
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif($class): ?>
    <div class="alert alert-warning">
        <i class="fa fa-info-circle me-2"></i> No student logs found in class <?= htmlspecialchars($class) ?> for <?= $months_list[$month] ?> <?= $year ?>.
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
