<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Placement Record
if (isset($_POST['save_placement'])) {
    $student_id     = (int)$_POST['student_id'];
    $company_id     = (int)$_POST['company_id'];
    $package_amount = trim($_POST['package_amount']);
    $joining_date   = trim($_POST['joining_date']);

    if (empty($student_id) || empty($company_id)) {
        $error = "Student and Company are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO placements (school_id, student_id, company_id, package_amount, joining_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $company_id,
            $package_amount ?: null,
            $joining_date ?: null
        ]);
        $message = "Placement record added successfully!";
    }
}

// Fetch Students for Dropdown
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Companies for Dropdown
$stmt_comp = $pdo->prepare("SELECT id, company_name FROM companies WHERE school_id = ? ORDER BY company_name ASC");
$stmt_comp->execute([CURRENT_SCHOOL_ID]);
$companies = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);

// Fetch Placement Records
$stmt_placements = $pdo->prepare("
    SELECT p.*, s.first_name, s.last_name, s.admission_no, c.company_name 
    FROM placements p
    JOIN students s ON p.student_id = s.id
    JOIN companies c ON p.company_id = c.id
    WHERE p.school_id = ?
    ORDER BY p.id DESC
");
$stmt_placements->execute([CURRENT_SCHOOL_ID]);
$placementsList = $stmt_placements->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Placements Registry | VIC ERP";
$page_header = "Placement & Career Guidance";
$active_menu = "placement";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage placement drives and career records</h5>
    <div class="d-flex gap-2">
        <a href="companies.php" class="btn btn-outline-primary">
            <i class="fa fa-building me-1"></i> Companies
        </a>
        <a href="sessions.php" class="btn btn-outline-primary">
            <i class="fa fa-person-chalkboard me-1"></i> Career Sessions
        </a>
        <a href="placements.php" class="btn btn-success">
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
    <!-- Add Placement Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add Placement Record</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company...</option>
                            <?php foreach($companies as $cp): ?>
                                <option value="<?= $cp['id'] ?>">
                                    <?= htmlspecialchars($cp['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Package Amount (in LPA)</label>
                        <input type="number" step="0.01" name="package_amount" class="form-control" placeholder="e.g. 12.50">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Expected Joining Date</label>
                        <input type="date" name="joining_date" class="form-control">
                    </div>

                    <button type="submit" name="save_placement" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Record
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Placements List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Campus Placements</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student Name</th>
                                <th>Company</th>
                                <th>Package (LPA)</th>
                                <th>Joining Date</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($placementsList) > 0): ?>
                                <?php foreach($placementsList as $pl): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($pl['first_name'] . ' ' . $pl['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($pl['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($pl['company_name']) ?>
                                        </td>
                                        <td>
                                            <?php if ($pl['package_amount']): ?>
                                                <div class="fw-semibold text-success">₹ <?= htmlspecialchars($pl['package_amount']) ?> LPA</div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($pl['joining_date']): ?>
                                                <div class="small"><i class="fa fa-calendar-alt text-muted me-1"></i> <?= date('d M Y', strtotime($pl['joining_date'])) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-briefcase fs-2 mb-2 d-block"></i>
                                        No placement records found.
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
