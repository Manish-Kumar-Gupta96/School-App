<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_account') {
        $id = (int)$_POST['id'];
        $account_code = trim($_POST['account_code']);
        $account_name = trim($_POST['account_name']);
        $account_type = $_POST['account_type']; // asset, liability, income, expense, equity
        
        if (!empty($account_name) && !empty($account_type)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE accounts_chart SET account_code=?, account_name=?, account_type=? WHERE id=? AND school_id=?");
                $stmt->execute([$account_code, $account_name, $account_type, $id, $school_id]);
                $_SESSION['success'] = "Account updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO accounts_chart (school_id, account_code, account_name, account_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$school_id, $account_code, $account_name, $account_type]);
                $_SESSION['success'] = "Account created successfully.";
            }
        }
        header("Location: chart_of_accounts.php");
        exit;
    } elseif ($_POST['action'] === 'delete_account') {
        $id = (int)$_POST['id'];
        try {
            $pdo->prepare("DELETE FROM accounts_chart WHERE id=? AND school_id=?")->execute([$id, $school_id]);
            $_SESSION['success'] = "Account deleted.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Cannot delete account. It may have existing ledger entries.";
        }
        header("Location: chart_of_accounts.php");
        exit;
    }
}

// Fetch all accounts
$accounts = $pdo->prepare("SELECT * FROM accounts_chart WHERE school_id = ? ORDER BY account_type ASC, account_name ASC");
$accounts->execute([$school_id]);
$accounts = $accounts->fetchAll(PDO::FETCH_ASSOC);

$edit_acc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM accounts_chart WHERE id = ? AND school_id = ?");
    $stmt->execute([(int)$_GET['edit'], $school_id]);
    $edit_acc = $stmt->fetch(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Chart of Accounts | VIC School ERP";
$page_header = "Financial Structure";
$active_menu = "finance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Manage Account Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa fa-sitemap me-2"></i><?= $edit_acc ? 'Edit Account' : 'Create Ledger Account' ?></h5>
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
                        <input type="hidden" name="action" value="save_account">
                        <input type="hidden" name="id" value="<?= $edit_acc['id'] ?? 0 ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" class="form-control" value="<?= htmlspecialchars($edit_acc['account_name'] ?? '') ?>" required placeholder="e.g. Tuition Fee Income">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Account Code</label>
                            <input type="text" name="account_code" class="form-control" value="<?= htmlspecialchars($edit_acc['account_code'] ?? '') ?>" placeholder="e.g. INC-4001">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Account Type <span class="text-danger">*</span></label>
                            <select name="account_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="asset" <?= isset($edit_acc['account_type']) && $edit_acc['account_type'] == 'asset' ? 'selected' : '' ?>>Asset (e.g. Cash, Bank)</option>
                                <option value="liability" <?= isset($edit_acc['account_type']) && $edit_acc['account_type'] == 'liability' ? 'selected' : '' ?>>Liability (e.g. Loans, Deposits)</option>
                                <option value="income" <?= isset($edit_acc['account_type']) && $edit_acc['account_type'] == 'income' ? 'selected' : '' ?>>Income (e.g. Fees, Fines)</option>
                                <option value="expense" <?= isset($edit_acc['account_type']) && $edit_acc['account_type'] == 'expense' ? 'selected' : '' ?>>Expense (e.g. Salary, Electricity)</option>
                                <option value="equity" <?= isset($edit_acc['account_type']) && $edit_acc['account_type'] == 'equity' ? 'selected' : '' ?>>Equity (e.g. Capital)</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_acc): ?>
                                <a href="chart_of_accounts.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success fw-bold"><i class="fa fa-save me-2"></i> Save Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Accounts List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Chart of Accounts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Account Name & Code</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($accounts) > 0): ?>
                                    <?php foreach ($accounts as $acc): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($acc['account_name']) ?></div>
                                                <div class="text-muted small font-monospace"><?= htmlspecialchars($acc['account_code'] ?: 'No Code') ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                    $bg = 'secondary';
                                                    if($acc['account_type'] == 'asset') $bg = 'primary';
                                                    if($acc['account_type'] == 'liability') $bg = 'warning text-dark';
                                                    if($acc['account_type'] == 'income') $bg = 'success';
                                                    if($acc['account_type'] == 'expense') $bg = 'danger';
                                                ?>
                                                <span class="badge bg-<?= $bg ?> text-uppercase"><?= htmlspecialchars($acc['account_type']) ?></span>
                                            </td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td class="pe-4 text-end">
                                                <a href="?edit=<?= $acc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this account? Ensure no ledgers are attached.');">
                                                    <input type="hidden" name="action" value="delete_account">
                                                    <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No accounts configured yet. Create one to begin double-entry accounting.</td></tr>
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
