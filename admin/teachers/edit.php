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

$error = '';

if(isset($_POST['update'])){
    $employee_id   = trim($_POST['employee_id']);
    $name          = trim($_POST['name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $gender        = $_POST['gender'];
    $dob           = $_POST['dob'];
    $qualification = trim($_POST['qualification']);
    $experience    = trim($_POST['experience']);
    $subject       = trim($_POST['subject']);
    $joining_date  = $_POST['joining_date'];
    $salary        = $_POST['salary'] ?: 0.00;
    $address       = trim($_POST['address']);
    $status        = $_POST['status'];

    $photo = $teacher['photo'];

    // Check if employee ID already exists for another teacher
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE employee_id = ? AND id != ?");
    $checkStmt->execute([$employee_id, $id]);
    if ($checkStmt->fetchColumn() > 0) {
        $error = "Employee ID already exists for another teacher!";
    } else {
        if(!empty($_FILES['photo']['name'])){
            if(!empty($teacher['photo']) && file_exists("../../uploads/teachers/" . $teacher['photo'])){
                unlink("../../uploads/teachers/" . $teacher['photo']);
            }

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

        $update = $pdo->prepare("
            UPDATE teachers SET
                employee_id=?,
                name=?,
                email=?,
                phone=?,
                gender=?,
                dob=?,
                qualification=?,
                experience=?,
                subject=?,
                joining_date=?,
                salary=?,
                address=?,
                photo=?,
                status=?
            WHERE id=?
        ");

        $update->execute([
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
            $status,
            $id
        ]);

        header("Location: index.php?updated=1");
        exit();
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Edit Teacher | VIC ERP";
$page_header = "Edit Teacher Info";
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
                    <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($teacher['employee_id']) ?>" required>
                </div>

                <!-- Teacher Name -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Teacher Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($teacher['name']) ?>" required>
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>">
                </div>

                <!-- Phone -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone']) ?>">
                </div>

                <!-- Gender -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?= ($teacher['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($teacher['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($teacher['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <!-- DOB -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Date Of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($teacher['dob']) ?>">
                </div>

                <!-- Qualification -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Qualification</label>
                    <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($teacher['qualification']) ?>">
                </div>

                <!-- Experience -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Experience</label>
                    <input type="text" name="experience" class="form-control" value="<?= htmlspecialchars($teacher['experience']) ?>">
                </div>

                <!-- Subject -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($teacher['subject']) ?>">
                </div>

                <!-- Joining Date -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($teacher['joining_date']) ?>">
                </div>

                <!-- Salary -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Salary</label>
                    <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($teacher['salary']) ?>">
                </div>

                <!-- Status -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?= ($teacher['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($teacher['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($teacher['address']) ?></textarea>
                </div>

                <!-- Photo Upload -->
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Change Photo</label>
                    <input type="file" name="photo" class="form-control mb-2">
                    <?php if(!empty($teacher['photo']) && file_exists("../../uploads/teachers/" . $teacher['photo'])): ?>
                        <div class="mt-2">
                            <span class="d-block text-muted small mb-1">Current Photo:</span>
                            <img src="../../uploads/teachers/<?= htmlspecialchars($teacher['photo']) ?>" width="100" class="img-thumbnail rounded">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" name="update" class="btn btn-warning px-4 me-2">
                        <i class="fa fa-save me-1"></i> Update Teacher
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
