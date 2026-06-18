<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
METRICS COLLECTION
========================== */
$totalDepts = $pdo->query("SELECT COUNT(*) FROM hr_departments")->fetchColumn();
$totalDesignations = $pdo->query("SELECT COUNT(*) FROM hr_designations")->fetchColumn();
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalPaidSalaries = $pdo->query("SELECT IFNULL(SUM(net_salary), 0) FROM employee_payroll WHERE payment_status='Paid'")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM employee_leaves WHERE status='Pending'")->fetchColumn();

/* ==========================
DEPARTMENT HEADCOUNT
========================== */
$deptHeadcount = $pdo->query("
    SELECT d.department_name, d.status, COUNT(t.id) as headcount
    FROM hr_departments d
    LEFT JOIN teachers t ON t.department_id = d.id
    GROUP BY d.id
    ORDER BY headcount DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
RECENT LEAVES (10)
========================== */
$recentLeaves = $pdo->query("
    SELECT l.*, t.name AS teacher_name
    FROM employee_leaves l
    LEFT JOIN teachers t ON l.employee_id = t.id
    ORDER BY l.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
RECENT PAYROLLS (10)
========================== */
$recentPayrolls = $pdo->query("
    SELECT p.*, t.name AS teacher_name
    FROM employee_payroll p
    LEFT JOIN teachers t ON p.employee_id = t.id
    ORDER BY p.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "HR Reports & Analytics | VIC ERP";
$page_header = "HR Management";
$active_menu = "hr";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Overview of staff allocations, salaries, and leave requests</h5>
    <div class="d-flex gap-2">
        <a href="departments.php" class="btn btn-outline-primary">
            <i class="fa fa-building me-1"></i> Departments
        </a>
        <a href="designations.php" class="btn btn-outline-primary">
            <i class="fa fa-briefcase me-1"></i> Designations
        </a>
        <a href="employee-leave.php" class="btn btn-outline-warning">
            <i class="fa fa-calendar-times me-1"></i> Leaves
        </a>
        <a href="payroll.php" class="btn btn-outline-success">
            <i class="fa fa-money-check-alt me-1"></i> Payroll
        </a>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<!-- METRIC CARDS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--primary-color) !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded-3 me-3">
                        <i class="fa fa-user-tie fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Total Employees</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalEmployees ?></h4>
                        <span class="text-muted small text-start d-block"><?= (int)$totalDepts ?> Departments</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success-subtle text-success p-3 rounded-3 me-3">
                        <i class="fa fa-hand-holding-usd fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Paid Payroll</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start">₹ <?= number_format($totalPaidSalaries, 2) ?></h4>
                        <span class="text-muted small text-start d-block">All-time payouts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning-subtle text-warning p-3 rounded-3 me-3">
                        <i class="fa fa-calendar-minus fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Leaves</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$pendingLeaves ?> Applications</h4>
                        <span class="text-muted small text-start d-block">Awaiting approval</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #0dcaf0 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info-subtle text-info p-3 rounded-3 me-3">
                        <i class="fa fa-id-badge fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Designations</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalDesignations ?></h4>
                        <span class="text-muted small text-start d-block">Active staff roles</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- DEPT HEADCOUNT -->
    <div class="col-md-4 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-chart-pie me-2 text-primary"></i>Department Headcount</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Department</th>
                                <th>Status</th>
                                <th class="pe-4 text-center">Headcount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($deptHeadcount as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['department_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>-subtle text-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?> border px-2 py-1 small">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-center fw-bold text-primary"><?= (int)$row['headcount'] ?> Staff</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT LEAVE LOGS -->
    <div class="col-md-8 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-plane-departure me-2 text-warning"></i>Recent Leave Applications</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Employee</th>
                                <th>Type</th>
                                <th>Days</th>
                                <th>Applied Date</th>
                                <th class="pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentLeaves) > 0): ?>
                                <?php foreach($recentLeaves as $leave): ?>
                                    <?php
                                    $badge = 'warning';
                                    if($leave['status'] == 'Approved') $badge = 'success';
                                    if($leave['status'] == 'Rejected') $badge = 'danger';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($leave['teacher_name'] ?: 'Staff Employee') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($leave['leave_type']) ?></span></td>
                                        <td class="fw-semibold"><?= (int)$leave['total_days'] ?> Days</td>
                                        <td class="small text-muted"><?= date('d M Y', strtotime($leave['applied_at'])) ?></td>
                                        <td class="pe-4">
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border px-3 py-1">
                                                <?= $leave['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No recent leave request logs.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- PAYROLL SUMMARY LOGS -->
    <div class="col-12 mb-4">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-file-invoice-dollar me-2 text-success"></i>Monthly Payroll Summary</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Employee</th>
                                <th>Month</th>
                                <th>Basic Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Payment Status</th>
                                <th class="pe-4 text-center">Receipt Reference</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($recentPayrolls) > 0): ?>
                                <?php foreach($recentPayrolls as $pay): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($pay['teacher_name'] ?: 'Staff Employee') ?></td>
                                        <td class="fw-semibold text-muted"><?= date('F Y', strtotime($pay['payroll_month'] . '-01')) ?></td>
                                        <td>₹ <?= number_format($pay['basic_salary'], 2) ?></td>
                                        <td class="text-success">₹ <?= number_format($pay['allowances'], 2) ?></td>
                                        <td class="text-danger">₹ <?= number_format($pay['deductions'], 2) ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($pay['net_salary'], 2) ?></td>
                                        <td>
                                            <?php if($pay['payment_status'] == 'Paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <a href="salary-slips.php?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-light border btn-sm small">
                                                <i class="fa fa-print me-1"></i> Print Pay Slip
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No payroll transaction logs.</td>
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
