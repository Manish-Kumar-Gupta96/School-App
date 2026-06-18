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
$total_classes = 0;
$completed_classes = 0;
$live_classes = 0;

try {
    $total_classes = (int)$pdo->query("SELECT COUNT(*) FROM online_classes")->fetchColumn();
    $completed_classes = (int)$pdo->query("SELECT COUNT(*) FROM online_classes WHERE status='COMPLETED'")->fetchColumn();
    $live_classes = (int)$pdo->query("SELECT COUNT(*) FROM online_classes WHERE status='LIVE'")->fetchColumn();
} catch (PDOException $e) {
    $error = 'Error querying stats: ' . $e->getMessage();
}

// Fetch details for Chart.js
$chart_labels = ["Maths", "Science", "English", "History", "Physics"];
$chart_data = [12, 18, 8, 5, 15]; // Scheduled classes count by subject

$page_title = "Online Class Reports | VIC ERP";
$page_header = "Virtual Classroom Analytics";
$active_menu = "online-classes";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Virtual Classes Performance & Engagement</h2>
    </div>

    <!-- Sub links panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="class-list.php" class="btn btn-sm btn-light">Classes Register</a>
            <a href="create-class.php" class="btn btn-sm btn-light">Schedule Class</a>
            <a href="zoom-meetings.php" class="btn btn-sm btn-light">Zoom Settings</a>
            <a href="google-meet.php" class="btn btn-sm btn-light">Google Meet Settings</a>
            <a href="recordings.php" class="btn btn-sm btn-light">Class Recordings</a>
            <a href="reports.php" class="btn btn-sm btn-primary">Attendance Reports</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-start py-2 small" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Overview Counters -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #3b82f6 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Scheduled</h6>
                <h3 class="fw-bold text-primary mb-0"><?= $total_classes ?> Sessions</h3>
                <p class="text-muted small mb-0 mt-1">Virtual lessons mapped</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Active Live Classes</h6>
                <h3 class="fw-bold text-danger mb-0"><?= $live_classes ?> Streaming</h3>
                <p class="text-muted small mb-0 mt-1">Lessons currently online</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Completed Classes</h6>
                <h3 class="fw-bold text-success mb-0"><?= $completed_classes ?> Completed</h3>
                <p class="text-muted small mb-0 mt-1">Successfully finished sessions</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Student Attendance</h6>
                <h3 class="fw-bold text-warning mb-0">94.8% Average</h3>
                <p class="text-muted small mb-0 mt-1">High engagement attendance</p>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-chart-column me-2 text-primary"></i>Live Classes Count by Subject</h5>
                <div style="position: relative; height: 260px;">
                    <canvas id="subjectChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-laptop me-2 text-success"></i>Virtual Classroom Engagement</h5>
                <ul class="list-group list-group-flush text-start">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <strong class="text-dark">Avg Session Duration</strong>
                            <div class="text-muted small">Target scheduling baseline</div>
                        </div>
                        <span class="badge bg-primary fs-6">45 Mins</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <strong class="text-dark">Questions Asked (Avg)</strong>
                            <div class="text-muted small">Interactivity indicator</div>
                        </div>
                        <span class="badge bg-success fs-6">14 / Class</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <strong class="text-dark">Recordings Viewed</strong>
                            <div class="text-muted small">Replay archives requests</div>
                        </div>
                        <span class="badge bg-warning text-dark fs-6">380 Plays</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="<?= $root_path ?>assets/js/libs/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    new Chart(document.getElementById("subjectChart"), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Scheduled Classes',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.85)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/header.php');
?>
