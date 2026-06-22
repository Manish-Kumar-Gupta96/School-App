<?php
require_once('../config/database.php');
$active_menu = "fees";
$page_title = "Online Fee Payment | Parent Portal";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];
$fee_id = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : 0;

// Fetch fee invoice details
$stmt = $pdo->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.class, s.id AS student_id
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    JOIN parent_student_map psm ON s.id = psm.student_id
    WHERE fp.id = ? AND psm.parent_id = ? AND s.school_id = ?
");
$stmt->execute([$fee_id, $parent_id, CURRENT_SCHOOL_ID]);
$fee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fee) {
    echo "<div class='alert alert-danger p-4 text-start'><i class='fa fa-exclamation-triangle me-2'></i>Access Denied or Invoice not found.</div>";
    require_once('includes/footer.php');
    exit;
}

if ($fee['due_amount'] <= 0) {
    echo "<div class='alert alert-success p-4 text-start'><i class='fa fa-circle-check me-2'></i>This invoice is already fully paid.</div>";
    require_once('includes/footer.php');
    exit;
}

$net_payable = $fee['due_amount'];
?>

<div class="row text-start justify-content-center">
    <div class="col-xl-8">
        <div class="mb-3">
            <a href="fees.php" class="btn btn-sm btn-light"><i class="fa fa-arrow-left me-1"></i> Back to Ledger</a>
        </div>

        <div class="card shadow border-0 mb-4" style="border-radius: 15px;">
            <div class="card-header bg-primary text-white py-3" style="border-radius: 15px 15px 0 0;">
                <h5 class="fw-bold mb-0"><i class="fa fa-credit-card me-2"></i>Online Payment Gateway Checkout</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Fee Details column -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-muted text-uppercase mb-3 small" style="letter-spacing:1px;">Invoice Details</h6>
                        <div class="p-3 bg-light rounded" style="border-radius: 10px;">
                            <div class="mb-2">
                                <small class="text-muted d-block">Receipt Ref:</small>
                                <span class="fw-mono text-dark fw-bold"><?= htmlspecialchars($fee['receipt_no']) ?></span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">Fee Category / Description:</small>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($fee['fee_type']) ?></span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">Student Name:</small>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></span>
                            </div>
                            <div class="mb-0">
                                <small class="text-muted d-block">Class Section:</small>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($fee['class']) ?></span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold text-muted text-uppercase mb-3 small" style="letter-spacing:1px;">Summary Ledger</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tuition Base Fee:</span>
                                <span class="fw-bold text-dark">₹ <?= number_format($fee['total_fee'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Already Paid:</span>
                                <span class="fw-bold text-success">₹ <?= number_format($fee['paid_amount'], 2) ?></span>
                            </div>
                            <hr class="text-muted">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold text-dark">Net Payable Dues:</span>
                                <span class="fw-bold text-danger fs-5">₹ <?= number_format($net_payable, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment methods selector -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted text-uppercase mb-3 small" style="letter-spacing:1px;">Choose Payment Channel</h6>
                        
                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="border rounded p-3 d-flex align-items-center bg-white cursor-pointer" style="border-radius:10px; border-color: var(--primary) !important;">
                                <input type="radio" name="gateway" id="pay_razorpay" checked class="form-check-input me-3">
                                <label for="pay_razorpay" class="fw-semibold mb-0 text-dark cursor-pointer">
                                    <i class="fa fa-wallet text-primary me-2"></i> Razorpay Gateway (UPI, Netbanking, Cards)
                                </label>
                            </div>
                            <div class="border rounded p-3 d-flex align-items-center bg-white opacity-50">
                                <input type="radio" name="gateway" id="pay_paytm" disabled class="form-check-input me-3">
                                <label for="pay_paytm" class="fw-semibold mb-0 text-muted">
                                    <i class="fa fa-mobile-screen me-2"></i> Paytm UPI
                                </label>
                            </div>
                        </div>

                        <!-- Razorpay integration simulator -->
                        <div id="payment_trigger_block">
                            <button type="button" id="payBtn" class="btn btn-success w-100 py-3 fw-bold fs-6" style="border-radius: 8px;">
                                <i class="fa fa-shield-check me-2"></i> Pay Securely ₹ <?= number_format($net_payable, 2) ?>
                            </button>
                            <p class="text-muted text-center mt-2 small"><i class="fa fa-lock me-1"></i> Secured 256-bit SSL Gateway</p>
                        </div>

                        <div id="checkout_spinner" class="d-none text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h6 class="fw-bold text-dark">Initializing Secure Payment Window...</h6>
                            <p class="text-muted small">Please do not close this window or hit refresh.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mock Razorpay Checkout Sandbox Form -->
<form id="verifyForm" method="POST" action="verify-payment.php" class="d-none">
    <input type="hidden" name="fee_id" value="<?= $fee_id ?>">
    <input type="hidden" name="student_id" value="<?= $fee['student_id'] ?>">
    <input type="hidden" name="amount" value="<?= $net_payable ?>">
    <input type="hidden" name="gateway" value="Razorpay">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
</form>

<script>
document.getElementById('payBtn').addEventListener('click', function() {
    // Hide payment block and show sandbox verification spinner
    document.getElementById('payment_trigger_block').classList.add('d-none');
    document.getElementById('checkout_spinner').classList.remove('d-none');

    // Simulate sandbox verification response delay (1.5 seconds)
    setTimeout(function() {
        // Generate mock transaction tokens
        const randomTxnId = "pay_sandbox_" + Math.random().toString(36).substring(2, 12).toUpperCase();
        const randomSig = "sig_" + Math.random().toString(36).substring(2, 15);
        
        // Fill sandbox parameters
        document.getElementById('razorpay_payment_id').value = randomTxnId;
        document.getElementById('razorpay_signature').value = randomSig;
        
        // Auto submit verification call
        document.getElementById('verifyForm').submit();
    }, 1800);
});
</script>
<?php
require_once('includes/footer.php');
?>
