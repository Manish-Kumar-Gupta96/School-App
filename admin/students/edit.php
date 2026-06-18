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

$message = '';
$error = '';

if(isset($_POST['update'])){

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

    // Check unique admission_no for other students
    $chk = $pdo->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ? AND id != ?");
    $chk->execute([$admission_no, $id]);
    if ($chk->fetchColumn() > 0) {
        $error = "Admission Number already exists for another student!";
    } else {
        $photo = $student['photo'];

        if(!empty($_FILES['photo']['name'])){
            $photoName = time().'_'.preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], "../../uploads/students/".$photoName)) {
                // Delete old photo if exists
                if (!empty($student['photo']) && file_exists("../../uploads/students/".$student['photo'])) {
                    unlink("../../uploads/students/".$student['photo']);
                }
                $photo = $photoName;
            }
        }

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE students SET
                admission_no=?,
                roll_no=?,
                first_name=?,
                last_name=?,
                gender=?,
                dob=?,
                class=?,
                section=?,
                father_name=?,
                mother_name=?,
                phone=?,
                email=?,
                address=?,
                photo=?
                WHERE id=?
            ");

            $update->execute([
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
                $photo,
                $id
            ]);

            // Sync parents table via parent_students map
            $parentMapStmt = $pdo->prepare("SELECT parent_id FROM parent_students WHERE student_id = ? LIMIT 1");
            $parentMapStmt->execute([$id]);
            $parent_id = $parentMapStmt->fetchColumn();

            if ($parent_id) {
                $parentUpdate = $pdo->prepare("
                    UPDATE parents SET
                    parent_name=?,
                    father_name=?,
                    mother_name=?,
                    mobile=?,
                    email=?,
                    address=?
                    WHERE id=?
                ");
                $parent_name = !empty($father_name) ? $father_name : 'Parent of ' . $first_name . ' ' . $last_name;
                $parentUpdate->execute([
                    $parent_name,
                    $father_name,
                    $mother_name,
                    $phone, // mobile
                    $email,
                    $address,
                    $parent_id
                ]);
            } else {
                $parent_name = !empty($father_name) ? $father_name : 'Parent of ' . $first_name . ' ' . $last_name;
                $parentInsert = $pdo->prepare("
                    INSERT INTO parents (parent_name, father_name, mother_name, mobile, email, address, status, school_id, role_id)
                    VALUES (?, ?, ?, ?, ?, ?, 'Active', 1, 4)
                ");
                $parentInsert->execute([
                    $parent_name,
                    $father_name,
                    $mother_name,
                    $phone,
                    $email,
                    $address
                ]);
                $new_parent_id = $pdo->lastInsertId();

                $linkStmt = $pdo->prepare("
                    INSERT INTO parent_students (parent_id, student_id)
                    VALUES (?, ?)
                ");
                $linkStmt->execute([
                    $new_parent_id,
                    $id
                ]);
            }


            $pdo->commit();
            $message = "Student Updated Successfully!";

            // Reload fresh data
            $stmt->execute([$id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update record: " . $e->getMessage();
        }
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Edit Student | VIC ERP";
$page_header = "Edit Student";
$active_menu = "students";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header bg-warning text-white d-flex align-items-center py-3" style="border-radius: 15px 15px 0 0;">
                <i class="fa fa-user-edit me-2 fs-4"></i>
                <h5 class="mb-0">Edit Student Profile</h5>
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
                            <input type="text" name="admission_no" class="form-control" required value="<?= htmlspecialchars($student['admission_no']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Roll No</label>
                            <input type="text" name="roll_no" class="form-control" value="<?= htmlspecialchars($student['roll_no']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Class</label>
                            <input type="text" name="class" class="form-control" value="<?= htmlspecialchars($student['class']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Section</label>
                            <input type="text" name="section" class="form-control" value="<?= htmlspecialchars($student['section']) ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Personal Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($student['first_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($student['last_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option <?= ($student['gender']=='Male')?'selected':'' ?>>Male</option>
                                <option <?= ($student['gender']=='Female')?'selected':'' ?>>Female</option>
                                <option <?= ($student['gender']=='Other')?'selected':'' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Date Of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($student['dob']) ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Parent Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($student['father_name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($student['mother_name']) ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Contact & Address Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" rows="3" class="form-control"><?= htmlspecialchars($student['address']) ?></textarea>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2">Profile Photo</h5>
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-9 mb-3">
                            <label class="form-label fw-semibold">Upload New Student Photo</label>
                            <input type="file" name="photo" class="form-control">
                            <div class="form-text">Uploading a new photo will replace the existing one. Accepted formats: JPG, JPEG, PNG.</div>
                        </div>
                        <div class="col-md-3 mb-3 text-center">
                            <label class="form-label fw-semibold d-block">Current Photo</label>
                            <?php if(!empty($student['photo']) && file_exists("../../uploads/students/".$student['photo'])): ?>
                                <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" width="90" height="90" style="object-fit:cover; border-radius:10px;" class="img-thumbnail">
                            <?php else: ?>
                                <img src="../../assets/images/default-user.png" width="90" height="90" style="object-fit:cover; border-radius:10px;" class="img-thumbnail">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary px-4">
                            <i class="fa fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" name="update" class="btn btn-warning px-4">
                            <i class="fa fa-save me-1"></i> Update Student
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
