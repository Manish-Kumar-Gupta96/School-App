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

// Handle Application Status Update
if (isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['NEW', 'SHORTLISTED', 'REJECTED'])) {
        try {
            $stmt = $pdo->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $app_id]);
            $success = "Application status updated to " . htmlspecialchars($new_status) . ".";
        } catch (PDOException $e) {
            $error = 'Error updating status: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid status selected.';
    }
}

// Fetch all applications
try {
    $applications = $pdo->query("
        SELECT ja.*, jo.title AS job_title, jo.department 
        FROM job_applications ja
        LEFT JOIN job_openings jo ON ja.job_id = jo.id
        ORDER BY ja.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $applications = [];
    $error = 'Database Error: ' . $e->getMessage();
}

$page_title = "Job Applications | VIC ERP";
$page_header = "Received Job Applications";
$active_menu = "careers";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Job Applications</h2>
        <a href="create-job.php" class="btn btn-outline-primary shadow-sm">
            <i class="fa fa-briefcase me-2"></i> Manage Job Openings
        </a>
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

    <!-- Applications List -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-envelope-open-text me-2 text-primary"></i>Applicant Submissions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Applicant</th>
                        <th>Applied For</th>
                        <th>Contact info</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th class="pe-4 text-end">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applications) > 0): ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($app['name']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary"><?= htmlspecialchars($app['job_title'] ?: 'General Application') ?></span>
                                    <div class="text-muted small"><?= htmlspecialchars($app['department'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <div class="small"><i class="fa fa-envelope text-muted me-1"></i> <?= htmlspecialchars($app['email']) ?></div>
                                    <div class="small"><i class="fa fa-phone text-muted me-1"></i> <?= htmlspecialchars($app['phone']) ?></div>
                                </td>
                                <td>
                                    <?php if ($app['resume']): ?>
                                        <a href="<?= $root_path . htmlspecialchars($app['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fa fa-file-pdf me-1"></i> View CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($app['status'] === 'NEW'): ?>
                                        <span class="badge bg-info-subtle text-info"><i class="fa fa-star me-1"></i> New</span>
                                    <?php elseif ($app['status'] === 'SHORTLISTED'): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa fa-user-check me-1"></i> Shortlisted</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger"><i class="fa fa-user-xmark me-1"></i> Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($app['applied_at'])) ?></td>
                                <td class="pe-4 text-end">
                                    <form method="POST" class="d-inline-flex gap-1 align-items-center">
                                        <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: 120px; border-radius: 6px;">
                                            <option value="NEW" <?= $app['status'] === 'NEW' ? 'selected' : '' ?>>New</option>
                                            <option value="SHORTLISTED" <?= $app['status'] === 'SHORTLISTED' ? 'selected' : '' ?>>Shortlist</option>
                                            <option value="REJECTED" <?= $app['status'] === 'REJECTED' ? 'selected' : '' ?>>Reject</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                            <i class="fa fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                                No applications received yet.
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
