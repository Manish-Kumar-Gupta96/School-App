<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'post_voucher') {
        $voucher_type = $_POST['voucher_type']; // 'receipt' or 'payment'
        $account_dr = (int)$_POST['account_dr'];
        $account_cr = (int)$_POST['account_cr'];
        $amount = (float)$_POST['amount'];
        $narration = trim($_POST['narration']);
        $transaction_date = $_POST['transaction_date'];
        
        if ($amount > 0 && $account_dr > 0 && $account_cr > 0 && $account_dr !== $account_cr) {
            $pdo->beginTransaction();
            try {
                // Generate Voucher No
                $prefix = $voucher_type === 'receipt' ? 'RV-' : 'PV-';
                $voucher_no = $prefix . date('Ym') . rand(1000, 9999);
                
                // 1. Create Journal Entry
                $stmt = $pdo->prepare("INSERT INTO journal_entries (school_id, voucher_no, transaction_date, narration, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $voucher_no, $transaction_date, $narration, $_SESSION['admin_id']]);
                $journal_id = $pdo->lastInsertId();
                
                // 2. Insert Debit Item
                $stmt = $pdo->prepare("INSERT INTO journal_entry_items (journal_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt->execute([$journal_id, $account_dr, $amount]);
                
                // 3. Insert Credit Item
                $stmt = $pdo->prepare("INSERT INTO journal_entry_items (journal_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt->execute([$journal_id, $account_cr, $amount]);
                
                // 4. Post to Ledger (Debit)
                $stmt = $pdo->prepare("INSERT INTO ledger_entries (school_id, transaction_no, account_id, debit, credit, narration, entry_date, created_by) VALUES (?, ?, ?, ?, 0, ?, ?, ?)");
                $stmt->execute([$school_id, $voucher_no, $account_dr, $amount, $narration, $transaction_date, $_SESSION['admin_id']]);
                
                // 5. Post to Ledger (Credit)
                $stmt = $pdo->prepare("INSERT INTO ledger_entries (school_id, transaction_no, account_id, debit, credit, narration, entry_date, created_by) VALUES (?, ?, ?, 0, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $voucher_no, $account_cr, $amount, $narration, $transaction_date, $_SESSION['admin_id']]);
                
                $pdo->commit();
                $_SESSION['success'] = "Voucher $voucher_no posted successfully to the ledger.";
            } catch(PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to post voucher: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Invalid accounts or amount.";
        }
        header("Location: vouchers.php");
        exit;
    }
}

// Fetch Accounts
$accounts = $pdo->query("SELECT id, account_name, account_type FROM accounts_chart WHERE school_id=$school_id ORDER BY account_type, account_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent journals
$recent_vouchers = $pdo->query("
    SELECT j.*, a.name as admin_name, 
           (SELECT SUM(debit) FROM journal_entry_items WHERE journal_id=j.id) as total_amount
    FROM journal_entries j
    LEFT JOIN admins a ON j.created_by = a.id
    WHERE j.school_id=$school_id
    ORDER BY j.created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Voucher Entry | VIC School ERP";
$page_header = "Double-Entry Accounting";
$active_menu = "finance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Voucher Form -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-file-invoice-dollar me-2"></i>Post New Voucher</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="post_voucher">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Voucher Type <span class="text-danger">*</span></label>
                                <select name="voucher_type" class="form-select" required>
                                    <option value="receipt">Receipt (Income)</option>
                                    <option value="payment">Payment (Expense)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mb-3 border">
                            <label class="form-label fw-bold text-success">Debit Account (Dr) <span class="text-danger">*</span></label>
                            <select name="account_dr" class="form-select mb-2" required>
                                <option value="">Select Account to Debit...</option>
                                <?php foreach($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['account_type'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mb-3">Receipts usually debit Cash/Bank. Payments debit Expenses.</small>
                            
                            <label class="form-label fw-bold text-danger">Credit Account (Cr) <span class="text-danger">*</span></label>
                            <select name="account_cr" class="form-select" required>
                                <option value="">Select Account to Credit...</option>
                                <?php foreach($accounts as $acc): ?>
                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['account_type'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block">Receipts usually credit Income. Payments credit Cash/Bank.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-end fw-bold" required placeholder="0.00">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Narration / Remarks <span class="text-danger">*</span></label>
                            <textarea name="narration" class="form-control" rows="2" required placeholder="Being payment made for..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"><i class="fa fa-paper-plane me-2"></i> Post to Ledger</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Journals -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-book me-2 text-info"></i>Recent Journal Entries</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Voucher No</th>
                                    <th>Date</th>
                                    <th>Narration</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_vouchers) > 0): ?>
                                    <?php foreach ($recent_vouchers as $v): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold font-monospace text-primary"><?= htmlspecialchars($v['voucher_no']) ?></td>
                                            <td class="text-muted small"><?= date('d M Y', strtotime($v['transaction_date'])) ?></td>
                                            <td>
                                                <div class="text-truncate small text-dark" style="max-width: 200px;"><?= htmlspecialchars($v['narration']) ?></div>
                                                <div class="text-muted" style="font-size: 0.65rem;">By: <?= htmlspecialchars($v['admin_name']) ?></div>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-dark">₹<?= number_format($v['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No vouchers posted yet.</td></tr>
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
