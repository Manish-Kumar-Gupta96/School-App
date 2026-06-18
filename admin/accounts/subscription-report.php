<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$error = '';

// Calculate SaaS Billing Statistics
$total_saas_revenue = 0;
$active_licensing = 0;
$expired_licensing = 0;

try {
    // Total SaaS Revenue
    $total_saas_revenue = (float)$pdo->query("SELECT SUM(amount) FROM invoices WHERE status = 'PAID'")->fetchColumn();

    // Active & Expired Counts
    $active_licensing = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'ACTIVE'")->fetchColumn();
    $expired_licensing = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'EXPIRED'")->fetchColumn();
} catch (PDOException $e) {
    $error = 'Error calculating SaaS stats: ' . $e->getMessage();
}

// Fetch all SaaS invoices
$invoices = [];
try {
    $invoices = $pdo->query("
        SELECT i.*, s.school_name, sub.plan_name, sub.start_date, sub.end_date
        FROM invoices i
        LEFT JOIN schools s ON i.school_id = s.id
        LEFT JOIN subscriptions sub ON i.subscription_id = sub.id
        ORDER BY i.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching invoices: ' . $e->getMessage();
}

$page_title = "Monetization Reports | VIC ERP";
$page_header = "SaaS Licensing & Monetization Report";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">SaaS Licensing Analytics & Reports</h2>
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

    <!-- Overview Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total SaaS Revenue</h6>
                <h3 class="fw-bold text-success mb-0">₹ <?= number_format($total_saas_revenue, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Platform-wide premium earnings</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Active Subscriptions</h6>
                <h3 class="fw-bold text-primary mb-0"><?= $active_licensing ?> Schools</h3>
                <p class="text-muted small mb-0 mt-1">Schools holding active licenses</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Expired Licenses</h6>
                <h3 class="fw-bold text-danger mb-0"><?= $expired_licensing ?> Schools</h3>
                <p class="text-muted small mb-0 mt-1">Schools requiring updates</p>
            </div>
        </div>
    </div>

    <!-- Commercial Invoices Ledger -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-receipt me-2 text-primary"></i>Global SaaS Invoices Ledger</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Invoice No</th>
                        <th>School Client</th>
                        <th>Subscription Plan</th>
                        <th>Billing Cycle</th>
                        <th class="pe-4 text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($invoices) > 0): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td class="ps-4 font-monospace text-muted">#<?= htmlspecialchars($inv['invoice_no']) ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($inv['school_name'] ?: 'External School') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($inv['plan_name']) ?></span></td>
                                <td class="small text-muted">
                                    <?= date('d M Y', strtotime($inv['start_date'])) ?> to <?= date('d M Y', strtotime($inv['end_date'])) ?>
                                </td>
                                <td class="pe-4 text-end fw-bold text-success">
                                    ₹ <?= number_format($inv['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No SaaS subscription invoice records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
