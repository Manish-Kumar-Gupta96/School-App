<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['submit'])){

    $admission_no = $_POST['admission_no'];
    $roll_no      = $_POST['roll_no'];
    $first_name   = $_POST['first_name'];
    $last_name    = $_POST['last_name'];
    $gender       = $_POST['gender'];
    $dob          = $_POST['dob'];
    $class        = $_POST['class'];
    $section      = $_POST['section'];

    $father_name  = $_POST['father_name'];
    $mother_name  = $_POST['mother_name'];

    $phone        = $_POST['phone'];
    $email        = $_POST['email'];
    $address      = $_POST['address'];

    // Check unique admission_no
    $chk = $pdo->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ?");
    $chk->execute([$admission_no]);
    if ($chk->fetchColumn() > 0) {
        $error = "Admission Number already exists!";
    } else {
        $photo = '';

        if(!empty($_FILES['photo']['name'])){
            $photo = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['photo']['name']);
            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                "../../uploads/students/" . $photo
            );
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO students(
                    admission_no,
                    roll_no,
                    first_name,
                    last_name,
                    gender,
                    dob,
                    class,
                    section,
                    father_name,
                    mother_name,
                    phone,
                    email,
                    address,
                    photo
                )
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->execute([
                $admission_no,
                $roll_no,
                $first_name,
                $last_name,
                $gender,
                $dob,
                $class,
                $section,
                $father_name,
                $mother_name,
                $phone,
                $email,
                $address,
                $photo
            ]);

            $student_id = $pdo->lastInsertId();

            // Keep parent info in sync inside parents and parent_students tables
            $parent_name = !empty($father_name) ? $father_name : 'Parent of ' . $first_name . ' ' . $last_name;
            $parentStmt = $pdo->prepare("
                INSERT INTO parents (parent_name, father_name, mother_name, mobile, email, address, status, school_id, role_id)
                VALUES (?, ?, ?, ?, ?, ?, 'Active', 1, 4)
            ");
            $parentStmt->execute([
                $parent_name,
                $father_name,
                $mother_name,
                $phone, // which corresponds to parent mobile/phone
                $email,
                $address
            ]);
            $parent_id = $pdo->lastInsertId();

            $linkStmt = $pdo->prepare("
                INSERT INTO parent_students (parent_id, student_id)
                VALUES (?, ?)
            ");
            $linkStmt->execute([
                $parent_id,
                $student_id
            ]);


            require_once('../includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'] ?? 'Admin',
                'Student Created: ' . $first_name . ' ' . $last_name . ' (Admission No: ' . $admission_no . ')',
                'Students',
                $student_id
            );

            $pdo->commit();
            $message = "Student Added Successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Add Student | VIC ERP";
$page_header = "Add Student";
$active_menu = "students";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header bg-primary text-white d-flex align-items-center py-3" style="border-radius: 15px 15px 0 0;">
                <i class="fa fa-user-plus me-2 fs-4"></i>
                <h5 class="mb-0">Add New Student Profile</h5>
            </div>
            <div class="card-body p-4">

                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <h5 class="mb-3 text-primary border-bottom pb-2">Academic Information</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Admission No *</label>
                            <input type="text" name="admission_no" class="form-control" required placeholder="e.g. ADM2026001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Roll No</label>
                            <input type="text" name="roll_no" class="form-control" placeholder="e.g. 15">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Class</label>
                            <input type="text" name="class" class="form-control" placeholder="e.g. Class 10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Section</label>
                            <input type="text" name="section" class="form-control" placeholder="e.g. A">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Personal Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required placeholder="John">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Doe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Date Of Birth</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Parent Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" placeholder="Richard Doe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" placeholder="Jane Doe">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Contact & Address Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1234567890">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="john.doe@example.com">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="3" class="form-control" placeholder="Full residential address..."></textarea>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Upload Profile Photo</h5>
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Student Photo</label>
                            <input type="file" name="photo" class="form-control">
                            <div class="form-text">Accepted formats: JPG, JPEG, PNG. Max size 2MB.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary px-4">
                            <i class="fa fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" name="submit" class="btn btn-success px-4">
                            <i class="fa fa-save me-1"></i> Save Student
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
