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

// Fetch subscription plans
$plans = [];
try {
    $plans = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading subscription plans: ' . $e->getMessage();
}

// Fetch active subscription for the current school tenant
$active_sub = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE school_id = ? AND status = 'ACTIVE' ORDER BY id DESC LIMIT 1");
    $stmt->execute([CURRENT_SCHOOL_ID]);
    $active_sub = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silent
}

$page_title = "SaaS Subscriptions | VIC ERP";
$page_header = "SaaS Cloud ERP Billing Plans";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">SaaS Monetization & Licensing</h2>
        <a href="subscription-report.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Billing Reports
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

    <!-- Active Subscription details -->
    <div class="card border-0 shadow-sm p-4 mb-4 text-start" style="border-radius: 15px; background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-success mb-2">CURRENT ACTIVE PLAN</span>
                <?php if ($active_sub): ?>
                    <h3 class="fw-bold text-white mb-1"><?= htmlspecialchars($active_sub['plan_name']) ?></h3>
                    <p class="text-white-50 mb-0">
                        License cost: <strong class="text-white">₹ <?= number_format($active_sub['amount'], 2) ?></strong> | 
                        Valid from <strong class="text-white"><?= date('d M Y', strtotime($active_sub['start_date'])) ?></strong> 
                        to <strong class="text-white"><?= date('d M Y', strtotime($active_sub['end_date'])) ?></strong>
                    </p>
                <?php else: ?>
                    <h3 class="fw-bold text-white mb-1">Free Trial / No Active Plan</h3>
                    <p class="text-white-50 mb-0">Select a premium plan below to activate full enterprise access rights.</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <i class="fa fa-shield-halved fs-1 text-success opacity-75"></i>
            </div>
        </div>
    </div>

    <!-- Plans comparison grid -->
    <h4 class="fw-bold text-dark text-start mb-4">Choose Your Licensing Plan</h4>
    <div class="row g-4">
        <?php foreach ($plans as $plan): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-4 card-hover" style="border-radius: 15px; transition: transform 0.2s;">
                    <div class="card-body p-0">
                        <i class="fa fa-gem fs-2 text-primary mb-3"></i>
                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($plan['plan_name']) ?></h4>
                        <p class="text-muted small">Subscription duration: <?= $plan['duration_days'] ?> Days</p>
                        
                        <div class="my-4">
                            <span class="fs-1 fw-bold text-dark">₹ <?= number_format($plan['price'], 2) ?></span>
                        </div>

                        <hr class="text-muted">
                        
                        <ul class="list-unstyled text-start mb-4 fs-6">
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Real-time AI Dashboards</li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Attendance & Marks Tracker</li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Financial Ledger & Salary Module</li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Domain Tenancy Isolation</li>
                            <li class="mb-2"><i class="fa fa-check text-success me-2"></i> 24/7 Security & Support</li>
                        </ul>

                        <a href="activate-subscription.php?plan_id=<?= $plan['id'] ?>" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 10px;">
                            Activate Plan Now
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
