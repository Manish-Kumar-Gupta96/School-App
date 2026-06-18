<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
APPLY LEAVE
========================== */
if(isset($_POST['apply_leave'])){
    $employee_id = (int)$_POST['employee_id'];
    $leave_type  = $_POST['leave_type'];
    $from_date   = $_POST['from_date'];
    $to_date     = $_POST['to_date'];
    $reason      = trim($_POST['reason']);

    if(empty($employee_id) || empty($from_date) || empty($to_date)){
        $error = "Employee, From date and To date are required.";
    } elseif(strtotime($to_date) < strtotime($from_date)){
        $error = "To date cannot be earlier than From date.";
    } else {
        $days = (strtotime($to_date) - strtotime($from_date)) / 86400 + 1;

        $stmt = $pdo->prepare("
            INSERT INTO employee_leaves(employee_id, leave_type, from_date, to_date, total_days, reason)
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->execute([
            $employee_id,
            $leave_type,
            $from_date,
            $to_date,
            $days,
            $reason
        ]);
        $message = "Leave Application Submitted Successfully!";
    }
}

/* ==========================
APPROVE LEAVE
========================== */
if(isset($_GET['approve'])){
    $id = (int)$_GET['approve'];
    $pdo->prepare("
        UPDATE employee_leaves
        SET status='Approved'
        WHERE id=?
    ")->execute([$id]);
    header("Location: employee-leave.php");
    exit();
}

/* ==========================
REJECT LEAVE
========================== */
if(isset($_GET['reject'])){
    $id = (int)$_GET['reject'];
    $pdo->prepare("
        UPDATE employee_leaves
        SET status='Rejected'
        WHERE id=?
    ")->execute([$id]);
    header("Location: employee-leave.php");
    exit();
}

/* ==========================
LOAD EMPLOYEES
========================== */
$employees = $pdo->query("
    SELECT id, name AS teacher_name
    FROM teachers
    WHERE status='Active'
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LEAVE LIST
========================== */
$leaveList = $pdo->query("
    SELECT l.*, t.name AS teacher_name
    FROM employee_leaves l
    LEFT JOIN teachers t ON l.employee_id=t.id
    ORDER BY l.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Employee Leave Management | VIC ERP";
$page_header = "HR Management";
$active_menu = "hr";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and approve employee leave applications</h5>
    <div class="d-flex gap-2">
        <a href="departments.php" class="btn btn-outline-primary">
            <i class="fa fa-building me-1"></i> Departments
        </a>
        <a href="designations.php" class="btn btn-outline-primary">
            <i class="fa fa-briefcase me-1"></i> Designations
        </a>
        <a href="employee-leave.php" class="btn btn-warning text-white">
            <i class="fa fa-calendar-times me-1"></i> Leaves
        </a>
        <a href="payroll.php" class="btn btn-outline-success">
            <i class="fa fa-money-check-alt me-1"></i> Payroll
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- APPLY LEAVE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Apply Leave</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Employee / Teacher <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['teacher_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
                        <label class="form-label fw-semibold">Reason for Leave</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Specify explanation..." required></textarea>
                    </div>

                    <button type="submit" name="apply_leave" class="btn btn-primary w-100">
                        <i class="fa fa-paper-plane me-1"></i> Apply Leave
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LEAVE APPLICATIONS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Leave Applications Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Employee</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Total Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th class="pe-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($leaveList) > 0): ?>
                                <?php foreach($leaveList as $leave): ?>
                                    <?php
                                    $badge = 'warning';
                                    if($leave['status'] == 'Approved'){
                                        $badge = 'success';
                                    } elseif($leave['status'] == 'Rejected'){
                                        $badge = 'danger';
                                    }
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($leave['teacher_name'] ?: 'Staff Employee') ?></td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($leave['leave_type']) ?></span></td>
                                        <td class="small text-muted">
                                            <?= date('d M Y', strtotime($leave['from_date'])) ?> to<br>
                                            <?= date('d M Y', strtotime($leave['to_date'])) ?>
                                        </td>
                                        <td class="fw-bold text-dark"><?= (int)$leave['total_days'] ?> Days</td>
                                        <td><span class="text-muted small" title="<?= htmlspecialchars($leave['reason']) ?>"><?= htmlspecialchars(substr($leave['reason'], 0, 30)) ?><?= strlen($leave['reason']) > 30 ? '...' : '' ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2">
                                                <?= htmlspecialchars($leave['status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <?php if($leave['status'] == 'Pending'): ?>
                                                <a href="?approve=<?= $leave['id'] ?>" class="btn btn-success btn-sm me-1" onclick="return confirm('Approve leave?');">
                                                    <i class="fa fa-check"></i> Approve
                                                </a>
                                                <a href="?reject=<?= $leave['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject leave?');">
                                                    <i class="fa fa-times"></i> Reject
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small fw-semibold">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-calendar-times fs-2 mb-2 d-block"></i>
                                        No leave records found.
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
require_once('../includes/footer.php');
?>
