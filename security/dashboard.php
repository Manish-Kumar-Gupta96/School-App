<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

if ($_SESSION['role'] !== 'guard' && $_SESSION['role'] !== 'admin') {
    die("Access Denied. Security personnel only.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Metrics
$today = date('Y-m-d');

// 1. Visitors Inside right now
$stmt_inside = $pdo->prepare("
    SELECT COUNT(*) 
    FROM visitor_entries 
    WHERE school_id = ? AND status IN ('pending', 'approved') AND DATE(entry_time) = ? AND exit_time IS NULL
");
$stmt_inside->execute([$schoolId, $today]);
$visitors_inside = $stmt_inside->fetchColumn();

// 2. Visitors Today
$stmt_today = $pdo->prepare("
    SELECT COUNT(*) 
    FROM visitor_entries 
    WHERE school_id = ? AND DATE(entry_time) = ?
");
$stmt_today->execute([$schoolId, $today]);
$visitors_today = $stmt_today->fetchColumn();

// 3. Students Out (Early Leave)
$stmt_students = $pdo->prepare("
    SELECT COUNT(*)
    FROM student_gate_passes
    WHERE school_id = ? AND DATE(issue_time) = ? AND status = 'used' AND exit_time IS NOT NULL
");
$stmt_students->execute([$schoolId, $today]);
$students_out = $stmt_students->fetchColumn();

// 4. Pending Approvals
$stmt_pending = $pdo->prepare("
    SELECT COUNT(*)
    FROM visitor_entries
    WHERE school_id = ? AND status = 'pending' AND DATE(entry_time) = ?
");
$stmt_pending->execute([$schoolId, $today]);
$pending_approvals = $stmt_pending->fetchColumn();

// Recent Entries
$stmt_recent = $pdo->prepare("
    SELECT ve.*, v.visitor_name, v.visitor_type, v.mobile_no 
    FROM visitor_entries ve
    JOIN visitors v ON ve.visitor_id = v.id
    WHERE ve.school_id = ? AND DATE(ve.entry_time) = ?
    ORDER BY ve.entry_time DESC
    LIMIT 10
");
$stmt_recent->execute([$schoolId, $today]);
$recent_entries = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../";
$page_title = "Security Dashboard | Guard Portal";
$page_header = "Security Dashboard";
$active_menu = "security_dashboard";

// Note: If guard role doesn't have a specific header, we will include a simplified one
require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Today's Security Overview</h5>
    <div class="d-flex gap-2">
        <a href="visitor_entry.php" class="btn btn-primary"><i class="fa fa-user-plus me-1"></i> New Visitor</a>
        <a href="gate_pass.php" class="btn btn-outline-info"><i class="fa fa-qrcode me-1"></i> Scan Exit</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Visitors Inside</h6>
                <h2 class="fw-bold mb-0"><?= number_format($visitors_inside) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Total Visitors Today</h6>
                <h2 class="fw-bold mb-0"><?= number_format($visitors_today) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-dark h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Pending Approvals</h6>
                <h2 class="fw-bold mb-0"><?= number_format($pending_approvals) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Students On Early Leave</h6>
                <h2 class="fw-bold mb-0"><?= number_format($students_out) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0">Recent Visitors Today</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Visitor Details</th>
                        <th>Type</th>
                        <th>Meeting With</th>
                        <th>Entry Time</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">Exit Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_entries as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['visitor_name']) ?></div>
                                <div class="small text-muted"><i class="fa fa-phone me-1"></i> <?= htmlspecialchars($r['mobile_no']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['visitor_type']) ?></span></td>
                            <td>
                                <div class="text-uppercase small fw-bold text-muted"><?= htmlspecialchars($r['person_to_meet_type']) ?></div>
                                <div>ID: <?= htmlspecialchars($r['person_to_meet_id']) ?></div>
                            </td>
                            <td><?= date('h:i A', strtotime($r['entry_time'])) ?></td>
                            <td>
                                <?php if($r['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Waiting Approval</span>
                                <?php elseif($r['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved / Inside</span>
                                <?php elseif($r['status'] == 'completed'): ?>
                                    <span class="badge bg-secondary">Exited</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($r['exit_time']): ?>
                                    <span class="text-muted fw-bold"><?= date('h:i A', strtotime($r['exit_time'])) ?></span>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fa fa-clock"></i> Still Inside</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($recent_entries)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No visitors today yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
