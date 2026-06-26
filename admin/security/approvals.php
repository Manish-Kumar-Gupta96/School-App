<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Admin role check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Visitor Approval
if (isset($_POST['approve_visitor'])) {
    $entry_id = (int)$_POST['visitor_entry_id'];
    $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    try {
        $stmt_upd = $pdo->prepare("UPDATE visitor_entries SET status = ? WHERE id = ? AND school_id = ?");
        $stmt_upd->execute([$status, $entry_id, $schoolId]);
        $message = "Visitor entry {$status} successfully.";
    } catch(Exception $e) {
        $error = "Error updating visitor status: " . $e->getMessage();
    }
}

// Handle Student Early Leave Approval
if (isset($_POST['approve_student_leave'])) {
    $pass_id = (int)$_POST['student_gate_pass_id'];
    $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $admin_id = $_SESSION['admin_id'];
    
    try {
        $stmt_upd = $pdo->prepare("UPDATE student_gate_passes SET status = ?, approved_by = ? WHERE id = ? AND school_id = ?");
        $stmt_upd->execute([$status, $admin_id, $pass_id, $schoolId]);
        $message = "Student leave request {$status} successfully.";
    } catch(Exception $e) {
        $error = "Error updating student leave: " . $e->getMessage();
    }
}

// Fetch Pending Visitors
$stmt_visitors = $pdo->prepare("
    SELECT ve.*, v.visitor_name, v.mobile_no, v.visitor_type 
    FROM visitor_entries ve
    JOIN visitors v ON ve.visitor_id = v.id
    WHERE ve.school_id = ? AND ve.status = 'pending'
    ORDER BY ve.entry_time DESC
");
$stmt_visitors->execute([$schoolId]);
$pending_visitors = $stmt_visitors->fetchAll(PDO::FETCH_ASSOC);

// Fetch Pending Student Leaves
$stmt_leaves = $pdo->prepare("
    SELECT sgp.*, s.first_name, s.last_name, s.admission_number
    FROM student_gate_passes sgp
    JOIN students s ON sgp.student_id = s.id
    WHERE sgp.school_id = ? AND sgp.status = 'pending'
    ORDER BY sgp.issue_time DESC
");
$stmt_leaves->execute([$schoolId]);
$pending_leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Security Approvals | Admin Portal";
$page_header = "Security Management";
$active_menu = "security_approvals";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Pending Security Approvals</h5>
    <a href="reports.php" class="btn btn-outline-secondary"><i class="fa fa-chart-bar me-1"></i> View Reports</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Visitor Entries</h5>
                <span class="badge bg-primary rounded-pill"><?= count($pending_visitors) ?> Pending</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Purpose</th>
                                <th>Meeting</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_visitors as $pv): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($pv['visitor_name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($pv['visitor_type']) ?></div>
                                    </td>
                                    <td>
                                        <p class="m-0 small text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($pv['purpose']) ?>">
                                            <?= htmlspecialchars($pv['purpose']) ?>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="text-uppercase small fw-bold"><?= htmlspecialchars($pv['person_to_meet_type']) ?></div>
                                        <span class="text-muted small">ID: <?= htmlspecialchars($pv['person_to_meet_id']) ?></span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="visitor_entry_id" value="<?= $pv['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success shadow-sm rounded-circle me-1" title="Approve"><i class="fa fa-check"></i></button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger shadow-sm rounded-circle" title="Reject"><i class="fa fa-times"></i></button>
                                            <input type="hidden" name="approve_visitor" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pending_visitors)): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">No pending visitors.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Student Early Leaves</h5>
                <span class="badge bg-warning text-dark rounded-pill"><?= count($pending_leaves) ?> Pending</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Reason</th>
                                <th>Requested</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_leaves as $pl): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($pl['first_name'] . ' ' . $pl['last_name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($pl['admission_number']) ?></div>
                                    </td>
                                    <td>
                                        <p class="m-0 small text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($pl['reason']) ?>">
                                            <?= htmlspecialchars($pl['reason']) ?>
                                        </p>
                                    </td>
                                    <td><?= date('h:i A', strtotime($pl['issue_time'])) ?></td>
                                    <td class="pe-4 text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="student_gate_pass_id" value="<?= $pl['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success shadow-sm rounded-circle me-1" title="Approve"><i class="fa fa-check"></i></button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger shadow-sm rounded-circle" title="Reject"><i class="fa fa-times"></i></button>
                                            <input type="hidden" name="approve_student_leave" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pending_leaves)): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">No pending student leaves.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
