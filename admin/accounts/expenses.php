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
$ai_warning = '';

// Handle Adding Expense
if (isset($_POST['add_expense'])) {
    $entry_date = $_POST['entry_date'] ?: date('Y-m-d');
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    $bank_id = isset($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : 0;

    if (empty($category) || $amount <= 0) {
        $error = 'Category and valid Amount are required.';
    } else {
        // AI Integration for Anomaly Detection
        $is_anomaly = 0;
        $ai_reason = '';
        try {
            $ch = curl_init("http://127.0.0.1:5000/anomaly");
            $payload = json_encode([
                "amount" => $amount,
                "category" => $category,
                "description" => $description,
                "entry_type" => "EXPENSE"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $resData = json_decode($response, true);
                if (isset($resData['is_anomaly']) && $resData['is_anomaly']) {
                    $is_anomaly = 1;
                    $ai_reason = $resData['reason'] ?? 'Flagged as anomalous payout by AI.';
                    $ai_warning = "⚠️ AI Alert: This payout has been flagged as suspicious! Reason: " . htmlspecialchars($ai_reason);
                }
            }
        } catch (Exception $e) {
            // Ignore AI server failure and proceed
        }

        try {
            $pdo->beginTransaction();

            // Insert to Ledger
            $stmt = $pdo->prepare("INSERT INTO ledger (entry_type, category, amount, description, reference_type, reference_id, entry_date, school_id) VALUES ('EXPENSE', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category, $amount, $description, $is_anomaly ? 'AI_ANOMALY' : NULL, $is_anomaly ? 1 : NULL, $entry_date, CURRENT_SCHOOL_ID]);

            // If bank account is specified, update bank balance (subtract for expenses)
            if ($bank_id > 0) {
                $stmt_bank = $pdo->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ? AND school_id = ?");
                $stmt_bank->execute([$amount, $bank_id, CURRENT_SCHOOL_ID]);

                // Create Voucher entry automatically
                $stmt_v = $pdo->prepare("INSERT INTO vouchers (type, amount, note, date, school_id) VALUES ('Payment', ?, ?, ?, ?)");
                $stmt_v->execute([$amount, "Auto Payment for Expense: " . $description, $entry_date, CURRENT_SCHOOL_ID]);
            }

            $pdo->commit();
            $success = 'Expense transaction recorded successfully.' . ($ai_warning ? ' ' . $ai_warning : '');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database transaction failed: ' . $e->getMessage();
        }
    }
}

// Fetch bank accounts
$bank_accounts = $pdo->prepare("SELECT * FROM bank_accounts WHERE school_id = ?");
$bank_accounts->execute([CURRENT_SCHOOL_ID]);
$banks = $bank_accounts->fetchAll(PDO::FETCH_ASSOC);

// Fetch Expenses list
$expenses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM ledger WHERE school_id = ? AND entry_type = 'EXPENSE' ORDER BY id DESC");
    $stmt->execute([CURRENT_SCHOOL_ID]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching ledger: ' . $e->getMessage();
}

$page_title = "Log Expense | VIC ERP";
$page_header = "Expense Registry & AI Classification";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Expense & Payout Logs</h2>
        <div>
            <a href="income.php" class="btn btn-outline-success me-2">
                <i class="fa fa-money-bill-wave me-1"></i> Log Income
            </a>
            <a href="ledgers.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Ledger Book
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= $success ?>
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
        <!-- Record Expense Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-plus-circle me-2 text-danger"></i>Record Payout</h5>
                <hr class="text-muted mt-0 mb-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transaction Date</label>
                        <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description / Reference <span class="text-danger">*</span></label>
                        <input type="text" id="desc_field" name="description" class="form-control" placeholder="e.g. Monthly electricity bill payout" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Category <span class="text-danger">*</span></label>
                            <button type="button" id="ai_suggest_btn" class="btn btn-sm btn-outline-primary py-0" style="font-size: 0.75rem; border-radius: 6px;">
                                <i class="fa fa-wand-magic-sparkles"></i> AI Suggest
                            </button>
                        </div>
                        <input type="text" id="cat_field" name="category" class="form-control" placeholder="e.g. Utility Expense" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Source Bank Account (Optional)</label>
                        <select name="bank_account_id" class="form-select" style="border-radius: 8px;">
                            <option value="">-- Cash Transactions --</option>
                            <?php foreach ($banks as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['bank_name']) ?> (Acc: <?= htmlspecialchars($b['account_no']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_expense" class="btn btn-danger w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-save me-2"></i> Save Expense
                    </button>
                </form>
            </div>
        </div>

        <!-- Expense registry table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-history me-2 text-danger"></i>Expense Transactions History</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount (₹)</th>
                                <th class="pe-4 text-end">Alerts</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($expenses) > 0): ?>
                                <?php foreach ($expenses as $exp): ?>
                                    <tr class="<?= $exp['reference_type'] === 'AI_ANOMALY' ? 'table-warning' : '' ?>">
                                        <td class="ps-4 text-muted small"><?= date('d M Y', strtotime($exp['entry_date'])) ?></td>
                                        <td><span class="badge bg-danger-subtle text-danger"><?= htmlspecialchars($exp['category']) ?></span></td>
                                        <td><?= htmlspecialchars($exp['description']) ?></td>
                                        <td class="fw-bold text-danger">₹ <?= number_format($exp['amount'], 2) ?></td>
                                        <td class="pe-4 text-end">
                                            <?php if ($exp['reference_type'] === 'AI_ANOMALY'): ?>
                                                <span class="badge bg-danger text-white" title="Anomaly Detected!"><i class="fa fa-triangle-exclamation"></i> AI Suspect</span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No expense records logged yet.
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

<script>
document.getElementById('ai_suggest_btn').addEventListener('click', function() {
    const desc = document.getElementById('desc_field').value.trim();
    if (!desc) {
        alert('Please enter a description first to let the AI suggest a category.');
        return;
    }

    const originalBtn = this.innerHTML;
    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Analyzing...';
    this.disabled = true;

    fetch('http://127.0.0.1:5000/categorize', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ description: desc })
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.category) {
            document.getElementById('cat_field').value = data.category;
        } else {
            alert('AI was unable to predict category. Defaulting to Other.');
            document.getElementById('cat_field').value = 'Other';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Could not reach AI Microservice. Make sure Flask API is running.');
    })
    .finally(() => {
        this.innerHTML = originalBtn;
        this.disabled = false;
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
