<?php
require_once('../config/database.php');
require_once('includes/header.php');

$teacher_id = $_SESSION['teacher_id'];

/* ==========================
LOAD PROFILE DETAILS
========================== */
$stmt = $pdo->prepare("
    SELECT t.*, d.designation_name, dep.department_name
    FROM teachers t
    LEFT JOIN hr_designations d ON t.designation_id = d.id
    LEFT JOIN hr_departments dep ON t.department_id = dep.id
    WHERE t.id = ?
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = $teacher['name'];
$initials = strtoupper(substr($fullName, 0, 1) . (strpos($fullName, ' ') !== false ? substr($fullName, strpos($fullName, ' ') + 1, 1) : ''));

// Layout configuration
$active_menu = "profile";
$page_title = "My Profile | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">My Teacher Profile</h2>
        <p class="text-muted mb-0">Verify your academic employment registration details</p>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 p-4 text-center" style="border-radius: 15px;">
            <div class="mb-3 d-flex justify-content-center">
                <?php if($teacher['photo']): ?>
                    <img src="../uploads/teachers/<?= htmlspecialchars($teacher['photo']) ?>" alt="Teacher Photo" class="rounded-circle border" style="width: 130px; height: 130px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center border" style="width: 130px; height: 130px; font-size: 2.8rem; font-weight: 700;">
                        <?= htmlspecialchars($initials ?: 'T') ?>
                    </div>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fullName) ?></h4>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 mt-1">Emp ID: <?= htmlspecialchars($teacher['employee_id'] ?: '-') ?></span>
            <hr class="text-muted my-3">
            <div class="text-start">
                <p class="mb-2 text-muted"><i class="fa fa-briefcase me-2 text-muted small"></i><strong>Designation:</strong> <span class="text-dark float-end"><?= htmlspecialchars($teacher['designation_name'] ?: 'Teacher') ?></span></p>
                <p class="mb-2 text-muted"><i class="fa fa-building me-2 text-muted small"></i><strong>Department:</strong> <span class="text-dark float-end"><?= htmlspecialchars($teacher['department_name'] ?: 'Academics') ?></span></p>
                <p class="mb-0 text-muted"><i class="fa fa-wallet me-2 text-muted small"></i><strong>Base Salary:</strong> <span class="text-dark float-end">₹ <?= number_format($teacher['salary'] ?: 0.00, 2) ?></span></p>
            </div>
        </div>
    </div>

    <!-- Personal & Employment Info -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
            <h5 class="fw-bold text-dark text-start mb-4"><i class="fa fa-info-circle me-2 text-success"></i>Employment Details</h5>
            <div class="row text-start">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Email Address</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['email'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Mobile Phone</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['phone'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Date Of Birth</label>
                    <p class="border-bottom pb-2 text-dark"><?= $teacher['dob'] ? date('d M Y', strtotime($teacher['dob'])) : '-' ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Gender</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['gender'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Educational Qualifications</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['qualification'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Teaching Experience</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['experience'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Assigned Subject</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['subject'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Joining Date</label>
                    <p class="border-bottom pb-2 text-dark"><?= $teacher['joining_date'] ? date('d M Y', strtotime($teacher['joining_date'])) : '-' ?></p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small fw-semibold">Residential Address</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($teacher['address'] ?: '-') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
