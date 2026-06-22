<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

// Calculate calculations
$total_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$new_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'New Lead' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$contacted_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Contacted' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$interested_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Interested' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$scheduled_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Visit Scheduled' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$confirmed_leads = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Admission Confirmed' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();

// Conversion Rate Calculation
$conversion_rate = $total_leads > 0 ? round(($confirmed_leads / $total_leads) * 100, 1) : 0;

// Leads by source
$sources = ['Website', 'Chatbot', 'Admission Portal', 'Manual'];
$source_counts = [];
foreach ($sources as $src) {
    $source_counts[$src] = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE source = '$src' AND school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
}

// Monthly lead collection trend (last 6 months)
$monthly_trend = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_name, COUNT(*) as count 
    FROM crm_leads 
    WHERE school_id = " . CURRENT_SCHOOL_ID . " 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY MIN(created_at) ASC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$months_array = [];
$counts_array = [];
foreach ($monthly_trend as $mt) {
    $months_array[] = $mt['month_name'];
    $counts_array[] = (int)$mt['count'];
}

$page_title = "CRM Conversion Analytics | VIC ERP";
$page_header = "CRM Conversion Analytics";
$active_menu = "crm";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<!-- GSAP and ChartJS Local Libraries -->
<script src="<?= $root_path ?>assets/js/gsap/gsap.min.js"></script>
<script src="<?= $root_path ?>assets/js/libs/chart.js"></script>

<div class="main-dashboard p-4">
    <!-- Back Navigation -->
    <div class="mb-3 text-start">
        <a href="leads.php" class="btn btn-sm btn-light"><i class="fa fa-arrow-left me-1"></i> Back to Directory</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 text-start">
        <div>
            <h2 class="fw-bold text-dark mb-1">CRM Analytics</h2>
            <p class="text-muted small mb-0">Track candidate conversion velocity, campaign sources, and team performance metrics</p>
        </div>
    </div>

    <!-- Stats summary rows -->
    <div class="row g-4 mb-4 text-start">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; border-left: 5px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase fw-bold">Conversion Rate</span>
                    <i class="fa fa-chart-line text-success fs-4"></i>
                </div>
                <h2 class="fw-bold mb-0 text-dark"><?= $conversion_rate ?>%</h2>
                <p class="text-muted small mb-0 mt-1">Leads converted into confirmed admissions</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; border-left: 5px solid #0d6efd !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase fw-bold">Total Inquiries</span>
                    <i class="fa fa-users text-primary fs-4"></i>
                </div>
                <h2 class="fw-bold mb-0 text-dark"><?= $total_leads ?></h2>
                <p class="text-muted small mb-0 mt-1">Aggregate CRM candidates logged</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; border-left: 5px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase fw-bold">Confirmed Admissions</span>
                    <i class="fa fa-circle-check text-success fs-4"></i>
                </div>
                <h2 class="fw-bold mb-0 text-dark"><?= $confirmed_leads ?></h2>
                <p class="text-muted small mb-0 mt-1">Leads that reached final confirmed stage</p>
            </div>
        </div>
    </div>

    <div class="row g-4 text-start">
        <!-- Funnel Progression Visualizer -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 15px;">
                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-filter me-2 text-primary"></i>Admissions Conversion Funnel</h5>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark small">New Leads</span>
                        <span class="text-muted small fw-bold"><?= $new_leads ?> (100%)</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 20px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%; border-radius: 20px;"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <?php 
                        $contact_pct = $total_leads > 0 ? round(($contacted_leads / $total_leads) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark small">Contacted</span>
                        <span class="text-muted small fw-bold"><?= $contacted_leads ?> (<?= $contact_pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 20px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $contact_pct ?>%; border-radius: 20px;"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <?php 
                        $interest_pct = $total_leads > 0 ? round(($interested_leads / $total_leads) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark small">Interested Candidates</span>
                        <span class="text-muted small fw-bold"><?= $interested_leads ?> (<?= $interest_pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 20px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $interest_pct ?>%; border-radius: 20px;"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <?php 
                        $sched_pct = $total_leads > 0 ? round(($scheduled_leads / $total_leads) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark small">School Visit Scheduled</span>
                        <span class="text-muted small fw-bold"><?= $scheduled_leads ?> (<?= $sched_pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 20px;">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: <?= $sched_pct ?>%; border-radius: 20px;"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <?php 
                        $confirm_pct = $total_leads > 0 ? round(($confirmed_leads / $total_leads) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark small">Admission Confirmed</span>
                        <span class="text-muted small fw-bold"><?= $confirmed_leads ?> (<?= $confirm_pct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $confirm_pct ?>%; border-radius: 20px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads By Source Distributions -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 15px;">
                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-chart-pie me-2 text-primary"></i>Acquisition Channels</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center text-start">
                        <thead class="table-light">
                            <tr>
                                <th>Source</th>
                                <th>Lead Count</th>
                                <th>Share (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($source_counts as $src => $count): 
                                $pct = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td class="fw-bold text-dark text-start"><?= $src ?></td>
                                    <td><?= $count ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                            <div class="progress w-50" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                            <span class="small fw-semibold"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trend Line Graph -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-chart-area me-2 text-primary"></i>Monthly Enrollment Funnel Trend</h5>
                <div style="height: 350px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
    
    const months = <?= json_encode($months_array) ?>;
    const counts = <?= json_encode($counts_array) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months.length > 0 ? months : ['No Data'],
            datasets: [{
                label: 'Inquiries Captured',
                data: counts.length > 0 ? counts : [0],
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 3,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 5,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
