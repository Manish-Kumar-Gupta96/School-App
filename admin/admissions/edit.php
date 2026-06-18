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

$error = '';

if (isset($_POST['update'])) {
    $student_name  = trim($_POST['student_name'] ?? '');
    $father_name   = trim($_POST['father_name'] ?? '');
    $mother_name   = trim($_POST['mother_name'] ?? '');
    $dob           = $_POST['dob'] ?? '';
    $gender        = $_POST['gender'] ?? '';
    $class_applied = $_POST['class_applied'] ?? '';
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $status        = $_POST['status'] ?? 'Pending';

    $photo = $app['photo'];
    $document = $app['document'];

    if (!empty($student_name) && !empty($father_name) && !empty($phone) && !empty($email) && !empty($class_applied)) {
        try {
            // Handle student photo upload
            if (!empty($_FILES['photo']['name'])) {
                if(!empty($app['photo']) && file_exists("../../uploads/admissions/" . $app['photo'])){
                    unlink("../../uploads/admissions/" . $app['photo']);
                }

                $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = time() . "_photo_" . rand(1000, 9999) . "." . $photo_ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], "../../uploads/admissions/" . $photo);
            }

            // Handle document upload
            if (!empty($_FILES['document']['name'])) {
                if(!empty($app['document']) && file_exists("../../uploads/admissions/" . $app['document'])){
                    unlink("../../uploads/admissions/" . $app['document']);
                }

                $doc_ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                $document = time() . "_doc_" . rand(1000, 9999) . "." . $doc_ext;
                move_uploaded_file($_FILES['document']['tmp_name'], "../../uploads/admissions/" . $document);
            }

            $update = $pdo->prepare("
                UPDATE admissions SET
                    student_name=?, father_name=?, mother_name=?,
                    dob=?, gender=?, class_applied=?, phone=?,
                    email=?, address=?, photo=?, document=?, status=?
                WHERE id=?
            ");
            
            $update->execute([
                $student_name, $father_name, $mother_name,
                $dob, $gender, $class_applied, $phone,
                $email, $address, $photo, $document, $status,
                $id
            ]);

            header("Location: index.php?updated=1");
            exit();
        } catch (Exception $e) {
            $error = "Failed to update application: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Edit Admission Details | VIC ERP";
$page_header = "Edit Admission Application";
$active_menu = "admissions";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0" style="border-radius: 15px;">
    <div class="card-body p-4">
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <h5 class="mb-3 text-primary border-bottom pb-2">Student & Academic Details</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Student Name <span class="text-danger">*</span></label>
                    <input type="text" name="student_name" class="form-control" value="<?= htmlspecialchars($app['student_name']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Date Of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($app['dob']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?= $app['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $app['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $app['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Class Applied <span class="text-danger">*</span></label>
                    <select class="form-select" name="class_applied" required>
                        <option value="">Select Class</option>
                        <option <?= $app['class_applied'] === 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                        <option <?= $app['class_applied'] === 'LKG' ? 'selected' : '' ?>>LKG</option>
                        <option <?= $app['class_applied'] === 'UKG' ? 'selected' : '' ?>>UKG</option>
                        <option <?= $app['class_applied'] === 'Class I' ? 'selected' : '' ?>>Class I</option>
                        <option <?= $app['class_applied'] === 'Class II' ? 'selected' : '' ?>>Class II</option>
                        <option <?= $app['class_applied'] === 'Class III' ? 'selected' : '' ?>>Class III</option>
                        <option <?= $app['class_applied'] === 'Class IV' ? 'selected' : '' ?>>Class IV</option>
                        <option <?= $app['class_applied'] === 'Class V' ? 'selected' : '' ?>>Class V</option>
                        <option <?= $app['class_applied'] === 'Class VI' ? 'selected' : '' ?>>Class VI</option>
                        <option <?= $app['class_applied'] === 'Class VII' ? 'selected' : '' ?>>Class VII</option>
                        <option <?= $app['class_applied'] === 'Class VIII' ? 'selected' : '' ?>>Class VIII</option>
                        <option <?= $app['class_applied'] === 'Class IX' ? 'selected' : '' ?>>Class IX</option>
                        <option <?= $app['class_applied'] === 'Class X' ? 'selected' : '' ?>>Class X</option>
                        <option <?= $app['class_applied'] === 'Class XI' ? 'selected' : '' ?>>Class XI</option>
                        <option <?= $app['class_applied'] === 'Class XII' ? 'selected' : '' ?>>Class XII</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Application Status</label>
                    <select name="status" class="form-select">
                        <option value="Pending" <?= $app['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $app['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $app['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Parent Details</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Father's Name <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($app['father_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($app['mother_name']) ?>">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Contact & File Details</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($app['phone']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($app['email']) ?>" required>
                </div>
                
                <!-- Student Photo -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Change Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <?php if(!empty($app['photo']) && file_exists("../../uploads/admissions/" . $app['photo'])): ?>
                        <div class="mt-2">
                            <span class="d-block text-muted small mb-1">Current Photo:</span>
                            <img src="../../uploads/admissions/<?= htmlspecialchars($app['photo']) ?>" width="80" class="img-thumbnail rounded">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Document -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Change Document</label>
                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if(!empty($app['document']) && file_exists("../../uploads/admissions/" . $app['document'])): ?>
                        <div class="mt-2 small text-muted">
                            <i class="fa fa-file me-1"></i> Current document: <?= htmlspecialchars(basename($app['document'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Permanent Address <span class="text-danger">*</span></label>
                    <textarea name="address" rows="3" class="form-control" required><?= htmlspecialchars($app['address']) ?></textarea>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" name="update" class="btn btn-warning px-4 me-2">
                    <i class="fa fa-save me-1"></i> Update Details
                </button>
                <a href="index.php" class="btn btn-secondary px-4">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
