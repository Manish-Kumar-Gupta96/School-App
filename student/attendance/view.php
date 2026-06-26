<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch attendance records for the month
$stmt_att = $pdo->prepare("
    SELECT attendance_date, status, remarks 
    FROM attendance 
    WHERE student_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ? AND school_id = ?
");
$stmt_att->execute([$student_id, $selected_month, $selected_year, $schoolId]);
$records = $stmt_att->fetchAll(PDO::FETCH_ASSOC);

// Process for Calendar & Stats
$att_map = [];
$stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'halfday' => 0, 'total' => 0];

foreach ($records as $r) {
    $att_map[$r['attendance_date']] = $r;
    $stats[$r['status']]++;
    $stats['total']++;
}

// Calculate percentage: present + late + (halfday * 0.5)
$attended = $stats['present'] + $stats['late'] + ($stats['halfday'] * 0.5);
$percentage = ($stats['total'] > 0) ? round(($attended / $stats['total']) * 100, 1) : 0;

// Calendar Setup
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$first_day = mktime(0, 0, 0, $selected_month, 1, $selected_year);
$start_day_of_week = date('w', $first_day); // 0 = Sun, 6 = Sat

$root_path = "../../";
$page_title = "My Attendance | Student Portal";
$page_header = "Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Monthly Attendance & Calendar</h5>
    <div class="d-flex gap-2">
        <a href="view.php" class="btn btn-primary"><i class="fa fa-calendar-alt me-1"></i> My Attendance</a>
        <a href="leave_apply.php" class="btn btn-outline-info"><i class="fa fa-envelope-open-text me-1"></i> Apply Leave</a>
    </div>
</div>

<!-- Date Filter -->
<div class="card shadow-sm border-0 mb-4 bg-light" style="border-radius: 12px;">
    <div class="card-body py-3">
        <form method="GET" class="row align-items-center g-3 m-0">
            <div class="col-auto">
                <label class="fw-bold me-2">Filter Month:</label>
            </div>
            <div class="col-md-3">
                <select name="month" class="form-select form-select-sm">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    <?php for($y=date('Y')-2; $y<=date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">View</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Attendance %</h6>
                <h2 class="fw-bold mb-0"><?= $stats['total'] > 0 ? $percentage.'%' : 'N/A' ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Present Days</h6>
                <h2 class="fw-bold mb-0"><?= $stats['present'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Absent Days</h6>
                <h2 class="fw-bold mb-0"><?= $stats['absent'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-dark h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Late / Halfday</h6>
                <h2 class="fw-bold mb-0"><?= $stats['late'] ?> / <?= $stats['halfday'] ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Visual Calendar -->
<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Attendance Calendar</h5>
        <div class="d-flex gap-3 small fw-bold">
            <span class="text-success"><i class="fa fa-circle"></i> P - Present</span>
            <span class="text-danger"><i class="fa fa-circle"></i> A - Absent</span>
            <span class="text-warning"><i class="fa fa-circle"></i> L - Late</span>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle" style="table-layout: fixed;">
                <thead class="bg-light">
                    <tr>
                        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                            $day_count = 1;
                            
                            // Pad empty cells before the first day
                            for ($i = 0; $i < $start_day_of_week; $i++) {
                                echo "<td></td>";
                            }
                            
                            // Fill days
                            while ($day_count <= $days_in_month) {
                                if (($day_count + $start_day_of_week - 1) % 7 == 0 && $day_count != 1) {
                                    echo "</tr><tr>"; // new row for new week
                                }

                                $current_date = sprintf("%04d-%02d-%02d", $selected_year, $selected_month, $day_count);
                                $is_today = ($current_date === date('Y-m-d'));
                                
                                $status_html = "";
                                $cell_bg = "";
                                
                                if (isset($att_map[$current_date])) {
                                    $s = $att_map[$current_date]['status'];
                                    if ($s === 'present') { $status_html = '<span class="text-success fw-bold fs-5">P</span>'; $cell_bg = 'bg-success bg-opacity-10'; }
                                    elseif ($s === 'absent') { $status_html = '<span class="text-danger fw-bold fs-5">A</span>'; $cell_bg = 'bg-danger bg-opacity-10'; }
                                    elseif ($s === 'late') { $status_html = '<span class="text-warning fw-bold fs-5">L</span>'; $cell_bg = 'bg-warning bg-opacity-10'; }
                                    elseif ($s === 'halfday') { $status_html = '<span class="text-info fw-bold fs-5">H</span>'; $cell_bg = 'bg-info bg-opacity-10'; }
                                } else {
                                    $status_html = '<span class="text-muted small">-</span>';
                                }

                                $today_border = $is_today ? 'border border-primary border-2' : '';
                                
                                echo "<td class='p-3 {$cell_bg} {$today_border}'>";
                                echo "<div class='small text-muted mb-1'>{$day_count}</div>";
                                echo "<div>{$status_html}</div>";
                                echo "</td>";
                                
                                $day_count++;
                            }
                            
                            // Pad remaining cells
                            while (($day_count + $start_day_of_week - 1) % 7 != 0) {
                                echo "<td></td>";
                                $day_count++;
                            }
                        ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
