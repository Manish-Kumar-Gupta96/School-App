<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Leave Application
if (isset($_POST['apply_leave'])) {
    $from_date = trim($_POST['from_date']);
    $to_date = trim($_POST['to_date']);
    $reason = trim($_POST['reason']);

    if (strtotime($from_date) > strtotime($to_date)) {
        $error = "From Date cannot be later than To Date.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO leave_requests (school_id, student_id, from_date, to_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$schoolId, $student_id, $from_date, $to_date, $reason]);
            $message = "Leave request submitted successfully. Waiting for teacher approval.";
        } catch (Exception $e) {
            $error = "Failed to submit leave request: " . $e->getMessage();
        }
    }
}

// Fetch past leave requests
$stmt_leaves = $pdo->prepare("SELECT * FROM leave_requests WHERE student_id = ? AND school_id = ? ORDER BY created_at DESC");
$stmt_leaves->execute([$student_id, $schoolId]);
$leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Apply Leave | Student Portal";
$page_header = "Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Leave Management</h5>
    <div class="d-flex gap-2">
        <a href="view.php" class="btn btn-outline-primary"><i class="fa fa-calendar-alt me-1"></i> My Attendance</a>
        <a href="leave_apply.php" class="btn btn-info text-white"><i class="fa fa-envelope-open-text me-1"></i> Apply Leave</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Apply Leave Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">New Leave Request</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">From Date <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">To Date <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Briefly describe the reason for your leave..." required></textarea>
                    </div>
                    <button type="submit" name="apply_leave" class="btn btn-primary w-100 fw-bold"><i class="fa fa-paper-plane me-1"></i> Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave History -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-light border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">My Leave History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date Range</th>
                                <th>Reason</th>
                                <th>Applied On</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaves) > 0): ?>
                                <?php foreach($leaves as $l): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-secondary"><?= date('d M Y', strtotime($l['from_date'])) ?></span> 
                                            to 
                                            <span class="badge bg-secondary"><?= date('d M Y', strtotime($l['to_date'])) ?></span>
                                        </td>
                                        <td>
                                            <p class="m-0 small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($l['reason']) ?>">
                                                <?= htmlspecialchars($l['reason']) ?>
                                            </p>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, h:i A', strtotime($l['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($l['status'] === 'approved'): ?>
                                                <span class="badge bg-success"><i class="fa fa-check"></i> Approved</span>
                                            <?php elseif ($l['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger"><i class="fa fa-times"></i> Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fa fa-clock"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted p-5">You haven't applied for any leave yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
