<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$app){
    die("Admission Application Not Found");
}

// Layout setup
$root_path = "../../";
$page_title = "Application Details | VIC ERP";
$page_header = "Admission Application View";
$active_menu = "admissions";

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
.student-photo {
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
        <!-- PHOTO & STATUS CONTROLS -->
        <div class="col-lg-4 text-center border-end mb-4 mb-lg-0">
            <div class="position-relative d-inline-block">
                <?php if(!empty($app['photo']) && file_exists("../../uploads/admissions/" . $app['photo'])): ?>
                    <img src="../../uploads/admissions/<?= htmlspecialchars($app['photo']) ?>" class="student-photo">
                <?php else: ?>
                    <img src="../../assets/images/default-user.png" class="student-photo">
                <?php endif; ?>
            </div>
            
            <h3 class="mt-3 fw-bold text-dark">
                <?= htmlspecialchars($app['student_name']) ?>
            </h3>
            
            <p class="text-muted mb-3">
                Application No: <span class="badge bg-secondary"><?= htmlspecialchars($app['application_no']) ?></span>
            </p>
            
            <span class="badge bg-<?= ($app['status'] === 'Approved') ? 'success' : (($app['status'] === 'Rejected') ? 'danger' : 'warning') ?> px-3 py-2 mb-4">
                <?= htmlspecialchars($app['status']) ?>
            </span>
            
            <div class="d-grid gap-2 col-9 mx-auto">
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fa fa-print me-1"></i> Print Form
                </button>

                <?php if ($app['status'] === 'Pending'): ?>
                    <a href="approve.php?id=<?= $app['id'] ?>" class="btn btn-success" onclick="return confirm('Approve admission application and create a student profile?')">
                        <i class="fa fa-check me-1"></i> Approve
                    </a>
                    <a href="reject.php?id=<?= $app['id'] ?>" class="btn btn-danger" onclick="return confirm('Reject admission application?')">
                        <i class="fa fa-ban me-1"></i> Reject
                    </a>
                <?php endif; ?>

                <a href="edit.php?id=<?= $app['id'] ?>" class="btn btn-warning">
                    <i class="fa fa-edit me-1"></i> Edit Details
                </a>
                <a href="index.php" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>

        <!-- DETAILS -->
        <div class="col-lg-8 ps-lg-4">
            <h4 class="fw-bold mb-4 text-primary pb-2 border-bottom">
                <i class="fa fa-info-circle me-2"></i> Application Details
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Student Name</strong>
                        <div><?= htmlspecialchars($app['student_name']) ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Class Applied</strong>
                        <div><?= htmlspecialchars($app['class_applied'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Gender</strong>
                        <div><?= htmlspecialchars($app['gender'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Date Of Birth</strong>
                        <div><?= htmlspecialchars($app['dob'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Father's Name</strong>
                        <div><?= htmlspecialchars($app['father_name'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Mother's Name</strong>
                        <div><?= htmlspecialchars($app['mother_name'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Mobile Number</strong>
                        <div><?= htmlspecialchars($app['phone'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Email Address</strong>
                        <div><?= htmlspecialchars($app['email'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Application Status</strong>
                        <div><?= htmlspecialchars($app['status']) ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <strong>Date Applied</strong>
                        <div><?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="info-box">
                        <strong>Permanent Address</strong>
                        <div><?= nl2br(htmlspecialchars($app['address'] ?: 'N/A')) ?></div>
                    </div>
                </div>

                <!-- UPLOADED DOCUMENT VIEW -->
                <div class="col-md-12">
                    <div class="info-box mb-0">
                        <strong>Submitted Document (Birth Cert / Previous Records)</strong>
                        <div class="mt-2">
                            <?php if(!empty($app['document']) && file_exists("../../uploads/admissions/" . $app['document'])): ?>
                                <?php 
                                $ext = strtolower(pathinfo($app['document'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png'])): 
                                ?>
                                    <img src="../../uploads/admissions/<?= htmlspecialchars($app['document']) ?>" class="img-thumbnail rounded" style="max-width: 300px; display: block;">
                                    <a href="../../uploads/admissions/<?= htmlspecialchars($app['document']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="fa fa-external-link-alt"></i> View Full Image
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-secondary d-inline-block py-2 px-3 mb-0">
                                        <i class="fa fa-file-pdf me-2 text-danger"></i> PDF Document Submitted
                                    </div>
                                    <a href="../../uploads/admissions/<?= htmlspecialchars($app['document']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary d-block mt-2" style="max-width: 200px;">
                                        <i class="fa fa-download me-1"></i> Open/Download PDF
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No documents uploaded.</span>
                            <?php endif; ?>
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
