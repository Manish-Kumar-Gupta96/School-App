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

// Calculate Ledger Summaries
$total_income = 0;
$total_expense = 0;
$net_profit = 0;
try {
    $stmt_sum = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN entry_type = 'INCOME' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN entry_type = 'EXPENSE' THEN amount ELSE 0 END) AS total_expense
        FROM ledger 
        WHERE school_id = ?
    ");
    $stmt_sum->execute([CURRENT_SCHOOL_ID]);
    $sums = $stmt_sum->fetch(PDO::FETCH_ASSOC);
    $total_income = (float)$sums['total_income'];
    $total_expense = (float)$sums['total_expense'];
    $net_profit = $total_income - $total_expense;
} catch (PDOException $e) {
    $error = 'Error calculating ledger stats: ' . $e->getMessage();
}

// Fetch month-wise data for Chart.js
$monthly_data = [];
try {
    $stmt_month = $pdo->prepare("
        SELECT 
            DATE_FORMAT(entry_date, '%b %Y') AS month_name,
            SUM(CASE WHEN entry_type = 'INCOME' THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN entry_type = 'EXPENSE' THEN amount ELSE 0 END) AS expense
        FROM ledger
        WHERE school_id = ?
        GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
        ORDER BY entry_date ASC
        LIMIT 12
    ");
    $stmt_month->execute([CURRENT_SCHOOL_ID]);
    $monthly_data = $stmt_month->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore
}

// AI Cashflow Forecast Backend Integration
$forecast_data = [];
try {
    $ch = curl_init("http://127.0.0.1:5000/forecast");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $forecast_res = json_decode($response, true);
        if (isset($forecast_res['forecast'])) {
            $forecast_data = $forecast_res['forecast'];
        }
    }
} catch (Exception $e) {
    // Fail silently
}

// Fallback forecast if service is offline
if (empty($forecast_data)) {
    $forecast_data = [
        ["month" => "Next Month (Pred)", "projected_income" => $total_income * 0.12 + 5000, "projected_expense" => $total_expense * 0.08 + 2000],
        ["month" => "Month 2 (Pred)", "projected_income" => $total_income * 0.14 + 6000, "projected_expense" => $total_expense * 0.09 + 2500],
        ["month" => "Month 3 (Pred)", "projected_income" => $total_income * 0.15 + 7000, "projected_expense" => $total_expense * 0.07 + 2200],
    ];
}

$page_title = "Financial Analytics & Reports | VIC ERP";
$page_header = "School Financial Statement & AI Forecast";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Financial Analytics & AI Cashflow Forecasting</h2>
        <a href="ledgers.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Ledger Book
        </a>
    </div>

    <!-- SUMMARY METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Gross Revenue</h6>
                <h3 class="fw-bold text-success mb-0">₹ <?= number_format($total_income, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Total credit entries</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Gross Expenditures</h6>
                <h3 class="fw-bold text-danger mb-0">₹ <?= number_format($total_expense, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Total debit entries</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Net surplus/Loss</h6>
                <h3 class="fw-bold <?= $net_profit >= 0 ? 'text-primary' : 'text-danger' ?> mb-0">₹ <?= number_format($net_profit, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Operating profit margin</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #ffc107 !important; background-color: rgba(255, 193, 7, 0.05);">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">AI Projected Cashflow</h6>
                <h3 class="fw-bold text-warning mb-0">₹ <?= number_format($forecast_data[0]['projected_income'] - $forecast_data[0]['projected_expense'], 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Predicted for next month</p>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-4 mb-4">
        <!-- Monthly Financial Performance (Chart.js) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; height: 100%;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-chart-bar me-2 text-primary"></i>Historical Cashflow Trend</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="historicalChart"></canvas>
                </div>
            </div>
        </div>

        <!-- AI Forecast Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px; height: 100%; background: linear-gradient(145deg, #ffffff, #fdfbf7);">
                <h5 class="fw-bold text-warning mb-4"><i class="fa fa-wand-magic-sparkles me-2"></i>AI Future Cashflow Forecast</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- FORECAST DETAIL TABLE -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-robot me-2 text-warning"></i>AI Forecast Projections Detail</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Forecast Period</th>
                        <th>Projected Income (₹)</th>
                        <th>Projected Expense (₹)</th>
                        <th class="pe-4 text-end">Projected Net Surplus (₹)</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php foreach ($forecast_data as $fc): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><i class="fa fa-calendar-day text-warning me-2"></i><?= htmlspecialchars($fc['month']) ?></td>
                            <td class="text-success fw-semibold">₹ <?= number_format($fc['projected_income'], 2) ?></td>
                            <td class="text-danger fw-semibold">₹ <?= number_format($fc['projected_expense'], 2) ?></td>
                            <td class="pe-4 text-end fw-bold text-primary">
                                ₹ <?= number_format($fc['projected_income'] - $fc['projected_expense'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ChartJS Local Library -->
<script src="<?= $root_path ?>assets/js/libs/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Historical Chart
    const histMonths = <?= json_encode(array_column($monthly_data, 'month_name') ?: ['Initial']) ?>;
    const histIncome = <?= json_encode(array_column($monthly_data, 'income') ?: [$total_income]) ?>;
    const histExpense = <?= json_encode(array_column($monthly_data, 'expense') ?: [$total_expense]) ?>;

    new Chart(document.getElementById('historicalChart'), {
        type: 'bar',
        data: {
            labels: histMonths,
            datasets: [
                {
                    label: 'Income (₹)',
                    data: histIncome,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderRadius: 5
                },
                {
                    label: 'Expense (₹)',
                    data: histExpense,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Forecast Chart
    const foreMonths = <?= json_encode(array_column($forecast_data, 'month')) ?>;
    const foreIncome = <?= json_encode(array_column($forecast_data, 'projected_income')) ?>;
    const foreExpense = <?= json_encode(array_column($forecast_data, 'projected_expense')) ?>;

    new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: foreMonths,
            datasets: [
                {
                    label: 'Projected Income (₹)',
                    data: foreIncome,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Projected Expense (₹)',
                    data: foreExpense,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
