<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ADD DEPARTMENT
========================== */
if(isset($_POST['save_department'])){
    $department_name = trim($_POST['department_name']);
    $description     = trim($_POST['description']);
    $status          = $_POST['status'];

    if(empty($department_name)){
        $error = "Department name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hr_departments (department_name, description, status)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $department_name,
            $description,
            $status
        ]);
        $message = "Department Registered Successfully!";
    }
}

/* ==========================
LOAD DEPARTMENTS
========================== */
$departments = $pdo->query("
    SELECT *
    FROM hr_departments
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Departments Management | VIC ERP";
$page_header = "HR Management";
$active_menu = "hr";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Configure HR department divisions</h5>
    <div class="d-flex gap-2">
        <a href="departments.php" class="btn btn-primary">
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
    <!-- ADD DEPARTMENT -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Add Department</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="department_name" class="form-control" placeholder="e.g. Academic, Administration" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Roles and responsibilities..."></textarea>
                    </div>

                    <button type="submit" name="save_department" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Department
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST DEPARTMENTS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Department Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="pe-4">Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($departments) > 0): ?>
                                <?php foreach($departments as $dept): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= htmlspecialchars($dept['id']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($dept['department_name']) ?></td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($dept['description'] ?: '-') ?></span></td>
                                        <td>
                                            <?php if($dept['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-muted small"><?= date('d M Y', strtotime($dept['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-building fs-2 mb-2 d-block"></i>
                                        No departments defined yet.
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
