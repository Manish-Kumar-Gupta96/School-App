<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Date filter
$filter_date = $_GET['date'] ?? date('Y-m-d');

// 1. Total Visitors
$stmt_tot = $pdo->prepare("SELECT COUNT(*) FROM visitor_entries WHERE school_id = ? AND DATE(entry_time) = ?");
$stmt_tot->execute([$schoolId, $filter_date]);
$total_visitors = $stmt_tot->fetchColumn();

// 2. Visitors By Type
$stmt_type = $pdo->prepare("
    SELECT v.visitor_type, COUNT(*) as count 
    FROM visitor_entries ve 
    JOIN visitors v ON ve.visitor_id = v.id 
    WHERE ve.school_id = ? AND DATE(ve.entry_time) = ? 
    GROUP BY v.visitor_type
");
$stmt_type->execute([$schoolId, $filter_date]);
$visitors_by_type = $stmt_type->fetchAll(PDO::FETCH_ASSOC);

// 3. Early Leaves
$stmt_leaves = $pdo->prepare("SELECT COUNT(*) FROM student_gate_passes WHERE school_id = ? AND DATE(issue_time) = ?");
$stmt_leaves->execute([$schoolId, $filter_date]);
$total_leaves = $stmt_leaves->fetchColumn();

// Fetch Log
$stmt_log = $pdo->prepare("
    SELECT ve.entry_time, ve.exit_time, v.visitor_name, v.visitor_type, ve.purpose, ve.status
    FROM visitor_entries ve
    JOIN visitors v ON ve.visitor_id = v.id
    WHERE ve.school_id = ? AND DATE(ve.entry_time) = ?
    ORDER BY ve.entry_time DESC
");
$stmt_log->execute([$schoolId, $filter_date]);
$logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Security Reports | Admin Portal";
$page_header = "Security Management";
$active_menu = "security_reports";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Security Analytics</h5>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date) ?>" max="<?= date('Y-m-d') ?>">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Total Visitors</h6>
                <h2 class="fw-bold mb-0"><?= number_format($total_visitors) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-info text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Student Early Leaves</h6>
                <h2 class="fw-bold mb-0"><?= number_format($total_leaves) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-body p-3">
                <h6 class="fw-bold text-muted mb-2">By Visitor Type</h6>
                <?php if($visitors_by_type): ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach($visitors_by_type as $vt): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                <?= htmlspecialchars($vt['visitor_type']) ?>
                                <span class="badge bg-primary rounded-pill"><?= $vt['count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-muted small">No data for this date.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0">Visitor Log Details</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Visitor Name</th>
                        <th>Type</th>
                        <th>Purpose</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th class="pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= htmlspecialchars($l['visitor_name']) ?></td>
                            <td><?= htmlspecialchars($l['visitor_type']) ?></td>
                            <td><span class="text-truncate d-inline-block" style="max-width:200px;"><?= htmlspecialchars($l['purpose']) ?></span></td>
                            <td><?= date('h:i A', strtotime($l['entry_time'])) ?></td>
                            <td>
                                <?= $l['exit_time'] ? date('h:i A', strtotime($l['exit_time'])) : '<span class="text-danger small"><i class="fa fa-clock"></i> Inside</span>' ?>
                            </td>
                            <td class="pe-4">
                                <?php if($l['status'] == 'completed'): ?>
                                    <span class="badge bg-secondary">Completed</span>
                                <?php elseif($l['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= ucfirst($l['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No visitors found for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
