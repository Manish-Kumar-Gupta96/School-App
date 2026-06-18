<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
GENERATE PAYROLL
========================== */
if(isset($_POST['generate_payroll'])){
    $employee_id   = (int)$_POST['employee_id'];
    $month         = $_POST['payroll_month'];
    $basic_salary  = (float)$_POST['basic_salary'];
    $allowances    = (float)$_POST['allowances'];
    $deductions    = (float)$_POST['deductions'];

    if(empty($employee_id) || empty($month)){
        $error = "Employee and Payroll Month are required.";
    } else {
        // Check if payroll already generated for this month
        $check = $pdo->prepare("SELECT COUNT(*) FROM employee_payroll WHERE employee_id=? AND payroll_month=?");
        $check->execute([$employee_id, $month]);
        if($check->fetchColumn() > 0){
            $error = "Payroll for this employee has already been generated for $month.";
        } else {
            $net_salary = ($basic_salary + $allowances) - $deductions;

            $stmt = $pdo->prepare("
                INSERT INTO employee_payroll(employee_id, payroll_month, basic_salary, allowances, deductions, net_salary, payment_status)
                VALUES(?,?,?,?,?,?, 'Pending')
            ");
            $stmt->execute([
                $employee_id,
                $month,
                $basic_salary,
                $allowances,
                $deductions,
                $net_salary
            ]);
            $message = "Payroll Generated Successfully!";
        }
    }
}

/* ==========================
MARK AS PAID
========================== */
if(isset($_GET['paid'])){
    $id = (int)$_GET['paid'];
    $pdo->prepare("
        UPDATE employee_payroll
        SET payment_status='Paid'
        WHERE id=?
    ")->execute([$id]);
    header("Location: payroll.php");
    exit();
}

/* ==========================
LOAD EMPLOYEES
========================== */
$employees = $pdo->query("
    SELECT id, name AS teacher_name, salary
    FROM teachers
    WHERE status='Active'
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
PAYROLL LIST
========================== */
$payrolls = $pdo->query("
    SELECT p.*, t.name AS teacher_name
    FROM employee_payroll p
    LEFT JOIN teachers t ON p.employee_id=t.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Payroll Management | VIC ERP";
$page_header = "HR Management";
$active_menu = "hr";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Generate salaries and track monthly employee payments</h5>
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
        <a href="payroll.php" class="btn btn-success">
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
    <!-- GENERATE PAYROLL FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Generate Payroll</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" data-salary="<?= htmlspecialchars($emp['salary'] ?: 0.00) ?>"><?= htmlspecialchars($emp['teacher_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                        <input type="month" name="payroll_month" class="form-control" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Basic Salary (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" required placeholder="0.00">
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Allowances (₹)</label>
                            <input type="number" step="0.01" name="allowances" id="allowances" class="form-control" value="0.00" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Deductions (₹)</label>
                            <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" value="0.00" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Calculated Net Salary (₹)</label>
                        <input type="text" id="net_salary" class="form-control bg-light fw-bold text-success" value="0.00" readonly>
                    </div>

                    <button type="submit" name="generate_payroll" class="btn btn-success w-100">
                        <i class="fa fa-calculator me-1"></i> Generate Payroll
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PAYROLL LIST -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Payroll History Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Employee</th>
                                <th>Month</th>
                                <th>Earnings (Basic + Allow)</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th class="pe-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($payrolls) > 0): ?>
                                <?php foreach($payrolls as $pay): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($pay['teacher_name'] ?: 'Staff Employee') ?></td>
                                        <td class="fw-semibold text-dark"><?= date('F Y', strtotime($pay['payroll_month'] . '-01')) ?></td>
                                        <td>
                                            <span class="d-block small text-muted">Basic: ₹<?= number_format($pay['basic_salary'], 2) ?></span>
                                            <span class="d-block small text-success">Allow: ₹<?= number_format($pay['allowances'], 2) ?></span>
                                        </td>
                                        <td class="text-danger small">₹<?= number_format($pay['deductions'], 2) ?></td>
                                        <td class="fw-bold text-success" style="font-size: 0.95rem;">₹<?= number_format($pay['net_salary'], 2) ?></td>
                                        <td>
                                            <?php if($pay['payment_status'] == 'Paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <?php if($pay['payment_status'] == 'Pending'): ?>
                                                    <a href="?paid=<?= $pay['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('Mark this payroll as paid?');">
                                                        <i class="fa fa-hand-holding-usd"></i> Pay
                                                    </a>
                                                <?php endif; ?>
                                                <a href="salary-slips.php?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="View & Print Salary Slip">
                                                    <i class="fa fa-print"></i> Slip
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-money-check-alt fs-2 mb-2 d-block"></i>
                                        No payroll records found.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const basicInput = document.getElementById('basic_salary');
    const allowInput = document.getElementById('allowances');
    const dedInput = document.getElementById('deductions');
    const netInput = document.getElementById('net_salary');

    // Auto fill basic salary on employee selection
    employeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const defaultSalary = selectedOption.getAttribute('data-salary');
        if (defaultSalary) {
            basicInput.value = parseFloat(defaultSalary).toFixed(2);
        } else {
            basicInput.value = '';
        }
        calculateNet();
    });

    function calculateNet() {
        const basic = parseFloat(basicInput.value) || 0;
        const allowance = parseFloat(allowInput.value) || 0;
        const deduction = parseFloat(dedInput.value) || 0;
        const net = (basic + allowance) - deduction;
        netInput.value = net.toFixed(2);
    }

    basicInput.addEventListener('input', calculateNet);
    allowInput.addEventListener('input', calculateNet);
    dedInput.addEventListener('input', calculateNet);
});
</script>

<?php
require_once('../includes/footer.php');
?>
