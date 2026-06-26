<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Company
if (isset($_POST['save_company'])) {
    $company_name = trim($_POST['company_name']);
    $website      = trim($_POST['website']);
    $hr_email     = trim($_POST['hr_email']);

    if (empty($company_name)) {
        $error = "Company name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO companies (school_id, company_name, website, hr_email)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $company_name,
            $website,
            $hr_email
        ]);
        $message = "Company registered successfully!";
    }
}

// Fetch Companies
$stmt_companies = $pdo->prepare("
    SELECT * 
    FROM companies
    WHERE school_id = ?
    ORDER BY company_name ASC
");
$stmt_companies->execute([CURRENT_SCHOOL_ID]);
$companiesList = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Companies Registry | VIC ERP";
$page_header = "Placement & Career Guidance";
$active_menu = "placement";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage placement drives and career records</h5>
    <div class="d-flex gap-2">
        <a href="companies.php" class="btn btn-primary">
            <i class="fa fa-building me-1"></i> Companies
        </a>
        <a href="sessions.php" class="btn btn-outline-primary">
            <i class="fa fa-person-chalkboard me-1"></i> Career Sessions
        </a>
        <a href="placements.php" class="btn btn-outline-success">
            <i class="fa fa-briefcase me-1"></i> Placements
        </a>
        <a href="exams.php" class="btn btn-outline-info">
            <i class="fa fa-file-signature me-1"></i> Entrance Exams
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add Company Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Company</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. TCS, Infosys" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Website</label>
                        <input type="url" name="website" class="form-control" placeholder="e.g. https://www.tcs.com">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">HR Contact Email</label>
                        <input type="email" name="hr_email" class="form-control" placeholder="e.g. hr@tcs.com">
                    </div>

                    <button type="submit" name="save_company" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Register Company
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Companies List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Associated Companies</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Company Name</th>
                                <th>Website</th>
                                <th>HR Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($companiesList) > 0): ?>
                                <?php foreach($companiesList as $cp): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?= htmlspecialchars($cp['company_name']) ?>
                                        </td>
                                        <td>
                                            <?php if ($cp['website']): ?>
                                                <a href="<?= htmlspecialchars($cp['website']) ?>" target="_blank" class="text-decoration-none">
                                                    <?= htmlspecialchars($cp['website']) ?> <i class="fa fa-external-link-alt small ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($cp['hr_email']): ?>
                                                <a href="mailto:<?= htmlspecialchars($cp['hr_email']) ?>" class="text-decoration-none text-secondary">
                                                    <i class="fa fa-envelope me-1"></i> <?= htmlspecialchars($cp['hr_email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fa fa-building fs-2 mb-2 d-block"></i>
                                        No companies registered yet.
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

<?php require_once('../includes/footer.php'); ?>
