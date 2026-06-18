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

// Handle manual voucher entry
if (isset($_POST['create_voucher'])) {
    $type = $_POST['type'];
    $amount = (float)$_POST['amount'];
    $note = trim($_POST['note']);
    $date = $_POST['date'] ?: date('Y-m-d');

    if (!in_array($type, ['Receipt', 'Payment']) || $amount <= 0) {
        $error = 'Invalid type or amount.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO vouchers (type, amount, note, date, school_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$type, $amount, $note, $date, CURRENT_SCHOOL_ID]);
            $success = 'Voucher registered successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving voucher: ' . $e->getMessage();
        }
    }
}

// Fetch all vouchers
$vouchers = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE school_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([CURRENT_SCHOOL_ID]);
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching vouchers: ' . $e->getMessage();
}

$page_title = "Voucher Registry | VIC ERP";
$page_header = "Receipts & Payments Vouchers";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Receipts & Payments Vouchers</h2>
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
        <!-- New Voucher Entry Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-file-invoice-dollar me-2 text-primary"></i>New Voucher Record</h5>
                <hr class="text-muted mt-0 mb-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Voucher Type</label>
                        <select name="type" class="form-select" required style="border-radius: 8px;">
                            <option value="Receipt">Receipt (Inward Income)</option>
                            <option value="Payment">Payment (Outward Expense)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Voucher Date</label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" style="border-radius: 8px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Memo / Narration</label>
                        <textarea name="note" class="form-control" placeholder="Brief note on transaction details..." rows="3" style="border-radius: 8px;"></textarea>
                    </div>
                    <button type="submit" name="create_voucher" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-save me-2"></i> Create Voucher
                    </button>
                </form>
            </div>
        </div>

        <!-- Vouchers List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-success"></i>Voucher Register logs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Voucher No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Narration</th>
                                <th class="pe-4 text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($vouchers) > 0): ?>
                                <?php foreach ($vouchers as $v): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#V-<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td class="small text-muted"><?= date('d M Y', strtotime($v['date'])) ?></td>
                                        <td>
                                            <?php if ($v['type'] === 'Receipt'): ?>
                                                <span class="badge bg-success-subtle text-success"><i class="fa fa-arrow-down me-1"></i> Receipt</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger"><i class="fa fa-arrow-up me-1"></i> Payment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($v['note'] ?: 'Cash Voucher') ?></td>
                                        <td class="pe-4 text-end fw-bold <?= $v['type'] === 'Receipt' ? 'text-success' : 'text-danger' ?>">
                                            ₹ <?= number_format($v['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No vouchers recorded in this school.
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
