<?php
require_once('../config/database.php');
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

/* ==========================
ATTENDANCE STATISTICS
========================== */
$attendanceRecords = [];
$totalDays = 0;
$presentDays = 0;
$absentDays = 0;
$leaveDays = 0;
$attendancePercentage = 0;

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

// Layout configuration
$active_menu = "attendance";
$page_title = "My Attendance History | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">My Attendance History</h2>
        <p class="text-muted mb-0">Review your daily class attendance status logs</p>
    </div>
</div>

<!-- STATS COUNTERS -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Present Days</h6>
                <h3 class="fw-bold mb-0 text-success"><?= (int)$presentDays ?> / <?= (int)$totalDays ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Absent Days</h6>
                <h3 class="fw-bold mb-0 text-danger"><?= (int)$absentDays ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Approved Leaves</h6>
                <h3 class="fw-bold mb-0 text-warning"><?= (int)$leaveDays ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Attendance Ratio</h6>
                <h3 class="fw-bold mb-0 text-primary"><?= $attendancePercentage ?> %</h3>
            </div>
        </div>
    </div>
</div>

<!-- DIARY LIST -->
<div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-header bg-white border-0 py-3 ps-4 text-start">
        <h5 class="fw-bold mb-0 text-dark">Daily Attendance Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Class Date</th>
                        <th>Status Badge</th>
                        <th class="pe-4">Remarks / Notes</th>
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
                                <i class="fa fa-calendar-alt fs-2 mb-2 d-block"></i>
                                No recorded attendance registers found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
