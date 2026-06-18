<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM students
    WHERE id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student){
    die("Student Not Found");
}

// Layout configuration
$root_path = "../../";
$page_title = "Student Profile - " . htmlspecialchars($student['first_name']) . " | VIC ERP";
$page_header = "Student Profile";
$active_menu = "students";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="row justify-content-center">
    <div class="col-xl-11">
        <div class="profile-card">
            <div class="row">
                <!-- PHOTO AND QUICK ACTIONS -->
                <div class="col-lg-4 text-center border-end pe-lg-4 mb-4 mb-lg-0">
                    <div class="position-relative d-inline-block mb-3">
                        <?php if(!empty($student['photo']) && file_exists("../../uploads/students/" . $student['photo'])): ?>
                            <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" class="student-photo shadow-sm">
                        <?php else: ?>
                            <img src="../../assets/images/default-user.png" class="student-photo shadow-sm">
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="fw-bold mb-1 text-primary">
                        <?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars($student['last_name']) ?>
                    </h3>
                    <span class="badge bg-secondary px-3 py-2 fs-6 mb-3">ID: <?= htmlspecialchars($student['admission_no']) ?></span>
                    
                    <div class="d-grid gap-2 col-md-8 mx-auto mt-2">
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="fa fa-print me-1"></i> Print Profile
                        </button>
                        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-warning">
                            <i class="fa fa-edit me-1"></i> Edit Student
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>

                <!-- DETAILED INFORMATION -->
                <div class="col-lg-8 ps-lg-4">
                    <h4 class="mb-4 text-primary border-bottom pb-2">
                        <i class="fa fa-info-circle me-1"></i> Academic & Personal Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Roll Number</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['roll_no']) ? htmlspecialchars($student['roll_no']) : 'N/A' ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Class & Section</span>
                                <div class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['class'] . ' - ' . $student['section']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Gender</span>
                                <div class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['gender']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Date Of Birth</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['dob']) ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Phone</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'N/A' ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Email</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['email']) ? htmlspecialchars($student['email']) : 'N/A' ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Residential Address</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['address']) ? nl2br(htmlspecialchars($student['address'])) : 'N/A' ?></div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3 text-primary border-bottom pb-2">
                        <i class="fa fa-users me-1"></i> Parent & Guardian Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Father's Name</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['father_name']) ? htmlspecialchars($student['father_name']) : 'N/A' ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box">
                                <span class="text-muted small fw-semibold">Mother's Name</span>
                                <div class="fs-6 fw-bold text-dark"><?= !empty($student['mother_name']) ? htmlspecialchars($student['mother_name']) : 'N/A' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
