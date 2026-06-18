<?php
require_once('../config/database.php');
$active_menu = "dashboard";
$page_title = "Parent Portal Dashboard | VIC School";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];

// Get Child Student Details
$stmt = $pdo->prepare("
    SELECT s.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name 
    FROM students s
    JOIN parent_student_map psm ON s.id = psm.student_id
    WHERE psm.parent_id = ? AND s.school_id = ?
");
$stmt->execute([$parent_id, CURRENT_SCHOOL_ID]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    // Attempt fallback or warning
    $child = $pdo->query("SELECT *, CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE school_id = " . CURRENT_SCHOOL_ID . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">👨‍👩‍👧 Parent Portal Dashboard</h2>
        <p class="text-muted mb-0">Welcome to the ERP controller dashboard. Monitor your child's academic track records.</p>
    </div>
</div>

<?php if($child): ?>
    <div class="row g-4 text-start">
        <!-- Student Details Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4 text-center h-100" style="border-radius: 15px;">
                <div class="mb-3">
                    <?php if($child['photo']): ?>
                        <img src="../<?= htmlspecialchars($child['photo']) ?>" alt="Student Photo" class="rounded-circle border" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            <?= strtoupper(substr($child['first_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($child['student_name']) ?></h4>
                <p class="text-muted mb-3">Roll Number: #<?= htmlspecialchars($child['roll_no'] ?: '-') ?></p>
                
                <div class="text-start border-top pt-3">
                    <p class="mb-2 text-muted small"><i class="fa fa-graduation-cap me-2 text-primary"></i>Class: <strong>Class <?= htmlspecialchars($child['class']) ?></strong></p>
                    <p class="mb-2 text-muted small"><i class="fa fa-id-card me-2 text-success"></i>Admission No: <strong><?= htmlspecialchars($child['admission_no'] ?: '-') ?></strong></p>
                    <p class="mb-2 text-muted small"><i class="fa fa-phone me-2 text-warning"></i>Mobile: <strong><?= htmlspecialchars($child['phone'] ?: '-') ?></strong></p>
                    <p class="mb-0 text-muted small"><i class="fa fa-envelope me-2 text-info"></i>Email: <strong><?= htmlspecialchars($child['email'] ?: '-') ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Quick Summary Metrics -->
        <div class="col-lg-8">
            <div class="row g-4">
                <!-- Attendance Quick Summary -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 card-hover" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Attendance Index</h6>
                            <i class="fa fa-user-check text-success fs-4"></i>
                        </div>
                        <?php
                        // Fetch total present / absent count
                        $stmt_att = $pdo->prepare("SELECT status, COUNT(*) as count FROM student_attendance WHERE student_id=? GROUP BY status");
                        $stmt_att->execute([$child['id']]);
                        $att_records = $stmt_att->fetchAll(PDO::FETCH_KEY_PAIR);
                        $present = $att_records['Present'] ?? 0;
                        $absent = $att_records['Absent'] ?? 0;
                        $leave = $att_records['Leave'] ?? 0;
                        $total_days = $present + $absent + $leave;
                        $ratio = $total_days > 0 ? round(($present / $total_days) * 100, 1) : 100;
                        ?>
                        <h3 class="fw-bold mb-1"><?= $ratio ?>%</h3>
                        <p class="text-muted small mb-3">Present: <?= $present ?> | Absent: <?= $absent ?></p>
                        <a href="attendance.php" class="btn btn-outline-success btn-sm w-100 mt-2">View Detailed Log</a>
                    </div>
                </div>

                <!-- Marks Index -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 card-hover" style="border-radius: 12px; border-left: 4px solid #6366f1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Academic Performance</h6>
                            <i class="fa fa-chart-line text-primary fs-4"></i>
                        </div>
                        <?php
                        // Fetch average percentage
                        $stmt_m = $pdo->prepare("SELECT AVG(percentage) FROM student_marks WHERE student_id=?");
                        $stmt_m->execute([$child['id']]);
                        $avg_pct = round((float)$stmt_m->fetchColumn(), 1);
                        ?>
                        <h3 class="fw-bold mb-1"><?= $avg_pct ?>%</h3>
                        <p class="text-muted small mb-3">Overall average score across subjects</p>
                        <a href="marks.php" class="btn btn-outline-primary btn-sm w-100 mt-2">Open Report Card</a>
                    </div>
                </div>

                <!-- Pending Homework -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 card-hover" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Assignments</h6>
                            <i class="fa fa-tasks text-warning fs-4"></i>
                        </div>
                        <?php
                        // Fetch total assignments for class
                        $stmt_hw = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE class=?");
                        $stmt_hw->execute([$child['class']]);
                        $total_hw = (int)$stmt_hw->fetchColumn();

                        // Fetch submitted assignments
                        $stmt_sub = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE student_id=?");
                        $stmt_sub->execute([$child['id']]);
                        $sub_hw = (int)$stmt_sub->fetchColumn();
                        $pending_hw = max(0, $total_hw - $sub_hw);
                        ?>
                        <h3 class="fw-bold mb-1"><?= $pending_hw ?> Pending</h3>
                        <p class="text-muted small mb-3">Total Assigned: <?= $total_hw ?> | Completed: <?= $sub_hw ?></p>
                        <a href="homework.php" class="btn btn-outline-warning btn-sm w-100 mt-2">Check Homeworks</a>
                    </div>
                </div>

                <!-- Fees Due -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 card-hover" style="border-radius: 12px; border-left: 4px solid #ec4899 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted small text-uppercase fw-bold mb-0">Fees Ledger</h6>
                            <i class="fa fa-wallet text-danger fs-4"></i>
                        </div>
                        <?php
                        // Check pending fees
                        $stmt_fee = $pdo->prepare("SELECT IFNULL(SUM(due_amount), 0) FROM fee_payments WHERE student_id=?");
                        $stmt_fee->execute([$child['id']]);
                        $dues = (float)$stmt_fee->fetchColumn();
                        ?>
                        <h3 class="fw-bold mb-1 text-danger">₹ <?= number_format($dues, 2) ?></h3>
                        <p class="text-muted small mb-3">Total outstanding pending fee dues</p>
                        <a href="fees.php" class="btn btn-outline-danger btn-sm w-100 mt-2">Dues Statement</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning text-center shadow py-5 border-0">
        <i class="fa fa-exclamation-triangle fs-1 mb-3 text-warning"></i>
        <h4>Access Restricted</h4>
        <p class="mb-0 text-muted">No student child is currently linked to your parent portal account. Please contact school administration.</p>
    </div>
<?php endif; ?>

<?php require_once('includes/footer.php'); ?>
