<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch Metrics
$stmt_active_visitors = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE school_id = ? AND checkout_time IS NULL");
$stmt_active_visitors->execute([CURRENT_SCHOOL_ID]);
$active_visitors = $stmt_active_visitors->fetchColumn();

$stmt_students_out = $pdo->prepare("SELECT COUNT(*) FROM student_exit_passes WHERE school_id = ? AND return_time IS NULL");
$stmt_students_out->execute([CURRENT_SCHOOL_ID]);
$students_out = $stmt_students_out->fetchColumn();

$stmt_today_visitors = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE school_id = ? AND DATE(checkin_time) = CURDATE()");
$stmt_today_visitors->execute([CURRENT_SCHOOL_ID]);
$today_visitors = $stmt_today_visitors->fetchColumn();

// Fetch Recent Gate Logs / Activity
$stmt_recent = $pdo->prepare("
    SELECT 'Visitor' as type, visitor_name as name, checkin_time as time, purpose as detail
    FROM visitors WHERE school_id = ?
    UNION ALL
    SELECT 'Student Exit' as type, s.first_name as name, p.exit_time as time, p.reason as detail
    FROM student_exit_passes p JOIN students s ON p.student_id = s.id WHERE p.school_id = ?
    ORDER BY time DESC LIMIT 15
");
$stmt_recent->execute([CURRENT_SCHOOL_ID, CURRENT_SCHOOL_ID]);
$recentActivities = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Security Dashboard | VIC ERP";
$page_header = "Gate Pass & Visitor Management";
$active_menu = "visitor";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage security logs, passes, and visitor tracking</h5>
    <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-outline-primary">
            <i class="fa fa-users me-1"></i> Visitor Register
        </a>
        <a href="exit-passes.php" class="btn btn-outline-primary">
            <i class="fa fa-person-walking-arrow-right me-1"></i> Exit Passes
        </a>
        <a href="pickup.php" class="btn btn-outline-success">
            <i class="fa fa-car-side me-1"></i> Parent Pickup
        </a>
        <a href="security-dashboard.php" class="btn btn-info text-white">
            <i class="fa fa-shield-halved me-1"></i> Security Dashboard
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card shadow border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Active Visitors In Campus</h6>
                        <h2 class="mb-0 fw-bold"><?= $active_visitors ?></h2>
                    </div>
                    <div class="fs-1 text-white-50"><i class="fa fa-id-badge"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow border-0 bg-warning text-dark" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark-50 text-uppercase fw-bold mb-2">Students On Exit Pass</h6>
                        <h2 class="mb-0 fw-bold"><?= $students_out ?></h2>
                    </div>
                    <div class="fs-1 text-dark-50"><i class="fa fa-person-walking-arrow-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Check-ins Today</h6>
                        <h2 class="mb-0 fw-bold"><?= $today_visitors ?></h2>
                    </div>
                    <div class="fs-1 text-white-50"><i class="fa fa-users"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Live Feed / Recent Activity -->
    <div class="col-12">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-video text-danger me-2"></i> Live Security Log</h5>
                <span class="badge bg-danger rounded-pill px-3 py-1"><i class="fa fa-circle text-white small me-1"></i> LIVE</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Time</th>
                                <th>Log Type</th>
                                <th>Name / Person</th>
                                <th>Details / Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentActivities) > 0): ?>
                                <?php foreach($recentActivities as $act): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= date('h:i A', strtotime($act['time'])) ?></div>
                                            <div class="small text-muted"><?= date('d M Y', strtotime($act['time'])) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($act['type'] == 'Visitor'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="fa fa-user-tie me-1"></i> Visitor Check-in</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fa fa-user-graduate me-1"></i> Student Exit</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($act['name']) ?>
                                        </td>
                                        <td>
                                            <div class="small text-muted text-wrap" style="max-width: 300px;">
                                                <?= htmlspecialchars($act['detail']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-shield-halved fs-2 mb-2 d-block"></i>
                                        No recent security logs found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
