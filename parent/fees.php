<?php
require_once('../config/database.php');
$active_menu = "fees";
$page_title = "Child Fees Status | Parent Portal";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];

// Get child details
$stmt = $pdo->prepare("
    SELECT s.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name 
    FROM students s
    JOIN parent_student_map psm ON s.id = psm.student_id
    WHERE psm.parent_id = ? AND s.school_id = ?
");
$stmt->execute([$parent_id, CURRENT_SCHOOL_ID]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    $child = $pdo->query("SELECT *, CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE school_id = " . CURRENT_SCHOOL_ID . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

$fees = [];
$total_fee = 0;
$total_paid = 0;
$total_due = 0;

if($child){
    $stmt = $pdo->prepare("
        SELECT * FROM fee_payments
        WHERE student_id=?
        ORDER BY id DESC
    ");
    $stmt->execute([$child['id']]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($fees as $fee){
        $total_fee += $fee['total_fee'];
        $total_paid += $fee['paid_amount'];
        $total_due += $fee['due_amount'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">💰 Child Fees Ledger</h2>
        <p class="text-muted mb-0">Student: <strong><?= htmlspecialchars($child['student_name'] ?? 'N/A') ?></strong> | Class: <strong><?= htmlspecialchars($child['class'] ?? 'N/A') ?></strong></p>
    </div>
</div>

<!-- METRIC COUNTERS -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Total Scheduled Fee</h6>
                <h3 class="fw-bold mb-0 text-dark">₹ <?= number_format($total_fee, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Total Paid Amount</h6>
                <h3 class="fw-bold mb-0 text-success">₹ <?= number_format($total_paid, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
            <div class="card-body text-start py-4 ps-4">
                <h6 class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Due Balance</h6>
                <h3 class="fw-bold mb-0 text-danger">₹ <?= number_format($total_due, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- FEES LIST -->
<div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden; text-align: start;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-wallet me-2 text-danger"></i>Invoice Ledgers</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Receipt Ref.</th>
                        <th>Fee Type</th>
                        <th>Total Fee</th>
                        <th>Paid Amount</th>
                        <th>Pending Dues</th>
                         <th>Due Date</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th class="pe-4 text-center">Action</th>
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
                                <td>
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        <?= htmlspecialchars($fee['payment_mode'] ?: 'N/A') ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-center">
                                    <?php if ($fee['due_amount'] > 0): ?>
                                        <a href="pay-fee.php?fee_id=<?= $fee['id'] ?>" class="btn btn-sm btn-primary fw-semibold" style="border-radius: 6px;">
                                            <i class="fa fa-credit-card me-1"></i> Pay Online
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-circle-check me-1"></i> Fully Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa fa-file-invoice fs-2 mb-2 d-block"></i>
                                No child fee invoice statements found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
