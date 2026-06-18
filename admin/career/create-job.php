<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle Job Creation
if (isset($_POST['create_job'])) {
    $title = trim($_POST['title']);
    $department = trim($_POST['department']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $salary = trim($_POST['salary']);

    if (empty($title) || empty($department)) {
        $error = 'Job Title and Department are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO job_openings (title, department, description, requirements, salary, status) VALUES (?, ?, ?, ?, ?, 'OPEN')");
            $stmt->execute([$title, $department, $description, $requirements, $salary]);
            $success = 'Job vacancy posted successfully!';
        } catch (PDOException $e) {
            $error = 'Error creating job: ' . $e->getMessage();
        }
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $job_id = (int)$_GET['id'];
    $new_status = $_GET['toggle_status'] === 'OPEN' ? 'OPEN' : 'CLOSED';
    try {
        $stmt = $pdo->prepare("UPDATE job_openings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $job_id]);
        $success = 'Job status updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Fetch all jobs
$jobs = $pdo->query("SELECT * FROM job_openings ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Job Vacancies | VIC ERP";
$page_header = "Post and Manage Jobs";
$active_menu = "careers";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Job Vacancies Dashboard</h2>
        <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#postJobCollapse" aria-expanded="false" aria-controls="postJobCollapse">
            <i class="fa fa-plus me-2"></i> Post New Vacancy
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Collapse Form to Post a Job -->
    <div class="collapse mb-4" id="postJobCollapse">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 12px;">
            <h5 class="fw-bold text-dark mb-3"><i class="fa fa-briefcase me-2 text-primary"></i>Post a New Job Opening</h5>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Senior Math Teacher" required style="border-radius: 8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. Academic, Administration, IT" required style="border-radius: 8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Salary Package / Range</label>
                        <input type="text" name="salary" class="form-control" placeholder="e.g. ₹ 40,000 - ₹ 50,000 per month" style="border-radius: 8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Qualifications & Requirements</label>
                        <textarea name="requirements" class="form-control" placeholder="e.g. B.Ed, M.Sc in Mathematics, 3+ years experience" rows="2" style="border-radius: 8px;"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Job Description</label>
                        <textarea name="description" class="form-control" placeholder="Describe the roles, responsibilities, and schedule..." rows="4" style="border-radius: 8px;"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-toggle="collapse" data-bs-target="#postJobCollapse">Cancel</button>
                        <button type="submit" name="create_job" class="btn btn-primary px-4">Publish Job</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Job Vacancies List -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-success"></i>Current Job Openings</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Job Title</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Posted Date</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></span>
                                    <div class="text-muted small"><?= htmlspecialchars(substr($job['description'], 0, 80)) ?>...</div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($job['department']) ?></span></td>
                                <td class="text-muted"><?= htmlspecialchars($job['salary'] ?: 'Not Specified') ?></td>
                                <td>
                                    <?php if ($job['status'] === 'OPEN'): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa fa-circle-check me-1"></i> Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger"><i class="fa fa-circle-xmark me-1"></i> Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($job['created_at'])) ?></td>
                                <td class="pe-4 text-end">
                                    <?php if ($job['status'] === 'OPEN'): ?>
                                        <a href="?toggle_status=CLOSED&id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Are you sure you want to close this job?')">
                                            <i class="fa fa-ban me-1"></i> Close
                                        </a>
                                    <?php else: ?>
                                        <a href="?toggle_status=OPEN&id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to reopen this job?')">
                                            <i class="fa fa-redo me-1"></i> Reopen
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                                No job openings posted yet. Click 'Post New Vacancy' to start.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
