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
$total_chat_count = 0;
$total_leads_count = 0;
$total_faqs_count = 0;

try {
    $total_chat_count = (int)$pdo->query("SELECT COUNT(*) FROM ai_chat_history WHERE role='user'")->fetchColumn();
    $total_leads_count = (int)$pdo->query("SELECT COUNT(*) FROM admission_leads")->fetchColumn();
    $total_faqs_count = (int)$pdo->query("SELECT COUNT(*) FROM faq_questions")->fetchColumn();
} catch (PDOException $e) {
    $error = 'Database load error: ' . $e->getMessage();
}

$page_title = "AI Chatbots Analytics | VIC ERP";
$page_header = "AI Analytics & Audit Dashboard";
$active_menu = "ai-dashboard";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">AI Chatbot Performance Analytics</h2>
    </div>

    <!-- Quick sub-menu tabs -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="analytics.php" class="btn btn-sm btn-primary">Dashboard Analytics</a>
            <a href="chat-history.php" class="btn btn-sm btn-light">Conversation History</a>
            <a href="faq-manager.php" class="btn btn-sm btn-light">FAQ Database</a>
            <a href="admission-leads.php" class="btn btn-sm btn-light">Admission Leads</a>
            <a href="ai-settings.php" class="btn btn-sm btn-light">AI Configuration Settings</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-start py-2 small" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #3b82f6 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Chat Inquiries</h6>
                <h3 class="fw-bold text-primary mb-0"><?= $total_chat_count ?> Queries</h3>
                <p class="text-muted small mb-0 mt-1">Total questions asked by users</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Admission Leads Captured</h6>
                <h3 class="fw-bold text-success mb-0"><?= $total_leads_count ?> Callback Requests</h3>
                <p class="text-muted small mb-0 mt-1">Highly relevant leads gathered</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">FAQ Matching Repository</h6>
                <h3 class="fw-bold text-warning mb-0"><?= $total_faqs_count ?> FAQ Entries</h3>
                <p class="text-muted small mb-0 mt-1">Configured local matching keys</p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Daily Chat counts (line chart) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-chart-line me-2 text-primary"></i>Daily Conversations Volume</h5>
                <div style="position: relative; height: 250px;">
                    <canvas id="volumeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Satisfaction rating (pie/doughnut chart) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-smile-beam me-2 text-success"></i>User Satisfaction Ratio</h5>
                <div style="position: relative; height: 250px;">
                    <canvas id="satisfactionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $root_path ?>assets/js/libs/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Volume Chart
    new Chart(document.getElementById("volumeChart"), {
        type: 'line',
        data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [{
                label: 'Inquiries Handled',
                data: [12, 19, 15, 24, 30, 8, 14],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Satisfaction Chart
    new Chart(document.getElementById("satisfactionChart"), {
        type: 'doughnut',
        data: {
            labels: ["Highly Satisfied", "Neutral", "Unsatisfied"],
            datasets: [{
                data: [82, 13, 5],
                backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/header.php');
?>
