<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$status = $_GET['status'] ?? '';
$month  = $_GET['month'] ?? '';

$sql = "
    SELECT 
        f.*,
        s.admission_no,
        s.first_name,
        s.last_name
    FROM fees f
    LEFT JOIN students s ON f.student_id = s.id
    WHERE 1
";
$params = [];

if(!empty($status)){
    $sql .= " AND f.status = ? ";
    $params[] = $status;
}

if(!empty($month)){
    $sql .= " AND f.fee_month = ? ";
    $params[] = $month;
}

$sql .= " ORDER BY f.id DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Fee Transactions | VIC ERP";
$page_header = "Fee Transaction Logs";
$active_menu = "fees";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Browse and Filter Student Payments</h5>
    <a href="collect-fee.php" class="btn btn-primary">
        <i class="fa fa-plus-circle me-1"></i> Collect Fee
    </a>
</div>

<!-- FILTER CARD -->
<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Payment Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Paid" <?= ($status == 'Paid') ? 'selected' : '' ?>>Paid</option>
                    <option value="Partial" <?= ($status == 'Partial') ? 'selected' : '' ?>>Partial</option>
                    <option value="Pending" <?= ($status == 'Pending') ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
            
            <div class="col-md-5">
                <label class="form-label fw-semibold">Month</label>
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    <?php 
                    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    foreach($months as $m):
                    ?>
                        <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">
                    <i class="fa fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE CARD -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr class="text-start">
                        <th class="ps-4">Receipt</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Month</th>
                        <th>Total (₹)</th>
                        <th>Paid (₹)</th>
                        <th>Due (₹)</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($fees) > 0): ?>
                        <?php foreach($fees as $row): ?>
                            <tr class="text-start">
                                <td class="ps-4 fw-semibold"><span class="badge bg-secondary"><?= htmlspecialchars($row['receipt_no']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['admission_no'] ?: 'N/A') ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['fee_month']) ?></td>
                                <td>₹ <?= number_format($row['amount'] + $row['fine'] - $row['discount'], 2) ?></td>
                                <td class="text-success fw-bold">₹ <?= number_format($row['paid_amount'], 2) ?></td>
                                <td class="text-danger fw-bold">₹ <?= number_format($row['due_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    if($row['status'] == 'Paid'){
                                        echo "<span class='badge bg-success-subtle text-success border border-success-subtle px-3 py-2'>Paid</span>";
                                    } elseif($row['status'] == 'Partial'){
                                        echo "<span class='badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2'>Partial</span>";
                                    } else {
                                        echo "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2'>Pending</span>";
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="receipt.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fa fa-print me-1"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa fa-file-invoice-dollar fs-2 mb-2 d-block"></i>
                                No fee transactions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
