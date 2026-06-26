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

// Handle Salary Payment Payout
if (isset($_POST['pay_salary'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $amount = (float)$_POST['amount'];
    $bank_id = (int)$_POST['bank_account_id'];
    $month = $_POST['salary_month'];

    if ($amount <= 0 || empty($month)) {
        $error = 'Valid Amount and Month are required.';
    } else {
        try {
            $pdo->beginTransaction();

            // Fetch teacher name
            $stmt_t = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM teachers WHERE id = ? AND school_id = ?");
            $stmt_t->execute([$teacher_id, CURRENT_SCHOOL_ID]);
            $teacher = $stmt_t->fetch(PDO::FETCH_ASSOC);
            $teacher_name = $teacher ? $teacher['name'] : 'Teacher';

            $desc = "Salary Payout to {$teacher_name} for {$month}";

            // Insert to Ledger
            $stmt_l = $pdo->prepare("INSERT INTO ledger (entry_type, category, amount, description, reference_type, reference_id, entry_date, school_id) VALUES ('EXPENSE', 'Salary Expense', ?, ?, 'TEACHER_SALARY', ?, ?, ?)");
            $stmt_l->execute([$amount, $desc, $teacher_id, date('Y-m-d'), CURRENT_SCHOOL_ID]);

            // Deduct from bank account
            if ($bank_id > 0) {
                $stmt_bank = $pdo->prepare("UPDATE bank_accounts SET balance = balance - ? WHERE id = ? AND school_id = ?");
                $stmt_bank->execute([$amount, $bank_id, CURRENT_SCHOOL_ID]);
            }

            // Create payment voucher
            $stmt_v = $pdo->prepare("INSERT INTO vouchers (type, amount, note, date, school_id) VALUES ('Payment', ?, ?, ?, ?)");
            $stmt_v->execute([$amount, $desc, date('Y-m-d'), CURRENT_SCHOOL_ID]);

            $pdo->commit();
            $success = "Salary of ₹ " . number_format($amount, 2) . " paid to {$teacher_name} successfully for {$month}.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Salary payout transaction failed: ' . $e->getMessage();
        }
    }
}

// Fetch bank accounts
$banks = $pdo->prepare("SELECT * FROM bank_accounts WHERE school_id = ?");
$banks->execute([CURRENT_SCHOOL_ID]);
$bank_list = $banks->fetchAll(PDO::FETCH_ASSOC);

// Fetch Teachers
$teachers = [];
try {
    // Check columns first or select basic fields
    $stmt_t = $pdo->prepare("SELECT id, name, email, phone FROM teachers WHERE school_id = ?");
    $stmt_t->execute([CURRENT_SCHOOL_ID]);
    $teachers = $stmt_t->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading teachers: ' . $e->getMessage();
}

$page_title = "Teacher Payroll | VIC ERP";
$page_header = "Faculty Salary Payouts";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Payroll & Salaries</h2>
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

    <!-- Payout Dialog Card -->
    <div class="card border-0 shadow-sm mb-4 p-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark mb-3"><i class="fa fa-money-check-dollar me-2 text-primary"></i>Process Salary Payout</h5>
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Select Teacher</label>
                <select name="teacher_id" class="form-select" required style="border-radius: 8px;">
                    <option value="">-- Choose Faculty --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (ID: <?= $t['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Salary Month & Year</label>
                <input type="month" name="salary_month" class="form-control" value="<?= date('Y-m') ?>" required style="border-radius: 8px;">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Salary Amount (₹)</label>
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 35000" required style="border-radius: 8px;">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Source Bank Account</label>
                <select name="bank_account_id" class="form-select" required style="border-radius: 8px;">
                    <option value="">-- Choose Account --</option>
                    <?php foreach ($bank_list as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['bank_name']) ?> (₹ <?= number_format($b['balance'], 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" name="pay_salary" class="btn btn-primary px-4 fw-semibold" style="border-radius: 8px;">
                    <i class="fa fa-paper-plane me-2"></i> Disburse Salary
                </button>
            </div>
        </form>
    </div>

    <!-- Teacher Directory / Payroll Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-users me-2 text-success"></i>Faculty Directory</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Teacher ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="pe-4 text-end">Disbursements</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($teachers) > 0): ?>
                        <?php foreach ($teachers as $t): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $t['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($t['name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($t['email']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($t['phone'] ?: '-') ?></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick="disburseTo(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>')">
                                        <i class="fa fa-circle-dollar-to-slot"></i> Quick Pay
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No teachers registered in this school tenant.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function disburseTo(id, name) {
    const select = document.querySelector('select[name="teacher_id"]');
    select.value = id;
    select.dispatchEvent(new Event('change'));
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
