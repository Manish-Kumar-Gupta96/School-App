<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
$success = '';
$error = '';

// Fetch requested plan
$plan = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading plan: ' . $e->getMessage();
}

if (!$plan) {
    header("Location: subscription-plans.php");
    exit;
}

// Handle checkout / activation submission
if (isset($_POST['confirm_payment'])) {
    try {
        $pdo->beginTransaction();

        // Expire current active subscriptions for this school
        $stmt_exp = $pdo->prepare("UPDATE subscriptions SET status = 'EXPIRED' WHERE school_id = ? AND status = 'ACTIVE'");
        $stmt_exp->execute([CURRENT_SCHOOL_ID]);

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+" . $plan['duration_days'] . " days"));

        // Insert new active subscription
        $stmt_sub = $pdo->prepare("
            INSERT INTO subscriptions (school_id, plan_name, amount, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, 'ACTIVE')
        ");
        $stmt_sub->execute([CURRENT_SCHOOL_ID, $plan['plan_name'], $plan['price'], $start_date, $end_date]);
        $sub_id = $pdo->lastInsertId();

        // Generate Invoice Number
        $invoice_no = "INV-" . CURRENT_SCHOOL_ID . "-" . time();

        // Insert Invoice
        $stmt_inv = $pdo->prepare("
            INSERT INTO invoices (school_id, subscription_id, invoice_no, amount, status)
            VALUES (?, ?, ?, ?, 'PAID')
        ");
        $stmt_inv->execute([CURRENT_SCHOOL_ID, $sub_id, $invoice_no, $plan['price']]);
        $invoice_id = $pdo->lastInsertId();

        // Save layout configurations or properties inside school table to sync
        $stmt_sch = $pdo->prepare("UPDATE schools SET subscription_plan = ? WHERE id = ?");
        $stmt_sch->execute([$plan['plan_name'], CURRENT_SCHOOL_ID]);

        $pdo->commit();
        header("Location: generate-invoice.php?invoice_id=" . $invoice_id . "&success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Failed to activate plan: ' . $e->getMessage();
    }
}

$page_title = "Checkout Licensing | VIC ERP";
$page_header = "Activate SaaS Plan & Checkout";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Confirm Checkout Subscription</h2>
        <a href="subscription-plans.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Plans
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-wallet text-success me-2"></i>Razorpay Gateway Checkout Integration</h5>
                <hr class="text-muted mt-0 mb-4">
                
                <div class="p-3 bg-light rounded mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Selected Plan:</span>
                        <strong class="text-dark"><?= htmlspecialchars($plan['plan_name']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">License Duration:</span>
                        <strong class="text-dark"><?= $plan['duration_days'] ?> Days</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fw-bold">Total Amount Due:</span>
                        <strong class="text-primary fs-5">₹ <?= number_format($plan['price'], 2) ?></strong>
                    </div>
                </div>

                <form method="POST" action="">
                    <p class="text-muted small mb-4">Note: Clicking "Confirm Payment Simulation" triggers an active subscription token mapping bypass in the tenant schema and generates a paid commercial invoice automatically.</p>
                    <button type="submit" name="confirm_payment" class="btn btn-success w-100 py-3 fw-bold fs-6" style="border-radius: 10px;">
                        <i class="fa fa-circle-check me-2"></i> Confirm Payment Simulation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
