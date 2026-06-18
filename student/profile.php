<?php
require_once('../config/database.php');
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

/* ==========================
LOAD PROFILE DETAILS
========================== */
$stmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = $student['first_name'] . ' ' . $student['last_name'];
$initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));

// Layout configuration
$active_menu = "profile";
$page_title = "My Profile | VIC School";
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">My Personal Profile</h2>
        <p class="text-muted mb-0">Verify your academic registration and contact details</p>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 p-4 text-center" style="border-radius: 15px;">
            <div class="mb-3 d-flex justify-content-center">
                <?php if($student['photo']): ?>
                    <img src="../uploads/students/<?= htmlspecialchars($student['photo']) ?>" alt="Student Photo" class="rounded-circle border" style="width: 130px; height: 130px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center border" style="width: 130px; height: 130px; font-size: 2.8rem; font-weight: 700;">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($fullName) ?></h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 mt-1">Roll No: <?= htmlspecialchars($student['roll_no'] ?: '-') ?></span>
            <hr class="text-muted my-3">
            <div class="text-start">
                <p class="mb-2 text-muted"><i class="fa fa-id-card me-2 text-muted small"></i><strong>Admission No:</strong> <span class="text-dark float-end"><?= htmlspecialchars($student['admission_no'] ?: '-') ?></span></p>
                <p class="mb-2 text-muted"><i class="fa fa-school me-2 text-muted small"></i><strong>Class Group:</strong> <span class="text-dark float-end"><?= htmlspecialchars($student['class'] ?: '-') ?></span></p>
                <p class="mb-0 text-muted"><i class="fa fa-layer-group me-2 text-muted small"></i><strong>Section Group:</strong> <span class="text-dark float-end"><?= htmlspecialchars($student['section'] ?: 'A') ?></span></p>
            </div>
        </div>
    </div>

    <!-- Personal & Guardian Info -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
            <h5 class="fw-bold text-dark text-start mb-4"><i class="fa fa-info-circle me-2 text-primary"></i>Profile Details</h5>
            <div class="row text-start">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Email Address</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['email'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Mobile Phone</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['phone'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Date Of Birth</label>
                    <p class="border-bottom pb-2 text-dark"><?= $student['dob'] ? date('d M Y', strtotime($student['dob'])) : '-' ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Gender</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['gender'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Father Name</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['father_name'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Mother Name</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['mother_name'] ?: '-') ?></p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small fw-semibold">Residential Address</label>
                    <p class="border-bottom pb-2 text-dark"><?= htmlspecialchars($student['address'] ?: '-') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
