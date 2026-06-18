<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle bank account registration
if (isset($_POST['add_account'])) {
    $bank_name = trim($_POST['bank_name']);
    $account_no = trim($_POST['account_no']);
    $balance = (float)$_POST['balance'];

    if (empty($bank_name) || empty($account_no) || $balance < 0) {
        $error = 'Please enter valid Bank Name, Account Number and Balance.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_no, balance, school_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bank_name, $account_no, $balance, CURRENT_SCHOOL_ID]);
            $success = 'Bank account registered successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving bank account: ' . $e->getMessage();
        }
    }
}

// Handle bank account deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ? AND school_id = ?");
        $stmt->execute([$id, CURRENT_SCHOOL_ID]);
        $success = 'Bank account removed successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting bank account: ' . $e->getMessage();
    }
}

// Fetch bank accounts
$accounts = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE school_id = ? ORDER BY id DESC");
    $stmt->execute([CURRENT_SCHOOL_ID]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching bank accounts: ' . $e->getMessage();
}

$page_title = "Bank Accounts | VIC ERP";
$page_header = "Manage Bank Accounts";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Bank Accounts Registry</h2>
        <a href="ledgers.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Ledger Book
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- New Account Registration Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-plus-circle me-2 text-primary"></i>Register Account</h5>
                <hr class="text-muted mt-0 mb-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. State Bank of India" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account Number</label>
                        <input type="text" name="account_no" class="form-control" placeholder="e.g. 123456789012" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Opening Balance (₹)</label>
                        <input type="number" step="0.01" name="balance" class="form-control" placeholder="e.g. 50000.00" required style="border-radius: 8px;">
                    </div>
                    <button type="submit" name="add_account" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-save me-2"></i> Register Bank
                    </button>
                </form>
            </div>
        </div>

        <!-- Bank Accounts List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-university me-2 text-success"></i>School Bank Accounts</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Bank Name</th>
                                <th>Account Number</th>
                                <th>Current Balance (₹)</th>
                                <th>Registered Date</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($accounts) > 0): ?>
                                <?php foreach ($accounts as $acc): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($acc['bank_name']) ?></td>
                                        <td class="font-monospace"><?= htmlspecialchars($acc['account_no']) ?></td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($acc['balance'], 2) ?></td>
                                        <td class="text-muted small"><?= date('d M Y', strtotime($acc['created_at'])) ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="?delete=1&id=<?= $acc['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this bank account?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No bank accounts configured. Use the form to register one.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
