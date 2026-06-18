<?php
require_once('../config/database.php');
require_once('includes/header.php');

$teacher_id = $_SESSION['teacher_id'];
$message = '';
$error = '';

/* ==========================
APPLY LEAVE
========================== */
if(isset($_POST['apply_leave'])){
    $leave_type  = $_POST['leave_type'];
    $from_date   = $_POST['from_date'];
    $to_date     = $_POST['to_date'];
    $reason      = trim($_POST['reason']);

    if(empty($from_date) || empty($to_date) || empty($reason)){
        $error = "All form fields are required.";
    } elseif(strtotime($to_date) < strtotime($from_date)){
        $error = "To date cannot be earlier than From date.";
    } else {
        $days = (strtotime($to_date) - strtotime($from_date)) / 86400 + 1;

        $stmt = $pdo->prepare("
            INSERT INTO employee_leaves(employee_id, leave_type, from_date, to_date, total_days, reason, status)
            VALUES(?,?,?,?,?,?,'Pending')
        ");
        $stmt->execute([
            $teacher_id,
            $leave_type,
            $from_date,
            $to_date,
            $days,
            $reason
        ]);
        $message = "Leave application submitted to HR successfully!";
    }
}

/* ==========================
LOAD LEAVE HISTORY
========================== */
$stmt_leaves = $pdo->prepare("
    SELECT *
    FROM employee_leaves
    WHERE employee_id=?
    ORDER BY id DESC
");
$stmt_leaves->execute([$teacher_id]);
$leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

// Layout configuration
$active_menu = "leave";
$page_title = "My Leave Requests | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">My Leave Requests</h2>
        <p class="text-muted mb-0">Apply for leaves and track your approval status</p>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- SUBMIT APPLICATION -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Request Leave</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select" required>
                            <option value="Casual Leave">Casual Leave</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Paid Leave">Paid Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                        </select>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="4" class="form-control" placeholder="Explain the reason for leave..." required></textarea>
                    </div>

                    <button type="submit" name="apply_leave" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa fa-paper-plane me-1"></i> Apply Leave
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LEAVE HISTORY LOG -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 text-start">
                <h5 class="fw-bold mb-0 text-dark">Leave Application History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Leave Type</th>
                                <th>Duration</th>
                                <th>Total Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th class="pe-4">Applied At</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($leaves) > 0): ?>
                                <?php foreach($leaves as $row): ?>
                                    <?php
                                    $badge = 'warning';
                                    if($row['status'] == 'Approved') $badge = 'success';
                                    elseif($row['status'] == 'Rejected') $badge = 'danger';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['leave_type']) ?></td>
                                        <td class="small text-muted">
                                            <?= date('d M Y', strtotime($row['from_date'])) ?> to<br>
                                            <?= date('d M Y', strtotime($row['to_date'])) ?>
                                        </td>
                                        <td class="fw-bold"><?= (int)$row['total_days'] ?> Days</td>
                                        <td><span class="text-muted small" title="<?= htmlspecialchars($row['reason']) ?>"><?= htmlspecialchars(substr($row['reason'], 0, 35)) ?><?= strlen($row['reason']) > 35 ? '...' : '' ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-muted small"><?= date('d M Y, h:i A', strtotime($row['applied_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-plane-departure fs-2 mb-2 d-block"></i>
                                        No leave requests submitted yet.
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

<?php
require_once('includes/footer.php');
?>
