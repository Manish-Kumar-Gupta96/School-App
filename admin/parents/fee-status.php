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
$fees = [];
$total_fee = 0;
$total_paid = 0;
$total_due = 0;

if($student_id){
    $stmt = $pdo->prepare("
        SELECT *
        FROM fee_payments
        WHERE student_id=?
        ORDER BY id DESC
    ");
    $stmt->execute([$student_id]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($fees as $fee){
        $total_fee += $fee['total_fee'];
        $total_paid += $fee['paid_amount'];
        $total_due += $fee['due_amount'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fee Status | VIC ERP</title>
</head>
<body>

<?php
// Layout setup
$root_path = "../../";
$page_title = "Fee Status | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Track student fee dues and invoice payment histories</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-outline-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
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
                            <option value="<?= $student['id'] ?>" <?= ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($student['student_name']) ?> (Class <?= htmlspecialchars($student['class']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search me-1"></i> View Fee Status
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if($student_id): ?>
    <!-- METRIC CARDS -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Total Scheduled Fee</h6>
                    <h3 class="fw-bold mb-0 text-dark">₹ <?= number_format($total_fee, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Paid Amount</h6>
                    <h3 class="fw-bold mb-0 text-success">₹ <?= number_format($total_paid, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <div class="card-body text-start py-4 ps-4">
                    <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Due Dues</h6>
                    <h3 class="fw-bold mb-0 text-danger">₹ <?= number_format($total_due, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYMENT HISTORY -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark text-start">Fee Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Receipt Ref.</th>
                            <th>Fee Type</th>
                            <th>Total Scheduled</th>
                            <th>Paid Amount</th>
                            <th>Pending Balance</th>
                            <th>Due Date</th>
                            <th>Pay Date</th>
                            <th class="pe-4">Method</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php if (count($fees) > 0): ?>
                            <?php foreach($fees as $fee): ?>
                                <tr>
                                    <td class="ps-4 fw-mono text-dark fw-bold small"><?= htmlspecialchars($fee['receipt_no']) ?></td>
                                    <td class="fw-semibold text-muted"><?= htmlspecialchars($fee['fee_type']) ?></td>
                                    <td class="fw-semibold text-dark">₹ <?= number_format($fee['total_fee'], 2) ?></td>
                                    <td class="fw-bold text-success">₹ <?= number_format($fee['paid_amount'], 2) ?></td>
                                    <td class="fw-bold <?= $fee['due_amount'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                        ₹ <?= number_format($fee['due_amount'], 2) ?>
                                    </td>
                                    <td class="small text-muted"><?= date('d M Y', strtotime($fee['due_date'])) ?></td>
                                    <td class="small text-muted">
                                        <?= $fee['payment_date'] ? date('d M Y', strtotime($fee['payment_date'])) : '<span class="text-danger">Unpaid</span>' ?>
                                    </td>
                                    <td class="pe-4">
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            <?= htmlspecialchars($fee['payment_mode'] ?: 'N/A') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-receipt fs-2 mb-2 d-block"></i>
                                    No fee invoices linked to this student.
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
