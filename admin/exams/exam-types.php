<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $exam_name   = trim($_POST['exam_name']);
    $description = trim($_POST['description']);
    $status      = $_POST['status'];

    if(empty($exam_name)){
        $error = "Exam Type name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO exam_types(exam_name, description, status)
            VALUES(?,?,?)
        ");
        $stmt->execute([$exam_name, $description, $status]);
        $message = "Exam Type Added Successfully";
    }
}

$examTypes = $pdo->query("
    SELECT *
    FROM exam_types
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Exam Types Management | VIC ERP";
$page_header = "Exam Types";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Configure academic examination categories</h5>
    <a href="create-exam.php" class="btn btn-outline-primary">
        <i class="fa fa-arrow-right me-1"></i> Manage Exams
    </a>
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
    <!-- ADD EXAM TYPE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add New Exam Type</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. Unit Test, Half Yearly" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Monthly progress evaluation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" name="save" class="btn btn-success w-100">
                        <i class="fa fa-plus me-1"></i> Save Exam Type
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST EXAM TYPES -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" width="80">ID</th>
                                <th>Exam Type</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($examTypes) > 0): ?>
                                <?php foreach($examTypes as $type): ?>
                                    <tr>
                                        <td class="ps-4"><?= htmlspecialchars($type['id']) ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($type['exam_name']) ?></td>
                                        <td><?= htmlspecialchars($type['description'] ?: '-') ?></td>
                                        <td>
                                            <?php if($type['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-clipboard-list fs-2 mb-2 d-block"></i>
                                        No exam types registered.
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
