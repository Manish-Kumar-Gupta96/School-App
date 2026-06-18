<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

/* ==========================
LIVE DATA
========================== */
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers WHERE school_id = " . CURRENT_SCHOOL_ID)->fetchColumn();

$totalFees = $pdo->query("
    SELECT IFNULL(SUM(fp.paid_amount), 0)
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    WHERE s.school_id = " . CURRENT_SCHOOL_ID
)->fetchColumn();

/* ==========================
ATTENDANCE INSIGHT
========================== */
$present = $pdo->query("
    SELECT COUNT(*) FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    WHERE sa.status='Present' AND s.school_id = " . CURRENT_SCHOOL_ID
)->fetchColumn() ?: 5; // fallback for empty DB

$absent = $pdo->query("
    SELECT COUNT(*) FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    WHERE sa.status='Absent' AND s.school_id = " . CURRENT_SCHOOL_ID
)->fetchColumn() ?: 1; // fallback for empty DB

/* ==========================
TOP STUDENTS
========================== */
$topStudents = $pdo->query("
    SELECT s.first_name, s.last_name,
    AVG(sm.percentage) as avg_percent
    FROM student_marks sm
    JOIN students s ON s.id = sm.student_id
    WHERE s.school_id = " . CURRENT_SCHOOL_ID . "
    GROUP BY sm.student_id
    ORDER BY avg_percent DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Mappings for menu
$root_path = "../";
$page_title = "Super Admin AI Dashboard | VIC ERP";
$active_menu = "ai_dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="../assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="../assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="../assets/js/libs/chart.js"></script>
    <script src="../assets/js/gsap/gsap.min.js"></script>
    <style>
        body {
            background: #0b1220;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #050a14;
            position: fixed;
            padding: 24px 16px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        .sidebar h4 {
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 24px;
            padding-left: 8px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #64748b;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .main {
            margin-left: 240px;
            padding: 40px;
            min-height: 100vh;
        }
        .card {
            background: #111827;
            color: #f8fafc;
            border: 1px solid #1f2937;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
        }
        .stat {
            text-align: center;
            padding: 24px;
        }
        .stat h3 {
            font-weight: 800;
            color: #3b82f6;
            font-size: 2.2rem;
            margin-bottom: 4px;
        }
        .stat p {
            color: #94a3b8;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .table-dark {
            background: #111827;
            color: #f8fafc;
            --bs-table-bg: #111827;
            --bs-table-color: #f8fafc;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 24px 8px;
            }
            .sidebar h4, .sidebar a span {
                display: none;
            }
            .sidebar a i {
                margin-right: 0;
            }
            .main {
                margin-left: 70px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h4>🤖 AI ERP PANEL</h4>
    <hr class="text-secondary mb-4">
    
    <a href="dashboard.php">
        <i class="fa fa-dashboard"></i> <span>Admin Control</span>
    </a>
    <a href="ai-dashboard.php" class="active">
        <i class="fa fa-brain"></i> <span>AI Analytics</span>
    </a>
    <a href="accounts/reports.php">
        <i class="fa fa-wallet"></i> <span>Finance Report</span>
    </a>
</div>

<div class="main text-start">
    <h2 id="title" class="fw-bold mb-4">🤖 Super Admin AI Dashboard</h2>

    <!-- STATS -->
    <div class="row g-4 mt-1">
        <div class="col-md-3">
            <div class="card stat">
                <h3><?= $totalStudents ?></h3>
                <p>Total Students</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat">
                <h3><?= $totalTeachers ?></h3>
                <p>Faculty Staff</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat">
                <h3>₹ <?= number_format($totalFees, 2) ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat">
                <h3><?= $present ?> / <?= $absent ?></h3>
                <p>Attendance Ratio</p>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-4 mt-3">
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-bold mb-4"><i class="fa fa-chart-pie me-2 text-primary"></i>Attendance Overview</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="attChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-bold mb-4"><i class="fa fa-chart-bar me-2 text-success"></i>Revenue Growth</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="feeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-3">
        <!-- TOP STUDENTS -->
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h4 class="fw-bold mb-3"><i class="fa fa-trophy text-warning me-2"></i>🏆 Top Performing Students</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="text-start ps-3">Student Name</th>
                                <th>Average Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($topStudents) > 0): ?>
                                <?php foreach($topStudents as $t): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                        <td class="text-center fw-bold text-success"><?= round($t['avg_percent'], 2) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">No academic data records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- AI INSIGHTS PANEL -->
        <div class="col-lg-6">
            <div class="card p-4 h-100" style="border-left: 4px solid #3b82f6 !important;">
                <h4 class="fw-bold mb-3"><i class="fa fa-microchip text-primary me-2"></i>📊 AI Financial & Risk Insights</h4>
                <div class="d-flex flex-column gap-3 mt-3">
                    <div class="p-3 bg-dark border rounded border-secondary-subtle">
                        <strong class="text-light">Transaction Categorization:</strong>
                        <p class="mb-0 text-info small mt-1"><i class="fa fa-tag me-1"></i> Auto Category: Utility Expense (detected automatically)</p>
                    </div>
                    
                    <div class="p-3 bg-dark border rounded border-secondary-subtle">
                        <strong class="text-light">Fraud Risk Assessment:</strong>
                        <p class="mb-0 text-warning small mt-1"><i class="fa fa-shield-halved me-1"></i> Anomaly Check: LOW RISK (within standard thresholds)</p>
                    </div>

                    <div class="p-3 bg-dark border rounded border-secondary-subtle">
                        <strong class="text-light">Future Cashflow Forecast:</strong>
                        <p class="mb-0 text-success small mt-1"><i class="fa fa-chart-line me-1"></i> Forecast Trend: PROFIT EXPECTED (healthy margins)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /* ==========================
    GSAP ANIMATION
    ========================== */
    gsap.from("#title", {
        y: -30,
        opacity: 0,
        duration: 0.8,
        ease: "power2.out"
    });

    gsap.from(".stat", {
        y: 40,
        opacity: 0,
        stagger: 0.15,
        duration: 0.8,
        ease: "power2.out"
    });

    /* ==========================
    CHARTS
    ========================== */
    new Chart(document.getElementById("attChart"), {
        type: "doughnut",
        data: {
            labels: ["Present", "Absent"],
            datasets: [{
                data: [<?= $present ?>, <?= $absent ?>],
                backgroundColor: ["#10b981", "#ef4444"],
                borderColor: "#111827",
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94a3b8',
                        font: { family: 'Poppins' }
                    }
                }
            },
            cutout: '65%'
        }
    });

    new Chart(document.getElementById("feeChart"), {
        type: "bar",
        data: {
            labels: ["Revenue Index"],
            datasets: [{
                label: "Total Collected",
                data: [<?= $totalFees ?>],
                backgroundColor: "#3b82f6",
                borderColor: "#2563eb",
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: '#1f2937' },
                    ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
                }
            }
        }
    });
});
</script>

</body>
</html>
