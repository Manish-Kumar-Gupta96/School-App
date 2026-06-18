<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM teachers
    WHERE id = ?
");

$stmt->execute([$id]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher){
    die("Teacher Not Found");
}

// Layout setup
$root_path = "../../";
$page_title = "Teacher Profile | VIC ERP";
$page_header = "Teacher Profile";
$active_menu = "teachers";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<style>
.profile-card {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,.04);
}
.teacher-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary);
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
}
.info-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    border-left: 3px solid var(--primary);
}
.info-box strong {
    color: #495057;
    font-size: 13px;
    text-transform: uppercase;
}
.info-box div {
    font-size: 15px;
    font-weight: 500;
    color: #212529;
    margin-top: 2px;
}
</style>

<div class="profile-card">
    <div class="row">
        <!-- PHOTO & QUICK INFO -->
        <div class="col-lg-4 text-center border-end mb-4 mb-lg-0">
            <div class="position-relative d-inline-block">
                <?php if(!empty($teacher['photo']) && file_exists("../../uploads/teachers/" . $teacher['photo'])): ?>
                    <img src="../../uploads/teachers/<?= htmlspecialchars($teacher['photo']) ?>" class="teacher-photo">
                <?php else: ?>
                    <img src="../../assets/images/default-user.png" class="teacher-photo">
                <?php endif; ?>
            </div>
            
            <h3 class="mt-3 fw-bold text-dark">
                <?= htmlspecialchars($teacher['name']) ?>
            </h3>
            
            <p class="text-muted mb-3">
                Employee ID: <span class="badge bg-secondary"><?= htmlspecialchars($teacher['employee_id']) ?></span>
            </p>
            
            <span class="badge bg-<?= ($teacher['status'] == 'Active') ? 'success' : 'danger' ?> px-3 py-2 mb-4">
                <?= htmlspecialchars($teacher['status']) ?>
            </span>
            
            <div class="d-grid gap-2 col-9 mx-auto">
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fa fa-print me-1"></i> Print Profile
                </button>
                <a href="edit.php?id=<?= $teacher['id'] ?>" class="btn btn-warning">
                    <i class="fa fa-edit me-1"></i> Edit Profile
                </a>
                <a href="index.php" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>

        <!-- DETAILS -->
        <div class="col-lg-8 ps-lg-4">
            <h4 class="fw-bold mb-4 text-primary pb-2 border-bottom">
                <i class="fa fa-info-circle me-2"></i> Teacher Details
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Email Address</strong>
                        <div><?= htmlspecialchars($teacher['email'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Phone Number</strong>
                        <div><?= htmlspecialchars($teacher['phone'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Gender</strong>
                        <div><?= htmlspecialchars($teacher['gender'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Date Of Birth</strong>
                        <div><?= htmlspecialchars($teacher['dob'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Qualification</strong>
                        <div><?= htmlspecialchars($teacher['qualification'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Experience</strong>
                        <div><?= htmlspecialchars($teacher['experience'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Teaching Subject</strong>
                        <div><?= htmlspecialchars($teacher['subject'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Joining Date</strong>
                        <div><?= htmlspecialchars($teacher['joining_date'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Monthly Salary</strong>
                        <div>₹ <?= number_format($teacher['salary'], 2) ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Profile Status</strong>
                        <div><?= htmlspecialchars($teacher['status']) ?></div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="info-box mb-0">
                        <strong>Permanent Address</strong>
                        <div><?= nl2br(htmlspecialchars($teacher['address'] ?: 'N/A')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
