<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$error = '';

if (isset($_POST['submit'])) {
    $student_name  = trim($_POST['student_name'] ?? '');
    $father_name   = trim($_POST['father_name'] ?? '');
    $mother_name   = trim($_POST['mother_name'] ?? '');
    $dob           = $_POST['dob'] ?? '';
    $gender        = $_POST['gender'] ?? '';
    $class_applied = $_POST['class_applied'] ?? '';
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $address       = trim($_POST['address'] ?? '');

    if (!empty($student_name) && !empty($father_name) && !empty($phone) && !empty($email) && !empty($class_applied)) {
        try {
            // Generate Application Number: APP-YYYY-XXXX
            $application_no = 'APP-' . date('Y') . '-' . rand(1000, 9999);

            $photo = '';
            $document = '';

            // Handle student photo upload
            if (!empty($_FILES['photo']['name'])) {
                $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = time() . "_photo_" . rand(1000, 9999) . "." . $photo_ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], "../../uploads/admissions/" . $photo);
            }

            // Handle document upload
            if (!empty($_FILES['document']['name'])) {
                $doc_ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                $document = time() . "_doc_" . rand(1000, 9999) . "." . $doc_ext;
                move_uploaded_file($_FILES['document']['tmp_name'], "../../uploads/admissions/" . $document);
            }

            $stmt = $pdo->prepare("
                INSERT INTO admissions (
                    application_no, student_name, father_name, mother_name,
                    dob, gender, class_applied, phone,
                    email, address, photo, document, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->execute([
                $application_no, $student_name, $father_name, $mother_name,
                $dob, $gender, $class_applied, $phone,
                $email, $address, $photo, $document
            ]);

            header("Location: index.php?added=1");
            exit();
        } catch (Exception $e) {
            $error = "Failed to add application: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Record Admission Application | VIC ERP";
$page_header = "New Admission Application";
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
                    <input type="text" name="student_name" class="form-control" required placeholder="Full Name">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Date Of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Class Applied <span class="text-danger">*</span></label>
                    <select class="form-select" name="class_applied" required>
                        <option value="">Select Class</option>
                        <option>Nursery</option>
                        <option>LKG</option>
                        <option>UKG</option>
                        <option>Class I</option>
                        <option>Class II</option>
                        <option>Class III</option>
                        <option>Class IV</option>
                        <option>Class V</option>
                        <option>Class VI</option>
                        <option>Class VII</option>
                        <option>Class VIII</option>
                        <option>Class IX</option>
                        <option>Class X</option>
                        <option>Class XI</option>
                        <option>Class XII</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Parent Details</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Father's Name <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control" required placeholder="Father's Full Name">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control" placeholder="Mother's Full Name">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Contact & File Details</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required placeholder="10-digit number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="email@example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Student Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Documents (Birth Certificate etc.)</label>
                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Permanent Address <span class="text-danger">*</span></label>
                    <textarea name="address" rows="3" class="form-control" required placeholder="Full address details..."></textarea>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" name="submit" class="btn btn-success px-4 me-2">
                    <i class="fa fa-save me-1"></i> Submit Application
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
