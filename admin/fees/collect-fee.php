<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$student = null;
$suggested_fee = 0;

$student_id = $_GET['student_id'] ?? '';

if(!empty($student_id)){
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Look up the class monthly fee in fee_structure
        $fee_stmt = $pdo->prepare("SELECT monthly_fee FROM fee_structure WHERE class_name = ?");
        $fee_stmt->execute([$student['class']]);
        $suggested_fee = $fee_stmt->fetchColumn() ?: 0;
    }
}

// Fetch all students for search list
$students_list = $pdo->query("SELECT id, admission_no, first_name, last_name, class FROM students ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['collect_fee'])){
    $receipt_no = "RCPT" . date('Ymd') . rand(1000,9999);

    $student_id      = $_POST['student_id'];
    $class           = $_POST['class'];
    $fee_month       = $_POST['fee_month'];

    $amount          = $_POST['amount'] ?: 0.00;
    $fine            = $_POST['fine'] ?: 0.00;
    $discount        = $_POST['discount'] ?: 0.00;
    $paid_amount     = $_POST['paid_amount'] ?: 0.00;

    $due_amount = ($amount + $fine) - ($discount + $paid_amount);
    $payment_method  = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];
    $remarks = trim($_POST['remarks']);

    if($due_amount <= 0){
        $status = "Paid";
    } elseif($paid_amount > 0){
        $status = "Partial";
    } else {
        $status = "Pending";
    }

    $insert = $pdo->prepare("
        INSERT INTO fees(
            receipt_no,
            student_id,
            class,
            fee_month,
            amount,
            fine,
            discount,
            paid_amount,
            due_amount,
            payment_method,
            payment_date,
            remarks,
            status
        )
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $insert->execute([
        $receipt_no,
        $student_id,
        $class,
        $fee_month,
        $amount,
        $fine,
        $discount,
        $paid_amount,
        $due_amount,
        $payment_method,
        $payment_date,
        $remarks,
        $status
    ]);

    // Sync to fee_payments table for parent and student dashboards
    try {
        $fee_type = "Tuition Fee (" . $fee_month . ")";
        $total_fee = ($amount + $fine) - $discount;
        $stmt_sync = $pdo->prepare("
            INSERT INTO fee_payments (
                student_id,
                fee_type,
                total_fee,
                paid_amount,
                due_amount,
                due_date,
                payment_date,
                payment_mode,
                receipt_no
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_sync->execute([
            $student_id,
            $fee_type,
            $total_fee,
            $paid_amount,
            $due_amount,
            $payment_date,
            $payment_date,
            $payment_method,
            $receipt_no
        ]);
    } catch (PDOException $ex) {
        // Log sync error but don't crash the main process
        error_log("Fee sync error: " . $ex->getMessage());
    }

    $fee_id = $pdo->lastInsertId();
    require_once('../includes/audit-helper.php');
    addAuditLog(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['name'] ?? 'Admin',
        $_SESSION['role'] ?? 'Admin',
        'Fee Collected: Receipt ' . $receipt_no . ' (Amount: ' . $paid_amount . ')',
        'Finance',
        $fee_id
    );

    $message = "Fee Collected Successfully. Receipt: " . $receipt_no;
    
    // Clear selected student after saving
    $student = null;
    $student_id = '';
}

// Layout setup
$root_path = "../../";
$page_title = "Collect Fee | VIC ERP";
$page_header = "Collect Student Fees";
$active_menu = "fees";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- STUDENT SELECTOR -->
    <div class="col-xl-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white py-3" style="border-radius: 12px 12px 0 0;">
                <h6 class="fw-bold mb-0"><i class="fa fa-search me-2"></i> Find Student</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Student</label>
                    <select id="studentSelector" class="form-select" onchange="if(this.value) window.location.href='collect-fee.php?student_id='+this.value">
                        <option value="">Choose Student...</option>
                        <?php foreach($students_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($student_id == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?> (<?= htmlspecialchars($s['admission_no']) ?>) - <?= htmlspecialchars($s['class']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($student): ?>
                    <hr>
                    <div class="text-center py-2">
                        <?php if($student['photo'] && file_exists("../../uploads/students/" . $student['photo'])) : ?>
                            <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" width="80" height="80" style="object-fit:cover; border-radius:50%; border: 2px solid var(--primary);" class="mb-2">
                        <?php else : ?>
                            <img src="../../assets/images/default-user.png" width="80" height="80" style="object-fit:cover; border-radius:50%;" class="mb-2">
                        <?php endif; ?>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h6>
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($student['admission_no']) ?></span>
                        <div class="text-muted small">Class: <?= htmlspecialchars($student['class']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLLECT FEE FORM -->
    <div class="col-xl-8">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Record Payment Details</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['id'] ?? '') ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Class</label>
                            <input type="text" name="class" class="form-control" value="<?= htmlspecialchars($student['class'] ?? '') ?>" placeholder="Class (auto-loaded)" readonly required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Fee Month</label>
                            <select name="fee_month" class="form-select" required>
                                <?php 
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                $curr_month = date('F');
                                foreach($months as $m):
                                ?>
                                    <option value="<?= $m ?>" <?= ($curr_month === $m) ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Monthly Tuition Fee (₹)</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="<?= $suggested_fee ?>" required placeholder="Total base amount" oninput="calculateNetDue()">
                            <?php if ($suggested_fee > 0): ?>
                                <div class="form-text text-success"><i class="fa fa-info-circle"></i> Loaded default tuition fee for <?= htmlspecialchars($student['class']) ?>.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Fine (₹)</label>
                            <input type="number" step="0.01" name="fine" id="fine" value="0" class="form-control" oninput="calculateNetDue()">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Discount (₹)</label>
                            <input type="number" step="0.01" name="discount" id="discount" value="0" class="form-control" oninput="calculateNetDue()">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Paid Amount (₹) *</label>
                            <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" required placeholder="Amount received" oninput="calculateNetDue()">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option>Cash</option>
                                <option>UPI</option>
                                <option>Bank Transfer</option>
                                <option>Cheque</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Receipt notes..."></textarea>
                        </div>
                        
                        <!-- Net Due calculation preview block -->
                        <div class="col-12 mb-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-bold uppercase">Net Remaining Due</span>
                                <h4 class="fw-bold text-dark mb-0" id="duePreview">₹ 0.00</h4>
                            </div>
                            <div>
                                <button type="submit" name="collect_fee" class="btn btn-success px-4" <?= !$student ? 'disabled' : '' ?>>
                                    <i class="fa fa-cash-register me-1"></i> Record Fee Payment
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calculateNetDue() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const fine = parseFloat(document.getElementById('fine').value) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
    
    const due = (amount + fine) - (discount + paid);
    document.getElementById('duePreview').innerText = '₹ ' + (due > 0 ? due.toFixed(2) : '0.00');
}
</script>

<?php
require_once('../includes/footer.php');
?>
