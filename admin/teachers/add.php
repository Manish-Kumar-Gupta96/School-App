<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$error = '';

if(isset($_POST['submit'])){

    $employee_id  = trim($_POST['employee_id']);
    $name         = trim($_POST['name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $gender       = $_POST['gender'];
    $dob          = $_POST['dob'];
    $qualification= trim($_POST['qualification']);
    $experience   = trim($_POST['experience']);
    $subject      = trim($_POST['subject']);
    $joining_date = $_POST['joining_date'];
    $salary       = $_POST['salary'] ?: 0.00;
    $address      = trim($_POST['address']);
    $status       = $_POST['status'];

    $photo = '';

    // Check if employee ID already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE employee_id = ?");
    $checkStmt->execute([$employee_id]);
    if ($checkStmt->fetchColumn() > 0) {
        $error = "Employee ID already exists!";
    } else {
        if(!empty($_FILES['photo']['name'])){
            $extension = pathinfo(
                $_FILES['photo']['name'],
                PATHINFO_EXTENSION
            );

            $photo =
            time() . "_" .
            rand(1000,9999) . "." .
            $extension;

            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                "../../uploads/teachers/" . $photo
            );
        }

        $stmt = $pdo->prepare("
            INSERT INTO teachers(
                employee_id,
                name,
                email,
                phone,
                gender,
                dob,
                qualification,
                experience,
                subject,
                joining_date,
                salary,
                address,
                photo,
                status
            )
            VALUES(
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )
        ");

        $stmt->execute([
            $employee_id,
            $name,
            $email,
            $phone,
            $gender,
            $dob,
            $qualification,
            $experience,
            $subject,
            $joining_date,
            $salary,
            $address,
            $photo,
            $status
        ]);

        header("Location: index.php?added=1");
        exit();
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Add Teacher | VIC ERP";
$page_header = "Add New Teacher";
$active_menu = "teachers";

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
            <div class="row">
                <!-- Employee ID -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" placeholder="e.g. TCH101" required>
                </div>

                <!-- Teacher Name -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Teacher Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@vicschool.edu.in">
                </div>

                <!-- Phone -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="10-digit number">
                </div>

                <!-- Gender -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- DOB -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Date Of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>

                <!-- Qualification -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Qualification</label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. M.Sc, B.Ed">
                </div>

                <!-- Experience -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Experience</label>
                    <input type="text" name="experience" class="form-control" placeholder="e.g. 5 Years">
                </div>

                <!-- Subject -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics">
                </div>

                <!-- Joining Date -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control">
                </div>

                <!-- Salary -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Salary</label>
                    <input type="number" name="salary" class="form-control" placeholder="Monthly Salary">
                </div>

                <!-- Status -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" rows="3" class="form-control" placeholder="Current residential address..."></textarea>
                </div>

                <!-- Photo -->
                <div class="col-12 mb-4">
                    <label class="form-label fw-semibold">Teacher Photo</label>
                    <input type="file" name="photo" class="form-control">
                </div>

                <div class="col-12">
                    <button type="submit" name="submit" class="btn btn-success px-4 me-2">
                        <i class="fa fa-save me-1"></i> Save Teacher
                    </button>
                    <a href="index.php" class="btn btn-secondary px-4">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
