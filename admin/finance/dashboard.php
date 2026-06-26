<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

// Accounting Logic
// Total Assets (Debit Balances)
$stmt = $pdo->query("SELECT SUM(debit - credit) FROM ledger_entries le JOIN accounts_chart ac ON le.account_id = ac.id WHERE ac.account_type = 'asset' AND le.school_id=$school_id");
$total_assets = $stmt->fetchColumn() ?? 0;

// Total Liabilities (Credit Balances)
$stmt = $pdo->query("SELECT SUM(credit - debit) FROM ledger_entries le JOIN accounts_chart ac ON le.account_id = ac.id WHERE ac.account_type = 'liability' AND le.school_id=$school_id");
$total_liabilities = $stmt->fetchColumn() ?? 0;

// Total Income (Credit Balances)
$stmt = $pdo->query("SELECT SUM(credit - debit) FROM ledger_entries le JOIN accounts_chart ac ON le.account_id = ac.id WHERE ac.account_type = 'income' AND le.school_id=$school_id");
$total_income = $stmt->fetchColumn() ?? 0;

// Total Expenses (Debit Balances)
$stmt = $pdo->query("SELECT SUM(debit - credit) FROM ledger_entries le JOIN accounts_chart ac ON le.account_id = ac.id WHERE ac.account_type = 'expense' AND le.school_id=$school_id");
$total_expenses = $stmt->fetchColumn() ?? 0;

$net_profit = $total_income - $total_expenses;

// Recent Ledgers
$recent_ledgers = $pdo->query("
    SELECT le.*, ac.account_name, ac.account_type 
    FROM ledger_entries le 
    JOIN accounts_chart ac ON le.account_id = ac.id 
    WHERE le.school_id=$school_id 
    ORDER BY le.created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Finance Dashboard | VIC School ERP";
$page_header = "Financial Overview";
$active_menu = "finance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">

    <!-- Finance Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-success mb-2"><i class="fa fa-arrow-up fs-1"></i></div>
                <h3 class="fw-bold mb-0">₹<?= number_format($total_income, 2) ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Total Income</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-danger mb-2"><i class="fa fa-arrow-down fs-1"></i></div>
                <h3 class="fw-bold mb-0">₹<?= number_format($total_expenses, 2) ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Total Expenses</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3 <?= $net_profit >= 0 ? 'bg-primary text-white' : 'bg-warning text-dark' ?>" style="border-radius: 15px;">
                <div class="mb-2"><i class="fa fa-chart-pie fs-1"></i></div>
                <h3 class="fw-bold mb-0">₹<?= number_format($net_profit, 2) ?></h3>
                <div class="small text-uppercase fw-bold opacity-75">Net Profit/Loss</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-info mb-2"><i class="fa fa-building fs-1"></i></div>
                <h3 class="fw-bold mb-0">₹<?= number_format($total_assets, 2) ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Total Assets</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- AI Financial Insight -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4 bg-dark text-white" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fa fa-robot fs-2 text-warning me-3"></i>
                        <h5 class="fw-bold mb-0">AI Finance Insight</h5>
                    </div>
                    <?php if($net_profit > 0): ?>
                        <p class="small text-white-50">The school is operating at a healthy surplus. Income exceeds expenses by <?= number_format(($net_profit/$total_income)*100, 1) ?>%. AI recommends allocating surplus to the Library Budget.</p>
                    <?php else: ?>
                        <p class="small text-warning">Warning: Expenses are currently exceeding income. AI recommends reviewing Transport and Maintenance costs immediately.</p>
                    <?php endif; ?>
                    <hr class="border-secondary">
                    <div class="d-flex justify-content-between small font-monospace">
                        <span>Assets: ₹<?= number_format($total_assets) ?></span>
                        <span>Liab: ₹<?= number_format($total_liabilities) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4">Quick Links</h6>
                    <div class="d-grid gap-3">
                        <a href="vouchers.php" class="btn btn-outline-primary"><i class="fa fa-plus me-2"></i>Post Voucher</a>
                        <a href="chart_of_accounts.php" class="btn btn-outline-secondary"><i class="fa fa-sitemap me-2"></i>Chart of Accounts</a>
                        <button class="btn btn-outline-success"><i class="fa fa-file-pdf me-2"></i>Balance Sheet</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Stream -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-stream me-2 text-primary"></i>Live Ledger Stream</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Voucher</th>
                                    <th>Account</th>
                                    <th class="text-end">Debit (Dr)</th>
                                    <th class="text-end pe-4">Credit (Cr)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_ledgers) > 0): ?>
                                    <?php foreach ($recent_ledgers as $l): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= date('d M Y', strtotime($l['entry_date'])) ?></td>
                                            <td class="fw-bold font-monospace text-secondary small"><?= htmlspecialchars($l['transaction_no']) ?></td>
                                            <td>
                                                <div class="text-dark fw-bold"><?= htmlspecialchars($l['account_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.65rem; text-transform: uppercase;"><?= htmlspecialchars($l['account_type']) ?></div>
                                            </td>
                                            <td class="text-end text-success fw-bold"><?= $l['debit'] > 0 ? number_format($l['debit'], 2) : '-' ?></td>
                                            <td class="text-end pe-4 text-danger fw-bold"><?= $l['credit'] > 0 ? number_format($l['credit'], 2) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No ledger entries found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
