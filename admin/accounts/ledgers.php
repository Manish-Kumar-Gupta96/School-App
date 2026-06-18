<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$error = '';

// Filters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$type = $_GET['type'] ?? '';

$query = "SELECT * FROM ledger WHERE school_id = ?";
$params = [CURRENT_SCHOOL_ID];

if (!empty($start_date)) {
    $query .= " AND entry_date >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND entry_date <= ?";
    $params[] = $end_date;
}
if (!empty($type)) {
    $query .= " AND entry_type = ?";
    $params[] = $type;
}

$query .= " ORDER BY entry_date DESC, id DESC";

// Fetch ledger entries
$ledger_entries = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ledger_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading ledger: ' . $e->getMessage();
}

// Calculate Summaries
try {
    $stmt_sum = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN entry_type = 'INCOME' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN entry_type = 'EXPENSE' THEN amount ELSE 0 END) AS total_expense
        FROM ledger 
        WHERE school_id = ?
    ");
    $stmt_sum->execute([CURRENT_SCHOOL_ID]);
    $sums = $stmt_sum->fetch(PDO::FETCH_ASSOC);
    $total_income = (float)$sums['total_income'];
    $total_expense = (float)$sums['total_expense'];
    $net_balance = $total_income - $total_expense;
} catch (PDOException $e) {
    $total_income = 0;
    $total_expense = 0;
    $net_balance = 0;
}

$page_title = "General Ledger | VIC ERP";
$page_header = "Finance & Accounts Central Ledger";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <!-- Quick Shortcuts Toolbar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h2 class="fw-bold text-dark mb-0">General Ledger Book</h2>
        <div class="d-flex flex-wrap gap-1">
            <a href="income.php" class="btn btn-sm btn-success"><i class="fa fa-money-bill-wave me-1"></i> + Income</a>
            <a href="expenses.php" class="btn btn-sm btn-danger"><i class="fa fa-wallet me-1"></i> - Payout</a>
            <a href="salary.php" class="btn btn-sm btn-warning"><i class="fa fa-users me-1"></i> Salaries</a>
            <a href="fee-heads.php" class="btn btn-sm btn-info text-white"><i class="fa fa-graduation-cap me-1"></i> Fee Heads</a>
            <a href="vouchers.php" class="btn btn-sm btn-secondary"><i class="fa fa-file-invoice me-1"></i> Vouchers</a>
            <a href="bank-accounts.php" class="btn btn-sm btn-primary"><i class="fa fa-university me-1"></i> Banks</a>
            <a href="reports.php" class="btn btn-sm btn-dark"><i class="fa fa-chart-line me-1"></i> Reports</a>
            <a href="subscription-plans.php" class="btn btn-sm btn-outline-primary"><i class="fa fa-cloud me-1"></i> SaaS Plans</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- STATS CARDS -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Income</h6>
                <h3 class="fw-bold text-success mb-0">₹ <?= number_format($total_income, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Accumulated school revenues</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Expenses</h6>
                <h3 class="fw-bold text-danger mb-0">₹ <?= number_format($total_expense, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Accumulated school payouts</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Net Balance</h6>
                <h3 class="fw-bold text-primary mb-0">₹ <?= number_format($net_balance, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Total cash reserves in vault/banks</p>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card border-0 shadow-sm p-3 mb-4" style="border-radius: 12px;">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" style="border-radius: 8px;">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" style="border-radius: 8px;">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Transaction Type</label>
                <select name="type" class="form-select" style="border-radius: 8px;">
                    <option value="">All Transactions</option>
                    <option value="INCOME" <?= $type === 'INCOME' ? 'selected' : '' ?>>Incomes Only</option>
                    <option value="EXPENSE" <?= $type === 'EXPENSE' ? 'selected' : '' ?>>Expenses Only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold" style="border-radius: 8px;">
                    <i class="fa fa-filter me-1"></i> Filter
                </button>
                <a href="ledgers.php" class="btn btn-outline-secondary w-100 fw-semibold" style="border-radius: 8px;">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Ledger Entries -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-book-open me-2 text-primary"></i>Ledger Entries Register</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description / Reference</th>
                        <th class="pe-4 text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($ledger_entries) > 0): ?>
                        <?php foreach ($ledger_entries as $entry): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= date('d M Y', strtotime($entry['entry_date'])) ?></td>
                                <td>
                                    <?php if ($entry['entry_type'] === 'INCOME'): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa fa-arrow-down-long me-1"></i> Credit</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger"><i class="fa fa-arrow-up-long me-1"></i> Debit</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($entry['category']) ?></span></td>
                                <td>
                                    <?= htmlspecialchars($entry['description']) ?>
                                    <?php if ($entry['reference_type'] === 'AI_ANOMALY'): ?>
                                        <span class="badge bg-danger text-white ms-1" style="font-size: 0.7rem;">Anomaly Flagged</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end fw-bold <?= $entry['entry_type'] === 'INCOME' ? 'text-success' : 'text-danger' ?>">
                                    ₹ <?= number_format($entry['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No ledger items matched these filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
