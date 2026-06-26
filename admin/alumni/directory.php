<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Alumni
if (isset($_POST['save_alumni'])) {
    $student_id   = (int)$_POST['student_id'];
    $passout_year = (int)$_POST['passout_year'];
    $profession   = trim($_POST['profession']);
    $company_name = trim($_POST['company_name']);
    $email        = trim($_POST['email']);
    $mobile       = trim($_POST['mobile']);

    if (empty($student_id) || empty($passout_year) || empty($email)) {
        $error = "Student, Passout Year, and Email are required.";
    } else {
        // Check if already registered
        $check = $pdo->prepare("SELECT id FROM alumni WHERE student_id = ? AND school_id = ?");
        $check->execute([$student_id, CURRENT_SCHOOL_ID]);

        if ($check->rowCount() > 0) {
            $error = "This student is already registered as an alumni.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO alumni (school_id, student_id, passout_year, profession, company_name, email, mobile)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                CURRENT_SCHOOL_ID,
                $student_id,
                $passout_year,
                $profession,
                $company_name,
                $email,
                $mobile
            ]);
            $message = "Alumni registered successfully!";
        }
    }
}

// Fetch Students for Dropdown
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name, email, phone FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Alumni list
$stmt_alumni = $pdo->prepare("
    SELECT a.*, s.first_name, s.last_name, s.admission_no 
    FROM alumni a
    JOIN students s ON a.student_id = s.id
    WHERE a.school_id = ?
    ORDER BY a.passout_year DESC, s.first_name ASC
");
$stmt_alumni->execute([CURRENT_SCHOOL_ID]);
$alumniList = $stmt_alumni->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Alumni Directory | VIC ERP";
$page_header = "Alumni Management";
$active_menu = "alumni";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage school ex-students and networking portals</h5>
    <div class="d-flex gap-2">
        <a href="directory.php" class="btn btn-primary">
            <i class="fa fa-graduation-cap me-1"></i> Alumni Directory
        </a>
        <a href="events.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-alt me-1"></i> Meet Events
        </a>
        <a href="donations.php" class="btn btn-outline-success">
            <i class="fa fa-hand-holding-dollar me-1"></i> Donations
        </a>
        <a href="portal.php" class="btn btn-outline-info">
            <i class="fa fa-share-nodes me-1"></i> Networking & Stories
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
    <!-- Add Alumni Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add Alumni</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select" onchange="autoFillStudentDetails()" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>" data-email="<?= htmlspecialchars($st['email']) ?>" data-phone="<?= htmlspecialchars($st['phone']) ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Passout Year <span class="text-danger">*</span></label>
                        <input type="number" name="passout_year" class="form-control" placeholder="e.g. 2023" min="1900" max="2100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Profession</label>
                        <input type="text" name="profession" class="form-control" placeholder="e.g. Software Engineer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company / Institution</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. Microsoft">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alumni Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="e.g. alumni@email.com" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Alumni Mobile</label>
                        <input type="text" name="mobile" id="mobile" class="form-control" placeholder="e.g. 9876543210">
                    </div>

                    <button type="submit" name="save_alumni" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Register Alumni
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Alumni List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Alumni Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Alumni Student</th>
                                <th>Passout Year</th>
                                <th>Professional Details</th>
                                <th>Contact Information</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($alumniList) > 0): ?>
                                <?php foreach($alumniList as $al): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($al['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-bold text-primary">Class of <?= (int)$al['passout_year'] ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($al['profession'] ?: 'Not Specified') ?></div>
                                            <span class="small text-muted"><?= htmlspecialchars($al['company_name'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-envelope text-muted me-1"></i> <?= htmlspecialchars($al['email']) ?></div>
                                            <div class="small"><i class="fa fa-phone text-muted me-1"></i> <?= htmlspecialchars($al['mobile'] ?: '-') ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-graduation-cap fs-2 mb-2 d-block"></i>
                                        No alumni records found.
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
function autoFillStudentDetails() {
    const select = document.getElementById('student_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('email').value = selectedOption.getAttribute('data-email') || '';
        document.getElementById('mobile').value = selectedOption.getAttribute('data-phone') || '';
    } else {
        document.getElementById('email').value = '';
        document.getElementById('mobile').value = '';
    }
}
</script>

<?php
require_once('../includes/footer.php');
?>
