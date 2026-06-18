<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$success_msg = isset($_GET['success']) ? 'Subscription activated successfully!' : '';

// Fetch invoice details
$invoice = null;
try {
    $stmt = $pdo->prepare("
        SELECT i.*, s.plan_name, s.start_date, s.end_date, sch.school_name, sch.domain
        FROM invoices i
        LEFT JOIN subscriptions s ON i.subscription_id = s.id
        LEFT JOIN schools sch ON i.school_id = sch.id
        WHERE i.id = ? AND i.school_id = ?
    ");
    $stmt->execute([$invoice_id, CURRENT_SCHOOL_ID]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore
}

if (!$invoice) {
    header("Location: subscription-plans.php");
    exit;
}

$page_title = "Commercial Invoice | VIC ERP";
$page_header = "Commercial Invoice Details";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-check me-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Commercial SaaS Invoice</h2>
        <div>
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fa fa-print me-1"></i> Print Invoice
            </button>
            <a href="subscription-plans.php" class="btn btn-outline-secondary">
                Back to Plans
            </a>
        </div>
    </div>

    <!-- Printable Invoice Template -->
    <div class="card border-0 shadow-sm p-5 text-start" id="printable-area" style="border-radius: 15px;">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h3 class="fw-bold text-primary mb-1">VIC CLOUD ERP</h3>
                <p class="text-muted small mb-0">Licensing and Subscription division</p>
                <p class="text-muted small">info@vicschool.edu.in</p>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h4 class="fw-bold text-dark">INVOICE</h4>
                <div class="text-muted font-monospace small">#<?= htmlspecialchars($invoice['invoice_no']) ?></div>
                <div class="text-muted small mt-1">Date: <?= date('d M Y', strtotime($invoice['created_at'])) ?></div>
            </div>
        </div>

        <hr class="text-muted">

        <div class="row my-4">
            <div class="col-sm-6">
                <h6 class="text-muted fw-bold">BILLED TO:</h6>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['school_name']) ?></h5>
                <p class="text-muted small mb-0">Associated Domain: <?= htmlspecialchars($invoice['domain']) ?></p>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h6 class="text-muted fw-bold">PAYMENT STATUS:</h6>
                <span class="badge bg-success-subtle text-success fs-6 py-2 px-3"><i class="fa fa-circle-check me-1"></i> PAID</span>
            </div>
        </div>

        <table class="table table-striped align-middle my-4">
            <thead>
                <tr class="table-light">
                    <th>Plan Name</th>
                    <th>Billing Cycle Validity</th>
                    <th class="text-end">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong class="text-dark"><?= htmlspecialchars($invoice['plan_name']) ?></strong>
                        <div class="text-muted small">Includes multi-school tenant database domain isolation, smart AI financial analytics layer, and student failure forecasts.</div>
                    </td>
                    <td>
                        <?= date('d M Y', strtotime($invoice['start_date'])) ?> to <?= date('d M Y', strtotime($invoice['end_date'])) ?>
                    </td>
                    <td class="text-end fw-semibold">₹ <?= number_format($invoice['amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="row justify-content-end mt-4">
            <div class="col-sm-5 text-sm-end">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal:</span>
                    <strong class="text-dark">₹ <?= number_format($invoice['amount'], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Taxes (0%):</span>
                    <strong class="text-dark">₹ 0.00</strong>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-dark fs-5">Total Paid:</span>
                    <strong class="text-primary fs-5">₹ <?= number_format($invoice['amount'], 2) ?></strong>
                </div>
            </div>
        </div>

        <div class="mt-5 text-center text-muted small">
            Thank you for subscribing to VIC Cloud ERP licensing. This is a computer generated commercial invoice.
        </div>
    </div>
</div>

<!-- Special CSS styling for printing -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-area, #printable-area * {
        visibility: visible;
    }
    #printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
