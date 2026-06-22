<?php
require_once('../config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure parent is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];
$success = false;
$error_msg = "";

$fee_id = 0;
$student_id = 0;
$amount = 0.0;
$gateway = 'Razorpay';
$razorpay_payment_id = '';
$razorpay_signature = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fee_id = isset($_POST['fee_id']) ? (int)$_POST['fee_id'] : 0;
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $gateway = isset($_POST['gateway']) ? trim($_POST['gateway']) : 'Razorpay';
    $razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? trim($_POST['razorpay_payment_id']) : '';
    $razorpay_signature = isset($_POST['razorpay_signature']) ? trim($_POST['razorpay_signature']) : '';

    if ($fee_id <= 0 || $student_id <= 0 || empty($razorpay_payment_id)) {
        $error_msg = "Invalid transaction parameters.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Verify parent maps to this student and invoice exists
            $stmt_check = $pdo->prepare("
                SELECT fp.*, s.class
                FROM fee_payments fp
                JOIN students s ON fp.student_id = s.id
                JOIN parent_student_map psm ON s.id = psm.student_id
                WHERE fp.id = ? AND psm.parent_id = ? AND s.id = ? AND s.school_id = ?
            ");
            $stmt_check->execute([$fee_id, $parent_id, $student_id, CURRENT_SCHOOL_ID]);
            $fee_invoice = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$fee_invoice) {
                throw new Exception("Unauthorized payment transaction request.");
            }

            // 2. Insert transaction details into fee_transactions
            $stmt_txn = $pdo->prepare("
                INSERT INTO fee_transactions (
                    student_id,
                    fee_id,
                    amount,
                    payment_method,
                    transaction_id,
                    gateway,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, 'success')
            ");
            $stmt_txn->execute([
                $student_id,
                $fee_id,
                $amount,
                'Online',
                $razorpay_payment_id,
                $gateway
            ]);

            // 3. Update the main invoice record in fee_payments
            $new_paid_amount = $fee_invoice['paid_amount'] + $amount;
            $new_due_amount = max(0.0, $fee_invoice['total_fee'] - $new_paid_amount);

            $stmt_update = $pdo->prepare("
                UPDATE fee_payments
                SET paid_amount = ?,
                    due_amount = ?,
                    payment_date = NOW(),
                    payment_mode = ?
                WHERE id = ?
            ");
            $stmt_update->execute([
                $new_paid_amount,
                $new_due_amount,
                $gateway,
                $fee_id
            ]);

            // 4. Update the basic `fees` table by inserting a synchronized record
            $fee_month = "";
            if (preg_match('/\((.*?)\)/', $fee_invoice['fee_type'], $matches)) {
                $fee_month = $matches[1];
            } else {
                $fee_month = $fee_invoice['fee_type'];
            }

            $stmt_fees = $pdo->prepare("
                INSERT INTO fees (
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
                ) VALUES (?, ?, ?, ?, ?, 0.00, 0.00, ?, ?, 'Online', CURDATE(), 'Online Payment via Razorpay Gateway', ?)
            ");
            $stmt_fees->execute([
                $fee_invoice['receipt_no'],
                $student_id,
                $fee_invoice['class'],
                $fee_month,
                $fee_invoice['total_fee'],
                $amount,
                $new_due_amount,
                $new_due_amount <= 0 ? 'Paid' : 'Partial'
            ]);

            // 5. Insert into fee_receipts
            $txn_id = $pdo->lastInsertId();
            $stmt_receipt = $pdo->prepare("
                INSERT INTO fee_receipts (transaction_id, receipt_no)
                VALUES (?, ?)
            ");
            $stmt_receipt->execute([
                $txn_id,
                $fee_invoice['receipt_no']
            ]);

            // 6. Trigger automated notifications via NotificationService
            require_once('../app/Services/NotificationService.php');
            $ns = new NotificationService($pdo);
            $ns->create(
                $parent_id,
                'parent',
                '💳 Fee Payment Received',
                "Dear Parent, ₹" . number_format($amount, 2) . " received successfully. Receipt No: " . $fee_invoice['receipt_no'] . ". Thank You.",
                'fee_payment',
                $fee_id
            );

            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Transaction failed: " . $e->getMessage();
        }
    }
} else {
    $error_msg = "Invalid request method.";
}

$active_menu = "fees";
$page_title = "Verify Online Payment | Parent Portal";
require_once('includes/header.php');
?>

<div class="row text-start justify-content-center">
    <div class="col-xl-6 col-lg-8">
        <div class="card shadow border-0 overflow-hidden" style="border-radius: 16px;">
            <?php if ($success): ?>
                <!-- SUCCESS STATE -->
                <div class="card-header bg-success text-white py-4 text-center">
                    <div class="mb-3">
                        <i class="fa fa-circle-check fa-4x animate__animated animate__bounceIn"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Payment Successful!</h3>
                    <p class="mb-0 text-white-50">Transaction processed securely via Razorpay Sandbox</p>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold">Amount Paid</h6>
                        <h1 class="fw-extrabold text-success mb-0">₹ <?= number_format($amount, 2) ?></h1>
                    </div>
                    
                    <div class="p-3 bg-light rounded" style="border-radius: 12px;">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Transaction ID:</span>
                            <strong class="text-dark fw-mono"><?= htmlspecialchars($razorpay_payment_id) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Payment Channel:</span>
                            <strong class="text-dark"><?= htmlspecialchars($gateway) ?> Online</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Student ID:</span>
                            <strong class="text-dark">#<?= $student_id ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Fee Invoice ID:</span>
                            <strong class="text-dark">#<?= $fee_id ?></strong>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <p class="text-muted small"><i class="fa fa-info-circle me-1"></i> Redirecting you back to the fees dashboard in <span id="countdown">5</span> seconds...</p>
                        <a href="fees.php" class="btn btn-primary px-4 py-2 fw-semibold w-100" style="border-radius: 8px;">
                            <i class="fa fa-wallet me-1"></i> Return to Ledger
                        </a>
                    </div>
                </div>
                
                <script>
                    let timeLeft = 5;
                    const countdownEl = document.getElementById('countdown');
                    const timer = setInterval(() => {
                        timeLeft--;
                        if (countdownEl) {
                            countdownEl.textContent = timeLeft;
                        }
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            window.location.href = "fees.php";
                        }
                    }, 1000);
                </script>

            <?php else: ?>
                <!-- FAILURE / ERROR STATE -->
                <div class="card-header bg-danger text-white py-4 text-center">
                    <div class="mb-3">
                        <i class="fa fa-circle-xmark fa-4x animate__animated animate__shakeX"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Payment Verification Failed</h3>
                    <p class="mb-0 text-white-50">An error occurred during verification checks</p>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="alert alert-danger-subtle border border-danger-subtle text-danger p-3 mb-4 rounded-3" style="border-radius: 12px;">
                        <i class="fa fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error_msg ?: 'Unknown error has occurred.') ?>
                    </div>
                    
                    <p class="text-muted small mb-4">Please contact administrative helpdesk if your account was debited but payment status is still pending.</p>
                    
                    <div class="d-flex gap-2">
                        <a href="pay-fee.php?fee_id=<?= $fee_id ?>" class="btn btn-outline-danger flex-fill py-2 fw-semibold" style="border-radius: 8px;">
                            <i class="fa fa-arrow-rotate-left me-1"></i> Retry Payment
                        </a>
                        <a href="fees.php" class="btn btn-secondary flex-fill py-2 fw-semibold" style="border-radius: 8px;">
                            <i class="fa fa-wallet me-1"></i> Back to Ledger
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
